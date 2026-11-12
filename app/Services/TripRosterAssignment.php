<?php

namespace App\Services;

use App\Exceptions\RosterAssignmentConflict;
use App\Models\{DriverProfile, Roster, Trip, TripSheetEntry, User, Vehicle};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripRosterAssignment
{
    public function authorize(User $user, Trip $trip): void
    {
        abort_unless($user->is_active && $user->can('rosters.create'), 403);
        // if (! $user->hasRole('Super Admin')) {
        //     foreach (['Supervisor' => 'supervisorProfile', 'Controller' => 'controllerProfile', 'Staff' => 'staffProfile'] as $role => $relation) {
        //         if ($user->hasRole($role) && $user->{$relation}?->depot_id) {
        //             abort_unless((int) $user->{$relation}->depot_id === (int) $trip->depot_id, 403);
        //         }
        //     }
        // }
    }

    public function assign(User $user, Trip $trip, int $entryId, int $driverId, ?int $vehicleId = null, ?array $expected = null): string
    {
        $this->authorize($user, $trip);
        return DB::transaction(function () use ($user, $trip, $entryId, $driverId, $vehicleId, $expected) {
            // Serialize same-trip changes and competing allocations of a driver/vehicle.
            $trip = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $checked = $this->inspect($user, $trip, $entryId, $driverId, $vehicleId, $expected, true);
            if ($checked['status'] === 'unchanged') return 'unchanged';
            ['entry' => $entry, 'existing' => $existing, 'vehicle' => $vehicle, 'vehicleId' => $vehicleId,
                'context' => $context, 'date' => $date, 'start' => $start] = $checked;
            $oldDriverId = $existing?->driver_profile_id ?? $entry->driver_profile_id;
            $oldVehicleId = $existing?->vehicle_id ?? $entry->vehicle_id;
            $driverChanged = (int) $oldDriverId !== $driverId;
            $vehicleChanged = (int) $oldVehicleId !== $vehicleId;
            $payload = $context->only(['depot_id', 'duty_date', 'shift_start_time', 'shift_end_time', 'shift_type']) + [
                'state_id' => $trip->state_id, 'oem_id' => $vehicle?->oem_id ?? $existing?->oem_id,
                'trip_assignment_id' => $trip->assignments()->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->value('id'),
                'driver_profile_id' => $driverId, 'vehicle_id' => $vehicleId,
                'reporting_time' => Carbon::parse($date.' '.$start)->subMinutes(20)->format('H:i'),
                'status' => 'assigned', 'updated_by' => $user->id,
            ];
            if ($existing && $existing->tripSheetEntries()->count() > 1) {
                $roster = $existing->replicate(['code', 'created_by', 'updated_by']);
                $roster->fill($payload + ['created_by' => $user->id]);
                if ($driverChanged) $roster->attendance_status = null;
                $roster->save();
                $existing->tripSheetEntries()->detach($entry->id);
                $existing->update(['updated_by' => $user->id]);
            } elseif ($existing) {
                $roster = $existing;
                $roster->fill($payload);
                if ($driverChanged) $roster->attendance_status = null;
                $roster->save();
            } else {
                $roster = Roster::create($payload + ['created_by' => $user->id]);
            }
            if (! $roster->code) $roster->update(['code' => generate_code(Roster::PREFIX_MODULE, $roster->id, 4, 'RST')]);
            $roster->tripSheetEntries()->syncWithoutDetaching([$entry->id]);
            $entryData = ['driver_profile_id' => $driverId, 'vehicle_id' => $vehicleId];
            if ($driverChanged || (int) $entry->driver_profile_id !== $driverId) {
                $entryData += ['is_driver_verified' => false, 'driver_verified_by' => null, 'driver_verified_at' => null];
            }
            if ($vehicleChanged || (int) $entry->vehicle_id !== $vehicleId) {
                $entryData += ['is_vehicle_verified' => false, 'vehicle_verified_by' => null, 'vehicle_verified_at' => null];
            }
            $entry->update($entryData);
            DB::afterCommit(function () use ($roster, $entry, $oldDriverId, $oldVehicleId, $driverChanged, $vehicleChanged) {
                try {
                    $notifier = app(RosterReassignmentNotifier::class);
                    if ($driverChanged) $notifier->sendTripDriverChange($roster, $entry, $oldDriverId);
                    if ($vehicleChanged) $notifier->sendTripVehicleChange($roster, $entry, $oldVehicleId);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
            return $existing ? 'updated' : 'created';
        }, 3);
    }

    public function inspect(User $user, Trip $trip, int $entryId, int $driverId, ?int $vehicleId = null, ?array $expected = null, bool $lock = false): array
    {
        $this->authorize($user, $trip);
        $entry = TripSheetEntry::whereKey($entryId)->when($lock, fn ($query) => $query->lockForUpdate())->firstOrFail();
        $entry->load('sheet');
        $this->require((int) $entry->sheet?->trip_id === $trip->id, 'The entry does not belong to this trip.');
        $label = 'Trip-sheet entry '.($entry->code ?: '#'.$entry->id);
        $this->require((bool) $entry->sheet->date, $label.' has no duty date. Complete the trip sheet before importing.');
        $this->require(filled($entry->departure_time), $label.' has no departure time. Complete the trip sheet before importing.');
        $this->require(filled($entry->arrival_time), $label.' has no arrival time. Complete the trip sheet before importing.');
        $this->require((bool) $trip->state_id, 'The trip has no state. Complete the trip details before importing.');
        $this->require(! isset($expected['code']) || (string) $entry->code === $expected['code'], 'The Trip Code changed since preview. Download a fresh sample.');
        $this->require(! $expected || ($entry->sheet->date?->toDateString() === $expected['date']
            && (string) $entry->service_code === $expected['service']), 'The trip date or service code changed after upload.');
        $this->require($trip->is_active && $entry->status === 'pending'
            && $entry->actual_start_time === null && $entry->actual_reach_time === null
            && ! $entry->is_initial_verified && ! $entry->is_final_verified
            && ! in_array($entry->sheet->status, ['cancelled', 'verification_completed'], true),
            'Started, completed, cancelled or inactive trips cannot be changed.');
        $rosters = $entry->rosters()->when($lock, fn ($query) => $query->lockForUpdate())->get();
        $this->require($rosters->count() <= 1, 'The entry has multiple rosters; resolve them before importing.');
        $existing = $rosters->first();
        $this->require(! $existing || $existing->status === 'assigned', 'Started, completed or inactive rosters cannot be changed.');
        $vehicleId ??= $entry->vehicle_id ?: $existing?->vehicle_id;
        $vehicleId = $vehicleId ? (int) $vehicleId : null;
        $driver = DriverProfile::whereKey($driverId)->when($lock, fn ($query) => $query->lockForUpdate())->first();
        $vehicle = $vehicleId ? Vehicle::whereKey($vehicleId)->when($lock, fn ($query) => $query->lockForUpdate())->first() : null;
        $this->require($driver && (int) $driver->depot_id === (int) $trip->depot_id, 'The driver must belong to this trip depot.');
        $this->require(! $vehicleId || ($vehicle && $vehicle->status === 'Active' && $vehicle->oem_id
            && (int) $vehicle->depot_id === (int) $trip->depot_id
            && (int) $vehicle->vehicle_classification_id === (int) $trip->vehicle_classification_id),
            'The vehicle must be active, in this depot, and match the trip classification with an OEM.');
        $date = $entry->sheet->date->toDateString();
        $start = substr((string) $entry->departure_time, 0, 5);
        $end = substr((string) $entry->arrival_time, 0, 5);
        $hour = (int) substr($start, 0, 2);
        $context = new Roster([
            'depot_id' => $trip->depot_id, 'duty_date' => $date, 'shift_start_time' => $start,
            'shift_end_time' => $end, 'shift_type' => $hour >= 5 && $hour < 12 ? 'morning' : ($hour >= 12 && $hour < 20 ? 'evening' : 'night'),
        ]);
        $context->id = $existing?->id;
        $driver->load('user');
        $this->require($driver->user?->is_active && $driver->user->hasRole('Driver'), 'The selected driver is inactive or does not have the Driver role.');
        [, $dutyEnd] = app(TripAssignmentContext::class)->window($context);
        $this->require($driver->expiry_date && $driver->expiry_date->toDateString() >= $dutyEnd->toDateString(), 'The driver licence is not valid through duty end.');
        if (in_array($driverId, app(TripAssignmentContext::class)->busyIds($context, 'driver_profile_id', [$driverId], $lock), true)) {
            throw $this->conflict($context, 'driver_profile_id', $driverId);
        }
        $this->require(app(TripDriverAssignment::class)->available($context, $entry, false, $driverId, $lock)->contains('id', $driverId),
            'The driver is on approved or auto-marked leave for this duty.');
        if ($vehicleId && in_array($vehicleId, app(TripAssignmentContext::class)->busyIds($context, 'vehicle_id', [$vehicleId], $lock), true)) {
            throw $this->conflict($context, 'vehicle_id', $vehicleId);
        }
        $unchanged = $existing && (int) $existing->driver_profile_id === $driverId && (int) $existing->vehicle_id === (int) $vehicleId
            && (int) $entry->driver_profile_id === $driverId && (int) $entry->vehicle_id === (int) $vehicleId;
        $snapshot = $this->snapshot($trip, $entry, $existing);
        $this->require(! isset($expected['snapshot']) || hash_equals($expected['snapshot'], $snapshot),
            'The assignment changed since preview. Upload again to review the latest assignment.');
        return compact('entry', 'existing', 'vehicle', 'vehicleId', 'driver', 'context', 'date', 'start', 'end', 'snapshot')
            + ['status' => $unchanged ? 'unchanged' : ($existing ? 'updated' : 'created')];
    }

    private function snapshot(Trip $trip, TripSheetEntry $entry, ?Roster $roster): string
    {
        return hash('sha256', json_encode([
            $trip->only(['id', 'depot_id', 'state_id', 'vehicle_classification_id', 'is_active']),
            $entry->only(['id', 'code', 'trip_sheet_id', 'service_code', 'driver_profile_id', 'vehicle_id', 'departure_time', 'arrival_time', 'status',
                'actual_start_time', 'actual_reach_time', 'is_initial_verified', 'is_final_verified']),
            $entry->sheet->only(['trip_id', 'date', 'status']),
            $roster?->only(['id', 'depot_id', 'driver_profile_id', 'vehicle_id', 'duty_date', 'shift_start_time', 'shift_end_time', 'status']),
        ], JSON_THROW_ON_ERROR));
    }

    private function conflict(Roster $context, string $field, int $id): RosterAssignmentConflict
    {
        [$start, $end] = app(TripAssignmentContext::class)->window($context);
        $matches = Roster::where($field, $id)->when($context->id, fn ($query) => $query->whereKeyNot($context->id))
            ->whereIn('status', ['assigned', 'in_progress'])
            ->where(function ($query) use ($start, $end) {
                $query->where(fn ($query) => $query->whereDate('duty_date', '>=', $start->copy()->subDay()->toDateString())
                    ->whereDate('duty_date', '<=', $end->toDateString()))
                    ->orWhereNull('duty_date')
                    ->orWhere(fn ($query) => $query->where('status', 'in_progress')->whereDate('duty_date', '<=', $end));
            })->with('tripSheetEntries.sheet.trip')->orderBy('duty_date')->get();
        $details = [];
        $seen = [];
        foreach ($matches as $other) {
            if ($other->duty_date && $other->shift_start_time && $other->shift_end_time) {
                [$otherStart, $otherEnd] = app(TripAssignmentContext::class)->window($other);
                if (! ($otherStart->lt($end) && ($otherEnd->gt($start) || $other->status === 'in_progress'))) continue;
            }
            if ($other->tripSheetEntries->isEmpty()) $details[] = $this->conflictDetail(null, $other, 'roster', $field);
            foreach ($other->tripSheetEntries as $entry) {
                $details[] = $this->conflictDetail($entry, $other, 'roster', $field);
                $seen[$entry->id] = true;
            }
        }
        $running = TripSheetEntry::where($field, $id)
            ->when($context->id, fn ($query) => $query->whereDoesntHave('rosters', fn ($query) => $query->where('rosters.id', $context->id)))
            ->whereNotIn('status', ['cancelled', 'verification_completed'])
            ->where(fn ($query) => $query->where('status', 'initial_verification_completed')->orWhereNotNull('actual_start_time'))
            ->whereNull('actual_reach_time')
            ->where(fn ($query) => $query->where('is_final_verified', false)->orWhereNull('is_final_verified'))
            ->with('sheet.trip')->get();
        foreach ($running as $entry) {
            if (! isset($seen[$entry->id])) $details[] = $this->conflictDetail($entry, null, 'running_trip', $field);
        }
        $label = $field === 'driver_profile_id' ? 'Driver' : 'The vehicle';
        $first = $details[0] ?? null;
        $message = $label.' has an overlapping duty'.($first
            ? ': '.($first['service_code'] ? 'service '.$first['service_code'].' ' : '').'(Trip Code '.$first['trip_code'].') on '
                .$first['date'].' ('.$first['start_time'].'–'.$first['end_time'].').'
            : ' or another running trip.');
        if (count($details) > 1) $message .= ' '.count($details).' conflicting duties are listed below.';
        return new RosterAssignmentConflict($details, $message);
    }

    private function conflictDetail(?TripSheetEntry $entry, ?Roster $roster, string $source, string $field): array
    {
        return [
            'trip_code' => $entry?->code ?: ($entry?->sheet?->trip?->code ?: ($roster?->code ?: ($entry ? 'Trip entry #'.$entry->id : 'Roster #'.$roster?->id))),
            'parent_trip_code' => $entry?->sheet?->trip?->code ?? '',
            'service_code' => $entry?->service_code ?? '',
            'date' => ($roster?->duty_date ?? $entry?->sheet?->date)?->format('d-m-Y') ?? 'Not recorded',
            'start_time' => substr((string) ($roster?->shift_start_time ?? $entry?->actual_start_time ?? $entry?->departure_time), 0, 5) ?: 'Not recorded',
            'end_time' => substr((string) ($roster?->shift_end_time ?? $entry?->arrival_time), 0, 5) ?: 'Not recorded',
            'status' => $roster?->status ?? 'in_progress',
            'source' => $source,
            'resource' => $field === 'driver_profile_id' ? 'driver' : 'vehicle',
        ];
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) throw ValidationException::withMessages(['assignment' => $message]);
    }
}

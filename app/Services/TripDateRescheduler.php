<?php

namespace App\Services;

use App\Models\{DriverProfile, Roster, Trip, TripSheetEntry, Vehicle};
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripDateRescheduler
{
    public array $summary = ['moved' => 0, 'generated' => 0, 'removed' => 0, 'rosters' => 0];
    public array $serviceSummary = ['updated' => 0, 'protected' => 0, 'unrecognized' => 0];

    public function update(Trip $trip, array $data): Trip
    {
        return DB::transaction(function () use ($trip, $data) {
            $this->summary = ['moved' => 0, 'generated' => 0, 'removed' => 0, 'rosters' => 0];
            $trip = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $oldFrom = $trip->from_date?->toDateString();
            $oldTo = $trip->to_date?->toDateString();
            $codes = app(TripServiceCodes::class);
            $oldPrefix = trim((string) $trip->serviceCode?->title);
            $trip->fill($data);
            $trip->unsetRelation('serviceCode');
            $this->serviceSummary = $codes->synchronize($trip, $oldPrefix);
            $this->reconcile($trip, $oldFrom, $oldTo, $data['updated_by'] ?? null, $oldPrefix);
            $trip->save();
            return $trip;
        }, 3);
    }

    private function reconcile(Trip $trip, ?string $oldFrom, ?string $oldTo, ?int $actorId, ?string $oldPrefix = null): void
    {
        $sheets = $trip->sheets()->orderBy('date')->lockForUpdate()->get();
        if ($sheets->isEmpty()) {
            $this->require(! $trip->isDirty(['from_date', 'to_date']), 'Create or import a trip sheet before changing dates so a saved schedule can be used.');
            return;
        }
        $this->require($oldFrom && $oldTo && $trip->from_date && $trip->to_date, 'Both original and new trip dates are required.');
        $from = Carbon::parse($oldFrom);
        $to = Carbon::parse($oldTo);
        $this->require($trip->to_date->gte($trip->from_date), 'To Date must be on or after From Date.');
        $outsideOriginal = $sheets->contains(fn ($s) => ! $s->date || ! $s->date->betweenIncluded($from, $to));
        if ($outsideOriginal) {
            $dates = $sheets->pluck('date')->map(fn ($d) => $d?->toDateString())->unique();
            $first = $sheets->first()->date;
            $last = $sheets->last()->date;
            $this->require($first && $last && $dates->count() === (int) $first->diffInDays($last) + 1
                && $first->diffInDays($last) === $trip->from_date->diffInDays($trip->to_date)
                && ! $sheets->contains(fn ($s) => ! $s->entries()->exists()),
                'Existing trip-sheet dates are ambiguous. Reconcile the original dates before updating.');
            $from = $first;
            $to = $last;
        }
        $sameDuration = $from->diffInDays($to) === $trip->from_date->diffInDays($trip->to_date);
        if ($sameDuration && $from->toDateString() !== $trip->from_date->toDateString()) {
            $this->summary['moved'] = TripSheetEntry::whereIn('trip_sheet_id', $sheets->modelKeys())->count();
            $this->shift($trip, $from->toDateString(), $to->toDateString(), $actorId);
        }

        $sheets = $trip->sheets()->orderBy('date')->lockForUpdate()->get();
        $source = $sheets->last(fn ($s) => $s->entries()->exists());
        $template = $source?->entries()->with('scheduleStopTimes')->orderBy('trip_order_sequence_no')->orderBy('id')->get();
        $outside = $sheets->filter(fn ($s) => ! $s->date->betweenIncluded($trip->from_date, $trip->to_date));
        foreach ($outside as $sheet) $this->removeUnassignedSheet($trip, $sheet, $actorId);

        $next = 0;
        foreach (TripSheetEntry::whereHas('sheet', fn ($q) => $q->where('trip_id', $trip->id))->pluck('code') as $code) {
            if (preg_match('/-(\d+)$/', (string) $code, $match)) $next = max($next, (int) $match[1]);
        }
        $generatedIds = [];
        foreach (CarbonPeriod::create($trip->from_date, $trip->to_date) as $date) {
            $sheet = $trip->sheets()->whereDate('date', $date)->first();
            if (! $sheet || ! $sheet->entries()->exists()) {
                $this->require($template && $template->isNotEmpty(), 'Create or import a trip sheet before generating new dates.');
                $sheet ??= $trip->sheets()->create(['date' => $date->toDateString(), 'status' => 'pending',
                    'code' => ($trip->code ?: 'TRIP-'.$trip->id).'-'.$date->format('Ymd')]);
                $this->require($sheet->status === 'pending', 'A protected trip sheet cannot receive generated duties.');
                foreach ($template as $sourceEntry) {
                    $this->require(filled($sourceEntry->departure_time) && filled($sourceEntry->arrival_time) && filled($sourceEntry->service_code),
                        'The latest saved day needs service codes and departure/arrival times before generating dates.');
                    $entry = new TripSheetEntry;
                    $entry->forceFill(['service_code' => app(TripServiceCodes::class)->copiedCode($trip, $sourceEntry, $oldPrefix)]
                        + $sourceEntry->only(['side', 'trip_order_sequence_no', 'round_no',
                        'trip_nature', 'schedule_km', 'departure_time', 'arrival_time']) + [
                        'trip_sheet_id' => $sheet->id, 'status' => 'pending',
                        'code' => ($trip->code ?: 'TRIP-'.$trip->id).'-'.str_pad((string) ++$next, 4, '0', STR_PAD_LEFT),
                    ])->save();
                    foreach ($sourceEntry->scheduleStopTimes as $stop) {
                        $entry->scheduleStopTimes()->create($stop->only(['location_id', 'route_stop_id', 'sequence_no',
                            'location_name', 'event', 'show_location', 'scheduled_time']));
                    }
                    $this->summary['generated']++;
                    $generatedIds[] = $entry->id;
                }
            }
            foreach ($sheet->entries()->lockForUpdate()->get() as $entry) {
                if (! $entry->rosters()->exists()) $this->createRoster($trip, $sheet, $entry, $actorId, in_array($entry->id, $generatedIds, true));
            }
        }
    }

    private function removeUnassignedSheet(Trip $trip, $sheet, ?int $actorId): void
    {
        $this->require($sheet->status === 'pending', 'Protected trip sheet '.$sheet->code.' cannot be removed.');
        $this->require(! $trip->assignments()->whereDate('from_date', '<=', $sheet->date)->whereDate('to_date', '>=', $sheet->date)
            ->where(fn ($q) => $q->whereNotNull('driver_profile_id')->orWhereNotNull('vehicle_id'))->exists(),
            'The removed date has an assignment. Keep that date or reconcile its assignment first.');
        foreach ($sheet->entries()->lockForUpdate()->get() as $entry) {
            $this->require($this->isPending($entry) && ! $entry->driver_profile_id && ! $entry->vehicle_id && ! $entry->dor()->exists(),
                'Trip Code '.$entry->code.' is assigned or protected and cannot be removed.');
            foreach ($entry->rosters()->lockForUpdate()->get() as $roster) {
                $this->require(in_array($roster->status, ['unassigned', 'assigned'], true) && ! $roster->driver_profile_id
                    && ! $roster->vehicle_id && ! $roster->attendance_status && $roster->tripSheetEntries()->count() === 1,
                    'Roster '.$roster->code.' is assigned, shared or protected and cannot be removed.');
                $entry->rosters()->detach($roster->id);
                $roster->delete();
            }
            $entry->scheduleStopTimes()->delete();
            $entry->delete();
            $this->summary['removed']++;
        }
        $sheet->delete();
    }

    private function createRoster(Trip $trip, $sheet, TripSheetEntry $entry, ?int $actorId, bool $newDuty = false): void
    {
        if (! $this->isPending($entry)) return;
        $this->require(filled($entry->departure_time) && filled($entry->arrival_time) && $trip->state_id && $trip->depot_id,
            'Complete the trip state, depot and duty times before generating rosters.');
        $assignment = $newDuty ? null : $trip->assignments()->whereDate('from_date', '<=', $sheet->date)
            ->whereDate('to_date', '>=', $sheet->date)->first();
        $driverId = $entry->driver_profile_id ?: $assignment?->driver_profile_id;
        $vehicleId = $entry->vehicle_id ?: $assignment?->vehicle_id;
        $vehicle = $vehicleId ? Vehicle::findOrFail($vehicleId) : null;
        $hour = (int) substr($entry->departure_time, 0, 2);
        $roster = Roster::create(['state_id' => $trip->state_id, 'depot_id' => $trip->depot_id, 'oem_id' => $vehicle?->oem_id,
            'duty_date' => $sheet->date, 'shift_start_time' => $entry->departure_time, 'shift_end_time' => $entry->arrival_time,
            'shift_type' => $hour >= 5 && $hour < 12 ? 'morning' : ($hour >= 12 && $hour < 20 ? 'evening' : 'night'),
            'reporting_time' => Carbon::parse($sheet->date->toDateString().' '.$entry->departure_time)->subMinutes(20)->format('H:i'),
            'driver_profile_id' => $driverId, 'vehicle_id' => $vehicleId,
            'trip_assignment_id' => $assignment?->id, 'status' => $driverId || $vehicleId ? 'assigned' : 'unassigned',
            'inherit_trip_assignment' => ! $newDuty,
            'created_by' => $actorId, 'updated_by' => $actorId]);
        $roster->update(['code' => generate_code(Roster::PREFIX_MODULE, $roster->id, 4, 'RST')]);
        $entry->rosters()->attach($roster->id);
        if ($driverId || $vehicleId) $entry->update(['driver_profile_id' => $driverId, 'vehicle_id' => $vehicleId]);
        $this->summary['rosters']++;
    }

    private function isPending(TripSheetEntry $entry): bool
    {
        return $entry->status === 'pending' && ! $entry->actual_start_time && ! $entry->actual_reach_time
            && ! $entry->is_initial_verified && ! $entry->is_final_verified && ! $entry->is_driver_verified && ! $entry->is_vehicle_verified
            && ! $entry->dor()->exists();
    }

    public function repair(Trip $trip, string $oldFrom, string $oldTo, ?int $actorId, bool $apply = false): array
    {
        // Preview executes exactly the same checks and rolls back every write.
        DB::beginTransaction();
        try {
            $trip = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $this->require($trip->sheets()->exists(), 'No trip sheets exist to repair.');
            $this->require($oldFrom !== $trip->from_date?->toDateString(), 'Original dates already match the trip; no repair is needed.');
            $this->require($trip->sheets()->where(fn ($q) => $q->whereDate('date', '<', $trip->from_date)
                ->orWhereDate('date', '>', $trip->to_date))->exists(),
                'Sheets already fit the current trip range; automatic repair is ambiguous or already applied.');
            $summary = $this->shift($trip, $oldFrom, $oldTo, $actorId);
            if ($apply) {
                $trip->update(['updated_by' => $actorId]);
                DB::commit();
            } else {
                DB::rollBack();
            }
            return $summary;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    private function shift(Trip $trip, ?string $oldFrom, ?string $oldTo, ?int $actorId): array
    {
        $sheets = $trip->sheets()->orderBy('id')->lockForUpdate()->get();
        $assignments = $trip->assignments()->orderBy('id')->lockForUpdate()->get();
        if ($sheets->isEmpty() && $assignments->isEmpty()) return ['sheets' => 0, 'rosters' => 0, 'assignments' => 0];
        $this->require($oldFrom && $oldTo && $trip->from_date && $trip->to_date, 'Both original and new trip dates are required.');
        $from = Carbon::parse($oldFrom)->startOfDay();
        $to = Carbon::parse($oldTo)->startOfDay();
        $this->require($to->gte($from) && $trip->to_date->gte($trip->from_date)
            && $from->diffInDays($to) === $trip->from_date->diffInDays($trip->to_date),
            'Changing the duration requires manual reconciliation of existing duties.');
        $days = (int) $from->diffInDays($trip->from_date, false);
        $entries = TripSheetEntry::whereIn('trip_sheet_id', $sheets->modelKeys())->orderBy('id')->lockForUpdate()->get();
        foreach ($sheets as $sheet) {
            $this->require($sheet->date && $sheet->date->betweenIncluded($from, $to), 'Existing sheet dates do not fit the original range; reconcile manually.');
            $this->require($sheet->status === 'pending', 'Trip sheet '.$sheet->code.' is protected and cannot move.');
        }
        foreach ($entries as $entry) {
            $this->require($this->isPending($entry),
                'Trip Code '.$entry->code.' has started, been verified or is protected.');
        }
        $rosters = Roster::whereHas('tripSheetEntries', fn ($q) => $q->whereIn('trip_sheet_entries.id', $entries->modelKeys()))
            ->orderBy('id')->lockForUpdate()->get();
        foreach ($rosters as $roster) {
            $this->require(in_array($roster->status, ['assigned', 'unassigned'], true) && $roster->attendance_status === null,
                'Roster '.$roster->code.' has attendance or is protected.');
            $this->require(! $roster->tripSheetEntries()->whereNotIn('trip_sheet_entries.id', $entries->modelKeys())->exists(),
                'Roster '.$roster->code.' is shared with duties outside this trip.');
            $this->require($roster->duty_date && $roster->duty_date->betweenIncluded($from, $to),
                'Roster '.$roster->code.' does not fit the original dates.');
        }
        // Lock resources in the same order as roster assignment before changing dates.
        $driverIds = $entries->pluck('driver_profile_id')->merge($rosters->pluck('driver_profile_id'))
            ->merge($assignments->pluck('driver_profile_id'))->filter()->unique()->sort()->values();
        $drivers = DriverProfile::whereIn('id', $driverIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $vehicleIds = $entries->pluck('vehicle_id')->merge($rosters->pluck('vehicle_id'))
            ->merge($assignments->pluck('vehicle_id'))->filter()->unique()->sort()->values();
        Vehicle::whereIn('id', $vehicleIds)->orderBy('id')->lockForUpdate()->get();
        foreach ($assignments as $assignment) {
            $this->require(! Roster::where('trip_assignment_id', $assignment->id)->whereNotIn('id', $rosters->modelKeys())->exists(),
                'An assignment has rosters outside the affected duties; reconcile manually.');
            $this->require($assignment->from_date && $assignment->to_date
                && $assignment->from_date->betweenIncluded($from, $to) && $assignment->to_date->betweenIncluded($from, $to),
                'An assignment does not fit the original dates; reconcile manually.');
            $assignment->update(['from_date' => $assignment->from_date->copy()->addDays($days),
                'to_date' => $assignment->to_date->copy()->addDays($days), 'updated_by' => $actorId]);
        }
        // Directional order avoids unique trip/date collisions for overlapping ranges.
        foreach ($sheets->sortBy(fn ($s) => $s->date->toDateString(), SORT_REGULAR, $days > 0) as $sheet) {
            $date = $sheet->date->copy()->addDays($days)->toDateString();
            $sheet->update(['date' => $date, 'code' => ($trip->code ?: 'TRIP-'.$trip->id).'-'.str_replace('-', '', $date)]);
        }
        foreach ($rosters as $roster) {
            $roster->update(['duty_date' => $roster->duty_date->copy()->addDays($days), 'updated_by' => $actorId]);
        }
        $contextService = app(TripAssignmentContext::class);
        foreach ($entries as $entry) {
            $linked = $rosters->filter(fn ($r) => $r->tripSheetEntries()->whereKey($entry->id)->exists());
            $this->require($linked->count() <= 1, 'Trip Code '.$entry->code.' has multiple rosters; reconcile manually.');
            $roster = $linked->first();
            $date = $sheets->firstWhere('id', $entry->trip_sheet_id)->date;
            if ($roster) {
                $this->require($roster->duty_date->equalTo($date), 'Roster and trip-sheet dates disagree for '.$entry->code.'.');
            }
            $context = $roster ?? new Roster(['duty_date' => $date, 'shift_start_time' => $entry->departure_time,
                'shift_end_time' => $entry->arrival_time]);
            $assignment = $roster && ($roster->status === 'unassigned' || $roster->inherit_trip_assignment === false) ? null
                : $assignments->first(fn ($a) => $date->betweenIncluded($a->from_date, $a->to_date));
            $driverId = $entry->driver_profile_id ?: ($roster?->driver_profile_id ?: $assignment?->driver_profile_id);
            $vehicleId = $entry->vehicle_id ?: ($roster?->vehicle_id ?: $assignment?->vehicle_id);
            if (! $driverId && ! $vehicleId) continue;
            [, $end] = $contextService->window($context);
            if ($driverId) {
                $driver = $drivers->get($driverId);
                $this->require($driver?->expiry_date && $driver->expiry_date->toDateString() >= $end->toDateString(),
                    'Driver licence is not valid for the new duty date: '.$entry->code.'.');
            }
            foreach (['driver_profile_id' => $driverId, 'vehicle_id' => $vehicleId] as $field => $id) {
                $this->require(! $id || ! $contextService->busyIds($context, $field, [(int) $id], true),
                    'Trip Code '.$entry->code.' has a conflicting '.$field.' duty on the new date.');
                if (! $id) continue;
                // Pending entries can reserve resources even before a roster exists.
                [$start] = $contextService->window($context);
                $others = TripSheetEntry::where($field, $id)->whereKeyNot($entry->id)
                    ->whereNotIn('status', ['cancelled', 'verification_completed'])
                    ->whereDoesntHave('rosters')
                    ->whereHas('sheet', fn ($q) => $q->whereDate('date', '>=', $start->copy()->subDay()->toDateString())
                        ->whereDate('date', '<=', $end->toDateString()))
                    ->with('sheet')->lockForUpdate()->get();
                foreach ($others as $other) {
                    $otherContext = new Roster(['duty_date' => $other->sheet->date,
                        'shift_start_time' => $other->departure_time, 'shift_end_time' => $other->arrival_time]);
                    [$otherStart, $otherEnd] = $contextService->window($otherContext);
                    $this->require(! ($otherStart->lt($end) && $otherEnd->gt($start)),
                        'Trip Code '.$entry->code.' conflicts with '.$other->code.' on the new date.');
                }
            }
        }
        return ['sheets' => $sheets->count(), 'rosters' => $rosters->count(), 'assignments' => $assignments->count()];
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) throw ValidationException::withMessages(['from_date' => $message]);
    }
}

<?php

namespace App\Services;

use App\Models\Roster;
use App\Models\TripSheetEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TripAssignmentContext
{
    public function context(User $user, TripSheetEntry $entry, bool $lock = false, string $field = 'driver_profile_id'): Roster
    {
        $depotId = $this->authorize($user, $entry);
        $trip = $entry->sheet?->trip;

        if ($entry->status !== 'pending' || $entry->actual_start_time !== null
            || $entry->actual_reach_time !== null || $entry->is_initial_verified || $entry->is_final_verified
            || $entry->sheet->status === 'cancelled' || ! $trip->is_active) {
            $this->invalid($field, 'The assignment can only be changed before the trip starts.');
        }

        $rosters = $entry->rosters()->when($lock, fn ($query) => $query->lockForUpdate())->get();
        if ($rosters->count() !== 1) {
            $this->invalid($field, 'The trip must have exactly one roster before its assignment can be changed.');
        }
        $roster = $rosters->first();
        abort_unless((int) $roster->depot_id === $depotId, 403);
        if (! in_array($roster->status, ['assigned', 'in_progress'], true)) {
            $this->invalid($field, 'The trip roster is not active.');
        }
        $this->window($roster, $field);

        return $roster;
    }

    public function authorize(User $user, TripSheetEntry $entry): int
    {
        abort_unless($user->hasRole('Supervisor') && $user->is_active && $user->supervisorProfile?->depot_id, 403);
        $entry->load('sheet.trip');
        $trip = $entry->sheet?->trip;
        $depotId = (int) $user->supervisorProfile->depot_id;
        $operatingDepot = $trip?->trip_side === 'both' ? $trip->depot_id : $trip?->from_depot_id;
        abort_unless($operatingDepot && (int) $operatingDepot === $depotId, 403);

        return $depotId;
    }

    public function busyIds(Roster $context, string $field, array $ids, bool $lock = false): array
    {
        [$start, $end] = $this->window($context, $field);

        $busy = Roster::query()->whereIn($field, $ids)
            ->when($context->id, fn ($query) => $query->whereKeyNot($context->id))->whereIn('status', ['assigned', 'in_progress'])
            ->where(function ($query) use ($start, $end) {
                $query->where(fn ($query) => $query
                    ->whereDate('duty_date', '>=', $start->copy()->subDay()->toDateString())
                    ->whereDate('duty_date', '<=', $end->toDateString()))
                    ->orWhereNull('duty_date')
                    // An overdue roster still reserves its driver and vehicle.
                    ->orWhere(fn ($query) => $query->where('status', 'in_progress')->whereDate('duty_date', '<=', $end));
            })->when($lock, fn ($query) => $query->lockForUpdate())->get()->filter(function ($other) use ($start, $end, $field) {
                if (! $other->duty_date || ! $other->shift_start_time || ! $other->shift_end_time) {
                    return true;
                }
                [$otherStart, $otherEnd] = $this->window($other, $field);

                return $otherStart->lt($end) && ($otherEnd->gt($start) || $other->status === 'in_progress');
            })->pluck($field)->map(fn ($id) => (int) $id)->unique()->all();

        $running = TripSheetEntry::query()->whereIn($field, $ids)
            ->when($context->id, fn ($query) => $query->whereDoesntHave('rosters', fn ($query) => $query->where('rosters.id', $context->id)))
            ->whereNotIn('status', ['cancelled', 'verification_completed'])
            ->where(fn ($query) => $query->where('status', 'initial_verification_completed')->orWhereNotNull('actual_start_time'))
            ->whereNull('actual_reach_time')
            ->where(fn ($query) => $query->where('is_final_verified', false)->orWhereNull('is_final_verified'))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->pluck($field)->map(fn ($id) => (int) $id)->all();

        return array_values(array_unique(array_merge($busy, $running)));
    }

    public function window(Roster $roster, string $field = 'driver_profile_id'): array
    {
        if (! $roster->duty_date || ! $roster->shift_start_time || ! $roster->shift_end_time) {
            $this->invalid($field, 'The roster needs a duty date and shift start/end times to check availability.');
        }
        $start = Carbon::parse($roster->duty_date->toDateString().' '.$roster->shift_start_time);
        $end = Carbon::parse($roster->duty_date->toDateString().' '.$roster->shift_end_time);
        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}

<?php

namespace App\Services;

use App\Models\DriverProfile;
use App\Models\Leave;
use App\Models\Roster;
use App\Models\TripSheetEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripDriverAssignment
{
    public function __construct(private TripAssignmentContext $assignments) {}

    public function context(User $user, TripSheetEntry $entry, bool $lock = false): Roster
    {
        return $this->assignments->context($user, $entry, $lock);
    }

    public function available(Roster $roster, TripSheetEntry $entry, bool $excludeCurrent = true, ?int $lockedDriverId = null): Collection
    {
        [, $end] = $this->window($roster);
        $drivers = DriverProfile::query()
            ->when($lockedDriverId, fn ($query) => $query->whereKey($lockedDriverId)->lockForUpdate())
            ->where('depot_id', $roster->depot_id)
            ->when($excludeCurrent, fn ($query) => $query->whereNotIn('id', array_filter([$roster->driver_profile_id, $entry->driver_profile_id])))
            ->whereDate('expiry_date', '>=', $end->toDateString())
            ->whereHas('user', fn ($query) => $query->where('is_active', true)->role('Driver'))
            ->with('user:id,name,code')
            ->get();

        $busy = $this->assignments->busyIds($roster, 'driver_profile_id', $drivers->pluck('id')->all(), $lockedDriverId !== null);
        $leaves = Leave::query()->whereIn('user_id', $drivers->pluck('user_id'))
            ->whereIn('status', ['Approved', 'Auto Marked'])
            ->when($lockedDriverId, fn ($query) => $query->lockForUpdate())->get();

        return $drivers->reject(fn ($driver) => in_array((int) $driver->id, $busy, true)
            || $leaves->contains(fn ($leave) => (int) $leave->user_id === (int) $driver->user_id
                && $this->leaveOverlaps($leave, $roster)))
            ->sortBy(fn ($driver) => $driver->user->name)->values();
    }

    public function change(User $user, int $entryId, int $driverId): array
    {
        return DB::transaction(function () use ($user, $entryId, $driverId) {
            // Serialize requests for the same trip and competing requests for the same driver.
            $entry = TripSheetEntry::query()->lockForUpdate()->findOrFail($entryId);
            $roster = $this->context($user, $entry, true);
            DriverProfile::query()->lockForUpdate()->findOrFail($driverId);
            if (! $this->available($roster, $entry, true, $driverId)->contains('id', $driverId)) {
                $this->invalid('Select another active, available driver from your depot with a valid licence.');
            }

            $oldDriverId = $roster->driver_profile_id;
            if ($roster->tripSheetEntries()->count() > 1) {
                // Preserve the original driver's other trips and reserve the replacement for this duty window.
                $replacement = $roster->replicate(['code', 'attendance_status', 'created_by', 'updated_by']);
                $replacement->fill([
                    'driver_profile_id' => $driverId,
                    'status' => 'assigned',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ])->save();
                $replacement->update(['code' => generate_code(Roster::PREFIX_MODULE, $replacement->id, 4, 'RST')]);
                $roster->tripSheetEntries()->detach($entry->id);
                $replacement->tripSheetEntries()->attach($entry->id);
                $roster->update(['updated_by' => $user->id]);
                $roster = $replacement;
            } else {
                $roster->update([
                    'driver_profile_id' => $driverId,
                    'attendance_status' => null,
                    'updated_by' => $user->id,
                ]);
            }

            $entry->update([
                'driver_profile_id' => $driverId,
                'is_driver_verified' => false,
                'driver_verified_by' => null,
                'driver_verified_at' => null,
            ]);

            return [$entry, $roster, $oldDriverId];
        }, 3);
    }

    private function leaveOverlaps(Leave $leave, Roster $roster): bool
    {
        [$start, $end] = $this->window($roster);
        $from = $leave->from_date ?? $leave->leave_date;
        $to = $leave->to_date ?? $leave->leave_date ?? $from;
        if (! $from || ! $to) {
            return true;
        }
        // Leave records do not contain exact half-shift times; reserve the whole affected shift.
        if ($leave->leave_for === 'driver' && $leave->shift && $roster->shift_type
            && strtolower($leave->shift) !== strtolower($roster->shift_type)) {
            return false;
        }

        return $from->copy()->startOfDay()->lt($end) && $to->copy()->addDay()->startOfDay()->gt($start);
    }

    private function window(Roster $roster): array
    {
        return $this->assignments->window($roster);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['driver_profile_id' => $message]);
    }
}

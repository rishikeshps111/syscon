<?php

namespace App\Services;

use App\Models\Roster;
use App\Models\TripSheetEntry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripVehicleAssignment
{
    public function __construct(private TripAssignmentContext $assignments) {}

    public function context(User $user, TripSheetEntry $entry, bool $lock = false): Roster
    {
        return $this->assignments->context($user, $entry, $lock, 'vehicle_id');
    }

    public function available(Roster $roster, TripSheetEntry $entry, bool $excludeCurrent = true, ?int $lockedVehicleId = null): Collection
    {
        $this->assignments->window($roster, 'vehicle_id');
        $vehicles = Vehicle::query()
            ->when($lockedVehicleId, fn ($query) => $query->whereKey($lockedVehicleId)->lockForUpdate())
            ->where('depot_id', $roster->depot_id)
            ->where('status', 'Active')
            ->when($excludeCurrent, fn ($query) => $query->whereNotIn('id', array_filter([$roster->vehicle_id, $entry->vehicle_id])))
            ->orderBy('vehicle_no')
            ->get(['id', 'vehicle_no', 'vehicle_code', 'depot_id']);

        $busy = $this->assignments->busyIds($roster, 'vehicle_id', $vehicles->pluck('id')->all(), $lockedVehicleId !== null);

        return $vehicles->reject(fn ($vehicle) => in_array((int) $vehicle->id, $busy, true))->values();
    }

    public function change(User $user, int $entryId, int $vehicleId): array
    {
        return DB::transaction(function () use ($user, $entryId, $vehicleId) {
            $entry = TripSheetEntry::query()->lockForUpdate()->findOrFail($entryId);
            $roster = $this->context($user, $entry, true);
            Vehicle::query()->lockForUpdate()->findOrFail($vehicleId);
            if (! $this->available($roster, $entry, true, $vehicleId)->contains('id', $vehicleId)) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Select another active, available vehicle from your depot.',
                ]);
            }

            $oldVehicleId = $roster->vehicle_id;
            if ($roster->tripSheetEntries()->count() > 1) {
                // Keep the driver and attendance, while isolating this trip from the other roster trips.
                $replacement = $roster->replicate(['code', 'created_by', 'updated_by']);
                $replacement->fill([
                    'vehicle_id' => $vehicleId,
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
                $roster->update(['vehicle_id' => $vehicleId, 'updated_by' => $user->id]);
            }

            $entry->update([
                'vehicle_id' => $vehicleId,
                'is_vehicle_verified' => false,
                'vehicle_verified_by' => null,
                'vehicle_verified_at' => null,
            ]);

            return [$entry, $roster, $oldVehicleId];
        }, 3);
    }
}

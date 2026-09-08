<?php

namespace App\Services;

use App\Models\TripSheet;
use App\Models\TripSheetEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripCancellation
{
    public function __construct(private TripAssignmentContext $context, private TripSheetStatus $sheetStatus) {}

    public function cancel(User $user, TripSheetEntry $entry, string $reason): array
    {
        return DB::transaction(function () use ($user, $entry, $reason) {
            // Use the same sheet-before-entry order as verification to serialize sibling status changes.
            TripSheet::query()->lockForUpdate()->findOrFail($entry->trip_sheet_id);
            $lockedEntry = TripSheetEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ((int) $lockedEntry->trip_sheet_id !== (int) $entry->trip_sheet_id) {
                throw ValidationException::withMessages(['trip_id' => 'The trip sheet changed. Refresh and try again.']);
            }
            $entry = $lockedEntry;
            $depotId = $this->context->authorize($user, $entry);
            if ($entry->status === 'cancelled') {
                return [$entry, false, $depotId];
            }
            if ($entry->status !== 'pending' || $entry->actual_start_time !== null || $entry->actual_reach_time !== null
                || $entry->is_initial_verified || $entry->is_final_verified
                || $entry->initial_verification_at !== null || $entry->final_verification_at !== null) {
                throw ValidationException::withMessages(['trip_id' => 'Only a trip that has not started can be cancelled.']);
            }

            $rosters = $entry->rosters()->orderBy('rosters.id')->lockForUpdate()->get();
            foreach ($rosters as $roster) {
                abort_unless((int) $roster->depot_id === $depotId, 403);
            }
            $entry->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);

            foreach ($rosters as $roster) {
                $statuses = $roster->tripSheetEntries()->lockForUpdate()->pluck('status');
                if ($statuses->isNotEmpty() && $statuses->every(fn ($status) => $status === 'cancelled')) {
                    $roster->update(['status' => 'cancelled', 'updated_by' => $user->id]);
                }
            }
            $this->sheetStatus->sync($entry->sheet);

            return [$entry, true, $depotId];
        }, 3);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TripSheetEntry;
use App\Services\TripAssignmentContext;
use App\Services\TripCancellation;
use App\Services\TripCancellationNotifier;
use Illuminate\Http\Request;
use Throwable;

class TripCancellationController extends Controller
{
    public function cancel(Request $request, TripSheetEntry $tripSheetEntry, TripAssignmentContext $context, TripCancellation $cancellation, TripCancellationNotifier $notifier)
    {
        $context->authorize($request->user(), $tripSheetEntry);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        [$entry, $changed, $depotId] = $cancellation->cancel($request->user(), $tripSheetEntry, trim($data['reason']));
        $notifications = ['sent' => 0, 'failed' => 0];
        if ($changed) {
            try {
                $notifications = $notifier->send($entry, $depotId);
            } catch (Throwable $exception) {
                report($exception);
                $notifications = ['sent' => 0, 'failed' => 1];
            }
        }

        return response()->json([
            'success' => true,
            'message' => $changed ? 'Trip cancelled successfully.' : 'Trip is already cancelled.',
            'data' => [
                'trip_sheet_entry_id' => $entry->id,
                'status' => $entry->status,
                'cancellation_reason' => $entry->cancellation_reason,
                'cancelled_by' => $entry->cancelled_by,
                'cancelled_at' => $entry->cancelled_at?->toIso8601String(),
            ],
            'notifications' => $notifications,
        ]);
    }
}

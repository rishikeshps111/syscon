<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TripSheetEntry;
use App\Services\RosterReassignmentNotifier;
use App\Services\TripVehicleAssignment;
use Illuminate\Http\Request;
use Throwable;

class TripVehicleController extends Controller
{
    public function available(Request $request, TripSheetEntry $tripSheetEntry, TripVehicleAssignment $assignments)
    {
        $roster = $assignments->context($request->user(), $tripSheetEntry);

        return response()->json([
            'success' => true,
            'data' => $assignments->available($roster, $tripSheetEntry)->map(fn ($vehicle) => [
                'vehicle_id' => $vehicle->id,
                'vehicle_no' => $vehicle->vehicle_no,
                'vehicle_code' => $vehicle->vehicle_code,
                'depot_id' => $vehicle->depot_id,
            ]),
        ]);
    }

    public function update(Request $request, TripSheetEntry $tripSheetEntry, TripVehicleAssignment $assignments, RosterReassignmentNotifier $notifier)
    {
        $assignments->context($request->user(), $tripSheetEntry);
        $data = $request->validate(['vehicle_id' => ['required', 'integer', 'exists:vehicles,id']]);
        [$entry, $roster, $oldVehicleId] = $assignments->change($request->user(), $tripSheetEntry->id, $data['vehicle_id']);

        try {
            $notifications = $notifier->sendTripVehicleChange($roster, $entry, $oldVehicleId);
        } catch (Throwable $exception) {
            report($exception);
            $notifications = ['sent' => 0, 'failed' => 1];
        }

        return response()->json([
            'success' => true,
            'message' => 'Trip vehicle changed successfully.',
            'data' => ['trip_sheet_entry_id' => $entry->id, 'roster_id' => $roster->id, 'vehicle_id' => $entry->vehicle_id],
            'notifications' => $notifications,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TripSheetEntry;
use App\Services\RosterReassignmentNotifier;
use App\Services\TripDriverAssignment;
use Illuminate\Http\Request;
use Throwable;

class TripDriverController extends Controller
{
    public function available(Request $request, TripSheetEntry $tripSheetEntry, TripDriverAssignment $assignments)
    {
        $roster = $assignments->context($request->user(), $tripSheetEntry);

        return response()->json([
            'success' => true,
            'data' => $assignments->available($roster, $tripSheetEntry)->map(fn ($driver) => [
                'driver_profile_id' => $driver->id,
                'name' => $driver->user->name,
                'code' => $driver->user->code,
                'depot_id' => $driver->depot_id,
            ]),
        ]);
    }

    public function update(Request $request, TripSheetEntry $tripSheetEntry, TripDriverAssignment $assignments, RosterReassignmentNotifier $notifier)
    {
        $assignments->context($request->user(), $tripSheetEntry);
        $data = $request->validate(['driver_profile_id' => ['required', 'integer', 'exists:driver_profiles,id']]);
        [$entry, $roster, $oldDriverId] = $assignments->change($request->user(), $tripSheetEntry->id, $data['driver_profile_id']);

        try {
            $notifications = $notifier->sendTripDriverChange($roster, $entry, $oldDriverId);
        } catch (Throwable $exception) {
            report($exception);
            $notifications = ['sent' => 0, 'failed' => 1];
        }

        return response()->json([
            'success' => true,
            'message' => 'Trip driver changed successfully.',
            'data' => ['trip_sheet_entry_id' => $entry->id, 'roster_id' => $roster->id, 'driver_profile_id' => $entry->driver_profile_id],
            'notifications' => $notifications,
        ]);
    }
}

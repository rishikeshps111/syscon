<?php

namespace App\Services;

use App\Models\TripSheetEntry;
use App\Models\User;
use Throwable;

class TripCancellationNotifier
{
    public function __construct(private FirebaseMessaging $firebase) {}

    public function send(TripSheetEntry $entry, int $depotId): array
    {
        $entry->load(['sheet', 'driverProfile.user.deviceTokens', 'rosters.driverProfile.user.deviceTokens']);
        $drivers = $entry->rosters->map(fn ($roster) => $roster->driverProfile?->user)
            ->push($entry->driverProfile?->user)->filter()->unique('id');
        $operations = User::query()->role(['Controller', 'Supervisor'])->where('is_active', true)
            ->where(fn ($query) => $query
                ->whereHas('controllerProfile', fn ($profile) => $profile->where('depot_id', $depotId))
                ->orWhereHas('supervisorProfile', fn ($profile) => $profile->where('depot_id', $depotId)))
            ->with('deviceTokens')->get();
        $sent = 0;
        $failed = 0;
        $trip = $entry->code ?: (string) $entry->id;
        $body = "Trip {$trip} on {$entry->sheet->date->format('d M Y')} has been cancelled.";
        foreach (['driver' => $drivers, 'operations' => $operations] as $appType => $users) {
            foreach ($users as $user) {
                $tokens = $user->deviceTokens->where('app_type', $appType)->unique('token');
                if ($tokens->isEmpty()) {
                    $failed++;
                }
                foreach ($tokens as $device) {
                    try {
                        $response = $this->firebase->send($device->token, 'Trip Cancelled', $body, [
                            'type' => 'trip_cancelled',
                            'trip_sheet_entry_id' => $entry->id,
                            'depot_id' => $depotId,
                            'status' => 'cancelled',
                        ], $appType);
                        if ($response->successful()) {
                            $sent++;
                        } else {
                            $failed++;
                        }
                    } catch (Throwable) {
                        $failed++;
                    }
                }
            }
        }

        return compact('sent', 'failed');
    }
}

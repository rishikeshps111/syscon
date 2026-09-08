<?php

namespace App\Services;

use App\Models\DriverProfile;
use App\Models\Roster;
use App\Models\TripSheetEntry;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Support\Collection;
use Throwable;

class RosterReassignmentNotifier
{
    public function __construct(private FirebaseMessaging $firebase) {}

    public function sendTripVehicleChange(Roster $roster, TripSheetEntry $entry, ?int $oldVehicleId): array
    {
        $roster->load(['driverProfile.user.deviceTokens', 'vehicle']);
        $sent = 0;
        $failed = 0;
        $trip = $entry->code ?: (string) $entry->id;
        $date = $roster->duty_date->format('d M Y');
        $payload = ['type' => 'trip_vehicle_change', 'trip_sheet_entry_id' => $entry->id, 'old_vehicle_id' => $oldVehicleId];
        $body = "Vehicle for trip {$trip} on {$date} changed to {$roster->vehicle->vehicle_no}.";
        foreach ($this->operationsUsers((int) $roster->depot_id) as $user) {
            $this->sendToUser($user, 'Trip Vehicle Changed', $body, 'operations', $roster, $sent, $failed, $payload);
        }
        if ($roster->driverProfile?->user) {
            $this->sendToUser($roster->driverProfile->user, 'Trip Vehicle Changed', $body, 'driver', $roster, $sent, $failed, $payload);
        }

        return compact('sent', 'failed');
    }

    public function sendTripDriverChange(Roster $roster, TripSheetEntry $entry, ?int $oldDriverId): array
    {
        $roster->load(['driverProfile.user.deviceTokens', 'vehicle']);
        $oldDriver = $oldDriverId ? DriverProfile::with('user.deviceTokens')->find($oldDriverId) : null;
        $sent = 0;
        $failed = 0;
        $trip = $entry->code ?: (string) $entry->id;
        $date = $roster->duty_date->format('d M Y');
        $payload = ['type' => 'trip_driver_change', 'trip_sheet_entry_id' => $entry->id];
        foreach ($this->operationsUsers((int) $roster->depot_id) as $user) {
            $this->sendToUser($user, 'Trip Driver Changed',
                "Driver for trip {$trip} on {$date} changed to {$roster->driverProfile->user->name}.",
                'operations', $roster, $sent, $failed, $payload);
        }
        $this->sendToUser($roster->driverProfile->user, 'Trip Assignment Updated',
            "You are assigned to trip {$trip} on {$date}.", 'driver', $roster, $sent, $failed, $payload);
        if ($oldDriver?->user) {
            $this->sendToUser($oldDriver->user, 'Trip Assignment Changed',
                "You are no longer assigned to trip {$trip} on {$date}.", 'driver', $roster, $sent, $failed, $payload);
        }

        return compact('sent', 'failed');
    }

    public function send(
        Roster $roster,
        bool $driverChanged,
        bool $vehicleChanged,
        ?int $oldDriverProfileId = null,
    ): array {
        $roster->load([
            'depot:id,name',
            'driverProfile.user.deviceTokens',
            'vehicle:id,vehicle_no,vehicle_code',
        ]);

        $oldDriver = $oldDriverProfileId && $oldDriverProfileId !== $roster->driver_profile_id
            ? DriverProfile::with('user.deviceTokens')->find($oldDriverProfileId)
            : null;
        $operationsUsers = $this->operationsUsers((int) $roster->depot_id);
        $sent = 0;
        $failed = 0;

        foreach ($operationsUsers as $user) {
            $this->sendToUser(
                $user,
                'Roster Reassigned',
                $this->operationsMessage($roster, $driverChanged, $vehicleChanged, $oldDriver),
                'operations',
                $roster,
                $sent,
                $failed,
            );
        }

        if ($roster->driverProfile?->user) {
            $this->sendToUser(
                $roster->driverProfile->user,
                'Roster Assignment Updated',
                $this->currentDriverMessage($roster, $driverChanged, $vehicleChanged),
                'driver',
                $roster,
                $sent,
                $failed,
            );
        }

        if ($driverChanged && $oldDriver?->user) {
            $this->sendToUser(
                $oldDriver->user,
                'Roster Assignment Changed',
                $this->oldDriverMessage($roster),
                'driver',
                $roster,
                $sent,
                $failed,
            );
        }

        return compact('sent', 'failed');
    }

    private function operationsUsers(int $depotId): Collection
    {
        return User::query()
            ->role(['Controller', 'Supervisor'])
            ->where('is_active', true)
            ->where(function ($query) use ($depotId): void {
                $query->whereHas('controllerProfile', fn($profile) => $profile->where('depot_id', $depotId))
                    ->orWhereHas('supervisorProfile', fn($profile) => $profile->where('depot_id', $depotId));
            })
            ->with(['deviceTokens' => fn($query) => $query->where('app_type', 'operations')])
            ->get();
    }

    private function sendToUser(
        User $user,
        string $title,
        string $body,
        string $appType,
        Roster $roster,
        int &$sent,
        int &$failed,
        array $payload = [],
    ): void {
        $tokens = $user->deviceTokens->where('app_type', $appType);

        if ($tokens->isEmpty()) {
            $failed++;
            return;
        }

        foreach ($tokens as $device) {
            try {
                $response = $this->firebase->send($device->token, $title, $body, $payload + [
                    'type' => 'roster_reassignment',
                    'roster_id' => $roster->id,
                    'roster_code' => $roster->code,
                    'duty_date' => $roster->duty_date?->toDateString(),
                    'depot_id' => $roster->depot_id,
                    'driver_profile_id' => $roster->driver_profile_id,
                    'vehicle_id' => $roster->vehicle_id,
                ], $appType);

                if ($response->successful()) {
                    $sent++;
                } else {
                    $failed++;
                    $this->deleteInvalidToken($device, $response->json());
                }
            } catch (Throwable) {
                $failed++;
            }
        }
    }

    private function operationsMessage(Roster $roster, bool $driverChanged, bool $vehicleChanged, ?DriverProfile $oldDriver): string
    {
        $changes = [];
        if ($driverChanged) {
            $changes[] = 'driver ' . ($oldDriver?->user?->name ?: '-') . ' to ' . ($roster->driverProfile?->user?->name ?: '-');
        }
        if ($vehicleChanged) {
            $changes[] = 'vehicle to ' . ($roster->vehicle?->vehicle_no ?: '-');
        }

        return "Roster {$roster->code} for {$roster->duty_date?->format('d M Y')} was reassigned: " . implode(', ', $changes) . '.';
    }

    private function currentDriverMessage(Roster $roster, bool $driverChanged, bool $vehicleChanged): string
    {
        if ($driverChanged) {
            return "You are assigned to roster {$roster->code} on {$roster->duty_date?->format('d M Y')} with vehicle " . ($roster->vehicle?->vehicle_no ?: '-') . '.';
        }

        return "Vehicle for roster {$roster->code} on {$roster->duty_date?->format('d M Y')} changed to " . ($roster->vehicle?->vehicle_no ?: '-') . '.';
    }

    private function oldDriverMessage(Roster $roster): string
    {
        return "You are no longer assigned to roster {$roster->code} on {$roster->duty_date?->format('d M Y')}.";
    }

    private function deleteInvalidToken(UserDeviceToken $device, array $response): void
    {
        $codes = collect(data_get($response, 'error.details', []))->pluck('errorCode')->filter()->all();

        if (array_intersect($codes, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
            $device->delete();
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\DriverProfile;
use App\Models\DriverTripNotificationLog;
use App\Models\TripSheetEntry;
use App\Models\UserDeviceToken;
use App\Services\FirebaseMessaging;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SendDriverTodayTripNotifications extends Command
{
    protected $signature = 'drivers:today-trip-notifications {--date= : Date in YYYY-MM-DD format} {--force : Resend an already successful notification}';

    protected $description = "Send each driver a Firebase notification with tomorrow's assigned trip count.";

    public function handle(FirebaseMessaging $firebase): int
    {
        $date = Carbon::parse($this->option('date') ?: Carbon::tomorrow())->toDateString();
        $sentDrivers = 0;
        $failedDrivers = 0;

        DriverProfile::query()
            ->with(['user.deviceTokens' => fn ($query) => $query->where('app_type', 'driver')])
            ->whereHas('user', fn($query) => $query->where('is_active', true))
            ->whereHas('rosters.tripSheetEntries.sheet', fn($query) => $query->whereDate('date', $date))
            ->orderBy('id')
            ->chunkById(100, function ($drivers) use ($firebase, $date, &$sentDrivers, &$failedDrivers): void {
                foreach ($drivers as $driver) {
                    $tripCount = TripSheetEntry::query()
                        ->whereHas('sheet', fn($query) => $query->whereDate('date', $date))
                        ->whereHas('rosters', fn($query) => $query->where('driver_profile_id', $driver->id))
                        ->distinct()
                        ->count('trip_sheet_entries.id');

                    $log = DriverTripNotificationLog::firstOrCreate(
                        ['driver_profile_id' => $driver->id, 'trip_date' => $date],
                        ['trip_count' => $tripCount]
                    );

                    if ($log->status === 'sent' && ! $this->option('force')) {
                        continue;
                    }

                    $successfulTokens = 0;
                    $errors = [];

                    if ($driver->user->deviceTokens->isEmpty()) {
                        $errors[] = 'No registered FCM device tokens.';
                    }

                    foreach ($driver->user->deviceTokens as $device) {
                        try {
                            $response = $firebase->send(
                                $device->token,
                                "Tomorrow's trips",
                                $tripCount === 1 ? 'You have 1 assigned trip tomorrow.' : "You have {$tripCount} assigned trips tomorrow.",
                                ['type' => 'today_trips', 'date' => $date, 'trip_count' => $tripCount],
                                'driver'
                            );

                            if ($response->successful()) {
                                $successfulTokens++;
                            } else {
                                $errors[] = $response->body();
                                $this->deleteInvalidToken($device, $response->json());
                            }
                        } catch (Throwable $exception) {
                            $errors[] = $exception->getMessage();
                        }
                    }

                    $log->update([
                        'trip_count' => $tripCount,
                        'sent_count' => $successfulTokens,
                        'status' => $successfulTokens > 0 ? 'sent' : 'failed',
                        'error' => $errors ? mb_substr(implode(' | ', $errors), 0, 65000) : null,
                        'sent_at' => $successfulTokens > 0 ? now() : null,
                    ]);

                    if ($successfulTokens > 0) {
                        $sentDrivers++;
                    } else {
                        $failedDrivers++;
                    }
                }
            });

        $this->info("Driver trip notifications sent: {$sentDrivers}.");

        if ($failedDrivers > 0) {
            $this->warn("Driver trip notifications failed or had no device: {$failedDrivers}.");
        }

        return $failedDrivers > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function deleteInvalidToken(UserDeviceToken $device, array $response): void
    {
        $errorCodes = collect(data_get($response, 'error.details', []))
            ->pluck('errorCode')
            ->filter()
            ->all();

        if (array_intersect($errorCodes, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
            $device->delete();
        }
    }
}

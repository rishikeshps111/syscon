<?php

namespace App\Console\Commands;

use App\Models\ControllerProfile;
use App\Models\ControllerTripNotificationLog;
use App\Models\TripSheetEntry;
use App\Models\UserDeviceToken;
use App\Services\FirebaseMessaging;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SendControllerTodayTripNotifications extends Command
{
    protected $signature = 'controllers:today-trip-notifications {--date= : Date in YYYY-MM-DD format} {--force : Resend an already successful notification}';

    protected $description = "Send each controller a Firebase notification with today's assigned trip count.";

    public function handle(FirebaseMessaging $firebase): int
    {
        $date = Carbon::parse($this->option('date') ?: today())->toDateString();
        $sentControllers = 0;
        $failedControllers = 0;

        ControllerProfile::query()
            ->with(['user.deviceTokens'])
            ->whereHas('user', fn($query) => $query->where('is_active', true))
            ->whereHas('rosters.tripSheetEntries.sheet', fn($query) => $query->whereDate('date', $date))
            ->orderBy('id')
            ->chunkById(100, function ($controllers) use ($firebase, $date, &$sentControllers, &$failedControllers): void {
                foreach ($controllers as $controller) {
                    $tripCount = TripSheetEntry::query()
                        ->whereHas('sheet', fn($query) => $query->whereDate('date', $date))
                        ->whereHas('rosters', fn($query) => $query->where('controller_profile_id', $controller->id))
                        ->distinct()
                        ->count('trip_sheet_entries.id');

                    $log = ControllerTripNotificationLog::firstOrCreate(
                        ['controller_profile_id' => $controller->id, 'trip_date' => $date],
                        ['trip_count' => $tripCount]
                    );

                    if ($log->status === 'sent' && ! $this->option('force')) {
                        continue;
                    }

                    $successfulTokens = 0;
                    $errors = [];

                    if ($controller->user->deviceTokens->isEmpty()) {
                        $errors[] = 'No registered FCM device tokens.';
                    }

                    foreach ($controller->user->deviceTokens as $device) {
                        try {
                            $response = $firebase->send(
                                $device->token,
                                "Today's trips",
                                $tripCount === 1 ? 'You have 1 assigned trip today.' : "You have {$tripCount} assigned trips today.",
                                ['type' => 'today_trips', 'date' => $date, 'trip_count' => $tripCount]
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
                        $sentControllers++;
                    } else {
                        $failedControllers++;
                    }
                }
            });

        $this->info("Controller trip notifications sent: {$sentControllers}.");

        if ($failedControllers > 0) {
            $this->warn("Controller trip notifications failed or had no device: {$failedControllers}.");
        }

        return $failedControllers > 0 ? self::FAILURE : self::SUCCESS;
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

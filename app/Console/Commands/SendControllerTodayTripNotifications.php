<?php

namespace App\Console\Commands;

use App\Models\TodayTripNotificationLog;
use App\Models\TripSheetEntry;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\FirebaseMessaging;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SendControllerTodayTripNotifications extends Command
{
    protected $signature = 'controllers:today-trip-notifications {--date= : Date in YYYY-MM-DD format} {--force : Resend an already successful notification}';

    protected $description = "Send controllers and supervisors a Firebase notification with tomorrow's depot trip count.";

    public function handle(FirebaseMessaging $firebase): int
    {
        $date = Carbon::parse($this->option('date') ?: Carbon::tomorrow())->toDateString();
        $sentRecipients = 0;
        $failedRecipients = 0;

        User::query()
            ->role(['Controller', 'Supervisor'])
            ->with([
                'roles',
                'deviceTokens' => fn ($query) => $query->where('app_type', 'operations'),
                'controllerProfile',
                'supervisorProfile',
            ])
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($firebase, $date, &$sentRecipients, &$failedRecipients): void {
                foreach ($users as $user) {
                    $profile = $user->hasRole('Controller')
                        ? $user->controllerProfile
                        : $user->supervisorProfile;

                    if (! $profile?->depot_id) {
                        continue;
                    }

                    $tripCount = TripSheetEntry::query()
                        ->whereHas('sheet', fn ($query) => $query->whereDate('date', $date))
                        ->forDepot((int) $profile->depot_id)
                        ->count();

                    if ($tripCount === 0) {
                        continue;
                    }

                    $log = TodayTripNotificationLog::firstOrCreate(
                        ['user_id' => $user->id, 'trip_date' => $date],
                        ['trip_count' => $tripCount]
                    );

                    if ($log->status === 'sent' && ! $this->option('force')) {
                        continue;
                    }

                    $successfulTokens = 0;
                    $errors = [];

                    if ($user->deviceTokens->isEmpty()) {
                        $errors[] = 'No registered FCM device tokens.';
                    }

                    foreach ($user->deviceTokens as $device) {
                        try {
                            $response = $firebase->send(
                                $device->token,
                                "Tomorrow's trips",
                                $tripCount === 1 ? 'Your depot has 1 trip tomorrow.' : "Your depot has {$tripCount} trips tomorrow.",
                                ['type' => 'today_trips', 'date' => $date, 'trip_count' => $tripCount],
                                'operations'
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
                        $sentRecipients++;
                    } else {
                        $failedRecipients++;
                    }
                }
            });

        $this->info("Controller and supervisor trip notifications sent: {$sentRecipients}.");

        if ($failedRecipients > 0) {
            $this->warn("Controller and supervisor trip notifications failed or had no device: {$failedRecipients}.");
        }

        return $failedRecipients > 0 ? self::FAILURE : self::SUCCESS;
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

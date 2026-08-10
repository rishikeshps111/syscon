<?php

namespace App\Console\Commands;

use App\Models\DriverDocumentExpiryNotificationLog;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\FirebaseMessaging;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SendDriverDocumentExpiryNotifications extends Command
{
    protected $signature = 'drivers:document-expiry-notifications
        {--date= : Date in YYYY-MM-DD format}
        {--force : Resend already successful notifications for this reminder date}';

    protected $description = 'Send licence and badge expiry reminders to drivers and their depot controllers and supervisors.';

    public function handle(FirebaseMessaging $firebase): int
    {
        $today = Carbon::parse($this->option('date') ?: today())->startOfDay();
        $windowEnd = $today->copy()->addMonthsNoOverflow(6)->endOfMonth();
        $sent = 0;
        $failed = 0;

        $operationsUsers = User::query()
            ->role(['Controller', 'Supervisor'])
            ->where('is_active', true)
            ->with([
                'roles',
                'deviceTokens' => fn($query) => $query->where('app_type', 'operations'),
                'controllerProfile:id,user_id,depot_id',
                'supervisorProfile:id,user_id,depot_id',
            ])
            ->get()
            ->groupBy(fn(User $user) => $user->hasRole('Controller')
                ? $user->controllerProfile?->depot_id
                : $user->supervisorProfile?->depot_id);

        DriverProfile::query()
            ->with(['user.deviceTokens' => fn($query) => $query->where('app_type', 'driver')])
            ->whereHas('user', fn($query) => $query->where('is_active', true))
            ->where(function ($query) use ($today, $windowEnd): void {
                $query->whereBetween('expiry_date', [$today->toDateString(), $windowEnd->toDateString()])
                    ->orWhereBetween('badge_expiry_date', [$today->toDateString(), $windowEnd->toDateString()]);
            })
            ->orderBy('id')
            ->chunkById(100, function ($drivers) use ($firebase, $today, $operationsUsers, &$sent, &$failed): void {
                foreach ($drivers as $driver) {
                    foreach (['licence' => $driver->expiry_date, 'badge' => $driver->badge_expiry_date] as $documentType => $expiryDate) {
                        if (! $expiryDate || ! $this->isWithinReminderWindow($today, $expiryDate)) {
                            continue;
                        }

                        $recipients = collect([[
                            'user' => $driver->user,
                            'recipient_type' => 'driver',
                            'app_type' => 'driver',
                        ]]);

                        foreach ($operationsUsers->get($driver->depot_id, collect()) as $user) {
                            $recipients->push([
                                'user' => $user,
                                'recipient_type' => strtolower($user->getRoleNames()->first() ?: 'operations'),
                                'app_type' => 'operations',
                            ]);
                        }

                        foreach ($recipients as $recipient) {
                            $result = $this->notify(
                                $firebase,
                                $driver,
                                $documentType,
                                $expiryDate,
                                $today,
                                $recipient['user'],
                                $recipient['recipient_type'],
                                $recipient['app_type'],
                            );

                            if ($result === true) {
                                $sent++;
                            } elseif ($result === false) {
                                $failed++;
                            }
                        }
                    }
                }
            });

        $this->info("Driver document expiry notifications sent: {$sent}.");

        if ($failed > 0) {
            $this->warn("Driver document expiry notifications failed or had no device: {$failed}.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isWithinReminderWindow(Carbon $today, Carbon $expiryDate): bool
    {
        $expiry = $expiryDate->copy()->startOfDay();
        $firstReminder = $expiry->copy()->subMonthsNoOverflow(6);

        return $today->betweenIncluded($firstReminder, $expiry);
    }

    private function notify(
        FirebaseMessaging $firebase,
        DriverProfile $driver,
        string $documentType,
        Carbon $expiryDate,
        Carbon $reminderDate,
        User $recipient,
        string $recipientType,
        string $appType,
    ): ?bool {
        if (! $this->option('force')) {
            $lastSuccessfulReminderDate = DriverDocumentExpiryNotificationLog::query()
                ->where('driver_profile_id', $driver->id)
                ->where('recipient_user_id', $recipient->id)
                ->where('document_type', $documentType)
                ->whereDate('expiry_date', $expiryDate->toDateString())
                ->where('status', 'sent')
                ->whereDate('reminder_date', '<=', $reminderDate->toDateString())
                ->max('reminder_date');

            if (
                $lastSuccessfulReminderDate
                && Carbon::parse($lastSuccessfulReminderDate)->gt($reminderDate->copy()->subDays(3))
            ) {
                return null;
            }
        }

        $log = DriverDocumentExpiryNotificationLog::firstOrCreate([
            'driver_profile_id' => $driver->id,
            'recipient_user_id' => $recipient->id,
            'document_type' => $documentType,
            'expiry_date' => $expiryDate->toDateString(),
            'reminder_date' => $reminderDate->toDateString(),
        ], [
            'recipient_type' => $recipientType,
            'expiry_status' => 'expire_soon',
        ]);

        if ($log->status === 'sent' && ! $this->option('force')) {
            return null;
        }

        $successfulTokens = 0;
        $errors = [];

        if ($recipient->deviceTokens->isEmpty()) {
            $errors[] = 'No registered FCM device tokens.';
        }

        foreach ($recipient->deviceTokens as $device) {
            try {
                $response = $firebase->send(
                    $device->token,
                    $this->title($documentType),
                    $this->body($driver, $documentType, $expiryDate, $recipientType === 'driver'),
                    [
                        'type' => 'document_expiry',
                        'document_type' => $documentType,
                        'status' => 'expire_soon',
                        'expiry_date' => $expiryDate->toDateString(),
                        'driver_profile_id' => $driver->id,
                    ],
                    $appType,
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
            'recipient_type' => $recipientType,
            'sent_count' => $successfulTokens,
            'status' => $successfulTokens > 0 ? 'sent' : 'failed',
            'error' => $errors ? mb_substr(implode(' | ', $errors), 0, 65000) : null,
            'sent_at' => $successfulTokens > 0 ? now() : null,
        ]);

        return $successfulTokens > 0;
    }

    private function title(string $documentType): string
    {
        return $documentType === 'licence' ? 'License Going to Expire' : 'Badge Going to Expire';
    }

    private function body(DriverProfile $driver, string $documentType, Carbon $expiryDate, bool $forDriver): string
    {
        $date = $expiryDate->format('d M Y');

        if ($forDriver) {
            return $documentType === 'licence'
                ? "Your license expires on {$date}. Please renew and update the system."
                : "Your badge expires on {$date}. Please renew and update the system.";
        }

        $name = $driver->user->name;
        $phone = $driver->user->full_phone ?: '-';

        return $documentType === 'licence'
            ? "License period going to expire on {$date}, Driver Name {$name}, Phone No {$phone}. Please renew and update the system."
            : "Badge expires on {$date}, Driver Name {$name}, Phone No {$phone}. Please renew and update the system.";
    }

    private function deleteInvalidToken(UserDeviceToken $device, array $response): void
    {
        $errorCodes = collect(data_get($response, 'error.details', []))->pluck('errorCode')->filter()->all();

        if (array_intersect($errorCodes, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
            $device->delete();
        }
    }
}

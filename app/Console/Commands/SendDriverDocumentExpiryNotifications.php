<?php

namespace App\Console\Commands;

use App\Models\DriverDocumentExpiryNotificationLog;
use App\Models\DriverProfile;
use App\Models\UserDeviceToken;
use App\Services\FirebaseMessaging;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SendDriverDocumentExpiryNotifications extends Command
{
    protected $signature = 'drivers:document-expiry-notifications {--force : Resend already successful notifications}';

    protected $description = 'Send drivers Firebase notifications when their licence or badge is expiring soon or expired.';

    public function handle(FirebaseMessaging $firebase): int
    {
        $today = Carbon::today();
        $sixMonthsFromToday = $today->copy()->addMonths(6);
        $sent = 0;
        $failed = 0;

        DriverProfile::query()
            ->with(['user.deviceTokens' => fn ($query) => $query->where('app_type', 'driver')])
            ->whereHas('user', fn($query) => $query->where('is_active', true))
            ->where(function ($query) use ($sixMonthsFromToday): void {
                $query->whereDate('expiry_date', '<', $sixMonthsFromToday)
                    ->orWhereDate('badge_expiry_date', '<', $sixMonthsFromToday);
            })
            ->orderBy('id')
            ->chunkById(100, function ($drivers) use ($firebase, $today, $sixMonthsFromToday, &$sent, &$failed): void {
                foreach ($drivers as $driver) {
                    $documents = [
                        'licence' => $driver->expiry_date,
                        'badge' => $driver->badge_expiry_date,
                    ];

                    foreach ($documents as $documentType => $expiryDate) {
                        if (! $expiryDate || ! $expiryDate->lt($sixMonthsFromToday)) {
                            continue;
                        }

                        $expiryStatus = $expiryDate->lt($today) ? 'expired' : 'expire_soon';
                        $log = DriverDocumentExpiryNotificationLog::firstOrCreate([
                            'driver_profile_id' => $driver->id,
                            'document_type' => $documentType,
                            'expiry_date' => $expiryDate->toDateString(),
                            'expiry_status' => $expiryStatus,
                        ]);

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
                                    $this->title($documentType, $expiryStatus),
                                    $this->body($documentType, $expiryStatus, $expiryDate),
                                    [
                                        'type' => 'document_expiry',
                                        'document_type' => $documentType,
                                        'status' => $expiryStatus,
                                        'expiry_date' => $expiryDate->toDateString(),
                                    ],
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
                            'sent_count' => $successfulTokens,
                            'status' => $successfulTokens > 0 ? 'sent' : 'failed',
                            'error' => $errors ? mb_substr(implode(' | ', $errors), 0, 65000) : null,
                            'sent_at' => $successfulTokens > 0 ? now() : null,
                        ]);

                        $successfulTokens > 0 ? $sent++ : $failed++;
                    }
                }
            });

        $this->info("Driver document expiry notifications sent: {$sent}.");

        if ($failed > 0) {
            $this->warn("Driver document expiry notifications failed or had no device: {$failed}.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function title(string $documentType, string $status): string
    {
        $document = ucfirst($documentType);

        return $status === 'expired' ? "{$document} expired" : "{$document} expiring soon";
    }

    private function body(string $documentType, string $status, Carbon $expiryDate): string
    {
        $document = ucfirst($documentType);
        $date = $expiryDate->format('d M Y');

        return $status === 'expired'
            ? "Your {$document} expired on {$date}. Please renew it."
            : "Your {$document} will expire on {$date}. Please renew it soon.";
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

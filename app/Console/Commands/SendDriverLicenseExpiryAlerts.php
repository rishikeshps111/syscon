<?php

namespace App\Console\Commands;

use App\Events\DriverLicenseExpiredAlert;
use App\Models\DriverLicenseExpiryAlert;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class SendDriverLicenseExpiryAlerts extends Command
{
    protected $signature = 'drivers:expired-license-alerts';

    protected $description = 'Send expired driver license alerts to users with driver management access.';

    public function handle(): int
    {
        $expiredCount = DriverProfile::expiredLicenseCount();

        if ($expiredCount === 0) {
            $this->info('No expired driver licenses found.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays(3);
        $sent = 0;

        User::permission('driver-management.view')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($expiredCount, $cutoff, &$sent) {
                foreach ($users as $user) {
                    $lastAlert = DriverLicenseExpiryAlert::where('user_id', $user->id)
                        ->latest('notified_at')
                        ->first();

                    if ($lastAlert && $lastAlert->notified_at->gt($cutoff)) {
                        continue;
                    }

                    $alert = DriverLicenseExpiryAlert::create([
                        'user_id' => $user->id,
                        'expired_count' => $expiredCount,
                        'notified_at' => now(),
                    ]);

                    try {
                        broadcast(new DriverLicenseExpiredAlert($alert));
                    } catch (Throwable $exception) {
                        $this->warn("Pusher alert failed for user {$user->id}: {$exception->getMessage()}");
                    }

                    $sent++;
                }
            });

        $this->info("Expired driver license alerts sent: {$sent}.");

        return self::SUCCESS;
    }
}

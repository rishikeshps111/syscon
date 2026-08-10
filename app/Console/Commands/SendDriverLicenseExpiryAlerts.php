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
    protected $signature = 'drivers:expired-license-alerts {--date= : Date in YYYY-MM-DD format} {--force : Send even if alerted within the last three days}';

    protected $description = 'Send admins individual alerts for driver licences and badges expiring within six months.';

    public function handle(): int
    {
        $today = \Carbon\Carbon::parse($this->option('date') ?: today())->startOfDay();
        $windowEnd = $today->copy()->addMonthsNoOverflow(6)->endOfMonth();
        $sent = 0;
        $failed = 0;
        $admins = User::role('Super Admin')->where('is_active', true)->get();

        DriverProfile::query()
            ->with('user:id,name,email,phone,country_code')
            ->whereHas('user', fn($query) => $query->where('is_active', true))
            ->where(function ($query) use ($today, $windowEnd): void {
                $query->whereBetween('expiry_date', [$today->toDateString(), $windowEnd->toDateString()])
                    ->orWhereBetween('badge_expiry_date', [$today->toDateString(), $windowEnd->toDateString()]);
            })
            ->orderBy('id')
            ->chunkById(100, function ($drivers) use ($admins, $today, &$sent, &$failed): void {
                foreach ($drivers as $driver) {
                    foreach (['licence' => $driver->expiry_date, 'badge' => $driver->badge_expiry_date] as $documentType => $expiryDate) {
                        if (! $expiryDate || ! $today->betweenIncluded(
                            $expiryDate->copy()->subMonthsNoOverflow(6),
                            $expiryDate,
                        )) {
                            continue;
                        }

                        foreach ($admins as $admin) {
                            $lastAlert = DriverLicenseExpiryAlert::query()
                                ->where('user_id', $admin->id)
                                ->where('driver_profile_id', $driver->id)
                                ->where('document_type', $documentType)
                                ->whereDate('expiry_date', $expiryDate->toDateString())
                                ->latest('notified_at')
                                ->first();

                            if (! $this->option('force') && $lastAlert?->notified_at?->gt(now()->subDays(3))) {
                                continue;
                            }

                            $alert = DriverLicenseExpiryAlert::create([
                                'user_id' => $admin->id,
                                'driver_profile_id' => $driver->id,
                                'document_type' => $documentType,
                                'expiry_date' => $expiryDate,
                                'expired_count' => 1,
                                'notified_at' => now(),
                            ]);

                            try {
                                broadcast(new DriverLicenseExpiredAlert($alert));
                                $sent++;
                            } catch (Throwable $exception) {
                                $failed++;
                                $this->warn(ucfirst($documentType) . " alert failed for admin {$admin->id}, driver {$driver->id}: {$exception->getMessage()}");
                            }
                        }
                    }
                }
            });

        $this->info("Driver licence and badge expiry alerts sent to admins: {$sent}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

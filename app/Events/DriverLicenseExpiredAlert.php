<?php

namespace App\Events;

use App\Models\DriverLicenseExpiryAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLicenseExpiredAlert implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DriverLicenseExpiryAlert $alert)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('license-alert.user.' . $this->alert->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver-license.expired';
    }

    public function broadcastWith(): array
    {
        $this->alert->loadMissing('driverProfile.user');
        $driver = $this->alert->driverProfile;

        return [
            'id' => $this->alert->id,
            'expired_count' => 1,
            'driver_name' => $driver?->user?->name,
            'document_type' => $this->alert->document_type,
            'title' => $this->alert->document_type === 'badge' ? 'Badge Going to Expire' : 'License Going to Expire',
            'expiry_date' => $this->alert->expiry_date?->format('d M Y'),
            'message' => $this->message(),
            'url' => $driver?->user_id ? route('driver-management.edit', $driver->user_id) : route('driver-management.index'),
            'notified_at' => $this->alert->notified_at?->format('d-m-Y h:i A'),
        ];
    }

    private function message(): string
    {
        $name = $this->alert->driverProfile?->user?->name ?: 'Driver';
        $date = $this->alert->expiry_date?->format('d M Y') ?: '-';

        $document = $this->alert->document_type === 'badge' ? 'badge' : 'license';

        return "{$name}'s {$document} expires on {$date}. Please renew and update the system.";
    }
}

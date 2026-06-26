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
        return [
            'id' => $this->alert->id,
            'expired_count' => $this->alert->expired_count,
            'message' => $this->message(),
            'url' => route('driver-management.index', ['expiry_filter' => 'license_expired']),
            'notified_at' => $this->alert->notified_at?->format('d-m-Y h:i A'),
        ];
    }

    private function message(): string
    {
        return $this->alert->expired_count . ' driver license' . ($this->alert->expired_count === 1 ? ' has' : 's have') . ' expired.';
    }
}

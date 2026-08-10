<?php

namespace App\Events;

use App\Models\TripVerificationCompletedAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripVerificationCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TripVerificationCompletedAlert $alert) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('trip-verification.user.' . $this->alert->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'trip-verification.completed';
    }

    public function broadcastWith(): array
    {
        $this->alert->loadMissing('tripSheetEntry.sheet');
        $entry = $this->alert->tripSheetEntry;

        return [
            'id' => $this->alert->id,
            'verification_stage' => $this->alert->verification_stage,
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => route('trips.sheet.entries.edit', [
                'trip' => $entry?->sheet?->trip_id,
                'tripSheetEntry' => $entry?->id,
            ]),
            'trip_sheet_entry_id' => $entry?->id,
            'notified_at' => $this->alert->notified_at?->toIso8601String(),
        ];
    }

    private function message(): string
    {
        $entry = $this->alert->tripSheetEntry;
        $code = $entry?->code ?: "Entry #{$entry?->id}";
        $date = $entry?->sheet?->date?->format('d M Y') ?: '-';

        $stage = $this->alert->verification_stage === 'initial' ? 'Initial' : 'Final';

        return "{$stage} verification completed for trip {$code} on {$date}.";
    }

    private function title(): string
    {
        return $this->alert->verification_stage === 'initial'
            ? 'Initial Verification Completed'
            : 'Final Verification Completed';
    }
}

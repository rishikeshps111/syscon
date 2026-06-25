<?php

namespace App\Events;

use App\Models\ChatConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessagesSeen implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatConversation $conversation,
        public int $viewerId,
        public array $messageIds,
        public string $seenAt
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.user.' . $this->conversation->staff_user_id),
            new PrivateChannel('chat.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.seen';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'viewer_id' => $this->viewerId,
            'message_ids' => $this->messageIds,
            'seen_at' => $this->seenAt,
        ];
    }
}

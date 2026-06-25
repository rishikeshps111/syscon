<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
        $this->message->loadMissing(['conversation.staff', 'sender']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.user.' . $this->message->conversation->staff_user_id),
            new PrivateChannel('chat.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => app(\App\Http\Controllers\ChatController::class)->messagePayload($this->message),
            'conversation' => app(\App\Http\Controllers\ChatController::class)->conversationPayload($this->message->conversation, null),
        ];
    }
}

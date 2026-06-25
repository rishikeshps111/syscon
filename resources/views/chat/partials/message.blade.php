@php
    $mine = (int) $message['sender_id'] === (int) $currentUserId;
@endphp

<div class="chat-message {{ $mine ? 'mine' : 'theirs' }}" data-message-id="{{ $message['id'] }}">
    <div class="chat-bubble">
        @if ($message['attachment_url'])
            @if ($message['is_image'])
                <a href="{{ $message['attachment_url'] }}" target="_blank" class="chat-image-link">
                    <img src="{{ $message['attachment_url'] }}" alt="{{ $message['attachment_name'] ?: 'Image' }}">
                </a>
            @else
                <a href="{{ $message['attachment_url'] }}" target="_blank" class="chat-file-link">
                    <i class="fa-solid fa-file"></i>
                    <span>{{ $message['attachment_name'] ?: 'Attachment' }}</span>
                </a>
            @endif
        @endif

        @if ($message['body'])
            <div class="chat-text">{!! nl2br(e($message['body'])) !!}</div>
        @endif

        <div class="chat-meta">
            <span>{{ $message['time'] }}</span>
            @if ($mine)
                <span class="chat-ticks {{ $message['seen_at'] ? 'seen' : '' }}"
                    title="{{ $message['seen_at'] ? 'Seen' : ($message['delivered_at'] ? 'Delivered' : 'Sent') }}">
                    <i class="fa-solid fa-check-double"></i>
                </span>
            @endif
        </div>
    </div>
</div>

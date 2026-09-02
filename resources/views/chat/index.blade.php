@section('title')
    Chat
@endsection

<x-app-layout>
    @php
        $currentUser = auth()->user();
        $currentUserId = $currentUser->id;
    @endphp

    <section class="section dashboard section-top-padding">
        {{-- <div class="page-title">
            <h3>{{ $mode === 'admin' ? 'Chat' : 'Chat with Admin' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Chat</li>
                </ol>
            </nav>
        </div> --}}

        <div id="chatShell" class="chat-shell" data-mode="{{ $mode }}">
            @if ($mode === 'admin')
                <aside class="chat-list-panel">
                    <div class="chat-list-head">
                        <strong>Staff Chats</strong>
                        <span id="chatListCount">{{ $conversations->count() }}</span>
                    </div>
                    <div class="chat-search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="chatSearch" placeholder="Search staff chats">
                    </div>
                    <div id="conversationList" class="chat-list">
                        @forelse ($conversations as $item)
                            <button type="button"
                                class="chat-contact {{ $conversation && $conversation['id'] === $item['id'] ? 'active' : '' }}"
                                data-conversation-id="{{ $item['id'] }}"
                                data-search="{{ strtolower(trim(($item['staff_name'] ?? '') . ' ' . ($item['staff_code'] ?? '') . ' ' . ($item['latest_preview'] ?? ''))) }}">
                                <img src="{{ $item['staff_avatar_url'] }}" alt="{{ $item['staff_name'] }}">
                                <span class="chat-contact-main">
                                    <span class="chat-contact-name">{{ $item['staff_name'] }}</span>
                                    <span class="chat-contact-preview">{{ $item['latest_preview'] }}</span>
                                </span>
                                <span class="chat-contact-meta">
                                    <small>{{ $item['latest_time'] }}</small>
                                    <span class="chat-contact-badge {{ $item['unread_count'] ? '' : 'd-none' }}">{{ $item['unread_count'] }}</span>
                                </span>
                            </button>
                        @empty
                            <div class="chat-empty-list">No staff users found.</div>
                        @endforelse
                        <div id="chatNoResults" class="chat-empty-list d-none">No matching staff chats.</div>
                    </div>
                </aside>
            @endif

            <section class="chat-room">
                <div id="chatRoomEmpty" class="chat-empty {{ $conversation ? 'd-none' : '' }}">
                    Select a staff user to start chatting.
                </div>

                <div id="chatRoomContent" class="{{ $conversation ? '' : 'd-none' }}">
                    <div class="chat-room-head">
                        <img id="roomAvatar" src="{{ $conversation['staff_avatar_url'] ?? asset('assets/img/user.png') }}"
                            alt="{{ $conversation['staff_name'] ?? 'Staff' }}">
                        <div>
                            <strong id="roomName">{{ $mode === 'admin' ? ($conversation['staff_name'] ?? 'Staff') : 'Super Admin' }}</strong>
                            <small id="roomSub">{{ $mode === 'admin' ? ($conversation['staff_code'] ?? 'Staff') : 'Support chat' }}</small>
                        </div>
                        <button type="button" id="chatFullscreenToggle" class="chat-fullscreen-btn ms-auto" title="Full screen">
                            <i class="fa-solid fa-expand"></i>
                        </button>
                    </div>

                    <div id="messageList" class="chat-messages">
                        @foreach ($messages as $message)
                            @include('chat.partials.message', ['message' => $message, 'currentUserId' => $currentUserId])
                        @endforeach
                    </div>

                    <form id="chatForm" class="chat-compose" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="conversation_id" id="conversationId" value="{{ $conversation['id'] ?? '' }}">
                        <input type="file" name="attachment" id="chatAttachment" class="d-none"
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip">
                        <button type="button" class="chat-icon-btn" id="chooseAttachment" title="Attach file">
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                        <div class="chat-input-wrap">
                            <textarea name="body" id="chatBody" rows="1" placeholder="Type a message"></textarea>
                            <div id="attachmentName" class="chat-attachment-name d-none"></div>
                        </div>
                        <button type="submit" class="chat-send-btn" title="Send">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var currentUserId = {{ $currentUserId }};
                var mode = @json($mode);
                var pusherConfig = @json($pusher);
                var selectedConversationId = Number($('#conversationId').val()) || null;
                var csrf = $('meta[name="csrf-token"]').attr('content');

                function escapeHtml(value) {
                    return $('<div>').text(value == null ? '' : value).html();
                }

                function scrollMessages() {
                    var list = $('#messageList');
                    list.scrollTop(list.prop('scrollHeight') || 0);
                }

                function scrollMessagesToLatest() {
                    scrollMessages();
                    window.requestAnimationFrame(scrollMessages);
                    setTimeout(scrollMessages, 120);
                    setTimeout(scrollMessages, 350);
                }

                function ticks(message) {
                    if (Number(message.sender_id) !== currentUserId) {
                        return '';
                    }

                    var statusClass = message.seen_at ? 'seen' : '';
                    var label = message.seen_at ? 'Seen' : (message.delivered_at ? 'Delivered' : 'Sent');

                    return '<span class="chat-ticks ' + statusClass + '" title="' + label + '"><i class="fa-solid fa-check-double"></i></span>';
                }

                function messageHtml(message) {
                    var mine = Number(message.sender_id) === currentUserId;
                    var attachment = '';

                    if (message.attachment_url) {
                        if (message.is_image) {
                            attachment = '<a href="' + escapeHtml(message.attachment_url) + '" target="_blank" class="chat-image-link"><img src="' + escapeHtml(message.attachment_url) + '" alt="' + escapeHtml(message.attachment_name || 'Image') + '"></a>';
                        } else {
                            attachment = '<a href="' + escapeHtml(message.attachment_url) + '" target="_blank" class="chat-file-link"><i class="fa-solid fa-file"></i><span>' + escapeHtml(message.attachment_name || 'Attachment') + '</span></a>';
                        }
                    }

                    return '<div class="chat-message ' + (mine ? 'mine' : 'theirs') + '" data-message-id="' + message.id + '">' +
                        '<div class="chat-bubble">' +
                        attachment +
                        (message.body ? '<div class="chat-text">' + escapeHtml(message.body).replace(/\n/g, '<br>') + '</div>' : '') +
                        '<div class="chat-meta"><span>' + escapeHtml(message.time || '') + '</span>' + ticks(message) + '</div>' +
                        '</div></div>';
                }

                function appendMessage(message) {
                    if ($('#messageList [data-message-id="' + message.id + '"]').length) {
                        return;
                    }

                    $('#messageList').append(messageHtml(message));
                    scrollMessagesToLatest();
                }

                function renderMessages(messages) {
                    $('#messageList').html((messages || []).map(messageHtml).join(''));
                    scrollMessagesToLatest();
                }

                function updateRoom(conversation) {
                    selectedConversationId = Number(conversation.id);
                    $('#conversationId').val(conversation.id);
                    $('#roomAvatar').attr('src', conversation.staff_avatar_url || '{{ asset('assets/img/user.png') }}');
                    $('#roomName').text(mode === 'admin' ? conversation.staff_name : 'Super Admin');
                    $('#roomSub').text(mode === 'admin' ? (conversation.staff_code || 'Staff') : 'Support chat');
                    $('#chatRoomEmpty').addClass('d-none');
                    $('#chatRoomContent').removeClass('d-none');
                }

                function updateUnreadBadge(count) {
                    if (window.updateChatUnreadBadge) {
                        window.updateChatUnreadBadge(count);
                    }
                }

                function refreshUnreadBadge() {
                    $.get("{{ route('chat.unread-count') }}").done(function (response) {
                        updateUnreadBadge(response.count || 0);
                    });
                }

                function markSeen() {
                    if (!selectedConversationId) {
                        return;
                    }

                    $.post("{{ url('/chat/conversations') }}/" + selectedConversationId + "/seen", { _token: csrf })
                        .always(refreshUnreadBadge);
                }

                function upsertConversation(conversation) {
                    if (mode !== 'admin') {
                        return;
                    }

                    var existing = $('.chat-contact[data-conversation-id="' + conversation.id + '"]');
                    var badgeClass = conversation.unread_count ? '' : 'd-none';
                    var searchText = [conversation.staff_name, conversation.staff_code, conversation.latest_preview].join(' ').toLowerCase();
                    var html = '<button type="button" class="chat-contact ' + (Number(conversation.id) === selectedConversationId ? 'active' : '') + '" data-conversation-id="' + conversation.id + '" data-search="' + escapeHtml(searchText) + '">' +
                        '<img src="' + escapeHtml(conversation.staff_avatar_url || '{{ asset('assets/img/user.png') }}') + '" alt="' + escapeHtml(conversation.staff_name) + '">' +
                        '<span class="chat-contact-main"><span class="chat-contact-name">' + escapeHtml(conversation.staff_name) + '</span><span class="chat-contact-preview">' + escapeHtml(conversation.latest_preview) + '</span></span>' +
                        '<span class="chat-contact-meta"><small>' + escapeHtml(conversation.latest_time || '') + '</small><span class="chat-contact-badge ' + badgeClass + '">' + (conversation.unread_count || '') + '</span></span>' +
                        '</button>';

                    if (existing.length) {
                        existing.replaceWith(html);
                    } else {
                        $('#conversationList').prepend(html);
                    }

                    $('#conversationList .chat-contact[data-conversation-id="' + conversation.id + '"]').prependTo('#conversationList');
                    filterConversations();
                }

                function filterConversations() {
                    var term = ($('#chatSearch').val() || '').toLowerCase().trim();
                    var visibleCount = 0;

                    $('#conversationList .chat-contact').each(function () {
                        var match = !term || String($(this).data('search') || '').indexOf(term) !== -1;
                        $(this).toggleClass('d-none', !match);

                        if (match) {
                            visibleCount++;
                        }
                    });

                    $('#chatListCount').text(visibleCount);
                    $('#chatNoResults').toggleClass('d-none', visibleCount !== 0);
                }

                $('#chatSearch').on('input', filterConversations);

                function fullscreenElement() {
                    return document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
                }

                function updateFullscreenButton(active) {
                    $('#chatShell').toggleClass('chat-fullscreen-active', active);
                    $('#chatFullscreenToggle')
                        .attr('title', active ? 'Exit full screen' : 'Full screen')
                        .html(active
                            ? '<i class="fa-solid fa-compress"></i>'
                            : '<i class="fa-solid fa-expand"></i>');
                    scrollMessagesToLatest();
                }

                $('#chatFullscreenToggle').on('click', function () {
                    var shell = document.getElementById('chatShell');

                    if (fullscreenElement()) {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        } else if (document.msExitFullscreen) {
                            document.msExitFullscreen();
                        }
                        return;
                    }

                    if (shell.requestFullscreen) {
                        shell.requestFullscreen();
                    } else if (shell.webkitRequestFullscreen) {
                        shell.webkitRequestFullscreen();
                    } else if (shell.msRequestFullscreen) {
                        shell.msRequestFullscreen();
                    } else {
                        updateFullscreenButton(!$('#chatShell').hasClass('chat-fullscreen-active'));
                    }
                });

                $(document).on('fullscreenchange webkitfullscreenchange msfullscreenchange', function () {
                    updateFullscreenButton(Boolean(fullscreenElement()));
                });

                $(document).on('click', '.chat-contact', function () {
                    var id = Number($(this).data('conversation-id'));
                    $('.chat-contact').removeClass('active');
                    $(this).addClass('active');

                    $.get("{{ url('/chat/conversations') }}/" + id).done(function (response) {
                        updateRoom(response.conversation);
                        renderMessages(response.messages);
                        upsertConversation(response.conversation);
                        markSeen();
                    }).fail(function () {
                        showToast('error', 'Unable to open chat.');
                    });
                });

                $('#chooseAttachment').on('click', function () {
                    $('#chatAttachment').trigger('click');
                });

                $('#chatAttachment').on('change', function () {
                    var file = this.files[0];
                    $('#attachmentName').toggleClass('d-none', !file).text(file ? file.name : '');
                });

                $('#chatBody').on('input', function () {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                    scrollMessages();
                });

                $('#messageList').on('load', 'img', scrollMessagesToLatest);

                $('#chatForm').on('submit', function (event) {
                    event.preventDefault();

                    if (!selectedConversationId && mode === 'admin') {
                        showToast('warning', 'Select a staff chat first.');
                        return;
                    }

                    var form = this;
                    var data = new FormData(form);
                    var button = $('.chat-send-btn');
                    button.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('chat.messages.store') }}",
                        method: 'POST',
                        data: data,
                        processData: false,
                        contentType: false
                    }).done(function (response) {
                        updateRoom(response.conversation);
                        appendMessage(response.message);
                        upsertConversation(response.conversation);
                        form.reset();
                        $('#chatBody').css('height', 'auto');
                        $('#attachmentName').addClass('d-none').text('');
                    }).fail(function (xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Unable to send message.');
                    }).always(function () {
                        button.prop('disabled', false);
                    });
                });

                function handleIncoming(data) {
                    var message = data.message;
                    var conversation = data.conversation;

                    if (Number(message.sender_id) === currentUserId) {
                        return;
                    }

                    if (Number(message.conversation_id) === selectedConversationId) {
                        conversation.unread_count = 0;
                        upsertConversation(conversation);
                        appendMessage(message);
                        markSeen();
                    } else {
                        var existingBadge = $('.chat-contact[data-conversation-id="' + conversation.id + '"] .chat-contact-badge');
                        conversation.unread_count = (Number(existingBadge.text()) || 0) + 1;
                        upsertConversation(conversation);
                        refreshUnreadBadge();
                    }
                }

                function handleSeen(data) {
                    if (Number(data.viewer_id) === currentUserId || Number(data.conversation_id) !== selectedConversationId) {
                        return;
                    }

                    (data.message_ids || []).forEach(function (id) {
                        var bubble = $('#messageList [data-message-id="' + id + '"]');
                        bubble.find('.chat-ticks').addClass('seen').attr('title', 'Seen');
                    });
                }

                if (pusherConfig.enabled) {
                    var pusher = new Pusher(pusherConfig.key, {
                        cluster: pusherConfig.cluster || 'mt1',
                        channelAuthorization: {
                            endpoint: '/broadcasting/auth',
                            headers: { 'X-CSRF-TOKEN': csrf }
                        }
                    });

                    var channelName = mode === 'admin' ? 'private-chat.admin' : 'private-chat.user.' + currentUserId;
                    var channel = pusher.subscribe(channelName);
                    channel.bind('message.sent', handleIncoming);
                    channel.bind('messages.seen', handleSeen);
                }

                scrollMessagesToLatest();
                markSeen();
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .chat-fullscreen-btn {
                align-items: center;
                background: #0d6efd;
                border: 0;
                border-radius: 6px;
                color: #fff;
                display: inline-flex;
                flex: 0 0 auto;
                font-size: 14px;
                height: 36px;
                justify-content: center;
                padding: 0;
                width: 36px;
            }

            .chat-shell {
                background: #fff;
                /*border: 1px solid #dfe6e2;*/
                border-radius: 15px;
                display: grid;
                grid-template-columns: minmax(280px, 34%) 1fr;
                height: calc(100vh - 190px);
                min-height: 560px;
                overflow: hidden;
                box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
            }

            .chat-shell:fullscreen,
            .chat-shell.chat-fullscreen-active {
                border: 0;
                border-radius: 0;
                height: 100vh;
                min-height: 100vh;
                width: 100vw;
            }

            .chat-shell[data-mode="staff"] {
                grid-template-columns: 1fr;
            }

            .chat-list-panel {
                background: #fff;
                border-right: 1px solid #dfe6e2;
                height: 100%;
                min-width: 0;
                overflow: hidden;
            }

            .chat-list-head {
                align-items: center;
                border-bottom: 1px solid #edf0ee;
                display: flex;
                height: 64px;
                justify-content: space-between;
                padding: 0 18px;
            }

            .chat-search-wrap {
                align-items: center;
                background: #f4f7f5;
                border-bottom: 1px solid #edf0ee;
                display: flex;
                gap: 8px;
                height: 52px;
                padding: 8px 14px;
            }

            .chat-search-wrap i {
                color: #6b7a75;
                font-size: 13px;
            }

            .chat-search-wrap input {
                background: #fff;
                border: 1px solid #dfe6e2;
                border-radius: 18px;
                box-shadow: none;
                font-size: 13px;
                outline: 0;
                padding: 7px 12px;
                width: 100%;
            }

            .chat-list {
                height: calc(100% - 116px);
                overflow-y: auto;
            }

            .chat-contact {
                align-items: center;
                background: #fff;
                border: 0;
                border-bottom: 1px solid #edf0ee;
                display: flex;
                gap: 12px;
                padding: 12px 14px;
                text-align: left;
                width: 100%;
            }

            .chat-contact.active,
            .chat-contact:hover {
                background: #eef7f1;
            }

            .chat-contact img,
            .chat-room-head img {
                border-radius: 50%;
                height: 42px;
                object-fit: cover;
                width: 42px;
            }

            .chat-contact-main {
                display: grid;
                flex: 1;
                min-width: 0;
            }

            .chat-contact-name,
            .chat-contact-preview {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .chat-contact-name {
                    color: #1f2d2a;
    font-weight: 600;
    text-transform: capitalize;
            }

            .chat-contact-preview,
            .chat-contact-meta small,
            .chat-room-head small {
                color: #6b7a75;
                font-size: 12px;
            }

            .chat-contact-meta {
                align-items: flex-end;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .chat-contact-badge {
                align-items: center;
                background: #25d366;
                border-radius: 999px;
                color: #fff;
                display: inline-flex;
                font-size: 11px;
                font-weight: 700;
                height: 20px;
                justify-content: center;
                min-width: 20px;
                padding: 0 6px;
            }

            .chat-room {
                height: 100%;
                min-width: 0;
                overflow: hidden;
                position: relative;
            }

            #chatRoomContent {
                display: grid;
                grid-template-rows: 64px 1fr auto;
                height: 100%;
                min-height: 0;
            }

            .chat-room-head {
                align-items: center;
                background: #fff;
                border-bottom: 1px solid #dfe6e2;
                display: flex;
                gap: 12px;
                padding: 0 18px;
            }

            .chat-room-head div {
                display: grid;
                min-width: 0;
            }

            .chat-messages {
                background: #e7eee9;
                min-height: 0;
                overflow-y: auto;
                padding: 22px;
                scroll-behavior: smooth;
            }

            .chat-message {
                display: flex;
                margin-bottom: 10px;
            }

            .chat-message.mine {
                justify-content: flex-end;
            }

            .chat-bubble {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .06);
                max-width: min(520px, 82%);
                padding: 8px 10px 6px;
            }

            .chat-message.mine .chat-bubble {
                background: #d9fdd3;
            }

            .chat-text {
                color: #24332f;
                font-size: 14px;
                line-height: 1.4;
                white-space: normal;
                word-break: break-word;
            }

            .chat-meta {
                align-items: center;
                color: #6b7a75;
                display: flex;
                font-size: 11px;
                gap: 6px;
                justify-content: flex-end;
                margin-top: 4px;
            }

            .chat-ticks.seen {
                color: #0d6efd;
            }

            .chat-image-link img {
                border-radius: 6px;
                display: block;
                max-height: 240px;
                max-width: 100%;
                object-fit: cover;
            }

            .chat-file-link {
                align-items: center;
                background: rgba(255, 255, 255, .7);
                border: 1px solid #dfe6e2;
                border-radius: 6px;
                color: #24332f;
                display: flex;
                gap: 8px;
                padding: 10px;
                text-decoration: none;
            }

            .chat-compose {
                align-items: flex-end;
                background: #f7f9f8;
                border-top: 1px solid #dfe6e2;
                display: flex;
                gap: 10px;
                padding: 12px;
            }

            .chat-icon-btn,
            .chat-send-btn {
                align-items: center;
                border: 0;
                border-radius: 50%;
                display: inline-flex;
                height: 42px;
                justify-content: center;
                width: 42px;
            }

            .chat-icon-btn {
                background: #e9efec;
                color: #43524e;
            }

            .chat-send-btn {
                background: #0d6efd;
                color: #fff;
            }

            .chat-input-wrap {
                background: #fff;
                border: 1px solid #dfe6e2;
                border-radius: 22px;
                flex: 1;
                padding: 8px 14px;
            }

            .chat-input-wrap textarea {
                border: 0;
                box-shadow: none;
                outline: 0;
                resize: none;
                width: 100%;
            }

            .chat-attachment-name {
                color: #128c7e;
                font-size: 12px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .chat-empty,
            .chat-empty-list {
                align-items: center;
                color: #6b7a75;
                display: flex;
                height: 100%;
                justify-content: center;
                padding: 24px;
                text-align: center;
            }

            @media (max-width: 991px) {
                .chat-shell {
                    grid-template-columns: 1fr;
                    height: calc(100vh - 160px);
                    min-height: 520px;
                }

                .chat-list-panel {
                    border-right: 0;
                    display: {{ $mode === 'admin' ? 'block' : 'none' }};
                    height: 220px;
                }

                #chatRoomContent {
                    height: 100%;
                }
            }
        </style>
    @endsection
</x-app-layout>

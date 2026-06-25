<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Events\ChatMessagesSeen;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($this->canUseChat($user), 403);

        if ($user->hasRole('Staff')) {
            $conversation = $this->conversationForStaff($user);
            $this->markConversationSeen($conversation, $user, true);

            return view('chat.index', [
                'mode' => 'staff',
                'conversation' => $this->conversationPayload($conversation, $user),
                'conversations' => collect(),
                'messages' => $conversation->messages()->with('sender')->oldest()->get()->map(fn (ChatMessage $message) => $this->messagePayload($message)),
                'pusher' => $this->pusherConfig(),
            ]);
        }

        $conversations = $this->adminConversations($user);
        $selected = $request->filled('conversation')
            ? $conversations->firstWhere('id', (int) $request->integer('conversation'))
            : $conversations->first();

        if ($selected) {
            $this->markConversationSeen($selected, $user, true);
        }

        return view('chat.index', [
            'mode' => 'admin',
            'conversation' => $selected ? $this->conversationPayload($selected, $user) : null,
            'conversations' => $conversations->map(fn (ChatConversation $conversation) => $this->conversationPayload($conversation, $user)),
            'messages' => $selected
                ? $selected->messages()->with('sender')->oldest()->get()->map(fn (ChatMessage $message) => $this->messagePayload($message))
                : collect(),
            'pusher' => $this->pusherConfig(),
        ]);
    }

    public function show(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request->user(), $conversation);
        $this->markConversationSeen($conversation, $request->user(), true);

        return response()->json([
            'conversation' => $this->conversationPayload($conversation->fresh(['staff', 'admin']), $request->user()),
            'messages' => $conversation->messages()->with('sender')->oldest()->get()->map(fn (ChatMessage $message) => $this->messagePayload($message)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->canUseChat($user), 403);

        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip'],
        ]);

        if (blank($validated['body'] ?? null) && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'Enter a message or attach a file.'], 422);
        }

        $conversation = $this->resolveConversationForSend($user, $validated['conversation_id'] ?? null);

        $message = DB::transaction(function () use ($request, $validated, $conversation, $user) {
            $attachment = $request->file('attachment');
            $path = $attachment?->store('chat-attachments', 'public');

            $message = ChatMessage::create([
                'chat_conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'body' => $validated['body'] ?? null,
                'attachment_path' => $path,
                'attachment_name' => $attachment?->getClientOriginalName(),
                'attachment_mime' => $attachment?->getMimeType(),
                'attachment_size' => $attachment?->getSize(),
                'delivered_at' => now(),
            ]);

            $conversation->update([
                'admin_user_id' => $user->hasRole('Super Admin') ? $user->id : $conversation->admin_user_id,
                'latest_message_at' => $message->created_at,
            ]);

            return $message->load(['conversation.staff', 'sender']);
        });

        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json([
            'message' => $this->messagePayload($message),
            'conversation' => $this->conversationPayload($message->conversation, $user),
        ]);
    }

    public function seen(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request->user(), $conversation);
        $updated = $this->markConversationSeen($conversation, $request->user(), true);

        return response()->json(['success' => true, 'updated' => count($updated)]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        abort_unless($this->canUseChat($request->user()), 403);

        return response()->json(['count' => $this->unreadCountFor($request->user())]);
    }

    public function messagePayload(ChatMessage $message): array
    {
        $message->loadMissing('sender');

        return [
            'id' => $message->id,
            'conversation_id' => $message->chat_conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name ?? 'User',
            'body' => $message->body,
            'attachment_url' => $message->attachment_url,
            'attachment_name' => $message->attachment_name,
            'attachment_mime' => $message->attachment_mime,
            'attachment_size' => $message->attachment_size,
            'is_image' => $message->is_image,
            'delivered_at' => $message->delivered_at?->toIso8601String(),
            'read_at' => $message->read_at?->toIso8601String(),
            'seen_at' => $message->seen_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
            'time' => $message->created_at?->format('h:i A'),
        ];
    }

    public function conversationPayload(ChatConversation $conversation, ?User $viewer): array
    {
        $conversation->loadMissing(['staff', 'admin']);
        $latest = $conversation->messages()->latest()->first();
        $viewer ??= auth()->user();

        return [
            'id' => $conversation->id,
            'staff_user_id' => $conversation->staff_user_id,
            'staff_name' => $conversation->staff?->name ?? 'Staff',
            'staff_code' => $conversation->staff?->code,
            'staff_avatar_url' => $conversation->staff?->avatar_url,
            'latest_message_at' => $conversation->latest_message_at?->toIso8601String(),
            'latest_time' => $conversation->latest_message_at?->format('d M, h:i A'),
            'latest_preview' => $latest ? ($latest->body ?: ($latest->is_image ? 'Image' : 'Attachment')) : 'No messages yet',
            'unread_count' => $viewer ? $this->conversationUnreadCount($conversation, $viewer) : 0,
        ];
    }

    public function unreadCountFor(User $user): int
    {
        if ($user->hasRole('Staff')) {
            $conversation = ChatConversation::where('staff_user_id', $user->id)->first();

            return $conversation ? $this->conversationUnreadCount($conversation, $user) : 0;
        }

        if ($user->hasRole('Super Admin')) {
            return ChatMessage::whereHas('conversation', fn ($query) => $query->whereNotNull('staff_user_id'))
                ->whereHas('sender', fn ($query) => $query->role('Staff'))
                ->whereNull('seen_at')
                ->count();
        }

        return 0;
    }

    private function adminConversations(User $user)
    {
        User::role('Staff')
            ->where('is_active', true)
            ->pluck('id')
            ->each(fn (int $staffId) => ChatConversation::firstOrCreate(['staff_user_id' => $staffId]));

        return ChatConversation::with(['staff', 'admin'])
            ->whereHas('staff', fn ($query) => $query->role('Staff'))
            ->orderByDesc(DB::raw('COALESCE(latest_message_at, updated_at)'))
            ->get();
    }

    private function conversationForStaff(User $staff): ChatConversation
    {
        return ChatConversation::firstOrCreate(
            ['staff_user_id' => $staff->id],
            ['latest_message_at' => now()]
        )->load(['staff', 'admin']);
    }

    private function resolveConversationForSend(User $user, ?int $conversationId): ChatConversation
    {
        if ($user->hasRole('Staff')) {
            return $this->conversationForStaff($user);
        }

        $conversation = ChatConversation::with(['staff', 'admin'])->findOrFail($conversationId);
        $this->authorizeConversation($user, $conversation);

        return $conversation;
    }

    private function authorizeConversation(User $user, ChatConversation $conversation): void
    {
        abort_unless(
            $user->hasRole('Super Admin') || ($user->hasRole('Staff') && (int) $conversation->staff_user_id === (int) $user->id),
            403
        );
    }

    private function markConversationSeen(ChatConversation $conversation, User $viewer, bool $broadcast = false): array
    {
        $messageIds = $this->unseenMessageQuery($conversation, $viewer)
            ->pluck('id')
            ->all();

        if ($messageIds === []) {
            return [];
        }

        $seenAt = now();

        ChatMessage::whereKey($messageIds)->update([
            'read_at' => $seenAt,
            'seen_at' => $seenAt,
        ]);

        if ($broadcast) {
            broadcast(new ChatMessagesSeen($conversation, $viewer->id, $messageIds, $seenAt->toIso8601String()))->toOthers();
        }

        return $messageIds;
    }

    private function conversationUnreadCount(ChatConversation $conversation, User $viewer): int
    {
        return $this->unseenMessageQuery($conversation, $viewer)->count();
    }

    private function unseenMessageQuery(ChatConversation $conversation, User $viewer)
    {
        $query = ChatMessage::where('chat_conversation_id', $conversation->id)
            ->whereNull('seen_at');

        if ($viewer->hasRole('Super Admin')) {
            return $query->whereHas('sender', fn ($sender) => $sender->role('Staff'));
        }

        return $query->where('sender_id', '!=', $viewer->id);
    }

    private function canUseChat(?User $user): bool
    {
        return (bool) $user?->hasAnyRole(['Super Admin', 'Staff']);
    }

    private function pusherConfig(): array
    {
        return [
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            'enabled' => filled(config('broadcasting.connections.pusher.key')),
        ];
    }
}

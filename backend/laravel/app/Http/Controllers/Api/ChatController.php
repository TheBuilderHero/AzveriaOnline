<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendChatMessageRequest;
use App\Http\Requests\Api\StoreChatRequest;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Chat::class);
        $userId = (int) $request->user()->id;
        $supportsLastReadTracking = $this->supportsLastReadTracking();

        $query = DB::table('chats as c')
            ->leftJoin('chat_members as cm', function ($j) use ($request) {
                $j->on('c.id', '=', 'cm.chat_id')
                  ->where('cm.user_id', '=', $request->user()->id);
            })
            ->where(function ($q) {
                $q->where(function ($memberQuery) {
                    $memberQuery->whereNotNull('cm.user_id')
                        ->whereNull('cm.deleted_at');
                })->orWhere(function ($globalQuery) {
                    $globalQuery->where('c.type', '=', 'global')
                        ->where(function ($membershipState) {
                            $membershipState->whereNull('cm.deleted_at')
                                ->orWhereNull('cm.user_id');
                        });
                });
            })
            ->select('c.*', 'cm.archived_at as membership_archived_at', 'cm.deleted_at as membership_deleted_at');

        if ($supportsLastReadTracking) {
            $query->addSelect('cm.last_read_message_id');
        } else {
            $query->selectRaw('NULL as last_read_message_id');
        }

        $chats = $query
            ->orderByDesc('c.updated_at')
            ->get()
            ->map(function ($chat) use ($userId, $supportsLastReadTracking) {
                $chat->is_archived = $chat->membership_archived_at !== null;
                $chat->is_deleted = $chat->membership_deleted_at !== null;
                $chat->can_manage_membership = !($chat->type === 'global' && $chat->created_by_user_id !== $userId);
                $lastReadMessageId = $supportsLastReadTracking ? (int) ($chat->last_read_message_id ?? 0) : 0;
                $chat->unread_messages = DB::table('chat_messages')
                    ->where('chat_id', $chat->id)
                    ->when($supportsLastReadTracking, function ($messageQuery) use ($lastReadMessageId) {
                        $messageQuery->where('id', '>', $lastReadMessageId);
                    })
                    ->where('sender_user_id', '!=', $userId)
                    ->count();
                return $chat;
            })
            ->values();

        return response()->json($chats);
    }

    public function store(StoreChatRequest $request)
    {
        $data = $request->validated();
        $memberState = ['archived_at' => null, 'deleted_at' => null];
        if ($this->supportsLastReadTracking()) {
            $memberState['last_read_message_id'] = null;
        }

        $chatId = DB::table('chats')->insertGetId([
            'name' => $data['name'],
            'type' => $data['type'],
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberIds = array_values(array_unique(array_merge($data['member_ids'] ?? [], [$request->user()->id])));
        foreach ($memberIds as $userId) {
            DB::table('chat_members')->updateOrInsert(
                ['chat_id' => $chatId, 'user_id' => $userId],
                $memberState
            );
        }

        return response()->json(['id' => $chatId, 'message' => 'Chat created'], 201);
    }

    public function messages(Request $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);
        $perPage = min((int) $request->query('per_page', 50), 200);

        $messages = DB::table('chat_messages as m')
            ->join('users as u', 'm.sender_user_id', '=', 'u.id')
            ->where('m.chat_id', $chatId)
            ->select('m.id', 'm.message', 'm.created_at', 'u.name as sender_name', 'm.sender_user_id')
            ->orderBy('m.id')
            ->paginate($perPage);

        return response()->json($messages);
    }

    public function markRead(Request $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        if (!$this->supportsLastReadTracking()) {
            return response()->json(['message' => 'Chat read tracking is not available until the latest manual upgrade is applied.'], 409);
        }

        DB::table('chat_members')->updateOrInsert(
            ['chat_id' => $chatId, 'user_id' => $request->user()->id],
            [
                'archived_at' => null,
                'deleted_at' => null,
                'last_read_message_id' => $this->latestMessageId($chatId),
            ]
        );

        return response()->json(['message' => 'Chat marked as read']);
    }

    public function markUnread(Request $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        if (!$this->supportsLastReadTracking()) {
            return response()->json(['message' => 'Chat read tracking is not available until the latest manual upgrade is applied.'], 409);
        }

        DB::table('chat_members')->updateOrInsert(
            ['chat_id' => $chatId, 'user_id' => $request->user()->id],
            [
                'archived_at' => null,
                'deleted_at' => null,
                'last_read_message_id' => 0,
            ]
        );

        return response()->json(['message' => 'Chat marked as unread']);
    }

    public function send(SendChatMessageRequest $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('sendMessage', $chat);
        $data = $request->validated();
        $memberState = ['archived_at' => null, 'deleted_at' => null];

        // Auto-add to global chat membership on first message
        if ($chat->type === 'global') {
            $isMember = DB::table('chat_members')
                ->where('chat_id', $chatId)
                ->where('user_id', $request->user()->id)
                ->exists();
            if (!$isMember) {
                DB::table('chat_members')->insert(array_merge([
                    'chat_id' => $chatId,
                    'user_id' => $request->user()->id,
                ], $memberState));
            } else {
                DB::table('chat_members')
                    ->where('chat_id', $chatId)
                    ->where('user_id', $request->user()->id)
                    ->update($memberState);
            }
        }

        $id = DB::table('chat_messages')->insertGetId([
            'chat_id' => $chatId,
            'sender_user_id' => $request->user()->id,
            'message' => $data['message'],
            'created_at' => now(),
        ]);

        DB::table('chats')->where('id', $chatId)->update(['updated_at' => now()]);

        // If a user deleted this chat previously, a new message should make it visible again.
        DB::table('chat_members')
            ->where('chat_id', $chatId)
            ->update(['deleted_at' => null]);

        return response()->json(['id' => $id, 'message' => 'Sent'], 201);
    }

    public function archive(Request $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        DB::table('chat_members')->updateOrInsert(
            ['chat_id' => $chatId, 'user_id' => $request->user()->id],
            ['archived_at' => now(), 'deleted_at' => null]
        );

        return response()->json(['message' => 'Chat archived']);
    }

    public function unarchive(Request $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        DB::table('chat_members')->updateOrInsert(
            ['chat_id' => $chatId, 'user_id' => $request->user()->id],
            ['archived_at' => null, 'deleted_at' => null]
        );

        return response()->json(['message' => 'Chat restored']);
    }

    public function removeForUser(Request $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        DB::table('chat_members')->updateOrInsert(
            ['chat_id' => $chatId, 'user_id' => $request->user()->id],
            ['archived_at' => null, 'deleted_at' => now()]
        );

        return response()->json(['message' => 'Chat removed from your list']);
    }

    private function latestMessageId(int $chatId): int
    {
        return (int) (DB::table('chat_messages')->where('chat_id', $chatId)->max('id') ?? 0);
    }

    private function supportsLastReadTracking(): bool
    {
        static $supportsLastReadTracking = null;

        if ($supportsLastReadTracking === null) {
            $supportsLastReadTracking = Schema::hasColumn('chat_members', 'last_read_message_id');
        }

        return $supportsLastReadTracking;
    }
}

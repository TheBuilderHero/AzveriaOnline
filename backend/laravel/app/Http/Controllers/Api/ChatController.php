<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendChatMessageRequest;
use App\Http\Requests\Api\StoreChatRequest;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Chat::class);
        $perPage = min((int) $request->query('per_page', 30), 100);

        $chats = DB::table('chats as c')
            ->leftJoin('chat_members as cm', function ($j) use ($request) {
                $j->on('c.id', '=', 'cm.chat_id')
                  ->where('cm.user_id', '=', $request->user()->id);
            })
            ->where(function ($q) {
                $q->whereNotNull('cm.user_id')
                  ->orWhere('c.type', '=', 'global');
            })
            ->select('c.*')
            ->orderByDesc('c.updated_at')
            ->paginate($perPage);

        return response()->json($chats);
    }

    public function store(StoreChatRequest $request)
    {
        $data = $request->validated();

        $chatId = DB::table('chats')->insertGetId([
            'name' => $data['name'],
            'type' => $data['type'],
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberIds = array_values(array_unique(array_merge($data['member_ids'], [$request->user()->id])));
        foreach ($memberIds as $userId) {
            DB::table('chat_members')->insert([
                'chat_id' => $chatId,
                'user_id' => $userId,
            ]);
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

    public function send(SendChatMessageRequest $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('sendMessage', $chat);
        $data = $request->validated();

        // Auto-add to global chat membership on first message
        if ($chat->type === 'global') {
            $isMember = DB::table('chat_members')
                ->where('chat_id', $chatId)
                ->where('user_id', $request->user()->id)
                ->exists();
            if (!$isMember) {
                DB::table('chat_members')->insert([
                    'chat_id' => $chatId,
                    'user_id' => $request->user()->id,
                ]);
            }
        }

        $id = DB::table('chat_messages')->insertGetId([
            'chat_id' => $chatId,
            'sender_user_id' => $request->user()->id,
            'message' => $data['message'],
            'created_at' => now(),
        ]);

        DB::table('chats')->where('id', $chatId)->update(['updated_at' => now()]);

        return response()->json(['id' => $id, 'message' => 'Sent'], 201);
    }
}

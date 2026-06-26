<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendChatMessageRequest;
use App\Http\Requests\Api\StoreChatRequest;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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

    public function exchangeRequests(Request $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        if (!Schema::hasTable('chat_exchange_requests')) {
            return response()->json([]);
        }

        $actorUser = $request->user();
        $isAdmin = (string) ($actorUser->role ?? '') === 'admin';
        $actorNationId = $this->findNationIdByUserId((int) $actorUser->id);
        $includeArchived = $isAdmin && (bool) $request->boolean('include_archived', false);

        $query = DB::table('chat_exchange_requests as cer')
            ->leftJoin('users as sender', 'sender.id', '=', 'cer.sender_user_id')
            ->leftJoin('users as handled', 'handled.id', '=', 'cer.handled_by_user_id')
            ->leftJoin('nations as sn', 'sn.id', '=', 'cer.sender_nation_id')
            ->leftJoin('nations as rn', 'rn.id', '=', 'cer.recipient_nation_id')
            ->where('cer.chat_id', $chatId);

        if (!$includeArchived) {
            $query->whereNull('cer.removed_at')
                ->where('cer.status', 'pending');
        }

        if (!$isAdmin) {
            $query->where(function ($visibilityQuery) use ($actorNationId) {
                $visibilityQuery
                    ->whereNull('cer.recipient_nation_id')
                    ->orWhere('cer.recipient_nation_id', '<=', 0);
                if ((int) $actorNationId > 0) {
                    $visibilityQuery
                        ->orWhere('cer.sender_nation_id', (int) $actorNationId)
                        ->orWhere('cer.recipient_nation_id', (int) $actorNationId);
                }
            });
        }

        $rows = $query
            ->select(
                'cer.*',
                'sender.name as sender_user_name',
                'handled.name as handled_by_user_name',
                'sn.name as sender_nation_name',
                'rn.name as recipient_nation_name'
            )
            ->orderByDesc('cer.created_at')
            ->limit(300)
            ->get()
            ->map(function ($row) {
                $row->offer = json_decode((string) ($row->offer_json ?? '[]'), true) ?: [];
                $row->receive = json_decode((string) ($row->receive_json ?? '[]'), true) ?: [];
                return $row;
            })
            ->values();

        return response()->json($rows);
    }

    public function storeExchangeRequest(Request $request, int $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('sendMessage', $chat);

        if (!Schema::hasTable('chat_exchange_requests')) {
            return response()->json(['message' => 'Exchange requests are not available until the latest manual upgrade is applied.'], 409);
        }

        $data = $request->validate([
            'mode' => ['required', 'in:exchange,direct_exchange'],
            'message' => ['nullable', 'string', 'max:5000'],
            'offer' => ['required', 'array'],
            'receive' => ['required', 'array'],
            'recipient_nation_id' => ['nullable', 'integer', 'exists:nations,id'],
        ]);

        $actorUser = $request->user();
        $senderNationId = $this->findNationIdByUserId((int) $actorUser->id);
        if (!$senderNationId) {
            return response()->json(['message' => 'No nation is associated with this account.'], 422);
        }

        $offer = $this->normalizeExchangeResources((array) ($data['offer'] ?? []));
        $receive = $this->normalizeExchangeResources((array) ($data['receive'] ?? []));
        if (empty($offer) || empty($receive)) {
            return response()->json(['message' => 'Offer and receive resources are both required.'], 422);
        }

        $recipientNationId = null;
        if (($data['mode'] ?? '') === 'direct_exchange') {
            $recipientNationId = (int) ($data['recipient_nation_id'] ?? 0);
            if ($recipientNationId <= 0) {
                return response()->json(['message' => 'A recipient nation is required for direct exchange.'], 422);
            }
            if ($recipientNationId === (int) $senderNationId) {
                return response()->json(['message' => 'You cannot send a direct exchange to your own nation.'], 422);
            }
        }

        $requestId = DB::table('chat_exchange_requests')->insertGetId([
            'chat_id' => $chatId,
            'sender_user_id' => (int) $actorUser->id,
            'sender_nation_id' => (int) $senderNationId,
            'recipient_nation_id' => $recipientNationId,
            'offer_json' => json_encode(array_values($offer)),
            'receive_json' => json_encode(array_values($receive)),
            'message' => trim((string) ($data['message'] ?? '')) ?: null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chats')->where('id', $chatId)->update(['updated_at' => now()]);

        return response()->json(['id' => $requestId, 'message' => 'Exchange request created'], 201);
    }

    public function acceptExchangeRequest(Request $request, int $chatId, int $requestId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        if (!Schema::hasTable('chat_exchange_requests')) {
            return response()->json(['message' => 'Exchange requests are not available until the latest manual upgrade is applied.'], 409);
        }

        return DB::transaction(function () use ($request, $chatId, $requestId) {
            $row = DB::table('chat_exchange_requests')
                ->where('id', $requestId)
                ->where('chat_id', $chatId)
                ->lockForUpdate()
                ->first();

            if (!$row || $row->removed_at !== null) {
                return response()->json(['message' => 'Exchange request not found.'], 404);
            }
            if ((string) ($row->status ?? '') !== 'pending') {
                return response()->json(['message' => 'Exchange request is no longer pending.'], 422);
            }

            $actorUser = $request->user();
            $actorNationId = $this->findNationIdByUserId((int) $actorUser->id);
            $isAdmin = (string) ($actorUser->role ?? '') === 'admin';

            if (!$isAdmin) {
                if (!$actorNationId) {
                    return response()->json(['message' => 'No nation is associated with this account.'], 422);
                }

                if ((int) ($row->recipient_nation_id ?? 0) > 0) {
                    if ((int) $actorNationId !== (int) $row->recipient_nation_id) {
                        return response()->json(['message' => 'Only the direct recipient nation can accept this request.'], 403);
                    }
                } elseif ((int) $actorNationId === (int) $row->sender_nation_id) {
                    return response()->json(['message' => 'The sender nation cannot accept its own request.'], 422);
                }
            }

            $senderNationId = (int) $row->sender_nation_id;
            $counterpartyNationId = (int) ($row->recipient_nation_id ?: ($actorNationId ?? 0));
            if ($counterpartyNationId <= 0 || $counterpartyNationId === $senderNationId) {
                return response()->json(['message' => 'A valid counterparty nation is required to complete the exchange.'], 422);
            }

            $offer = $this->normalizeExchangeResources((array) (json_decode((string) ($row->offer_json ?? '[]'), true) ?: []));
            $receive = $this->normalizeExchangeResources((array) (json_decode((string) ($row->receive_json ?? '[]'), true) ?: []));
            if (empty($offer) || empty($receive)) {
                return response()->json(['message' => 'Exchange request data is invalid.'], 422);
            }

            $senderResourceRow = DB::table('nation_resources')->where('nation_id', $senderNationId)->lockForUpdate()->first();
            $receiverResourceRow = DB::table('nation_resources')->where('nation_id', $counterpartyNationId)->lockForUpdate()->first();
            if (!$senderResourceRow || !$receiverResourceRow) {
                return response()->json(['message' => 'One of the nations in this exchange has no resource row.'], 422);
            }

            $senderState = $this->readNationResourceState((object) $senderResourceRow);
            $receiverState = $this->readNationResourceState((object) $receiverResourceRow);

            $senderDelta = [];
            $receiverDelta = [];
            foreach ($offer as $entry) {
                $key = $entry['type'] . ':' . $entry['name'];
                $amount = (float) $entry['amount'];
                $senderDelta[$key] = ($senderDelta[$key] ?? 0.0) - $amount;
                $receiverDelta[$key] = ($receiverDelta[$key] ?? 0.0) + $amount;
            }
            foreach ($receive as $entry) {
                $key = $entry['type'] . ':' . $entry['name'];
                $amount = (float) $entry['amount'];
                $senderDelta[$key] = ($senderDelta[$key] ?? 0.0) + $amount;
                $receiverDelta[$key] = ($receiverDelta[$key] ?? 0.0) - $amount;
            }

            $this->applyResourceDeltaToState($senderState, $senderDelta);
            $this->applyResourceDeltaToState($receiverState, $receiverDelta);
            $this->persistNationResourceState($senderNationId, $senderState);
            $this->persistNationResourceState($counterpartyNationId, $receiverState);

            DB::table('chat_exchange_requests')
                ->where('id', $requestId)
                ->update([
                    'status' => 'accepted',
                    'handled_by_user_id' => (int) $actorUser->id,
                    'handled_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->createAdminNotification(
                'trade_exchange_accepted',
                'Exchange Request Accepted',
                'An exchange request was accepted and resources were transferred.',
                [
                    'exchange_request_id' => $requestId,
                    'chat_id' => $chatId,
                    'sender_nation_id' => $senderNationId,
                    'counterparty_nation_id' => $counterpartyNationId,
                    'actor_user_id' => (int) $actorUser->id,
                    'offer' => $offer,
                    'receive' => $receive,
                ]
            );

            DB::table('chats')->where('id', $chatId)->update(['updated_at' => now()]);

            return response()->json(['message' => 'Exchange accepted and resources transferred.']);
        });
    }

    public function refuseExchangeRequest(Request $request, int $chatId, int $requestId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        if (!Schema::hasTable('chat_exchange_requests')) {
            return response()->json(['message' => 'Exchange requests are not available until the latest manual upgrade is applied.'], 409);
        }

        $row = DB::table('chat_exchange_requests')
            ->where('id', $requestId)
            ->where('chat_id', $chatId)
            ->first();

        if (!$row || $row->removed_at !== null) {
            return response()->json(['message' => 'Exchange request not found.'], 404);
        }
        if ((string) ($row->status ?? '') !== 'pending') {
            return response()->json(['message' => 'Exchange request is no longer pending.'], 422);
        }

        $actorUser = $request->user();
        $actorNationId = $this->findNationIdByUserId((int) $actorUser->id);
        $isAdmin = (string) ($actorUser->role ?? '') === 'admin';

        if (!$isAdmin) {
            if (!$actorNationId) {
                return response()->json(['message' => 'No nation is associated with this account.'], 422);
            }
            if ((int) ($row->recipient_nation_id ?? 0) > 0) {
                if ((int) $actorNationId !== (int) $row->recipient_nation_id) {
                    return response()->json(['message' => 'Only the direct recipient nation can refuse this request.'], 403);
                }
            } elseif ((int) $actorNationId === (int) $row->sender_nation_id) {
                return response()->json(['message' => 'The sender nation cannot refuse its own request.'], 422);
            }
        }

        DB::table('chat_exchange_requests')
            ->where('id', $requestId)
            ->update([
                'status' => 'refused',
                'handled_by_user_id' => (int) $actorUser->id,
                'handled_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('chats')->where('id', $chatId)->update(['updated_at' => now()]);

        return response()->json(['message' => 'Exchange request refused.']);
    }

    public function deleteExchangeRequest(Request $request, int $chatId, int $requestId)
    {
        $chat = Chat::findOrFail($chatId);
        $this->authorize('view', $chat);

        if (!Schema::hasTable('chat_exchange_requests')) {
            return response()->json(['message' => 'Exchange requests are not available until the latest manual upgrade is applied.'], 409);
        }

        $row = DB::table('chat_exchange_requests')
            ->where('id', $requestId)
            ->where('chat_id', $chatId)
            ->first();
        if (!$row || $row->removed_at !== null) {
            return response()->json(['message' => 'Exchange request not found.'], 404);
        }

        $actor = $request->user();
        $isAdmin = (string) ($actor->role ?? '') === 'admin';
        if (!$isAdmin && (int) $row->sender_user_id !== (int) $actor->id) {
            return response()->json(['message' => 'Only admins or the sender can remove this exchange request.'], 403);
        }

        DB::table('chat_exchange_requests')
            ->where('id', $requestId)
            ->update([
                'removed_at' => now(),
                'removed_by_user_id' => (int) $actor->id,
                'updated_at' => now(),
            ]);

        DB::table('chats')->where('id', $chatId)->update(['updated_at' => now()]);

        return response()->json(['message' => 'Exchange request removed.']);
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

    private function supportsExchangeRequests(): bool
    {
        static $supportsExchangeRequests = null;

        if ($supportsExchangeRequests === null) {
            $supportsExchangeRequests = Schema::hasTable('chat_exchange_requests');
        }

        return $supportsExchangeRequests;
    }

    private function findNationIdByUserId(int $userId): ?int
    {
        $id = DB::table('nations')->where('owner_user_id', $userId)->value('id');
        return $id !== null ? (int) $id : null;
    }

    private function normalizeExchangeResources(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = (($row['type'] ?? '') === 'advanced') ? 'advanced' : 'base';
            $name = trim((string) ($row['name'] ?? ''));
            $amount = (float) ($row['amount'] ?? 0);
            if ($name === '' || $amount <= 0) {
                continue;
            }
            $key = $type . ':' . $name;
            if (!isset($out[$key])) {
                $out[$key] = ['type' => $type, 'name' => $name, 'amount' => 0.0];
            }
            $out[$key]['amount'] += $amount;
        }

        return $out;
    }

    private function readNationResourceState(object $row): array
    {
        $excluded = ['nation_id', 'extra_json', 'updated_at', 'created_at'];
        $extra = json_decode((string) ($row->extra_json ?? '{}'), true);
        $extra = is_array($extra) ? $extra : [];
        $baseExtra = is_array($extra['base'] ?? null) ? $extra['base'] : [];
        $advanced = is_array($extra['advanced'] ?? null) ? $extra['advanced'] : (is_array($extra['refined'] ?? null) ? $extra['refined'] : []);
        $currencies = is_array($extra['currencies'] ?? null) ? $extra['currencies'] : [];

        $core = [];
        foreach (get_object_vars($row) as $key => $value) {
            $name = (string) $key;
            if (in_array($name, $excluded, true)) {
                continue;
            }
            if (!is_numeric($value)) {
                continue;
            }
            $core[$name] = (float) $value;
        }

        return [
            'core' => $core,
            'extra_base' => array_map(static fn ($v) => (float) $v, $baseExtra),
            'advanced' => array_map(static fn ($v) => (float) $v, $advanced),
            'currencies' => array_map(static fn ($v) => (float) $v, $currencies),
        ];
    }

    private function applyResourceDeltaToState(array &$state, array $delta): void
    {
        foreach ($delta as $rawKey => $amount) {
            $key = (string) $rawKey;
            $value = (float) $amount;
            if ($value === 0.0) {
                continue;
            }

            if (str_starts_with($key, 'base:')) {
                $name = substr($key, 5);
                if ($name === '') {
                    continue;
                }
                if (array_key_exists($name, $state['core'])) {
                    $next = (float) ($state['core'][$name] ?? 0) + $value;
                    if ($next < 0) {
                        throw ValidationException::withMessages([
                            'resources' => ['Insufficient base resource: ' . $name],
                        ]);
                    }
                    $state['core'][$name] = $next;
                } else {
                    $next = (float) ($state['extra_base'][$name] ?? 0) + $value;
                    if ($next < 0) {
                        throw ValidationException::withMessages([
                            'resources' => ['Insufficient base resource: ' . $name],
                        ]);
                    }
                    $state['extra_base'][$name] = $next;
                }
                continue;
            }

            if (str_starts_with($key, 'advanced:')) {
                $name = substr($key, 9);
                if ($name === '') {
                    continue;
                }
                $next = (float) ($state['advanced'][$name] ?? 0) + $value;
                if ($next < 0) {
                    throw ValidationException::withMessages([
                        'resources' => ['Insufficient advanced resource: ' . $name],
                    ]);
                }
                $state['advanced'][$name] = $next;
                continue;
            }

            if (str_starts_with($key, 'currencies:')) {
                $name = substr($key, 11);
                if ($name === '') {
                    continue;
                }
                $next = (float) ($state['currencies'][$name] ?? 0) + $value;
                if ($next < 0) {
                    throw ValidationException::withMessages([
                        'resources' => ['Insufficient currency: ' . $name],
                    ]);
                }
                $state['currencies'][$name] = $next;
            }
        }
    }

    private function persistNationResourceState(int $nationId, array $state): void
    {
        $extra = [
            'base' => array_filter($state['extra_base'] ?? [], static fn ($v) => (float) $v !== 0.0),
            'advanced' => array_filter($state['advanced'] ?? [], static fn ($v) => (float) $v !== 0.0),
            'currencies' => array_filter($state['currencies'] ?? [], static fn ($v) => (float) $v !== 0.0),
        ];

        $update = [
            'extra_json' => json_encode($extra),
            'updated_at' => now(),
        ];
        foreach (($state['core'] ?? []) as $key => $value) {
            $update[(string) $key] = (float) $value;
        }

        DB::table('nation_resources')->where('nation_id', $nationId)->update($update);
    }

    private function createAdminNotification(string $type, string $title, string $body, array $meta = []): void
    {
        if (!Schema::hasTable('admin_notifications')) {
            return;
        }

        DB::table('admin_notifications')->insert([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'meta_json' => json_encode($meta),
            'is_read' => 0,
            'created_at' => now(),
        ]);
    }
}

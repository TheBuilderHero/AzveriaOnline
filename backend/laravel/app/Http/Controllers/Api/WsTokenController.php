<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\WsToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WsTokenController extends Controller
{
    public function issue(Request $request)
    {
        $userId = (int) $request->user()->id;

        $memberChatIds = DB::table('chat_members')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->pluck('chat_id');

        $globalChatIds = DB::table('chats as c')
            ->leftJoin('chat_members as cm', function ($join) use ($userId) {
                $join->on('c.id', '=', 'cm.chat_id')
                    ->where('cm.user_id', '=', $userId);
            })
            ->where('c.type', 'global')
            ->where(function ($query) {
                $query->whereNull('cm.deleted_at')
                    ->orWhereNull('cm.user_id');
            })
            ->pluck('c.id');

        $chatIds = $memberChatIds
            ->merge($globalChatIds)
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $claims = [
            'sub' => (int) $request->user()->id,
            'role' => (string) $request->user()->role,
            'chat_ids' => $chatIds,
            'exp' => time() + (60 * 60),
        ];

        $secret = (string) env('JWT_SHARED_SECRET', 'change_me');
        $token = WsToken::issue($claims, $secret);

        return response()->json([
            'token' => $token,
            'expires_in' => 3600,
        ]);
    }
}

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
        $chatIds = DB::table('chat_members')
            ->where('user_id', $request->user()->id)
            ->pluck('chat_id')
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class MetaController extends Controller
{
    public function about()
    {
        return response()->json([
            'website_version' => '0.1.0',
            'game_version' => 'Azveria Ruleset v1',
            'stack' => 'Laravel + MySQL + Ratchet',
            'websocket_scope' => ['announcements', 'chat'],
        ]);
    }
}

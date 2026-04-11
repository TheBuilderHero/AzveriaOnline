<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class MetaController extends Controller
{
    public function about()
    {
        return response()->json([
            'website_version' => '1.0.0.0 Beta',
            'game_version' => 'Azveria Ruleset v1',
            'admin' => 'Issac',
            'developer' => 'Dakota',
            'websocket_scope' => ['announcements', 'chat'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class MetaController extends Controller
{
    public function about()
    {
        return response()->json(array_merge(config('azveria.about', []), [
            'websocket_scope' => ['announcements', 'chat'],
        ]));
    }
}

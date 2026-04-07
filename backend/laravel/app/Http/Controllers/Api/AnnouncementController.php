<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Announcement::class);

        $items = DB::table('announcements as a')
            ->join('users as u', 'a.author_user_id', '=', 'u.id')
            ->select('a.id', 'a.body', 'a.created_at', 'u.name as author_name')
            ->orderByDesc('a.id')
            ->paginate(50);

        return response()->json($items);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $this->authorize('create', Announcement::class);
        $data = $request->validated();

        $id = DB::table('announcements')->insertGetId([
            'author_user_id' => $request->user()->id,
            'body' => $data['body'],
            'created_at' => now(),
        ]);

        return response()->json([
            'id' => $id,
            'message' => 'Announcement created',
        ], 201);
    }
}

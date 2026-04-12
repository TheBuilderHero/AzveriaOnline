<?php

namespace App\Policies;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'player'], true);
    }

    public function view(User $user, Chat $chat): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $membership = DB::table('chat_members')
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->first();

        if ($chat->type === 'global') {
            return $membership?->deleted_at === null;
        }

        return $membership !== null && $membership->deleted_at === null;
    }

    public function sendMessage(User $user, Chat $chat): bool
    {
        if ($chat->type === 'global') {
            return true;
        }
        return $this->view($user, $chat);
    }

    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}

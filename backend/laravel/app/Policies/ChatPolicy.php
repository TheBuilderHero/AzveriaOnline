<?php

namespace App\Policies;

use App\Models\Chat;
use App\Models\User;

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

        if ($chat->type === 'global') {
            return true;
        }

        return $chat->members()->where('users.id', $user->id)->exists();
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

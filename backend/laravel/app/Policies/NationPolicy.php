<?php

namespace App\Policies;

use App\Models\Nation;
use App\Models\User;

class NationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'player'], true);
    }

    public function view(User $user, Nation $nation): bool
    {
        return in_array($user->role, ['admin', 'player'], true);
    }

    public function update(User $user, Nation $nation): bool
    {
        return $user->role === 'admin' || (int) $nation->owner_user_id === (int) $user->id;
    }

    public function adminManage(User $user): bool
    {
        return $user->role === 'admin';
    }
}

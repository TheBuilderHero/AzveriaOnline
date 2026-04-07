<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'player'], true);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }
}

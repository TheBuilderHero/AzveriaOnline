<?php

namespace App\Policies;

use App\Models\ShopItem;
use App\Models\User;

class ShopItemPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'player'], true);
    }

    public function buy(User $user, ShopItem $item): bool
    {
        return $user->role === 'player' && $item->is_active;
    }

    public function update(User $user, ShopItem $item): bool
    {
        return $user->role === 'admin';
    }
}

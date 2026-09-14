<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserCatalogItem;

class UserCatalogItemPolicy
{
    /**
     * Creating a personal item is reserved to regular owners: the admin role
     * never manages another user's private fork (R1).
     */
    public function create(User $user): bool
    {
        return $user->role !== 'admin';
    }

    public function view(User $user, UserCatalogItem $item): bool
    {
        return $user->id === $item->user_id;
    }

    public function update(User $user, UserCatalogItem $item): bool
    {
        return $user->id === $item->user_id;
    }

    public function delete(User $user, UserCatalogItem $item): bool
    {
        return $user->id === $item->user_id;
    }
}

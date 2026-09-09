<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserCatalogItem;

class UserCatalogItemPolicy
{
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

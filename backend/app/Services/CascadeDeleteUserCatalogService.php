<?php

namespace App\Services;

use App\Models\UserCatalogItem;
use App\Support\UserCatalogSubtree;
use Illuminate\Support\Facades\DB;

/**
 * Recursive owner-scoped cascade soft-delete of a fork subtree (R4, D-6/D9).
 *
 * The actor's live rows are read once to build a parent map, the subtree is
 * collected in memory, and ONE bulk UPDATE stamps `deleted_at` on every
 * collected id. Every write is scoped by `user_id`, so base rows (another
 * table) and other users' forks are never touched. Soft-deleting keeps the
 * rows outside the amended-D5 partial unique index, so re-forking the same
 * base afterwards is legal (S4.3). The whole operation is one transaction.
 */
class CascadeDeleteUserCatalogService
{
    /**
     * Soft-delete the item and all of its fork descendants.
     *
     * @return int number of soft-deleted rows
     */
    public function delete(UserCatalogItem $root): int
    {
        return DB::transaction(function () use ($root): int {
            $ids = UserCatalogSubtree::ids($root, $root->user_id);

            return UserCatalogItem::query()
                ->where('user_id', $root->user_id)
                ->whereIn('id', $ids)
                ->delete();
        });
    }
}

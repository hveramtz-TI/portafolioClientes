<?php

namespace App\Support;

use App\Models\UserCatalogItem;

/**
 * Owner-scoped fork-subtree traversal shared by the cascade delete service
 * and the move/attach validation (R3/R4, D-6). One scoped query builds the
 * parent map; the subtree is collected in memory, so the cost is O(depth) in
 * queries and O(nodes) in memory.
 */
final class UserCatalogSubtree
{
    /**
     * The ids of the root plus every live descendant, in breadth-first order.
     * When `$userId` is given (recommended), the traversal only ever sees that
     * owner's rows, so it can never reach another user's forks.
     *
     * @return array<int, string>
     */
    public static function ids(UserCatalogItem $root, ?string $userId = null): array
    {
        $query = UserCatalogItem::query();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $byParent = [];

        foreach ($query->get(['id', 'parent_fork_id']) as $row) {
            if ($row->parent_fork_id !== null) {
                $byParent[$row->parent_fork_id][] = $row->id;
            }
        }

        $ids = [$root->id];
        $queue = [$root->id];

        while ($queue !== []) {
            $current = array_pop($queue);

            foreach ($byParent[$current] ?? [] as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    /**
     * Whether `$candidateId` is the root itself or one of its live descendants.
     */
    public static function contains(UserCatalogItem $root, string $candidateId, ?string $userId = null): bool
    {
        return in_array($candidateId, self::ids($root, $userId), true);
    }
}

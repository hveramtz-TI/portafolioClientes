<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCatalogItem;
use Illuminate\Support\Collection;

/**
 * Builds the caller's complete non-deleted fork hierarchy (R6, D-4/D-5).
 *
 * The whole owner scope is loaded in ONE query plus the bounded morphTo base
 * eager-load (at most one query per base type), and the upward parent chain is
 * wired in memory: the resolver's recursive effective status reads
 * `parentFork`, and `loadMissing` is a no-op on a graph the caller already
 * loaded, so resolving, filtering and ordering the tree issues ZERO extra
 * queries — the count is constant regardless of node count (S6.4).
 *
 * Status/origin filters run in memory on the RESOLVED payload, never on the
 * own column: `status` reads the effective status (R6 recursive AND) and
 * `origin` reads the per-field origin (R5). Filtering a node out promotes its
 * surviving descendants to the nearest surviving level, so relative nesting
 * among survivors is preserved. Siblings are ordered by `sort_order`.
 */
class UserCatalogTreeService
{
    public function __construct(private readonly CatalogResolver $resolver) {}

    /**
     * @return array<int, array<string, mixed>> root nodes, nested downward
     */
    public function tree(User $user, ?string $status = null, ?string $origin = null): array
    {
        $items = UserCatalogItem::query()
            ->where('user_id', $user->id)
            ->with('base')
            ->get();

        $byId = $items->keyBy('id');

        // Wire the parent relation from the same in-memory set. A missing
        // parent (soft-deleted or out of scope) resolves to null, which the
        // resolver fail-safes to 'desactivado' (J2).
        foreach ($items as $item) {
            $item->setRelation(
                'parentFork',
                $item->parent_fork_id !== null ? ($byId[$item->parent_fork_id] ?? null) : null,
            );
        }

        $children = [];

        foreach ($items as $item) {
            if ($item->parent_fork_id !== null) {
                $children[$item->parent_fork_id][] = $item;
            }
        }

        $roots = $items->filter(fn (UserCatalogItem $item): bool => $item->parent_fork_id === null);

        return $this->build($roots, $children, $status, $origin);
    }

    /**
     * @param  Collection<int, UserCatalogItem>  $nodes
     * @param  array<string, array<int, UserCatalogItem>>  $children
     * @return array<int, array<string, mixed>>
     */
    private function build(Collection $nodes, array $children, ?string $status, ?string $origin): array
    {
        $result = [];

        foreach ($this->ordered($nodes) as $node) {
            $resolved = $this->resolver->resolve($node);
            $resolved['children'] = $this->build(
                collect($children[$node->id] ?? []),
                $children,
                $status,
                $origin,
            );

            if ($this->passes($resolved, $status, $origin)) {
                $result[] = $resolved;

                continue;
            }

            // Promote survivors when an intermediate node is filtered out.
            foreach ($resolved['children'] as $child) {
                $result[] = $child;
            }
        }

        return $result;
    }

    /**
     * @param  Collection<int, UserCatalogItem>  $nodes
     * @return Collection<int, UserCatalogItem>
     */
    private function ordered(Collection $nodes): Collection
    {
        return $nodes
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function passes(array $node, ?string $status, ?string $origin): bool
    {
        $statusOk = $status === null || $status === 'all' || $node['status'] === $status;
        $originOk = $origin === null || $node['origin'] === $origin;

        return $statusOk && $originOk;
    }
}

<?php

namespace App\Services;

use App\Models\UserCatalogItem;

/**
 * Resolves the dynamic effective view of a user catalog item (R5, R6).
 *
 * Per-field inheritance reads the live base row on every resolve — base edits
 * propagate without any re-sync (R5). The effective status ANDs the item's own
 * status, its base status, and every ancestor fork's EFFECTIVE status: an
 * ancestor counts as desactivado when its own status, its own base, or its own
 * ancestors are — the full-chain AND of `docs/flujos/rubro-categoria-servicio-
 * lifecycle.md` ("Rubro base desactivado bloquea el árbol"), S6.4. Personal
 * items (base_id null) resolve exclusively from their overrides and are always
 * 'personal' origin.
 *
 * The resolution graph (base + parentFork chain + each ancestor's base) is
 * eager-loaded through {@see self::CHAIN_DEPTH} levels via loadMissing, which
 * is idempotent: a caller that already preloaded the graph with `with(['base',
 * 'parentFork.base', 'parentFork.parentFork.base',
 * 'parentFork.parentFork.parentFork'])` pays zero extra queries per item
 * (D-4, no N+1, no caching). The recursion never adds queries on a loaded
 * chain — loadMissing is a no-op there — and terminates at a null parent
 * (chains are strictly shallower by construction, no cycles).
 */
class CatalogResolver
{
    /**
     * Resolved display fields per item type. Each type exposes exactly the
     * columns its base model actually has — no invented columns (R5).
     *
     * @var array<string, array<int, string>>
     */
    private const FIELDS = [
        'rubro' => ['name', 'description'],
        'categoria' => ['name', 'description'],
        'service' => ['title', 'description', 'value', 'tags'],
    ];

    /**
     * Fork levels eager-loaded above the item. The current hierarchy is at
     * most two forks deep (service → categoria → rubro); the extra level keeps
     * the load list uniform and costs nothing because Eloquent skips eager
     * loads whose foreign keys are null.
     */
    private const CHAIN_DEPTH = 3;

    /**
     * Resolve the effective view of the item: id, base_id, the per-type
     * display fields, the effective status (R6), the field origin (R5) and
     * the list of overridden fields.
     *
     * @return array<string, mixed>
     */
    public function resolve(UserCatalogItem $item): array
    {
        $this->loadResolutionGraph($item);

        $fields = self::FIELDS[$item->item_type] ?? [];
        $overrides = $item->overrides ?? [];
        $base = $item->base_id !== null ? $item->base : null;

        $resolved = [
            'id' => $item->id,
            'base_id' => $item->base_id,
        ];

        foreach ($fields as $field) {
            $resolved[$field] = array_key_exists($field, $overrides)
                ? $overrides[$field]
                : $base?->getAttribute($field);
        }

        $resolved['status'] = $this->effectiveStatus($item);
        $resolved['origin'] = $this->origin($item);
        $resolved['overridden_fields'] = array_keys($overrides);

        return $resolved;
    }

    /**
     * The origin of the resolved field values (R5): 'personal' for items
     * without a base, 'override' when any override exists, 'base' otherwise.
     */
    public function origin(UserCatalogItem $item): string
    {
        if ($item->base_id === null) {
            return 'personal';
        }

        return ($item->overrides ?? []) === [] ? 'base' : 'override';
    }

    /**
     * Effective status (R6, S6.4): 'desactivado' when the item, its base, or
     * any ancestor fork — by the ancestor's own EFFECTIVE status — is
     * desactivado; 'activo' only when the whole chain is active. No override
     * can win against a deactivated ancestor. Recursive on the parent fork:
     * the parent's effective status already folds in its own base and its own
     * ancestors, so a deactivated rubro base cascades to service forks two
     * levels below. The recursion terminates at a null parent (the data ends
     * the chain) and never triggers lazy loads on a preloaded graph.
     *
     * Fail-safe (S6.4 full-chain AND): an unresolvable link is NOT provably
     * active. A non-null base_id whose base() resolves null (base trashed or
     * missing) and a non-null parent_fork_id whose parent fork resolves null
     * (ancestor deleted) both return 'desactivado'. No extra queries: both
     * relations are already loaded by loadResolutionGraph (and by the D-4
     * preload contract), and Eloquent caches the null resolution, so a loaded
     * graph still resolves in zero queries (withTrashed() is never needed).
     */
    public function effectiveStatus(UserCatalogItem $item): string
    {
        $this->loadResolutionGraph($item);

        if ($item->status === 'desactivado') {
            return 'desactivado';
        }

        if ($item->base_id !== null && $item->base === null) {
            return 'desactivado';
        }

        if ($item->base_id !== null && $item->base?->status === 'desactivado') {
            return 'desactivado';
        }

        if ($item->parent_fork_id !== null && $item->parentFork === null) {
            return 'desactivado';
        }

        $parent = $item->parentFork;

        if ($parent !== null && $this->effectiveStatus($parent) === 'desactivado') {
            return 'desactivado';
        }

        return 'activo';
    }

    /**
     * Eager-load the resolution graph for the item: its base (morphTo), the
     * parentFork chain through {@see self::CHAIN_DEPTH} levels, and each
     * ancestor fork's own base (needed by the recursive effective status).
     * loadMissing is a no-op for relations the caller already loaded, so
     * preloaded collections resolve without issuing any query (D-4).
     */
    private function loadResolutionGraph(UserCatalogItem $item): void
    {
        $item->loadMissing([
            'base',
            'parentFork.base',
            'parentFork.parentFork.base',
            'parentFork.parentFork.parentFork',
        ]);
    }
}

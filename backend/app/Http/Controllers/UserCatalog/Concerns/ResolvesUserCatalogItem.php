<?php

namespace App\Http\Controllers\UserCatalog\Concerns;

use App\Models\UserCatalogItem;
use App\Services\CatalogResolver;
use App\Support\UserCatalogType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Shared behavior for the thin user-catalog controllers: resolve the plural
 * route type, enforce the {type} ↔ item_type coherence as a 422 (D-1) and
 * delegate authorization to the owner-only policy (403).
 */
trait ResolvesUserCatalogItem
{
    /**
     * The singular item type derived from the {type} route segment.
     */
    protected function routeType(): string
    {
        return UserCatalogType::fromRoute(request()->route('type')) ?? '';
    }

    /**
     * Enforce route-type coherence and the requested ability on a bound item.
     */
    protected function authorizeType(UserCatalogItem $item, string $routeType, string $ability): UserCatalogItem
    {
        if ($item->item_type !== $routeType) {
            throw ValidationException::withMessages([
                'type' => 'The route type does not match the item type.',
            ]);
        }

        Gate::authorize($ability, $item);

        return $item;
    }

    /**
     * The resolved effective view of an item (R5/R6 + structural keys).
     *
     * @return array<string, mixed>
     */
    protected function resolvedView(UserCatalogItem $item): array
    {
        return app(CatalogResolver::class)->resolve($item);
    }
}

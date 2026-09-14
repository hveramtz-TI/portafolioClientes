<?php

namespace App\Http\Controllers\UserCatalog;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserCatalog\Concerns\ResolvesUserCatalogItem;
use App\Http\Requests\StoreUserCatalogItemRequest;
use App\Models\UserCatalogItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Create a personal (base_id null) item owned by the caller (R2). Base items
 * are forked exclusively through the cascade endpoint, so store always writes
 * a personal row with its own `activo` status (D12).
 */
class StoreController extends Controller
{
    use ResolvesUserCatalogItem;

    public function store(StoreUserCatalogItemRequest $request): JsonResponse
    {
        Gate::authorize('create', UserCatalogItem::class);

        $validated = $request->validated();

        $item = UserCatalogItem::create([
            'user_id' => $request->user()->id,
            'item_type' => $this->routeType(),
            'base_id' => null,
            'parent_fork_id' => $validated['parent_fork_id'] ?? null,
            'overrides' => $request->personalOverrides(),
            'status' => 'activo',
            'sort_order' => 0,
        ]);

        return response()->json($this->resolvedView($item), 201);
    }
}

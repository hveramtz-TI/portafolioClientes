<?php

namespace App\Http\Controllers\UserCatalog;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserCatalog\Concerns\ResolvesUserCatalogItem;
use App\Http\Requests\UpdateUserCatalogItemRequest;
use App\Models\UserCatalogItem;
use Illuminate\Http\JsonResponse;

/**
 * Apply merged per-field overrides to a fork (R2, R5): a present field sets
 * the override, an explicit null removes it and an omitted field stays
 * untouched. `parent_fork_id` is applied only when the request accepts it.
 */
class UpdateController extends Controller
{
    use ResolvesUserCatalogItem;

    public function update(UpdateUserCatalogItemRequest $request, string $type, UserCatalogItem $fork): JsonResponse
    {
        $item = $this->authorizeType($fork, $this->routeType(), 'update');
        $validated = $request->validated();

        $data = ['overrides' => $request->mergedOverrides($item)];

        if (array_key_exists('parent_fork_id', $validated)) {
            $data['parent_fork_id'] = $validated['parent_fork_id'];
        }

        $item->update($data);

        return response()->json($this->resolvedView($item->refresh()));
    }
}

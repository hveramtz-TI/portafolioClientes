<?php

namespace App\Http\Controllers\UserCatalog;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserCatalog\Concerns\ResolvesUserCatalogItem;
use App\Models\UserCatalogItem;
use Illuminate\Http\JsonResponse;

/**
 * Show the resolved effective view of a fork: inherited/overridden values,
 * effective status, origin and the structural keys (R2, R5, D-4).
 */
class ShowController extends Controller
{
    use ResolvesUserCatalogItem;

    public function show(string $type, UserCatalogItem $fork): JsonResponse
    {
        $item = $this->authorizeType($fork, $this->routeType(), 'view');

        return response()->json($this->resolvedView($item));
    }
}

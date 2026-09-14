<?php

namespace App\Http\Controllers\UserCatalog;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserCatalog\Concerns\ResolvesUserCatalogItem;
use App\Models\UserCatalogItem;
use Illuminate\Http\JsonResponse;

/**
 * Dedicated status surface (R3, D3/D7): deactivate/reactivate write ONLY the
 * selected item's own `status` column — never `deleted_at`, never `overrides`.
 * Reactivation touches exactly one row; descendants keep their own status.
 */
class StatusController extends Controller
{
    use ResolvesUserCatalogItem;

    public function deactivate(string $type, UserCatalogItem $fork): JsonResponse
    {
        return $this->setStatus($fork, 'desactivado');
    }

    public function reactivate(string $type, UserCatalogItem $fork): JsonResponse
    {
        return $this->setStatus($fork, 'activo');
    }

    private function setStatus(UserCatalogItem $fork, string $status): JsonResponse
    {
        $item = $this->authorizeType($fork, $this->routeType(), 'update');

        $item->update(['status' => $status]);

        return response()->json($this->resolvedView($item->refresh()));
    }
}

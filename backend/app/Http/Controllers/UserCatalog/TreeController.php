<?php

namespace App\Http\Controllers\UserCatalog;

use App\Http\Controllers\Controller;
use App\Services\UserCatalogTreeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The caller's nested fork tree (R6). Filters are validated here and applied
 * in memory on the RESOLVED payload by the tree service: `status` over the
 * effective status and `origin` over the resolved origin. The list is scoped
 * by `user_id` in-query, so no `viewAny` policy ability is needed (D-8).
 */
class TreeController extends Controller
{
    public function tree(Request $request, UserCatalogTreeService $service): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string', 'in:all,activo,desactivado'],
            'origin' => ['sometimes', 'string', 'in:personal,override,base'],
        ]);

        return response()->json(
            $service->tree($request->user(), $filters['status'] ?? null, $filters['origin'] ?? null)
        );
    }
}

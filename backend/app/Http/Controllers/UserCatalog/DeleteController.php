<?php

namespace App\Http\Controllers\UserCatalog;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserCatalog\Concerns\ResolvesUserCatalogItem;
use App\Models\UserCatalogItem;
use App\Services\CascadeDeleteUserCatalogService;
use Illuminate\Http\Response;

/**
 * Cascade soft-delete of a fork subtree (R4, D9). The route-bound item is
 * type-checked against the route and authorized by the owner-only `delete`
 * policy (admin denied); the service recursively soft-deletes the caller's
 * live descendants in one transaction and the endpoint returns 204.
 */
class DeleteController extends Controller
{
    use ResolvesUserCatalogItem;

    public function destroy(string $type, UserCatalogItem $fork, CascadeDeleteUserCatalogService $service): Response
    {
        $item = $this->authorizeType($fork, $this->routeType(), 'delete');

        $service->delete($item);

        return response()->noContent();
    }
}

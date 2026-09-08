<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * List services, optionally filtered by status and categoria.
     *
     * Query params:
     * - status: 'activo' (default) | 'desactivado' | 'all'
     * - categoria_id: restrict to a single categoria
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'activo');
        $categoriaId = $request->query('categoria_id');

        $query = Service::query();

        if ($categoriaId) {
            $query->where('categoria_id', $categoriaId);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $services = $query->orderBy('title')->get();

        return response()->json($services);
    }

    /**
     * Store a newly created service.
     */
    public function store(StoreServiceRequest $request)
    {
        $service = Service::create([
            ...$request->validated(),
            'status' => 'activo',
        ]);

        return response()->json($service, 201);
    }

    /**
     * Update an existing service. Passing categoria_id moves the service
     * (HU-022) with title uniqueness validated in the destination category.
     */
    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update($request->validated());

        return response()->json($service);
    }

    /**
     * Deactivate a service (preserves order history and blocks new use).
     */
    public function deactivate(Service $service)
    {
        $service->update(['status' => 'desactivado']);

        return response()->json($service);
    }

    /**
     * Reactivate a service (only the selected item).
     */
    public function reactivate(Service $service)
    {
        $service->update(['status' => 'activo']);

        return response()->json($service);
    }

    /**
     * Permanently delete a service, guarded against forks/order history.
     *
     * D10: a service with order history must be deactivated, not hard-deleted.
     * The order_services snapshot table does not exist yet (future epic), so
     * history is checked via the fork table; when order_services lands, this
     * guard extends to it without changing the 409 contract.
     */
    public function destroy(Service $service)
    {
        $hasForks = \App\Models\UserCatalogItem::query()
            ->where('item_type', 'service')
            ->where('base_id', $service->id)
            ->exists();

        if ($hasForks) {
            return response()->json([
                'message' => 'El servicio tiene forks asociados y no puede eliminarse; desactívelo.',
            ], 409);
        }

        $service->forceDelete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRubroRequest;
use App\Http\Requests\UpdateRubroRequest;
use App\Models\Rubro;
use Illuminate\Http\Request;

class RubroController extends Controller
{
    /**
     * List rubros, optionally filtered by status.
     *
     * Query params:
     * - status: 'activo' (default) | 'desactivado' | 'all'
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'activo');

        $query = Rubro::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $rubros = $query->orderBy('name')->get();

        return response()->json($rubros);
    }

    /**
     * Store a newly created rubro.
     */
    public function store(StoreRubroRequest $request)
    {
        $rubro = Rubro::create([
            ...$request->validated(),
            'status' => 'activo',
        ]);

        return response()->json($rubro, 201);
    }

    /**
     * Update an existing rubro.
     */
    public function update(UpdateRubroRequest $request, Rubro $rubro)
    {
        $rubro->update($request->validated());

        return response()->json($rubro);
    }

    /**
     * Deactivate a rubro (preserves history and blocks visibility).
     */
    public function deactivate(Rubro $rubro)
    {
        $rubro->update(['status' => 'desactivado']);

        return response()->json($rubro);
    }

    /**
     * Reactivate a rubro (only the selected item).
     */
    public function reactivate(Rubro $rubro)
    {
        $rubro->update(['status' => 'activo']);

        return response()->json($rubro);
    }

    /**
     * List categorias belonging to this rubro, optionally filtered by status.
     */
    public function categorias(Request $request, Rubro $rubro)
    {
        $status = $request->query('status', 'activo');

        $query = $rubro->categorias();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return response()->json($query->orderBy('order')->get());
    }

    /**
     * Permanently delete a rubro, guarded against relations/forks.
     */
    public function destroy(Rubro $rubro)
    {
        if ($rubro->categorias()->withTrashed()->exists()) {
            return response()->json([
                'message' => 'El rubro tiene categorías asociadas y no puede eliminarse; desactívelo.',
            ], 409);
        }

        $hasForks = \App\Models\UserCatalogItem::query()
            ->where('item_type', 'rubro')
            ->where('base_id', $rubro->id)
            ->exists();

        if ($hasForks) {
            return response()->json([
                'message' => 'El rubro tiene forks asociados y no puede eliminarse; desactívelo.',
            ], 409);
        }

        $rubro->forceDelete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Models\UserCatalogItem;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * List categorias, optionally filtered by status and rubro.
     *
     * Query params:
     * - status: 'activo' (default) | 'desactivado' | 'all'
     * - rubro_id: restrict to a single rubro
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'activo');
        $rubroId = $request->query('rubro_id');

        $query = Categoria::query();

        if ($rubroId) {
            $query->where('rubro_id', $rubroId);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $categorias = $query->orderBy('order')->get();

        return response()->json($categorias);
    }

    /**
     * Store a newly created categoria.
     */
    public function store(StoreCategoriaRequest $request)
    {
        $categoria = Categoria::create([
            ...$request->validated(),
            'status' => 'activo',
        ]);

        return response()->json($categoria, 201);
    }

    /**
     * Update an existing categoria.
     */
    public function update(UpdateCategoriaRequest $request, Categoria $categoria)
    {
        $categoria->update($request->validated());

        return response()->json($categoria);
    }

    /**
     * Deactivate a categoria (preserves history and blocks visibility).
     */
    public function deactivate(Categoria $categoria)
    {
        $categoria->update(['status' => 'desactivado']);

        return response()->json($categoria);
    }

    /**
     * Reactivate a categoria (only the selected item).
     */
    public function reactivate(Categoria $categoria)
    {
        $categoria->update(['status' => 'activo']);

        return response()->json($categoria);
    }

    /**
     * List services belonging to this categoria, optionally filtered by status.
     */
    public function services(Request $request, Categoria $categoria)
    {
        $status = $request->query('status', 'activo');

        $query = $categoria->services();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return response()->json($query->orderBy('title')->get());
    }

    /**
     * Permanently delete a categoria, guarded against relations/forks.
     */
    public function destroy(Categoria $categoria)
    {
        if ($categoria->services()->withTrashed()->exists()) {
            return response()->json([
                'message' => 'La categoría tiene servicios asociados y no puede eliminarse; desactívela.',
            ], 409);
        }

        $hasForks = UserCatalogItem::query()
            ->where('item_type', 'categoria')
            ->where('base_id', $categoria->id)
            ->exists();

        if ($hasForks) {
            return response()->json([
                'message' => 'La categoría tiene forks asociados y no puede eliminarse; desactívela.',
            ], 409);
        }

        $categoria->forceDelete();

        return response()->noContent();
    }
}

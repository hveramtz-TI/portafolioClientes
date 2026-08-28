<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Requests\UpdateClientStatusRequest;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * List clients, optionally filtered by status and search text.
     *
     * Query params:
     * - status: 'activo' (default) | 'desactivado' | 'all'
     * - search: LIKE match on name, surname, rut or email.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'activo');
        $search = $request->query('search');

        $query = Client::query()->with('company:id,name');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('rut', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('name')->get();

        return response()->json($clients);
    }

    /**
     * Store a newly created client.
     */
    public function store(StoreClientRequest $request)
    {
        $client = Client::create([
            ...$request->validated(),
            'status' => 'activo',
        ]);

        return response()->json($client, 201);
    }

    /**
     * Update an existing client.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        $client->load('company:id,name');

        return response()->json($client);
    }

    /**
     * Update the status of an existing client (activate / deactivate).
     */
    public function updateStatus(UpdateClientStatusRequest $request, Client $client)
    {
        $client->update(['status' => $request->validated('status')]);

        return response()->json($client);
    }

    /**
     * Permanently delete a client.
     */
    public function destroy(Client $client)
    {
        // En Fase 1 no hay registros relacionados; cuando existan órdenes/solicitudes
        // habrá que definir aquí el alcance de "eliminar definitivamente" (HU-006).
        $client->delete();

        return response()->noContent();
    }
}

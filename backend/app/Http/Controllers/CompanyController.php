<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * List companies, optionally filtered by search text.
     *
     * Query params:
     * - search: LIKE match on name, rut or email.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Company::query();

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('rut', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $companies = $query->withCount('clients')->orderBy('name')->get();

        return response()->json($companies);
    }

    /**
     * Store a newly created company.
     */
    public function store(StoreCompanyRequest $request)
    {
        $company = Company::create($request->validated());

        return response()->json($company, 201);
    }

    /**
     * Display the specified company with its clients (id, name, surname).
     */
    public function show(Company $company)
    {
        $company->load('clients:id,name,surname');

        return response()->json($company);
    }

    /**
     * Update an existing company.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        return response()->json($company);
    }

    /**
     * Permanently delete a company, blocking when it has associated clients.
     */
    public function destroy(Company $company)
    {
        $clients = $company->clients()->select('id', 'name', 'surname')->get();

        if ($clients->isNotEmpty()) {
            return response()->json([
                'message' => 'No se puede eliminar la empresa porque tiene clientes asociados.',
                'clients' => $clients,
            ], 422);
        }

        $company->delete();

        return response()->noContent();
    }
}

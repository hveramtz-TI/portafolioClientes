<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that deleting a company without clients succeeds with a 204.
     */
    public function test_destroy_succeeds_without_clients(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $company = $this->createCompany('Empresa Sin Clientes');

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
        ]);
    }

    /**
     * Test that deleting a company with clients is blocked with a 422.
     */
    public function test_destroy_blocks_with_clients(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $company = $this->createCompany('Empresa Con Clientes');

        Client::create([
            'name' => 'Cliente Asociado',
            'surname' => 'Prueba',
            'rut' => $this->uniqueRut(),
            'email' => 'cliente.asociado@example.com',
            'phone' => '+56 9 1234 5678',
            'company_id' => $company->id,
            'status' => 'activo',
        ]);

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(422)
            ->assertJsonPath('clients.0.name', 'Cliente Asociado');
    }

    /**
     * Test that the blocked deletion does not remove the associated clients.
     */
    public function test_destroy_does_not_delete_clients(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $company = $this->createCompany('Empresa Persistente');

        $client = Client::create([
            'name' => 'Cliente Persistente',
            'surname' => 'Prueba',
            'rut' => $this->uniqueRut(),
            'email' => 'cliente.persistente@example.com',
            'phone' => '+56 9 1234 5678',
            'company_id' => $company->id,
            'status' => 'activo',
        ]);

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Cliente Persistente',
        ]);
    }

    /**
     * Create a company with a minimal set of fields for tests.
     */
    private function createCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'rut' => $this->uniqueRut(),
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'phone' => '+56 2 2000 0000',
        ]);
    }

    /**
     * Generate a unique, valid RUT for test fixtures.
     */
    private function uniqueRut(): string
    {
        $body = (string) random_int(10000000, 99999999);
        $sum = 0;
        $multiplier = 2;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += intval($body[$i]) * $multiplier;
            $multiplier = $multiplier < 7 ? $multiplier + 1 : 2;
        }

        $expected = 11 - ($sum % 11);
        $dv = $expected === 11 ? '0' : ($expected === 10 ? 'K' : (string) $expected);

        return $body.'-'.$dv;
    }
}

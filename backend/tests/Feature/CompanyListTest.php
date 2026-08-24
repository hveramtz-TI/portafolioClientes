<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that unauthenticated requests are rejected.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/companies');

        $response->assertStatus(401);
    }

    /**
     * Test that companies are returned ordered by name.
     */
    public function test_returns_companies(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->createCompany('Zeta Empresa');
        $this->createCompany('Alfa Empresa');

        $response = $this->getJson('/api/companies');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.name', 'Alfa Empresa')
            ->assertJsonPath('1.name', 'Zeta Empresa');
    }

    /**
     * Test the search param filters across name, rut and email.
     */
    public function test_search_filter(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Company::create([
            'name' => 'Empresa Alpha SPA',
            'rut' => '10101010-4',
            'email' => 'contacto@empresaalpha.cl',
            'phone' => '+56 2 2101 0101',
        ]);

        Company::create([
            'name' => 'Beta Tecnología LTDA',
            'rut' => '20202020-8',
            'email' => 'contacto@betatecnologia.cl',
            'phone' => '+56 2 2202 0202',
        ]);

        $response = $this->getJson('/api/companies?search=beta');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Beta Tecnología LTDA');
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

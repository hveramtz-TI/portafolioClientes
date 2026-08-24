<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a valid POST creates a company (normalizing the RUT).
     */
    public function test_store_creates_company(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/companies', [
            'name' => 'Empresa Nueva',
            'rut' => '10.101.010-4',
            'email' => 'empresa.nueva@example.com',
            'phone' => '+56 2 2101 0101',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Empresa Nueva');

        $this->assertDatabaseHas('companies', [
            'name' => 'Empresa Nueva',
            'rut' => '10101010-4',
        ]);
    }

    /**
     * Test that a duplicated RUT is rejected with a 422.
     */
    public function test_store_rejects_duplicate_rut(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $rut = $this->uniqueRut();

        Company::create([
            'name' => 'Empresa Existente',
            'rut' => $rut,
            'email' => 'existente@example.com',
            'phone' => '+56 2 2000 0000',
        ]);

        $response = $this->postJson('/api/companies', [
            'name' => 'Empresa Duplicada',
            'rut' => $rut,
            'email' => 'duplicada@example.com',
            'phone' => '+56 2 2111 1111',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'rut' => 'Ya existe una empresa con este RUT.',
            ]);
    }

    /**
     * Test that an invalid RUT (bad check digit) is rejected.
     */
    public function test_store_rejects_invalid_rut(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/companies', [
            'name' => 'RUT Inválido',
            'rut' => '10101010-0',
            'email' => 'invalido@example.com',
            'phone' => '+56 2 2222 2222',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('rut');
    }

    /**
     * Test that missing required fields are rejected.
     */
    public function test_store_rejects_missing_required_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/companies', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'rut', 'email', 'phone']);
    }

    /**
     * Test that a malformed email is rejected.
     */
    public function test_store_rejects_invalid_email(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/companies', [
            'name' => 'Email Inválido',
            'rut' => '10101010-4',
            'email' => 'correo-no-valido',
            'phone' => '+56 2 2333 3333',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
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

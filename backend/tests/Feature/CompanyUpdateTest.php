<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a valid update modifies the expected fields.
     */
    public function test_update_modifies_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $company = $this->createCompany('Empresa Original');

        $response = $this->putJson("/api/companies/{$company->id}", [
            'name' => 'Empresa Editada',
            'rut' => $company->rut,
            'email' => 'editada@example.com',
            'phone' => '+56 2 2999 9999',
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Empresa Editada')
            ->assertJsonPath('email', 'editada@example.com')
            ->assertJsonPath('phone', '+56 2 2999 9999');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Empresa Editada',
            'email' => 'editada@example.com',
            'phone' => '+56 2 2999 9999',
        ]);
    }

    /**
     * Test that updating while keeping the SAME RUT does not trigger a duplicate.
     */
    public function test_update_keeps_same_rut(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $company = $this->createCompany('Empresa Uno');

        $response = $this->putJson("/api/companies/{$company->id}", [
            'name' => 'Empresa Uno Actualizada',
            'rut' => $company->rut,
            'email' => $company->email,
            'phone' => $company->phone,
        ]);

        $response->assertOk()
            ->assertJsonPath('rut', $company->rut);
    }

    /**
     * Test that assigning another company's RUT is rejected with a 422.
     */
    public function test_update_rejects_duplicate_rut_of_other_company(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $first = $this->createCompany('Empresa Primera');
        $second = $this->createCompany('Empresa Segunda');

        $response = $this->putJson("/api/companies/{$second->id}", [
            'name' => 'Empresa Segunda',
            'rut' => $first->rut,
            'email' => $second->email,
            'phone' => $second->phone,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'rut' => 'Ya existe una empresa con este RUT.',
            ]);
    }

    /**
     * Test that an invalid RUT (bad check digit) is rejected.
     */
    public function test_update_rejects_invalid_rut(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $company = $this->createCompany('Empresa Inválida');

        $response = $this->putJson("/api/companies/{$company->id}", [
            'name' => 'Empresa Inválida',
            'rut' => '10101010-0',
            'email' => $company->email,
            'phone' => $company->phone,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('rut');
    }

    /**
     * Test that a malformed email is rejected.
     */
    public function test_update_rejects_invalid_email(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $company = $this->createCompany('Empresa Email Inválido');

        $response = $this->putJson("/api/companies/{$company->id}", [
            'name' => 'Empresa Email Inválido',
            'rut' => $company->rut,
            'email' => 'correo-no-valido',
            'phone' => $company->phone,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
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

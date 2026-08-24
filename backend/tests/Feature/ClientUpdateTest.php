<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a valid update modifies the expected fields.
     */
    public function test_update_modifies_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $client = $this->createClient('Nombre Original');

        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Nombre Editado',
            'surname' => 'Apellido Editado',
            'rut' => $client->rut,
            'email' => 'editado@example.com',
            'phone' => '+56 9 9999 9999',
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Nombre Editado')
            ->assertJsonPath('email', 'editado@example.com')
            ->assertJsonPath('phone', '+56 9 9999 9999');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Nombre Editado',
            'surname' => 'Apellido Editado',
            'email' => 'editado@example.com',
            'phone' => '+56 9 9999 9999',
        ]);
    }

    /**
     * Test that updating while keeping the SAME RUT does not trigger a duplicate.
     */
    public function test_update_keeps_same_rut(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $client = $this->createClient('Cliente Uno');

        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Cliente Uno Actualizado',
            'rut' => $client->rut,
            'email' => $client->email,
            'phone' => $client->phone,
        ]);

        $response->assertOk()
            ->assertJsonPath('rut', $client->rut);
    }

    /**
     * Test that assigning another client's RUT is rejected with a 422.
     */
    public function test_update_rejects_duplicate_rut_of_other_client(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $first = $this->createClient('Cliente Primero');
        $second = $this->createClient('Cliente Segundo');

        $response = $this->putJson("/api/clients/{$second->id}", [
            'name' => 'Cliente Segundo',
            'rut' => $first->rut,
            'email' => $second->email,
            'phone' => $second->phone,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'rut' => 'Ya existe un cliente con este RUT.',
            ]);
    }

    /**
     * Test that an invalid RUT (bad check digit) is rejected.
     */
    public function test_update_rejects_invalid_rut(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $client = $this->createClient('Cliente Inválido');

        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Cliente Inválido',
            'rut' => '12345678-0',
            'email' => $client->email,
            'phone' => $client->phone,
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

        $client = $this->createClient('Cliente Email Inválido');

        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Cliente Email Inválido',
            'rut' => $client->rut,
            'email' => 'correo-no-valido',
            'phone' => $client->phone,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /**
     * Create a client with a minimal set of fields for tests.
     */
    private function createClient(string $name): Client
    {
        return Client::create([
            'name' => $name,
            'surname' => 'Test',
            'rut' => $this->uniqueRut(),
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'phone' => '+56 9 0000 0000',
            'status' => 'activo',
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

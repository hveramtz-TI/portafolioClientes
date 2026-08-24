<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a valid POST creates a client (normalizing the RUT).
     */
    public function test_store_creates_client(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/clients', [
            'name' => 'Cliente Nuevo',
            'surname' => 'Prueba',
            'rut' => '12.345.678-5',
            'email' => 'cliente.nuevo@example.com',
            'phone' => '+56 9 1234 5678',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Cliente Nuevo');

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Nuevo',
            'rut' => '12345678-5',
            'status' => 'activo',
        ]);
    }

    /**
     * Test that a duplicated RUT is rejected with a 422.
     */
    public function test_store_rejects_duplicate_rut(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $rut = $this->uniqueRut();

        Client::create([
            'name' => 'Cliente Existente',
            'rut' => $rut,
            'email' => 'existente@example.com',
            'phone' => '+56 9 0000 0000',
        ]);

        $response = $this->postJson('/api/clients', [
            'name' => 'Cliente Duplicado',
            'rut' => $rut,
            'email' => 'duplicado@example.com',
            'phone' => '+56 9 1111 1111',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'rut' => 'Ya existe un cliente con este RUT.',
            ]);
    }

    /**
     * Test that an invalid RUT (bad check digit) is rejected.
     */
    public function test_store_rejects_invalid_rut(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/clients', [
            'name' => 'RUT Inválido',
            'rut' => '12345678-0',
            'email' => 'invalido@example.com',
            'phone' => '+56 9 2222 2222',
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

        $response = $this->postJson('/api/clients', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'rut', 'email', 'phone']);
    }

    /**
     * Test that a malformed email is rejected.
     */
    public function test_store_rejects_invalid_email(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/clients', [
            'name' => 'Email Inválido',
            'rut' => '12345678-5',
            'email' => 'correo-no-valido',
            'phone' => '+56 9 3333 3333',
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

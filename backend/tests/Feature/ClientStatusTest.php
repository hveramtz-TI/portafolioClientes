<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a client can be deactivated via PATCH status.
     */
    public function test_deactivate_client(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $client = $this->createClient('Cliente Desactivar', 'activo');

        $response = $this->patchJson("/api/clients/{$client->id}/status", [
            'status' => 'desactivado',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'desactivado');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => 'desactivado',
        ]);
    }

    /**
     * Test that a deactivated client can be reactivated via PATCH status.
     */
    public function test_reactivate_client(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $client = $this->createClient('Cliente Reactivar', 'desactivado');

        $response = $this->patchJson("/api/clients/{$client->id}/status", [
            'status' => 'activo',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'activo');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => 'activo',
        ]);
    }

    /**
     * Test that DELETE permanently removes a client.
     */
    public function test_destroy_client(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $client = $this->createClient('Cliente Eliminar', 'activo');

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('clients', [
            'id' => $client->id,
        ]);
    }

    /**
     * Test that an invalid status value is rejected with a 422.
     */
    public function test_rejects_invalid_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $client = $this->createClient('Cliente Status Inválido', 'activo');

        $response = $this->patchJson("/api/clients/{$client->id}/status", [
            'status' => 'foo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    /**
     * Create a client with a minimal set of fields for tests.
     */
    private function createClient(string $name, string $status): Client
    {
        return Client::create([
            'name' => $name,
            'surname' => 'Test',
            'rut' => $this->uniqueRut(),
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'phone' => '+56 9 0000 0000',
            'status' => $status,
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

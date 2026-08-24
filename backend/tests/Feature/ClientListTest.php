<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that unauthenticated requests are rejected.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/clients');

        $response->assertStatus(401);
    }

    /**
     * Test that active clients are returned by default.
     */
    public function test_returns_active_clients_by_default(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->createClient('Activo Uno', 'activo');
        $this->createClient('Desactivado Uno', 'desactivado');

        $response = $this->getJson('/api/clients');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Activo Uno');
    }

    /**
     * Test the status=desactivado filter.
     */
    public function test_status_desactivado_filter(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->createClient('Activo Uno', 'activo');
        $this->createClient('Desactivado Uno', 'desactivado');

        $response = $this->getJson('/api/clients?status=desactivado');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'desactivado')
            ->assertJsonPath('0.name', 'Desactivado Uno');
    }

    /**
     * Test the status=all filter returns every client.
     */
    public function test_status_all_filter(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->createClient('Activo Uno', 'activo');
        $this->createClient('Desactivado Uno', 'desactivado');

        $response = $this->getJson('/api/clients?status=all');

        $response->assertOk()->assertJsonCount(2);
    }

    /**
     * Test the search param filters across name, surname, rut and email.
     */
    public function test_search_filter(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Client::create([
            'name' => 'María',
            'surname' => 'González',
            'rut' => '11111111-1',
            'email' => 'maria.gonzalez@example.com',
            'phone' => '+56 9 1234 5678',
            'status' => 'activo',
        ]);

        Client::create([
            'name' => 'Juan',
            'surname' => 'Pérez',
            'rut' => '22222222-2',
            'email' => 'juan.perez@example.com',
            'phone' => '+56 9 2345 6789',
            'status' => 'activo',
        ]);

        $response = $this->getJson('/api/clients?status=all&search=gonzalez');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'María');
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

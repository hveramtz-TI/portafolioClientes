<?php

namespace Tests\Feature;

use App\Models\Rubro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseCatalogReadAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_read_active_base_rubros(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Rubro::create(['name' => 'Visible']);
        Rubro::create(['name' => 'Hidden', 'status' => 'desactivado']);

        $response = $this->actingAs($user)->getJson('/api/rubros');

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Visible');
    }

    public function test_normal_user_cannot_mutate_base_rubros(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $rubro = Rubro::create(['name' => 'Protected']);

        $this->actingAs($user)->putJson("/api/rubros/{$rubro->id}", ['name' => 'Changed'])
            ->assertForbidden();
    }
}

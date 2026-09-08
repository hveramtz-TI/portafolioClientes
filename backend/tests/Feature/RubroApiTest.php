<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RubroApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin helper: acting as a role=admin user.
     */
    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/rubros');

        $response->assertStatus(401);
    }

    public function test_regular_user_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $response = $this->getJson('/api/rubros');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_active_rubros_by_default(): void
    {
        $this->actingAsAdmin();

        Rubro::create(['name' => 'Informática', 'status' => 'activo']);
        Rubro::create(['name' => 'Diseño', 'status' => 'activo']);
        Rubro::create(['name' => 'Consultoría', 'status' => 'desactivado']);

        $response = $this->getJson('/api/rubros');

        $response->assertOk()
            ->assertJsonCount(2);
    }

    public function test_status_filter_desactivado(): void
    {
        $this->actingAsAdmin();

        Rubro::create(['name' => 'Informática', 'status' => 'activo']);
        Rubro::create(['name' => 'Consultoría', 'status' => 'desactivado']);

        $response = $this->getJson('/api/rubros?status=desactivado');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Consultoría');
    }

    public function test_admin_can_create_rubro(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/rubros', [
            'name' => 'Marketing',
            'description' => 'Servicios de marketing digital',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Marketing')
            ->assertJsonPath('status', 'activo');

        $this->assertDatabaseHas('rubros', ['name' => 'Marketing', 'status' => 'activo']);
    }

    public function test_create_rejects_duplicate_name(): void
    {
        $this->actingAsAdmin();

        Rubro::create(['name' => 'Informática']);

        $response = $this->postJson('/api/rubros', ['name' => 'Informática']);

        $response->assertStatus(422);
    }

    public function test_create_rejects_missing_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/rubros', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_admin_can_update_rubro(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);

        $response = $this->putJson("/api/rubros/{$rubro->id}", [
            'name' => 'Tecnología',
            'description' => 'Descripción nueva',
        ]);

        $response->assertOk()->assertJsonPath('name', 'Tecnología');

        $this->assertDatabaseHas('rubros', ['id' => $rubro->id, 'name' => 'Tecnología']);
    }

    public function test_update_keeps_same_name_without_conflict(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);

        $response = $this->putJson("/api/rubros/{$rubro->id}", ['name' => 'Informática']);

        $response->assertOk();
    }

    public function test_update_rejects_duplicate_name_of_other_rubro(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        Rubro::create(['name' => 'Diseño']);

        $response = $this->putJson("/api/rubros/{$rubro->id}", ['name' => 'Diseño']);

        $response->assertStatus(422);
    }

    public function test_admin_can_deactivate_rubro(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática', 'status' => 'activo']);

        $response = $this->patchJson("/api/rubros/{$rubro->id}/deactivate");

        $response->assertOk()->assertJsonPath('status', 'desactivado');

        $this->assertDatabaseHas('rubros', ['id' => $rubro->id, 'status' => 'desactivado']);
    }

    public function test_admin_can_reactivate_rubro(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática', 'status' => 'desactivado']);

        $response = $this->patchJson("/api/rubros/{$rubro->id}/reactivate");

        $response->assertOk()->assertJsonPath('status', 'activo');
    }

    public function test_delete_rubro_without_relations_returns_204(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Sin relaciones']);

        $response = $this->deleteJson("/api/rubros/{$rubro->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('rubros', ['id' => $rubro->id]);
    }

    public function test_delete_rubro_with_categorias_returns_409(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Con categorías']);
        Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);

        $response = $this->deleteJson("/api/rubros/{$rubro->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('rubros', ['id' => $rubro->id]);
    }

    public function test_admin_can_list_rubro_categorias(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web', 'status' => 'activo']);
        Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Apps', 'status' => 'activo']);

        $response = $this->getJson("/api/rubros/{$rubro->id}/categorias");

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_delete_nonexistent_rubro_returns_404(): void
    {
        $this->actingAsAdmin();

        $response = $this->deleteJson('/api/rubros/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use App\Models\User;
use App\Models\UserCatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoriaApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/categorias');

        $response->assertStatus(401);
    }

    public function test_regular_user_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $response = $this->getJson('/api/categorias');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_categorias_filtered_by_rubro(): void
    {
        $this->actingAsAdmin();

        $rubroA = Rubro::create(['name' => 'Informática']);
        $rubroB = Rubro::create(['name' => 'Diseño']);

        Categoria::create(['rubro_id' => $rubroA->id, 'name' => 'Web', 'status' => 'activo']);
        Categoria::create(['rubro_id' => $rubroB->id, 'name' => 'Logo', 'status' => 'activo']);

        $response = $this->getJson("/api/categorias?rubro_id={$rubroA->id}");

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Web');
    }

    public function test_admin_can_update_categoria(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);

        $response = $this->putJson("/api/categorias/{$categoria->id}", [
            'name' => 'Sitios web',
            'description' => 'Nueva descripción',
            'order' => 3,
        ]);

        $response->assertOk()->assertJsonPath('name', 'Sitios web');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'name' => 'Sitios web']);
    }

    public function test_rubro_id_is_immutable_on_update(): void
    {
        $this->actingAsAdmin();

        $rubroA = Rubro::create(['name' => 'Informática']);
        $rubroB = Rubro::create(['name' => 'Diseño']);

        $categoria = Categoria::create(['rubro_id' => $rubroA->id, 'name' => 'Web']);

        $response = $this->putJson("/api/categorias/{$categoria->id}", [
            'name' => 'Web',
            'rubro_id' => $rubroB->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'rubro_id' => $rubroA->id]);
    }

    public function test_update_rejects_duplicate_name_in_same_rubro(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Apps']);

        $response = $this->putJson("/api/categorias/{$categoria->id}", ['name' => 'Apps']);

        $response->assertStatus(422);
    }

    public function test_same_name_in_different_rubro_is_allowed(): void
    {
        $this->actingAsAdmin();

        $rubroA = Rubro::create(['name' => 'Informática']);
        $rubroB = Rubro::create(['name' => 'Diseño']);

        $categoria = Categoria::create(['rubro_id' => $rubroA->id, 'name' => 'Web']);
        Categoria::create(['rubro_id' => $rubroB->id, 'name' => 'Web']);

        $response = $this->putJson("/api/categorias/{$categoria->id}", ['name' => 'Web']);

        $response->assertOk();
    }

    public function test_admin_can_deactivate_categoria(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web', 'status' => 'activo']);

        $response = $this->patchJson("/api/categorias/{$categoria->id}/deactivate");

        $response->assertOk()->assertJsonPath('status', 'desactivado');
    }

    public function test_admin_can_reactivate_categoria(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web', 'status' => 'desactivado']);

        $response = $this->patchJson("/api/categorias/{$categoria->id}/reactivate");

        $response->assertOk()->assertJsonPath('status', 'activo');
    }

    public function test_delete_categoria_without_relations_returns_204(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);

        $response = $this->deleteJson("/api/categorias/{$categoria->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_delete_categoria_with_services_returns_409(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100]);

        $response = $this->deleteJson("/api/categorias/{$categoria->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
    }

    public function test_admin_can_list_categoria_services(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100, 'status' => 'activo']);
        Service::create(['categoria_id' => $categoria->id, 'title' => 'E-commerce', 'value' => 200, 'status' => 'activo']);

        $response = $this->getJson("/api/categorias/{$categoria->id}/services");

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_admin_can_store_categoria(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);

        $response = $this->postJson('/api/categorias', [
            'rubro_id' => $rubro->id,
            'name' => 'Desarrollo web',
            'description' => 'Sitios y tiendas',
            'order' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Desarrollo web')
            ->assertJsonPath('rubro_id', $rubro->id)
            ->assertJsonPath('status', 'activo');

        $this->assertDatabaseHas('categorias', [
            'name' => 'Desarrollo web',
            'rubro_id' => $rubro->id,
            'order' => 5,
        ]);
    }

    public function test_store_rejects_duplicate_name_in_same_rubro(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);

        $response = $this->postJson('/api/categorias', [
            'rubro_id' => $rubro->id,
            'name' => 'Web',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_rejects_explicit_null_order(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);

        $response = $this->postJson('/api/categorias', [
            'rubro_id' => $rubro->id,
            'name' => 'Web',
            'order' => null,
        ]);

        $response->assertStatus(422);
    }

    public function test_delete_categoria_with_forks_returns_409(): void
    {
        $this->actingAsAdmin();

        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        $owner = User::factory()->create();
        UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => 'categoria',
            'base_id' => $categoria->id,
        ]);

        $response = $this->deleteJson("/api/categorias/{$categoria->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
    }
}

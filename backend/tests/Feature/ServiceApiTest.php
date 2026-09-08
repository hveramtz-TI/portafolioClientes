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

class ServiceApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        return $admin;
    }

    private function makeCategoria(string $name = 'Web'): Categoria
    {
        $rubro = Rubro::create(['name' => 'Rubro '.$name.' '.uniqid()]);

        return Categoria::create(['rubro_id' => $rubro->id, 'name' => $name]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/services');

        $response->assertStatus(401);
    }

    public function test_regular_user_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $response = $this->getJson('/api/services');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_services_filtered_by_categoria(): void
    {
        $this->actingAsAdmin();

        $catA = $this->makeCategoria('Web');
        $catB = $this->makeCategoria('Apps');

        Service::create(['categoria_id' => $catA->id, 'title' => 'Landing', 'value' => 100, 'status' => 'activo']);
        Service::create(['categoria_id' => $catB->id, 'title' => 'App móvil', 'value' => 200, 'status' => 'activo']);

        $response = $this->getJson("/api/services?categoria_id={$catA->id}");

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Landing');
    }

    public function test_admin_can_create_service_with_allowed_tags(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();

        $response = $this->postJson('/api/services', [
            'categoria_id' => $categoria->id,
            'title' => 'Landing page',
            'description' => 'Página aterrizaje',
            'value' => 450000,
            'tags' => ['frontend', 'fullstack'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Landing page')
            ->assertJsonPath('value', 450000)
            ->assertJsonPath('status', 'activo')
            ->assertJsonPath('tags', ['frontend', 'fullstack']);

        $this->assertDatabaseHas('services', ['title' => 'Landing page', 'value' => 450000]);
    }

    public function test_create_rejects_invalid_tag(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();

        $response = $this->postJson('/api/services', [
            'categoria_id' => $categoria->id,
            'title' => 'Landing page',
            'value' => 100,
            'tags' => ['not-allowed'],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('tags.0');
    }

    public function test_create_rejects_negative_value(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();

        $response = $this->postJson('/api/services', [
            'categoria_id' => $categoria->id,
            'title' => 'Landing page',
            'value' => -1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('value');
    }

    public function test_create_rejects_non_integer_value(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();

        $response = $this->postJson('/api/services', [
            'categoria_id' => $categoria->id,
            'title' => 'Landing page',
            'value' => 10.5,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('value');
    }

    public function test_create_rejects_duplicate_title_in_same_categoria(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();
        Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100]);

        $response = $this->postJson('/api/services', [
            'categoria_id' => $categoria->id,
            'title' => 'Landing',
            'value' => 200,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('title');
    }

    public function test_admin_can_update_service(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100]);

        $response = $this->putJson("/api/services/{$service->id}", [
            'title' => 'Landing premium',
            'value' => 500,
            'tags' => ['frontend'],
        ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Landing premium')
            ->assertJsonPath('value', 500);

        $this->assertDatabaseHas('services', ['id' => $service->id, 'title' => 'Landing premium', 'value' => 500]);
    }

    public function test_move_service_to_different_categoria(): void
    {
        $this->actingAsAdmin();

        $catA = $this->makeCategoria('Web');
        $catB = $this->makeCategoria('Apps');

        $service = Service::create(['categoria_id' => $catA->id, 'title' => 'Landing', 'value' => 100]);

        $response = $this->putJson("/api/services/{$service->id}", [
            'title' => 'Landing',
            'categoria_id' => $catB->id,
            'value' => 100,
        ]);

        $response->assertOk()->assertJsonPath('categoria_id', $catB->id);

        $this->assertDatabaseHas('services', ['id' => $service->id, 'categoria_id' => $catB->id]);
    }

    public function test_move_service_rejected_when_title_exists_in_destination(): void
    {
        $this->actingAsAdmin();

        $catA = $this->makeCategoria('Web');
        $catB = $this->makeCategoria('Apps');

        $service = Service::create(['categoria_id' => $catA->id, 'title' => 'Landing', 'value' => 100]);
        Service::create(['categoria_id' => $catB->id, 'title' => 'Landing', 'value' => 200]);

        $response = $this->putJson("/api/services/{$service->id}", [
            'title' => 'Landing',
            'categoria_id' => $catB->id,
            'value' => 100,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('title');
    }

    public function test_admin_can_deactivate_service(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100, 'status' => 'activo']);

        $response = $this->patchJson("/api/services/{$service->id}/deactivate");

        $response->assertOk()->assertJsonPath('status', 'desactivado');
    }

    public function test_admin_can_reactivate_service(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100, 'status' => 'desactivado']);

        $response = $this->patchJson("/api/services/{$service->id}/reactivate");

        $response->assertOk()->assertJsonPath('status', 'activo');
    }

    public function test_delete_service_without_relations_returns_204(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100]);

        $response = $this->deleteJson("/api/services/{$service->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_delete_service_with_forks_returns_409(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100]);
        $owner = User::factory()->create();
        UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => 'service',
            'base_id' => $service->id,
        ]);

        $response = $this->deleteJson("/api/services/{$service->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_update_rejects_null_categoria_id(): void
    {
        $this->actingAsAdmin();

        $categoria = $this->makeCategoria();
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'Landing', 'value' => 100]);

        $response = $this->putJson("/api/services/{$service->id}", [
            'title' => 'Landing page',
            'value' => 100,
            'categoria_id' => null,
        ]);

        $response->assertStatus(422);
    }
}

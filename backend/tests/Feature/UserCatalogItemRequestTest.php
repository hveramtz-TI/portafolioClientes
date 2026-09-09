<?php

namespace Tests\Feature;

use App\Http\Requests\StoreUserCatalogItemRequest;
use App\Http\Requests\UpdateUserCatalogItemRequest;
use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use App\Models\User;
use App\Models\UserCatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserCatalogItemRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the base catalog a fork can point at.
     *
     * @return array{0: Rubro, 1: Categoria, 2: Service}
     */
    private function makeCatalog(): array
    {
        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'API', 'value' => 100]);

        return [$rubro, $categoria, $service];
    }

    private function rubroFork(User $owner, Rubro $base): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => 'rubro',
            'base_id' => $base->id,
        ]);
    }

    private function categoriaFork(User $owner, Categoria $base, ?UserCatalogItem $parent = null): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => 'categoria',
            'base_id' => $base->id,
            'parent_fork_id' => $parent?->id,
        ]);
    }

    private function serviceFork(User $owner, Service $base, ?UserCatalogItem $parent = null): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => 'service',
            'base_id' => $base->id,
            'parent_fork_id' => $parent?->id,
        ]);
    }

    /**
     * Create a personal (base_id null) item directly, as the engine would.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function personalItem(User $owner, string $type, array $overrides, ?UserCatalogItem $parent = null): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => $type,
            'base_id' => null,
            'parent_fork_id' => $parent?->id,
            'overrides' => $overrides,
        ]);
    }

    /**
     * Instantiate a FormRequest the way route resolution would, so authorize(),
     * rules() and the FailOnUnknownFields hook all run with real input.
     *
     * @param  array<string, mixed>  $payload
     */
    private function makeRequest(string $class, array $payload, ?User $user, ?UserCatalogItem $item = null, string $method = 'POST'): FormRequest
    {
        $request = $class::create(
            '/api/user-catalog-items',
            $method,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->setUserResolver(fn () => $user);

        if ($item !== null) {
            $route = new Route($method, '/user-catalog-items/{userCatalogItem}', ['uses' => fn () => null]);
            $route->bind(Request::create('/user-catalog-items/'.$item->id, $method));
            $route->setParameter('userCatalogItem', $item);
            $request->setRouteResolver(fn () => $route);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validated(string $class, array $payload, ?User $user, ?UserCatalogItem $item = null, string $method = 'POST'): array
    {
        $request = $this->makeRequest($class, $payload, $user, $item, $method);
        $request->validateResolved();

        return $request->validated();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertRequestFails(string $class, array $payload, ?User $user, string $errorKey, ?UserCatalogItem $item = null, string $method = 'POST'): void
    {
        try {
            $this->validated($class, $payload, $user, $item, $method);
            $this->fail('Expected ValidationException for payload '.json_encode($payload));
        } catch (ValidationException $exception) {
            $this->assertSame(422, $exception->status);
            $this->assertArrayHasKey($errorKey, $exception->errors());
        }
    }

    // --- R3/S3.1: fork identity ------------------------------------------

    public function test_store_valid_service_fork_passes(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
        ], $user);

        $this->assertSame('service', $validated['item_type']);
        $this->assertSame($service->id, $validated['base_id']);
    }

    public function test_store_duplicate_fork_is_rejected(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $this->serviceFork($user, $service);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
        ], $user, 'base_id');
    }

    public function test_store_duplicate_fork_against_soft_deleted_fork_passes(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $fork = $this->serviceFork($user, $service);
        $fork->delete();

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
        ], $user);

        $this->assertSame($service->id, $validated['base_id']);
    }

    public function test_store_duplicate_fork_allowed_for_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $this->serviceFork($owner, $service);

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
        ], $other);

        $this->assertSame($service->id, $validated['base_id']);
    }

    // --- R2 base_id referential integrity (J3) ------------------------------

    /**
     * A well-formed but non-existent base_id is an R2 orphan waiting to
     * happen: the fork must point at a LIVE row of the base table mapped by
     * item_type (rubro→rubros, categoria→categorias, service→services).
     */
    public function test_store_base_id_that_does_not_exist_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => (string) Str::uuid(),
        ], $user, 'base_id');
    }

    public function test_store_base_id_of_the_wrong_base_type_is_rejected(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();

        // A real rubro id under item_type=service must be checked against the
        // services table, where it does not exist.
        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $rubro->id,
        ], $user, 'base_id');
    }

    public function test_store_base_id_of_trashed_base_is_rejected(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $service->delete();

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
        ], $user, 'base_id');
    }

    // The positive case — a real, live, type-coherent service base_id passes —
    // is guarded by test_store_valid_service_fork_passes above.

    // --- R3/S3.2: sibling visible-name uniqueness (personal items) --------

    public function test_store_personal_service_title_unique_per_parent(): void
    {
        $user = User::factory()->create();
        [, $categoria, $service] = $this->makeCatalog();
        $parent = $this->categoriaFork($user, $categoria);
        $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100], $parent);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $parent->id,
            'title' => 'X',
            'value' => 200,
        ], $user, 'title');
    }

    public function test_store_personal_service_distinct_title_passes(): void
    {
        $user = User::factory()->create();
        [, $categoria, $service] = $this->makeCatalog();
        $parent = $this->categoriaFork($user, $categoria);
        $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100], $parent);

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $parent->id,
            'title' => 'Y',
            'value' => 100,
        ], $user);

        $this->assertSame('Y', $validated['title']);
    }

    public function test_store_personal_service_same_title_different_parent_passes(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria] = $this->makeCatalog();
        $categoria2 = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Móvil']);
        $parent1 = $this->categoriaFork($user, $categoria);
        $parent2 = $this->categoriaFork($user, $categoria2);
        $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100], $parent1);

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $parent2->id,
            'title' => 'X',
            'value' => 100,
        ], $user);

        $this->assertSame('X', $validated['title']);
        $this->assertSame($parent2->id, $validated['parent_fork_id']);
    }

    public function test_store_personal_service_title_clash_ignores_soft_deleted_sibling(): void
    {
        $user = User::factory()->create();
        [, $categoria] = $this->makeCatalog();
        $parent = $this->categoriaFork($user, $categoria);
        $sibling = $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100], $parent);
        $sibling->delete();

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $parent->id,
            'title' => 'X',
            'value' => 100,
        ], $user);

        $this->assertSame('X', $validated['title']);
    }

    public function test_store_personal_rubro_name_unique(): void
    {
        $user = User::factory()->create();
        $this->personalItem($user, 'rubro', ['name' => 'Diseño']);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'rubro',
            'name' => 'Diseño',
        ], $user, 'name');
    }

    public function test_store_personal_rubro_distinct_name_passes(): void
    {
        $user = User::factory()->create();
        $this->personalItem($user, 'rubro', ['name' => 'Diseño']);

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'rubro',
            'name' => 'Consultoría',
        ], $user);

        $this->assertSame('Consultoría', $validated['name']);
    }

    public function test_store_personal_categoria_name_unique_per_parent(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria] = $this->makeCatalog();
        $parent = $this->rubroFork($user, $rubro);
        $this->personalItem($user, 'categoria', ['name' => 'Web'], $parent);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'categoria',
            'parent_fork_id' => $parent->id,
            'name' => 'Web',
        ], $user, 'name');
    }

    public function test_store_personal_categoria_same_name_different_parent_passes(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();
        $parent1 = $this->rubroFork($user, $rubro);
        $rubro2 = Rubro::create(['name' => 'Consultoría']);
        $parent2 = $this->rubroFork($user, $rubro2);
        $this->personalItem($user, 'categoria', ['name' => 'Web'], $parent1);

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'categoria',
            'parent_fork_id' => $parent2->id,
            'name' => 'Web',
        ], $user);

        $this->assertSame('Web', $validated['name']);
    }

    // --- R3/S3.3: parent type coherence -----------------------------------

    public function test_store_service_parent_must_be_categoria_fork(): void
    {
        $user = User::factory()->create();
        [$rubro, , $service] = $this->makeCatalog();
        $rubroParent = $this->rubroFork($user, $rubro);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $rubroParent->id,
            'title' => 'X',
            'value' => 100,
        ], $user, 'parent_fork_id');
    }

    public function test_store_categoria_parent_must_be_rubro_fork(): void
    {
        $user = User::factory()->create();
        [, $categoria] = $this->makeCatalog();
        $categoriaParent = $this->categoriaFork($user, $categoria);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'categoria',
            'parent_fork_id' => $categoriaParent->id,
            'name' => 'Web',
        ], $user, 'parent_fork_id');
    }

    public function test_store_rubro_with_parent_is_rejected(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();
        $anyFork = $this->rubroFork($user, $rubro);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'rubro',
            'parent_fork_id' => $anyFork->id,
            'name' => 'Diseño',
        ], $user, 'parent_fork_id');
    }

    public function test_store_personal_categoria_with_rubro_fork_parent_passes(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();
        $parent = $this->rubroFork($user, $rubro);

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'categoria',
            'parent_fork_id' => $parent->id,
            'name' => 'Estrategia',
        ], $user);

        $this->assertSame('categoria', $validated['item_type']);
    }

    public function test_store_personal_service_with_categoria_fork_parent_passes(): void
    {
        $user = User::factory()->create();
        [, $categoria] = $this->makeCatalog();
        $parent = $this->categoriaFork($user, $categoria);

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $parent->id,
            'title' => 'API',
            'value' => 100,
        ], $user);

        $this->assertSame('API', $validated['title']);
    }

    public function test_store_parent_owned_by_another_user_is_rejected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        [, $categoria] = $this->makeCatalog();
        $otherParent = $this->categoriaFork($other, $categoria);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $otherParent->id,
            'title' => 'X',
            'value' => 100,
        ], $user, 'parent_fork_id');
    }

    public function test_store_forked_categoria_with_rubro_fork_parent_passes(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria] = $this->makeCatalog();
        $parent = $this->rubroFork($user, $rubro);

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'categoria',
            'base_id' => $categoria->id,
            'parent_fork_id' => $parent->id,
        ], $user);

        $this->assertSame($parent->id, $validated['parent_fork_id']);
    }

    // --- R4/S4.1: tags whitelist ------------------------------------------

    public function test_store_service_tags_whitelist_passes(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();

        $validated = $this->validated(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
            'tags' => ['frontend', 'backend'],
        ], $user);

        $this->assertSame(['frontend', 'backend'], $validated['tags']);
    }

    public function test_store_service_tags_outside_whitelist_fails(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
            'tags' => ['ai'],
        ], $user, 'tags.0');
    }

    // --- R4/S4.2: status forbidden, unknown keys fail, value >= 0 ---------

    public function test_store_status_key_is_rejected(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
            'status' => 'activo',
        ], $user, 'status');
    }

    public function test_store_unknown_top_level_key_is_rejected(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'rubro',
            'base_id' => $rubro->id,
            'foo' => 'bar',
        ], $user, 'foo');
    }

    public function test_store_negative_value_is_rejected(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'base_id' => $service->id,
            'value' => -5,
        ], $user, 'value');
    }

    public function test_store_personal_service_without_value_is_rejected(): void
    {
        $user = User::factory()->create();
        [, $categoria] = $this->makeCatalog();
        $parent = $this->categoriaFork($user, $categoria);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $parent->id,
            'title' => 'API',
        ], $user, 'value');
    }

    public function test_store_personal_service_without_title_is_rejected(): void
    {
        $user = User::factory()->create();
        [, $categoria] = $this->makeCatalog();
        $parent = $this->categoriaFork($user, $categoria);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'service',
            'parent_fork_id' => $parent->id,
            'value' => 100,
        ], $user, 'title');
    }

    public function test_store_personal_rubro_without_name_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'rubro',
        ], $user, 'name');
    }

    public function test_store_personal_categoria_without_name_is_rejected(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();
        $parent = $this->rubroFork($user, $rubro);

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'categoria',
            'parent_fork_id' => $parent->id,
        ], $user, 'name');
    }

    public function test_store_personal_categoria_without_parent_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->assertRequestFails(StoreUserCatalogItemRequest::class, [
            'item_type' => 'categoria',
            'name' => 'Web',
        ], $user, 'parent_fork_id');
    }

    // --- Store authorization ---------------------------------------------

    public function test_store_authenticated_user_is_authorized(): void
    {
        $user = User::factory()->create();

        $request = $this->makeRequest(StoreUserCatalogItemRequest::class, ['item_type' => 'rubro'], $user);

        $this->assertTrue($request->authorize());
    }

    public function test_store_guest_is_denied(): void
    {
        $request = $this->makeRequest(StoreUserCatalogItemRequest::class, ['item_type' => 'rubro'], null);

        $this->assertFalse($request->authorize());
    }

    // --- Update: authorization + per-field override semantics -------------

    public function test_update_owner_is_authorized(): void
    {
        $user = User::factory()->create();
        $item = $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100]);

        $request = $this->makeRequest(UpdateUserCatalogItemRequest::class, [], $user, $item, 'PUT');

        $this->assertTrue($request->authorize());
    }

    public function test_update_non_owner_is_denied(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $item = $this->personalItem($owner, 'service', ['title' => 'X', 'value' => 100]);

        $request = $this->makeRequest(UpdateUserCatalogItemRequest::class, [], $other, $item, 'PUT');

        $this->assertFalse($request->authorize());
    }

    public function test_update_guest_is_denied(): void
    {
        $owner = User::factory()->create();
        $item = $this->personalItem($owner, 'service', ['title' => 'X', 'value' => 100]);

        $request = $this->makeRequest(UpdateUserCatalogItemRequest::class, [], null, $item, 'PUT');

        $this->assertFalse($request->authorize());
    }

    public function test_update_omitted_field_keeps_existing_override(): void
    {
        $user = User::factory()->create();
        $item = $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100]);

        $request = $this->makeRequest(UpdateUserCatalogItemRequest::class, [], $user, $item, 'PUT');
        $request->validateResolved();

        $this->assertSame(['title' => 'X', 'value' => 100], $request->mergedOverrides($item));
    }

    public function test_update_present_value_sets_override(): void
    {
        $user = User::factory()->create();
        $item = $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100]);

        $request = $this->makeRequest(UpdateUserCatalogItemRequest::class, ['value' => 200], $user, $item, 'PUT');
        $request->validateResolved();

        $this->assertSame(['title' => 'X', 'value' => 200], $request->mergedOverrides($item));
    }

    public function test_update_explicit_null_removes_override(): void
    {
        $user = User::factory()->create();
        $item = $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100]);

        $request = $this->makeRequest(UpdateUserCatalogItemRequest::class, ['value' => null], $user, $item, 'PUT');
        $request->validateResolved();

        $this->assertSame(['title' => 'X'], $request->mergedOverrides($item));
    }

    public function test_update_status_is_rejected(): void
    {
        $user = User::factory()->create();
        $item = $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100]);

        $this->assertRequestFails(UpdateUserCatalogItemRequest::class, ['status' => 'activo'], $user, 'status', $item, 'PUT');
    }

    public function test_update_unknown_key_is_rejected(): void
    {
        $user = User::factory()->create();
        $item = $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100]);

        $this->assertRequestFails(UpdateUserCatalogItemRequest::class, ['foo' => 'bar'], $user, 'foo', $item, 'PUT');
    }

    public function test_update_personal_service_renamed_to_duplicate_sibling_title_is_rejected(): void
    {
        $user = User::factory()->create();
        [, $categoria] = $this->makeCatalog();
        $parent = $this->categoriaFork($user, $categoria);
        $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100], $parent);
        $item = $this->personalItem($user, 'service', ['title' => 'Y', 'value' => 100], $parent);

        $this->assertRequestFails(UpdateUserCatalogItemRequest::class, ['title' => 'X'], $user, 'title', $item, 'PUT');
    }

    public function test_update_personal_service_keeping_own_title_passes(): void
    {
        $user = User::factory()->create();
        [, $categoria] = $this->makeCatalog();
        $parent = $this->categoriaFork($user, $categoria);
        $item = $this->personalItem($user, 'service', ['title' => 'X', 'value' => 100], $parent);

        $request = $this->makeRequest(UpdateUserCatalogItemRequest::class, ['title' => 'X'], $user, $item, 'PUT');
        $request->validateResolved();

        $this->assertSame(['title' => 'X', 'value' => 100], $request->mergedOverrides($item));
    }
}

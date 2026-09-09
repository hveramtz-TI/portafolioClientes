<?php

namespace Tests\Feature;

use App\Http\Requests\UpdateUserCatalogItemRequest;
use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use App\Models\User;
use App\Models\UserCatalogItem;
use App\Services\CatalogResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * R5 per-field inheritance + origin and R6 effective status (S5.1–S5.3,
 * S6.1–S6.3) for the CatalogResolver domain engine (D-4). Database-backed,
 * so it lives in tests/Feature per the slice-3a convention.
 */
class CatalogResolverTest extends TestCase
{
    use RefreshDatabase;

    private CatalogResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new CatalogResolver;
    }

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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function serviceFork(User $owner, Service $base, ?UserCatalogItem $parent = null, array $overrides = []): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => 'service',
            'base_id' => $base->id,
            'parent_fork_id' => $parent?->id,
            'overrides' => $overrides,
        ]);
    }

    /**
     * Create a personal (base_id null) item directly, as the engine would.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function personalItem(User $owner, string $type, array $overrides, ?UserCatalogItem $parent = null, string $status = 'activo'): UserCatalogItem
    {
        return UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => $type,
            'base_id' => null,
            'parent_fork_id' => $parent?->id,
            'overrides' => $overrides,
            'status' => $status,
        ]);
    }

    /**
     * Instantiate a FormRequest the way route resolution would (same pattern
     * as the slice-3a request tests) so S5.3 exercises the real
     * UpdateUserCatalogItemRequest::mergedOverrides() contract.
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
     * Run resolve() with a fresh (not preloaded) item and count the queries
     * the resolver itself issues.
     */
    private function countResolveQueries(UserCatalogItem $item): int
    {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->resolver->resolve($item);

        return $queries;
    }

    // --- R5/S5.1: base edit propagates -----------------------------------

    public function test_s5_1_service_fork_reflects_live_base_edit(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $service->update(['value' => 300000]);
        $fork = $this->serviceFork($user, $service);

        // Admin edits the base directly; the fork has no overrides (R5: live
        // base read, no re-sync).
        $service->update(['value' => 320000]);

        $resolved = $this->resolver->resolve($fork);

        $this->assertSame(320000, $resolved['value']);
        $this->assertSame('API', $resolved['title']);
        $this->assertSame($fork->id, $resolved['id']);
        $this->assertSame($service->id, $resolved['base_id']);
        $this->assertSame('base', $resolved['origin']);
        $this->assertSame([], $resolved['overridden_fields']);
    }

    // --- R5/S5.2: override wins, is listed, origin override ----------------

    public function test_s5_2_override_wins_and_is_listed(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $service->update(['value' => 300000]);
        $fork = $this->serviceFork($user, $service, null, ['value' => 350000]);

        // Base is edited underneath; the override must keep winning.
        $service->update(['value' => 320000]);

        $resolved = $this->resolver->resolve($fork);

        $this->assertSame(350000, $resolved['value']);
        $this->assertSame('API', $resolved['title']);
        $this->assertSame('override', $resolved['origin']);
        $this->assertSame(['value'], $resolved['overridden_fields']);
    }

    // --- R5/S5.3: explicit null restores inheritance (integration) ---------

    public function test_s5_3_null_override_restores_inheritance(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $service->update(['value' => 300000]);
        $fork = $this->serviceFork($user, $service, null, ['value' => 350000]);

        // User clears the override: mergedOverrides removes the key (S5.3).
        $request = $this->makeRequest(UpdateUserCatalogItemRequest::class, ['value' => null], $user, $fork, 'PUT');
        $request->validateResolved();
        $fork->update(['overrides' => $request->mergedOverrides($fork)]);

        $resolved = $this->resolver->resolve($fork);

        $this->assertSame([], $request->mergedOverrides($fork));
        $this->assertSame(300000, $resolved['value']);
        $this->assertSame('base', $resolved['origin']);
        $this->assertSame([], $resolved['overridden_fields']);
    }

    // --- R5 origin matrix ---------------------------------------------------

    public function test_origin_matches_personal_override_and_base_states(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();

        $personal = $this->personalItem($user, 'service', ['title' => 'Mío', 'value' => 50]);
        $override = $this->serviceFork($user, $service, null, ['title' => 'Mío']);
        $base = $this->serviceFork($user, $service);

        $this->assertSame('personal', $this->resolver->origin($personal));
        $this->assertSame('override', $this->resolver->origin($override));
        $this->assertSame('base', $this->resolver->origin($base));
    }

    // --- R6/S6.1: deactivated ancestor fork --------------------------------

    public function test_s6_1_deactivated_parent_fork_resolves_desactivado(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria] = $this->makeCatalog();
        $rubroFork = $this->rubroFork($user, $rubro);
        $categoriaFork = $this->categoriaFork($user, $categoria, $rubroFork);
        $rubroFork->update(['status' => 'desactivado']);

        $this->assertSame('desactivado', $this->resolver->effectiveStatus($categoriaFork));
        $this->assertSame('desactivado', $this->resolver->resolve($categoriaFork)['status']);
    }

    public function test_s6_1_deactivated_grandparent_fork_resolves_desactivado(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria, $service] = $this->makeCatalog();
        $rubroFork = $this->rubroFork($user, $rubro);
        $categoriaFork = $this->categoriaFork($user, $categoria, $rubroFork);
        $serviceFork = $this->serviceFork($user, $service, $categoriaFork);
        $rubroFork->update(['status' => 'desactivado']);

        // The service fork and its direct parent are activo; the grandparent
        // rubro fork is not (depth-3 chain).
        $this->assertSame('desactivado', $this->resolver->effectiveStatus($serviceFork));
        $this->assertSame('desactivado', $this->resolver->resolve($serviceFork)['status']);
    }

    // --- R6/S6.2: deactivated base ------------------------------------------

    public function test_s6_2_deactivated_service_base_resolves_desactivado(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $fork = $this->serviceFork($user, $service);
        $service->update(['status' => 'desactivado']);

        $this->assertSame('desactivado', $this->resolver->effectiveStatus($fork));
        $this->assertSame('desactivado', $this->resolver->resolve($fork)['status']);
    }

    public function test_s6_2_deactivated_rubro_base_resolves_desactivado(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Diseño']);
        $fork = $this->rubroFork($user, $rubro);
        $rubro->update(['status' => 'desactivado']);

        $this->assertSame('desactivado', $this->resolver->resolve($fork)['status']);
    }

    // --- R6/S6.3: whole chain active ----------------------------------------

    public function test_s6_3_full_active_chain_resolves_activo(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria, $service] = $this->makeCatalog();
        $rubroFork = $this->rubroFork($user, $rubro);
        $categoriaFork = $this->categoriaFork($user, $categoria, $rubroFork);
        $serviceFork = $this->serviceFork($user, $service, $categoriaFork);

        $this->assertSame('activo', $this->resolver->effectiveStatus($serviceFork));
        $this->assertSame('activo', $this->resolver->resolve($serviceFork)['status']);
    }

    public function test_s6_3_personal_item_own_status_decides(): void
    {
        $user = User::factory()->create();
        $activo = $this->personalItem($user, 'service', ['title' => 'A', 'value' => 100]);
        $desactivado = $this->personalItem($user, 'service', ['title' => 'B', 'value' => 100], null, 'desactivado');

        $this->assertSame('activo', $this->resolver->effectiveStatus($activo));
        $this->assertSame('desactivado', $this->resolver->effectiveStatus($desactivado));

        // Personal items resolve from overrides only; missing fields are null.
        $resolved = $this->resolver->resolve($activo);
        $this->assertSame('personal', $resolved['origin']);
        $this->assertSame('A', $resolved['title']);
        $this->assertSame(100, $resolved['value']);
        $this->assertNull($resolved['description']);
        $this->assertNull($resolved['base_id']);
        $this->assertSame(['title', 'value'], $resolved['overridden_fields']);
        $this->assertSame('activo', $resolved['status']);
    }

    // --- R6/S6.4: deactivated base cascades through ancestor forks ----------

    public function test_s6_4_deactivated_rubro_base_cascades_to_deep_fork(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria, $service] = $this->makeCatalog();
        $rubro->update(['status' => 'desactivado']);

        // The user forks the whole tree; every fork's OWN status stays activo.
        $rubroFork = $this->rubroFork($user, $rubro);
        $categoriaFork = $this->categoriaFork($user, $categoria, $rubroFork);
        $serviceFork = $this->serviceFork($user, $service, $categoriaFork);

        // fresh(): the DB column default is 'activo' — create() does not pull
        // defaults back into the in-memory model.
        $this->assertSame('activo', $serviceFork->fresh()->status);

        // The rubro fork is effectively desactivado through its base, and the
        // cascade reaches the service fork two levels below (recursive AND).
        $this->assertSame('desactivado', $this->resolver->effectiveStatus($rubroFork));
        $this->assertSame('desactivado', $this->resolver->effectiveStatus($categoriaFork));
        $this->assertSame('desactivado', $this->resolver->effectiveStatus($serviceFork));
        $this->assertSame('desactivado', $this->resolver->resolve($serviceFork)['status']);
    }

    public function test_s6_4_deactivated_categoria_base_cascades_to_service_fork(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria, $service] = $this->makeCatalog();
        $categoria->update(['status' => 'desactivado']);

        $rubroFork = $this->rubroFork($user, $rubro);
        $categoriaFork = $this->categoriaFork($user, $categoria, $rubroFork);
        $serviceFork = $this->serviceFork($user, $service, $categoriaFork);

        // Base-in-the-middle: the service fork's own base is activo and the
        // categoria fork's own status is activo — only its BASE is not.
        $this->assertSame('desactivado', $this->resolver->effectiveStatus($serviceFork));
        $this->assertSame('desactivado', $this->resolver->resolve($serviceFork)['status']);
    }

    // --- R6/S6 fail-safe: unresolvable base / ancestor (J2) -----------------

    /**
     * 'activo' is only provable when the WHOLE chain resolves active (S6.4
     * full-chain AND). A fork whose non-null base_id points at no live base
     * row (soft-deleted or missing) can never prove that: it must resolve
     * 'desactivado', with inherited fields resolving null (J2).
     */
    public function test_fork_with_missing_base_resolves_desactivado(): void
    {
        $user = User::factory()->create();

        // base_id is a well-formed UUID with no row behind it.
        $fork = UserCatalogItem::create([
            'user_id' => $user->id,
            'item_type' => 'service',
            'base_id' => (string) Str::uuid(),
            'overrides' => ['title' => 'Mío'],
        ]);

        $this->assertSame('desactivado', $this->resolver->effectiveStatus($fork));

        $resolved = $this->resolver->resolve($fork);
        $this->assertSame('desactivado', $resolved['status']);
        // Overrides still resolve; base-inherited fields are null.
        $this->assertSame('Mío', $resolved['title']);
        $this->assertNull($resolved['value']);
        $this->assertNull($resolved['description']);
    }

    public function test_fork_with_soft_deleted_base_resolves_desactivado(): void
    {
        $user = User::factory()->create();
        [, , $service] = $this->makeCatalog();
        $fork = $this->serviceFork($user, $service);
        $service->delete(); // trashed: base() no longer resolves

        $this->assertSame('desactivado', $this->resolver->effectiveStatus($fork));
        $this->assertSame('desactivado', $this->resolver->resolve($fork)['status']);
    }

    /**
     * Same fail-safe one level up: a non-null parent_fork_id whose fork chain
     * no longer resolves (ancestor deleted) is not provably active either.
     */
    public function test_child_of_deleted_parent_fork_resolves_desactivado(): void
    {
        $user = User::factory()->create();
        [$rubro, $categoria, $service] = $this->makeCatalog();
        $rubroFork = $this->rubroFork($user, $rubro);
        $categoriaFork = $this->categoriaFork($user, $categoria, $rubroFork);
        $serviceFork = $this->serviceFork($user, $service, $categoriaFork);
        $categoriaFork->delete(); // child keeps a dangling parent_fork_id

        // Recursive AND: both the direct child and, through it, the grandchild
        // fail safe to desactivado while their own status stays 'activo'.
        $this->assertSame('desactivado', $this->resolver->effectiveStatus($serviceFork));
        $this->assertSame('desactivado', $this->resolver->resolve($serviceFork)['status']);
        $this->assertSame('activo', $serviceFork->fresh()->status);
    }

    // --- D-4 N+1 guard --------------------------------------------------------

    public function test_resolve_over_preloaded_chain_adds_no_queries(): void
    {
        $user = User::factory()->create();

        // Five independent depth-3 trees: service fork → categoria fork → rubro fork.
        $ids = [];
        for ($i = 0; $i < 5; $i++) {
            $rubro = Rubro::create(['name' => "Rubro {$i}"]);
            $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => "Categoria {$i}"]);
            $service = Service::create(['categoria_id' => $categoria->id, 'title' => "Service {$i}", 'value' => 100]);
            $rubroFork = $this->rubroFork($user, $rubro);
            $categoriaFork = $this->categoriaFork($user, $categoria, $rubroFork);
            $ids[] = $this->serviceFork($user, $service, $categoriaFork)->id;
        }

        // Preload the whole resolution graph the way a collection consumer
        // would (D-4: base + parentFork chain + each ancestor's base, which
        // the recursive effective status reads).
        $preloaded = UserCatalogItem::query()
            ->with(['base', 'parentFork.base', 'parentFork.parentFork.base', 'parentFork.parentFork.parentFork'])
            ->whereKey($ids)
            ->get();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        foreach ($preloaded as $item) {
            $resolved = $this->resolver->resolve($item);
            $this->assertSame('activo', $resolved['status']);
            $this->assertSame(100, $resolved['value']);
        }

        // Resolving every preloaded item issues zero additional queries: the
        // resolver reuses the loaded graph instead of lazy-loading per level.
        $this->assertSame(0, $queries);
    }

    public function test_resolve_of_fresh_item_has_bounded_eager_queries(): void
    {
        $user = User::factory()->create();

        // Personal item: no base, no parent → resolution needs no queries.
        $personal = $this->personalItem($user, 'service', ['title' => 'A', 'value' => 100]);
        $this->assertSame(0, $this->countResolveQueries($personal->fresh()));

        // Depth-2 chain: categoria fork → rubro fork.
        [$rubro, $categoria] = $this->makeCatalog();
        $rubroFork = $this->rubroFork($user, $rubro);
        $categoriaFork = $this->categoriaFork($user, $categoria, $rubroFork);
        $depth2Queries = $this->countResolveQueries($categoriaFork->fresh());

        // Depth-3 chain: service fork → categoria fork → rubro fork.
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'API Pro', 'value' => 100]);
        $serviceFork = $this->serviceFork($user, $service, $categoriaFork);
        $depth3Queries = $this->countResolveQueries($serviceFork->fresh());

        // The eager budget is constant per chain level: base + one query per
        // fork level + one per ancestor base (read by the recursive effective
        // status), so a deeper chain adds at most one query per level instead
        // of an N+1 blow-up per ancestor.
        $this->assertLessThanOrEqual(3, $depth2Queries);
        $this->assertLessThanOrEqual(5, $depth3Queries);
        $this->assertLessThanOrEqual($depth2Queries + 2, $depth3Queries);
    }
}

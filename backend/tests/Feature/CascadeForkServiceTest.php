<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use App\Models\User;
use App\Models\UserCatalogItem;
use App\Services\CascadeForkService;
use App\Services\CatalogResolver;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * R7 cascade fork (S7.1–S7.4) for the CascadeForkService domain engine (D-5):
 * atomic rubro/categoria/service fork trees with correct parent/base links,
 * own status 'activo', empty overrides, mid-copy rollback, standalone
 * categoria forks, and R6 resolution of deactivated descendants. Database-
 * backed, so it lives in tests/Feature per the slice-3a/3b convention.
 */
class CascadeForkServiceTest extends TestCase
{
    use RefreshDatabase;

    private CascadeForkService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CascadeForkService;
    }

    /**
     * Create the base catalog a fork can point at: one rubro, three
     * categorias (each with two services → six services total).
     *
     * @return array{0: Rubro, 1: Categoria[], 2: Service[]}
     */
    private function makeCatalog(): array
    {
        $rubro = Rubro::create(['name' => 'Informática']);

        $categorias = [];
        $services = [];

        foreach (['Web', 'Mobile', 'DevOps'] as $index => $name) {
            $categoria = Categoria::create([
                'rubro_id' => $rubro->id,
                'name' => $name,
                'order' => $index,
            ]);
            $categorias[] = $categoria;

            foreach (['API', 'Frontend'] as $title) {
                $services[] = Service::create([
                    'categoria_id' => $categoria->id,
                    'title' => $title,
                    'value' => 100,
                ]);
            }
        }

        return [$rubro, $categorias, $services];
    }

    // --- R7/S7.1: full rubro cascade tree -----------------------------------

    public function test_s7_1_rubro_cascade_creates_full_tree_with_links(): void
    {
        $user = User::factory()->create();
        [$rubro, $categorias, $services] = $this->makeCatalog();

        $summary = $this->service->forkRubro($rubro->id, $user);

        $this->assertDatabaseCount('user_catalog_items', 10);

        // Rubro fork: root of the tree, no parent, positional sort_order 0.
        $rubroFork = UserCatalogItem::where('user_id', $user->id)->where('item_type', 'rubro')->firstOrFail();
        $this->assertSame($rubro->id, $rubroFork->base_id);
        $this->assertNull($rubroFork->parent_fork_id);
        $this->assertSame([], $rubroFork->overrides);
        $this->assertSame('activo', $rubroFork->fresh()->status);
        $this->assertSame(0, $rubroFork->sort_order);

        // Categoria forks: one per base categoria, children of the rubro fork,
        // sort_order carried from the base `order` column.
        $categoriaForks = UserCatalogItem::where('user_id', $user->id)->where('item_type', 'categoria')->get()->keyBy('base_id');
        $this->assertCount(3, $categoriaForks);
        foreach ($categorias as $categoria) {
            $fork = $categoriaForks[$categoria->id];
            $this->assertSame($rubroFork->id, $fork->parent_fork_id);
            $this->assertSame([], $fork->overrides);
            $this->assertSame('activo', $fork->fresh()->status);
            $this->assertSame($categoria->order, $fork->sort_order);
        }

        // Service forks: six total, each a child of its categoria fork.
        $serviceForks = UserCatalogItem::where('user_id', $user->id)->where('item_type', 'service')->get()->keyBy('base_id');
        $this->assertCount(6, $serviceForks);
        foreach ($services as $service) {
            $fork = $serviceForks[$service->id];
            $this->assertSame($categoriaForks[$service->categoria_id]->id, $fork->parent_fork_id);
            $this->assertSame([], $fork->overrides);
            $this->assertSame('activo', $fork->fresh()->status);
        }

        // Positional sort_order: services have no order column, so each
        // categoria's service forks hold exactly 0..n-1 once each.
        $byParent = $serviceForks->groupBy('parent_fork_id');
        foreach ($categoriaForks as $categoriaFork) {
            $orders = $byParent->get($categoriaFork->id)->pluck('sort_order')->sort()->values()->all();
            $this->assertSame([0, 1], $orders);
        }

        // Tree summary: counts + created ids.
        $this->assertSame($rubroFork->id, $summary['rubro']);
        $this->assertSame(3, $summary['categorias']['count']);
        $this->assertCount(3, $summary['categorias']['ids']);
        $this->assertSame(6, $summary['services']['count']);
        $this->assertCount(6, $summary['services']['ids']);
    }

    // --- R7/S7.2: mid-copy failure rolls everything back --------------------

    public function test_s7_2_mid_copy_failure_rolls_back_everything(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();

        // Force a failure on the 3rd UserCatalogItem create (rubro fork + two
        // categoria forks already inserted inside the transaction).
        $creates = 0;
        UserCatalogItem::creating(function () use (&$creates): void {
            $creates++;
            if ($creates === 3) {
                throw new RuntimeException('forced mid-copy failure');
            }
        });

        try {
            $this->service->forkRubro($rubro->id, $user);
            $this->fail('Mid-copy failure should propagate, not be swallowed.');
        } catch (RuntimeException $e) {
            $this->assertSame('forced mid-copy failure', $e->getMessage());
        }

        // Zero fork rows persist; the base catalog is untouched.
        $this->assertDatabaseCount('user_catalog_items', 0);
        $this->assertDatabaseCount('rubros', 1);
        $this->assertDatabaseCount('categorias', 3);
        $this->assertDatabaseCount('services', 6);
    }

    // --- R7/S7.3: standalone categoria cascade ------------------------------

    public function test_s7_3_standalone_categoria_cascade_copies_services(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web', 'order' => 2]);
        foreach (['API', 'Frontend', 'Testing'] as $title) {
            Service::create(['categoria_id' => $categoria->id, 'title' => $title, 'value' => 100]);
        }

        $summary = $this->service->forkCategoria($categoria->id, $user);

        $this->assertDatabaseCount('user_catalog_items', 4);

        // Rubro context is NOT auto-forked (flujo: categoria fork standalone).
        $this->assertSame(0, UserCatalogItem::where('user_id', $user->id)->where('item_type', 'rubro')->count());

        $categoriaFork = UserCatalogItem::where('user_id', $user->id)->where('item_type', 'categoria')->firstOrFail();
        $this->assertSame($categoria->id, $categoriaFork->base_id);
        // No rubro fork to attach to: parent stays null (documented assumption
        // for Slice 4, where the categoria fork is linked via Update).
        $this->assertNull($categoriaFork->parent_fork_id);
        $this->assertSame([], $categoriaFork->overrides);
        $this->assertSame('activo', $categoriaFork->fresh()->status);
        $this->assertSame($categoria->order, $categoriaFork->sort_order);

        $serviceForks = UserCatalogItem::where('user_id', $user->id)->where('item_type', 'service')->get();
        $this->assertCount(3, $serviceForks);
        foreach ($serviceForks as $fork) {
            $this->assertSame($categoriaFork->id, $fork->parent_fork_id);
            $this->assertSame([], $fork->overrides);
            $this->assertSame('activo', $fork->fresh()->status);
        }

        $this->assertSame($categoriaFork->id, $summary['categoria']);
        $this->assertSame(3, $summary['services']['count']);
        $this->assertCount(3, $summary['services']['ids']);
    }

    // --- R7/S7.4: deactivated descendants copied, resolve via R6 ------------

    public function test_s7_4_deactivated_descendants_copied_and_resolve_desactivado(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Diseño']);
        $activa = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Branding', 'order' => 0]);
        $desactivada = Categoria::create([
            'rubro_id' => $rubro->id,
            'name' => 'Fotografía',
            'order' => 1,
            'status' => 'desactivado',
        ]);
        $serviceActivo = Service::create(['categoria_id' => $activa->id, 'title' => 'Logo', 'value' => 100]);
        $serviceDesactivado = Service::create(['categoria_id' => $desactivada->id, 'title' => 'Fotos', 'value' => 200]);

        $this->service->forkRubro($rubro->id, $user);

        // 1 rubro + 2 categorias + 2 services, copied REGARDLESS of status.
        $this->assertDatabaseCount('user_catalog_items', 5);
        $this->assertSame(
            5,
            UserCatalogItem::where('user_id', $user->id)->where('status', 'activo')->count()
        );

        // Resolve with the D-4 preload contract (base + parentFork chain +
        // each ancestor's base) exactly as a collection consumer would.
        $items = UserCatalogItem::query()
            ->where('user_id', $user->id)
            ->with(['base', 'parentFork.base', 'parentFork.parentFork.base', 'parentFork.parentFork.parentFork'])
            ->get();

        $resolver = new CatalogResolver;
        $resolved = [];
        foreach ($items as $item) {
            $resolved[$item->item_type.':'.$item->base_id] = $resolver->resolve($item)['status'];
        }

        // Active branch resolves activo; the deactivated categoria and its
        // service fork resolve desactivado through R6 (own status stays
        // activo; the base / ancestor chain deactivates them).
        $this->assertSame('activo', $resolved["rubro:{$rubro->id}"]);
        $this->assertSame('activo', $resolved["categoria:{$activa->id}"]);
        $this->assertSame('activo', $resolved["service:{$serviceActivo->id}"]);
        $this->assertSame('desactivado', $resolved["categoria:{$desactivada->id}"]);
        $this->assertSame('desactivado', $resolved["service:{$serviceDesactivado->id}"]);

        // The fork of the deactivated categoria is independently reactivable:
        // its OWN status is activo; only the base deactivates it (D7).
        $categoriaFork = UserCatalogItem::where('user_id', $user->id)
            ->where('item_type', 'categoria')
            ->where('base_id', $desactivada->id)
            ->firstOrFail();
        $this->assertSame('activo', $categoriaFork->fresh()->status);
    }

    // --- R3 identity guard (D-5: checked inside the transaction) -------------

    public function test_fork_rubro_twice_for_same_user_is_rejected(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();

        $this->service->forkRubro($rubro->id, $user);

        try {
            $this->service->forkRubro($rubro->id, $user);
            $this->fail('Duplicate fork should be rejected.');
        } catch (DomainException) {
            // Identity is a domain rule, not validation: expect the domain signal.
        }

        // The original tree is untouched.
        $this->assertDatabaseCount('user_catalog_items', 10);
    }

    public function test_fork_against_soft_deleted_prior_fork_is_allowed(): void
    {
        $user = User::factory()->create();
        [$rubro] = $this->makeCatalog();

        $first = $this->service->forkRubro($rubro->id, $user);

        // User deletes their fork tree (soft delete); identity must not block
        // re-forking the same base (non-trashed only). The WHOLE tree must go:
        // leaving non-trashed descendants would make the cascade a duplicate
        // (R3/S3.1 applies to every copied row — see the descendant tests).
        UserCatalogItem::where('user_id', $user->id)->delete();

        $second = $this->service->forkRubro($rubro->id, $user);

        $this->assertNotSame($first['rubro'], $second['rubro']);
        $this->assertSame(
            1,
            UserCatalogItem::where('user_id', $user->id)
                ->where('item_type', 'rubro')
                ->where('base_id', $rubro->id)
                ->count()
        );
        $this->assertSame(20, UserCatalogItem::withTrashed()->where('user_id', $user->id)->count());
    }

    // --- R3 identity guard for CASCADE DESCENDANTS (J1) ---------------------

    /**
     * A cascade must not copy a descendant the user already forked standalone
     * (R3/S3.1 applies to EVERY row written, not just the top-level one):
     * the whole transaction must abort with the same DomainException signal
     * and leave zero new rows (S7.2-style rollback).
     */
    public function test_fork_rubro_rejected_when_descendant_categoria_already_forked_standalone(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        Service::create(['categoria_id' => $categoria->id, 'title' => 'API', 'value' => 100]);

        // Standalone categoria fork: 1 categoria + 1 service rows.
        $this->service->forkCategoria($categoria->id, $user);
        $this->assertDatabaseCount('user_catalog_items', 2);

        try {
            // Cascading the parent rubro would copy the same categoria again.
            $this->service->forkRubro($rubro->id, $user);
            $this->fail('Duplicate descendant fork should be rejected.');
        } catch (DomainException) {
            // Same domain signal as a top-level duplicate.
        }

        // Full rollback: only the 2 standalone rows persist.
        $this->assertDatabaseCount('user_catalog_items', 2);
        $this->assertSame(0, UserCatalogItem::where('item_type', 'rubro')->count());
    }

    public function test_fork_rubro_rejected_when_descendant_service_already_forked_standalone(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'API', 'value' => 100]);

        // Standalone service fork: 1 row; no categoria fork exists.
        $this->service->forkService($service->id, $user);
        $this->assertDatabaseCount('user_catalog_items', 1);

        try {
            $this->service->forkRubro($rubro->id, $user);
            $this->fail('Duplicate descendant service fork should be rejected.');
        } catch (DomainException) {
            // Identity violation is a domain signal.
        }

        // Rubro + categoria forks created before the clash are rolled back too.
        $this->assertDatabaseCount('user_catalog_items', 1);
    }

    public function test_fork_categoria_rejected_when_descendant_service_already_forked_standalone(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'API', 'value' => 100]);

        // Standalone service fork: 1 row; the categoria itself is NOT forked.
        $this->service->forkService($service->id, $user);
        $this->assertDatabaseCount('user_catalog_items', 1);

        try {
            // Top-level categoria identity passes; the service descendant clashes.
            $this->service->forkCategoria($categoria->id, $user);
            $this->fail('Duplicate descendant service fork should be rejected.');
        } catch (DomainException) {
            // Identity violation is a domain signal.
        }

        // The categoria fork created before the clash is rolled back.
        $this->assertDatabaseCount('user_catalog_items', 1);
    }

    // --- R7: forkService single item ------------------------------------------

    public function test_fork_service_creates_single_item_with_null_parent(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Informática']);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        $service = Service::create(['categoria_id' => $categoria->id, 'title' => 'API', 'value' => 100]);

        $summary = $this->service->forkService($service->id, $user);

        $this->assertDatabaseCount('user_catalog_items', 1);
        $fork = UserCatalogItem::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('service', $fork->item_type);
        $this->assertSame($service->id, $fork->base_id);
        // Attachment (parent_fork_id) happens via Update in Slice 4; a
        // standalone service fork has no parent yet (documented assumption).
        $this->assertNull($fork->parent_fork_id);
        $this->assertSame([], $fork->overrides);
        $this->assertSame('activo', $fork->fresh()->status);
        $this->assertSame(0, $fork->sort_order);
        $this->assertSame($fork->id, $summary['service']);
    }

    // --- Not-found signals (missing / trashed base) ---------------------------

    public function test_fork_rubro_missing_base_is_not_found(): void
    {
        $user = User::factory()->create();

        try {
            $this->service->forkRubro((string) Str::uuid(), $user);
            $this->fail('Missing base should throw ModelNotFoundException.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('user_catalog_items', 0);
        }
    }

    public function test_fork_rubro_trashed_base_is_not_found(): void
    {
        $user = User::factory()->create();
        $rubro = Rubro::create(['name' => 'Descartado']);
        $rubro->delete(); // soft delete

        try {
            $this->service->forkRubro($rubro->id, $user);
            $this->fail('Trashed base should throw ModelNotFoundException.');
        } catch (ModelNotFoundException) {
            // The trashed base row survives; nothing was forked from it.
            $this->assertDatabaseCount('rubros', 1);
            $this->assertDatabaseCount('user_catalog_items', 0);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use App\Models\User;
use App\Models\UserCatalogItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Unit 2 HTTP surface: R4 cascade soft-delete (S4.1-S4.4), R5 move/attach
 * (S5.1-S5.8), R6 tree/list (S6.1-S6.5) and the amended-D5 DB identity guard
 * (S8.1-S8.2, direct insert). Dual-engine: SQLite by default, PostgreSQL via
 * ./test-pg.sh.
 */
class UserCatalogTreeMoveTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function owner(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Create a base rubro. `$servicesPerCategoria` is keyed by categoria
     * `order`; the value is how many services that categoria holds. Example:
     * [2, 1] → categoria order 0 with 2 services, order 1 with 1 service.
     *
     * @param  array<int, int>  $servicesPerCategoria
     */
    private function baseRubro(array $servicesPerCategoria): Rubro
    {
        $rubro = Rubro::create(['name' => 'Rubro '.$this->seq++]);

        foreach ($servicesPerCategoria as $order => $serviceCount) {
            $categoria = Categoria::create([
                'rubro_id' => $rubro->id,
                'name' => "Categoria {$order}",
                'order' => $order,
            ]);

            for ($s = 0; $s < $serviceCount; $s++) {
                Service::create([
                    'categoria_id' => $categoria->id,
                    'title' => "Service {$order}-{$s}",
                    'value' => 100,
                ]);
            }
        }

        return $rubro;
    }

    /**
     * Fork a base rubro through the real cascade endpoint and return the root
     * fork id.
     */
    private function forkRubro(Rubro $rubro): string
    {
        return $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork")
            ->assertCreated()
            ->json('id');
    }

    /**
     * Create an owner-scoped item directly, as a crafted fixture.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function item(
        User $owner,
        string $type,
        ?string $baseId,
        ?UserCatalogItem $parent = null,
        array $overrides = [],
        string $status = 'activo',
    ): UserCatalogItem {
        return UserCatalogItem::create([
            'user_id' => $owner->id,
            'item_type' => $type,
            'base_id' => $baseId,
            'parent_fork_id' => $parent?->id,
            'overrides' => $overrides,
            'status' => $status,
        ]);
    }

    /**
     * Flatten a nested tree payload into a flat id => node map.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $nodes): array
    {
        $flat = [];

        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);
            $flat[] = $node;
            $flat = array_merge($flat, $this->flatten($children));
        }

        return $flat;
    }

    /**
     * Find a node by id inside a nested tree payload.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<string, mixed>|null
     */
    private function findNode(array $nodes, string $id): ?array
    {
        foreach ($nodes as $node) {
            if ($node['id'] === $id) {
                return $node;
            }

            $found = $this->findNode($node['children'] ?? [], $id);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Count the queries a GET /api/user-catalog/tree request executes.
     */
    private function countTreeQueries(string $query = ''): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $this->getJson('/api/user-catalog/tree'.$query)->assertOk();

        return $count;
    }

    // --- R4: cascade soft-delete (S4.1-S4.4) ------------------------------

    public function test_s4_1_cascade_delete_soft_deletes_the_whole_subtree(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([2, 1]); // rubro + 2 categorias + 3 services = 6 rows

        $rootId = $this->forkRubro($rubro);
        $this->assertSame(6, UserCatalogItem::where('user_id', $user->id)->count());

        $this->deleteJson("/api/user-catalog/rubros/{$rootId}")->assertNoContent();

        $this->assertSame(0, UserCatalogItem::where('user_id', $user->id)->count());
        $this->assertSame(6, UserCatalogItem::onlyTrashed()->where('user_id', $user->id)->count());
        $this->assertNotNull(UserCatalogItem::withTrashed()->findOrFail($rootId)->deleted_at);

        // Base rows are untouched: cascade delete only writes user_catalog_items.
        $this->assertSame(1, Rubro::whereKey($rubro->id)->count());
        $this->assertSame(2, Categoria::where('rubro_id', $rubro->id)->count());
        $this->assertSame(3, Service::whereIn('categoria_id', Categoria::where('rubro_id', $rubro->id)->pluck('id'))->count());
    }

    public function test_s4_2_cascade_delete_leaves_other_users_rows_intact(): void
    {
        $userA = User::factory()->create();
        Sanctum::actingAs($userA);
        $rubro = $this->baseRubro([1]); // rubro + 1 categoria + 1 service
        $rootId = $this->forkRubro($rubro);
        $categoriaBase = Categoria::where('rubro_id', $rubro->id)->firstOrFail();

        $userB = User::factory()->create();
        Sanctum::actingAs($userB);
        $this->postJson("/api/user-catalog/categorias/{$categoriaBase->id}/fork")->assertCreated();
        $bRows = UserCatalogItem::where('user_id', $userB->id)->count();
        $this->assertSame(2, $bRows);

        Sanctum::actingAs($userA);
        $this->deleteJson("/api/user-catalog/rubros/{$rootId}")->assertNoContent();

        $this->assertSame(0, UserCatalogItem::where('user_id', $userA->id)->count());
        $this->assertSame($bRows, UserCatalogItem::where('user_id', $userB->id)->count());
        $this->assertSame(0, UserCatalogItem::onlyTrashed()->where('user_id', $userB->id)->count());
    }

    public function test_s4_3_refork_after_cascade_delete_is_legal(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([1]); // 3 rows

        $rootId = $this->forkRubro($rubro);
        $this->deleteJson("/api/user-catalog/rubros/{$rootId}")->assertNoContent();
        $trashed = UserCatalogItem::onlyTrashed()->where('user_id', $user->id)->count();
        $this->assertSame(3, $trashed);

        $response = $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork");
        $response->assertCreated()->assertJsonCount(3, 'ids');

        $this->assertSame(3, UserCatalogItem::where('user_id', $user->id)->count());
        // The old rows stay deleted: the re-fork creates fresh rows (no restore).
        $this->assertSame($trashed, UserCatalogItem::onlyTrashed()->where('user_id', $user->id)->count());
    }

    public function test_s4_4_soft_deleted_ids_behave_as_missing(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([]);
        $rootId = $this->forkRubro($rubro);

        $this->deleteJson("/api/user-catalog/rubros/{$rootId}")->assertNoContent();

        $this->getJson("/api/user-catalog/rubros/{$rootId}")->assertNotFound();
        $this->putJson("/api/user-catalog/rubros/{$rootId}", ['name' => 'X'])->assertNotFound();
        $this->patchJson("/api/user-catalog/rubros/{$rootId}/deactivate")->assertNotFound();
        $this->deleteJson("/api/user-catalog/rubros/{$rootId}")->assertNotFound();
    }

    // --- R5: move/attach (S5.1-S5.8) --------------------------------------

    public function test_s5_1_move_service_between_categorias(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([0, 0]);
        $rootId = $this->forkRubro($rubro);

        $catForks = UserCatalogItem::where('parent_fork_id', $rootId)->orderBy('sort_order')->get();
        [$catA, $catB] = [$catForks[0], $catForks[1]];

        $baseCategoriaA = Categoria::where('rubro_id', $rubro->id)->orderBy('order')->firstOrFail();
        $serviceBase = Service::create(['categoria_id' => $baseCategoriaA->id, 'title' => 'API', 'value' => 100]);

        $service = $this->item($user, 'service', $serviceBase->id, $catA, ['title' => 'Mío']);

        $this->putJson("/api/user-catalog/services/{$service->id}", ['parent_fork_id' => $catB->id])
            ->assertOk()
            ->assertJsonPath('parent_fork_id', $catB->id);

        $service->refresh();
        $this->assertSame($catB->id, $service->parent_fork_id);
        $this->assertSame(['title' => 'Mío'], $service->overrides);

        $tree = $this->getJson('/api/user-catalog/tree')->assertOk()->json();
        $catBNode = $this->findNode($tree, $catB->id);
        $this->assertNotNull($catBNode);
        $this->assertSame([$service->id], array_column($catBNode['children'], 'id'));
    }

    public function test_s5_2_attach_orphan_categoria_to_a_rubro_fork(): void
    {
        $user = $this->owner();

        $rubroOne = $this->baseRubro([]);
        $rubroForkId = $this->forkRubro($rubroOne);

        $rubroTwo = $this->baseRubro([0]);
        $baseCategoria = Categoria::where('rubro_id', $rubroTwo->id)->firstOrFail();
        $categoriaForkId = $this->postJson("/api/user-catalog/categorias/{$baseCategoria->id}/fork")
            ->assertCreated()
            ->json('id');
        $this->assertNull(UserCatalogItem::findOrFail($categoriaForkId)->parent_fork_id);

        $this->putJson("/api/user-catalog/categorias/{$categoriaForkId}", ['parent_fork_id' => $rubroForkId])
            ->assertOk()
            ->assertJsonPath('parent_fork_id', $rubroForkId);

        $this->assertSame($rubroForkId, UserCatalogItem::findOrFail($categoriaForkId)->parent_fork_id);
    }

    public function test_s5_3_cycle_is_rejected_and_parent_unchanged(): void
    {
        $user = $this->owner();

        // (a) Self-parent is rejected.
        $rubro = $this->baseRubro([]);
        $rubroForkId = $this->forkRubro($rubro);
        $this->putJson("/api/user-catalog/rubros/{$rubroForkId}", ['parent_fork_id' => $rubroForkId])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');
        $this->assertNull(UserCatalogItem::findOrFail($rubroForkId)->parent_fork_id);

        // (b) Reparenting into a descendant is rejected. Type coherence makes a
        // real cycle unreachable through valid rows, so the descendant edge is
        // crafted directly: a categoria whose parent is the service. A move of
        // the service under that categoria is type-coherent and would form a
        // cycle, so only the descendant guard can reject it.
        $categoriaBase = Categoria::create([
            'rubro_id' => $this->baseRubro([])->id,
            'name' => 'Web',
        ]);
        $parentFork = $this->item($user, 'categoria', $categoriaBase->id);
        $service = $this->item($user, 'service', null, $parentFork, ['title' => 'S']);
        $cycleCategoria = $this->item($user, 'categoria', null, $service, ['name' => 'Cycle']);

        $this->putJson("/api/user-catalog/services/{$service->id}", ['parent_fork_id' => $cycleCategoria->id])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $this->assertSame($parentFork->id, $service->fresh()->parent_fork_id);
    }

    public function test_s5_4_destination_sibling_title_clash_is_rejected(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([0, 0]);
        $rootId = $this->forkRubro($rubro);

        $catForks = UserCatalogItem::where('parent_fork_id', $rootId)->orderBy('sort_order')->get();
        [$catA, $catB] = [$catForks[0], $catForks[1]];

        // Destination sibling "T" already under B.
        $this->item($user, 'service', null, $catB, ['title' => 'T', 'value' => 1]);
        $mover = $this->item($user, 'service', null, $catA, ['title' => 'T', 'value' => 2]);

        $this->putJson("/api/user-catalog/services/{$mover->id}", ['parent_fork_id' => $catB->id])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $this->assertSame($catA->id, $mover->fresh()->parent_fork_id);
    }

    public function test_s5_5_type_incoherent_parent_is_rejected(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([]);
        $rubroForkId = $this->forkRubro($rubro);
        $service = $this->item($user, 'service', null, null, ['title' => 'S']);

        $this->putJson("/api/user-catalog/services/{$service->id}", ['parent_fork_id' => $rubroForkId])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $this->assertNull($service->fresh()->parent_fork_id);
    }

    public function test_s5_6_rubro_never_acquires_a_parent(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([]);
        $rubroForkId = $this->forkRubro($rubro);
        $anyFork = $this->item($user, 'categoria', null, null, ['name' => 'Any']);

        $this->putJson("/api/user-catalog/rubros/{$rubroForkId}", ['parent_fork_id' => $anyFork->id])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $this->assertNull(UserCatalogItem::findOrFail($rubroForkId)->parent_fork_id);
    }

    public function test_s5_7_foreign_parent_is_rejected(): void
    {
        $owner = $this->owner();
        $other = User::factory()->create();
        $foreignParent = $this->item($other, 'categoria', null, null, ['name' => 'Other']);
        $service = $this->item($owner, 'service', null, null, ['title' => 'S']);

        $this->putJson("/api/user-catalog/services/{$service->id}", ['parent_fork_id' => $foreignParent->id])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $this->assertNull($service->fresh()->parent_fork_id);
    }

    public function test_s5_8_move_under_deactivated_parent_keeps_own_status_and_resolves_desactivado(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([0, 0]);
        $rootId = $this->forkRubro($rubro);
        $catForks = UserCatalogItem::where('parent_fork_id', $rootId)->orderBy('sort_order')->get();
        [$catA, $catB] = [$catForks[0], $catForks[1]];

        $service = $this->item($user, 'service', null, $catA, ['title' => 'S']);

        $this->patchJson("/api/user-catalog/categorias/{$catB->id}/deactivate")->assertOk();

        $this->putJson("/api/user-catalog/services/{$service->id}", ['parent_fork_id' => $catB->id])
            ->assertOk()
            ->assertJsonPath('parent_fork_id', $catB->id)
            ->assertJsonPath('status', 'desactivado');

        $this->assertSame('activo', $service->fresh()->status);
    }

    // --- R6: tree/list (S6.1-S6.5) ----------------------------------------

    public function test_s6_1_tree_returns_owner_nested_graph_in_sort_order(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([2, 1]); // 1 rubro + 2 categorias + 3 services
        $rootId = $this->forkRubro($rubro);

        // Another user's rows must never leak into the response.
        $other = User::factory()->create();
        $this->item($other, 'rubro', $this->baseRubro([])->id);

        $tree = $this->getJson('/api/user-catalog/tree')->assertOk()->json();

        $this->assertCount(1, $tree);
        $root = $tree[0];
        $this->assertSame($rootId, $root['id']);
        $this->assertSame('rubro', $root['item_type']);
        $this->assertNull($root['parent_fork_id']);
        $this->assertIsInt($root['sort_order']);
        $this->assertCount(2, $root['children']);
        $this->assertSame([0, 1], array_column($root['children'], 'sort_order'));

        $categoriaOne = $root['children'][0];
        $this->assertSame('categoria', $categoriaOne['item_type']);
        $this->assertSame($rootId, $categoriaOne['parent_fork_id']);
        $this->assertCount(2, $categoriaOne['children']);
        $this->assertSame([0, 1], array_column($categoriaOne['children'], 'sort_order'));

        $categoriaTwo = $root['children'][1];
        $this->assertCount(1, $categoriaTwo['children']);
        $this->assertSame('service', $categoriaTwo['children'][0]['item_type']);
        $this->assertSame('service', $categoriaOne['children'][0]['item_type']);

        $this->assertSame(6, count($this->flatten($tree)));
    }

    public function test_s6_2_status_filter_uses_effective_status(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro([1]);
        $rootId = $this->forkRubro($rubro);
        $service = UserCatalogItem::where('user_id', $user->id)->where('item_type', 'service')->firstOrFail();

        // Own-activo service under an own-deactivated rubro fork: effective
        // status is desactivado for the whole subtree.
        $this->patchJson("/api/user-catalog/rubros/{$rootId}/deactivate")->assertOk();

        $activos = $this->getJson('/api/user-catalog/tree?status=activo')->assertOk()->json();
        $this->assertSame([], $activos);

        $desactivados = $this->getJson('/api/user-catalog/tree?status=desactivado')->assertOk()->json();
        $ids = array_column($this->flatten($desactivados), 'id');
        $this->assertCount(3, $ids);
        $this->assertContains($service->id, $ids);

        $this->assertSame('activo', $service->fresh()->status);
    }

    public function test_s6_3_origin_filter_matches_the_resolved_origin(): void
    {
        $user = $this->owner();

        $personal = $this->item($user, 'rubro', null, null, ['name' => 'Personal']);
        $override = $this->item($user, 'rubro', $this->baseRubro([])->id, null, ['name' => 'Overridden']);
        $base = $this->item($user, 'rubro', $this->baseRubro([])->id);

        $personalTree = $this->getJson('/api/user-catalog/tree?origin=personal')->assertOk()->json();
        $this->assertSame([$personal->id], array_column($personalTree, 'id'));

        $overrideTree = $this->getJson('/api/user-catalog/tree?origin=override')->assertOk()->json();
        $this->assertSame([$override->id], array_column($overrideTree, 'id'));

        $baseTree = $this->getJson('/api/user-catalog/tree?origin=base')->assertOk()->json();
        $this->assertSame([$base->id], array_column($baseTree, 'id'));
    }

    public function test_s6_4_tree_query_budget_is_constant(): void
    {
        $user = $this->owner();

        // N items: 1 rubro + 2 categorias + 3 services.
        $this->forkRubro($this->baseRubro([2, 1]));
        $queriesN = $this->countTreeQueries();

        // 2N items: a second identical tree for the same owner.
        $this->forkRubro($this->baseRubro([2, 1]));
        $this->assertSame(12, UserCatalogItem::where('user_id', $user->id)->count());
        $queries2N = $this->countTreeQueries();

        $this->assertSame($queriesN, $queries2N);
        // Observed budget: 1 owner-scope query + 3 morphTo base eager loads.
        // The N vs 2N equality above is the real N+1 guard; this pins the
        // absolute constant with a small margin.
        $this->assertLessThanOrEqual(6, $queriesN);

        // The effective-status filter runs in memory and adds no per-node query.
        $this->assertSame($queriesN, $this->countTreeQueries('?status=activo'));
    }

    public function test_s6_5_invalid_filters_return_422(): void
    {
        $this->owner();

        $this->getJson('/api/user-catalog/tree?status=bogus')
            ->assertStatus(422)->assertJsonValidationErrors('status');
        $this->getJson('/api/user-catalog/tree?origin=bogus')
            ->assertStatus(422)->assertJsonValidationErrors('origin');
    }

    // --- S8: DB identity guard (amended D5, direct insert) ----------------

    public function test_s8_1_index_blocks_a_raw_duplicate_triple(): void
    {
        $user = User::factory()->create();
        $baseId = (string) Str::uuid();

        $row = [
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'item_type' => 'service',
            'base_id' => $baseId,
            'parent_fork_id' => null,
            'overrides' => '{}',
            'status' => 'activo',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('user_catalog_items')->insert($row);

        $duplicate = $row;
        $duplicate['id'] = (string) Str::uuid();

        $this->expectException(QueryException::class);
        DB::table('user_catalog_items')->insert($duplicate);
    }

    public function test_s8_2_null_and_soft_deleted_rows_are_exempt(): void
    {
        $user = User::factory()->create();
        $baseId = (string) Str::uuid();

        $row = [
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'item_type' => 'service',
            'base_id' => $baseId,
            'parent_fork_id' => null,
            'overrides' => '{}',
            'status' => 'activo',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('user_catalog_items')->insert($row);
        DB::table('user_catalog_items')->where('id', $row['id'])->update(['deleted_at' => now()]);

        // Same triple again: the soft-deleted row is outside the partial index.
        $refork = $row;
        $refork['id'] = (string) Str::uuid();
        DB::table('user_catalog_items')->insert($refork);

        // Personal rows (base_id null) never enter the index, even with the
        // same visible name.
        foreach (range(1, 2) as $i) {
            DB::table('user_catalog_items')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'item_type' => 'service',
                'base_id' => null,
                'parent_fork_id' => null,
                'overrides' => json_encode(['title' => 'Same']),
                'status' => 'activo',
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertSame(2, DB::table('user_catalog_items')->whereNull('base_id')->count());
        $this->assertSame(1, DB::table('user_catalog_items')->whereNull('deleted_at')->where('base_id', $baseId)->count());
    }
}

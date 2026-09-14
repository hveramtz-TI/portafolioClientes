<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use App\Models\User;
use App\Models\UserCatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * HTTP surface for the user catalog (Unit 1): R1 fork cascade, R2 item CRUD,
 * R3 dedicated status endpoints and R7 single-render error contracts.
 *
 * Scenarios: S1.1-S1.6, S2.1-S2.7, S3.1-S3.4, S7.1-S7.6. Dual-engine: runs on
 * SQLite by default and on PostgreSQL through ./test-pg.sh.
 */
class UserCatalogForkApiCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Base names are globally unique on `rubros`; a per-test sequence keeps the
     * helpers composable inside a single scenario.
     */
    private int $seq = 0;

    private function owner(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function baseRubro(int $categorias = 3, int $servicesPerCategoria = 2): Rubro
    {
        $rubro = Rubro::create(['name' => 'Informática '.$this->seq++]);

        for ($c = 0; $c < $categorias; $c++) {
            $categoria = Categoria::create([
                'rubro_id' => $rubro->id,
                'name' => "Categoria {$c}",
                'order' => $c,
            ]);

            for ($s = 0; $s < $servicesPerCategoria; $s++) {
                Service::create([
                    'categoria_id' => $categoria->id,
                    'title' => "Service {$c}-{$s}",
                    'value' => 100,
                ]);
            }
        }

        return $rubro;
    }

    private function baseCategoria(int $services = 2): Categoria
    {
        $rubro = Rubro::create(['name' => 'Informática '.$this->seq++]);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);

        for ($s = 0; $s < $services; $s++) {
            Service::create(['categoria_id' => $categoria->id, 'title' => "Service {$s}", 'value' => 100]);
        }

        return $categoria;
    }

    private function baseService(): Service
    {
        $rubro = Rubro::create(['name' => 'Informática '.$this->seq++]);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);

        return Service::create(['categoria_id' => $categoria->id, 'title' => 'API', 'value' => 100]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function forkRow(
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

    // --- R1: fork endpoints (S1.1-S1.6) -----------------------------------

    public function test_s1_1_rubro_fork_returns_cascade_summary(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro(3, 2);

        $response = $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork");

        $response->assertCreated()
            ->assertJsonPath('item_type', 'rubro')
            ->assertJsonPath('counts.rubro', 1)
            ->assertJsonPath('counts.categoria', 3)
            ->assertJsonPath('counts.service', 6)
            ->assertJsonCount(10, 'ids');

        $root = UserCatalogItem::findOrFail($response->json('id'));
        $this->assertSame($user->id, $root->user_id);
        $this->assertSame($rubro->id, $root->base_id);
        $this->assertNull($root->parent_fork_id);
        $this->assertSame('activo', $root->status);
        $this->assertSame([], $root->overrides);

        $this->assertSame(3, UserCatalogItem::where('parent_fork_id', $root->id)->where('item_type', 'categoria')->count());
        $this->assertSame(6, UserCatalogItem::where('user_id', $user->id)->where('item_type', 'service')->count());
        $this->assertSame(10, UserCatalogItem::where('user_id', $user->id)->count());
    }

    public function test_s1_2_categoria_fork_copies_services_atomically(): void
    {
        $user = $this->owner();
        $categoria = $this->baseCategoria(2);

        $response = $this->postJson("/api/user-catalog/categorias/{$categoria->id}/fork");

        $response->assertCreated()
            ->assertJsonPath('item_type', 'categoria')
            ->assertJsonPath('counts.categoria', 1)
            ->assertJsonPath('counts.service', 2)
            ->assertJsonCount(3, 'ids');

        $root = UserCatalogItem::findOrFail($response->json('id'));
        $this->assertSame($categoria->id, $root->base_id);
        $this->assertNull($root->parent_fork_id);
        $this->assertSame(2, UserCatalogItem::where('parent_fork_id', $root->id)->count());

        $persisted = UserCatalogItem::where('user_id', $user->id)->pluck('id')->all();
        $this->assertEqualsCanonicalizing($persisted, $response->json('ids'));
    }

    public function test_s1_3_duplicate_root_fork_returns_409_and_persists_nothing(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro(1, 1);
        $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork")->assertCreated();
        $before = UserCatalogItem::where('user_id', $user->id)->count();

        $response = $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork");

        $response->assertStatus(409)->assertJsonStructure(['message']);
        $this->assertSame($before, UserCatalogItem::where('user_id', $user->id)->count());
    }

    public function test_s1_4_duplicate_descendant_fork_returns_409_atomically(): void
    {
        $user = $this->owner();
        $rubro = $this->baseRubro(1, 1);
        $categoria = $rubro->categorias()->firstOrFail();

        // The user already holds a standalone fork of a categoria under R (J1).
        $this->postJson("/api/user-catalog/categorias/{$categoria->id}/fork")->assertCreated();
        $before = UserCatalogItem::where('user_id', $user->id)->count();

        $response = $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork");

        $response->assertStatus(409);
        $this->assertSame($before, UserCatalogItem::where('user_id', $user->id)->count());
        $this->assertSame(0, UserCatalogItem::where('user_id', $user->id)->where('item_type', 'rubro')->count());
    }

    public function test_s1_5_unknown_base_returns_404(): void
    {
        $this->owner();

        $this->postJson('/api/user-catalog/rubros/'.Str::uuid().'/fork')->assertNotFound();
    }

    public function test_s1_6_fork_deactivated_base_stays_own_active_and_resolves_desactivado(): void
    {
        $this->owner();
        $rubro = Rubro::create(['name' => 'Informática', 'status' => 'desactivado']);
        Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);

        $response = $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork")->assertCreated();
        $root = UserCatalogItem::findOrFail($response->json('id'));

        $this->assertSame('activo', $root->fresh()->status);

        $this->getJson("/api/user-catalog/rubros/{$root->id}")
            ->assertOk()
            ->assertJsonPath('status', 'desactivado');
    }

    /**
     * JD4-1: the fork endpoint is a create-ability surface like store. The admin
     * role never manages another user's private catalog (policy create denies
     * admin), so a fork POST must 403 before any row is written.
     */
    public function test_s1_7_admin_is_forbidden_from_forking_and_regular_user_still_creates(): void
    {
        $rubro = $this->baseRubro(0, 0);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork")->assertForbidden();
        $this->assertSame(0, UserCatalogItem::count());

        $user = $this->owner();
        $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork")->assertCreated();
        $this->assertSame(1, UserCatalogItem::where('user_id', $user->id)->count());
    }

    // --- R2: item CRUD (S2.1-S2.7) ----------------------------------------

    public function test_s2_1_store_creates_personal_item_owned_by_caller(): void
    {
        $user = $this->owner();

        $response = $this->postJson('/api/user-catalog/rubros', ['name' => 'Mi Rubro']);

        $response->assertCreated()
            ->assertJsonPath('item_type', 'rubro')
            ->assertJsonPath('origin', 'personal')
            ->assertJsonPath('status', 'activo')
            ->assertJsonPath('base_id', null);

        $row = UserCatalogItem::findOrFail($response->json('id'));
        $this->assertSame($user->id, $row->user_id);
        $this->assertNull($row->base_id);
        $this->assertSame('activo', $row->status);
        $this->assertSame('Mi Rubro', $row->overrides['name']);
    }

    public function test_s2_2_store_non_null_base_id_is_rejected_citing_fork_endpoint(): void
    {
        $this->owner();
        $service = $this->baseService();
        $before = UserCatalogItem::count();

        $response = $this->postJson('/api/user-catalog/services', [
            'item_type' => 'service',
            'base_id' => $service->id,
            'title' => 'X',
            'value' => 100,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('base_id');
        $this->assertStringContainsString('fork', strtolower((string) $response->json('errors.base_id.0')));
        $this->assertSame($before, UserCatalogItem::count());
    }

    public function test_s2_3_show_returns_resolved_view_with_structural_keys(): void
    {
        $this->owner();
        $service = $this->baseService();
        $service->update(['value' => 150]);

        $forkId = $this->postJson("/api/user-catalog/services/{$service->id}/fork")->assertCreated()->json('id');
        $this->putJson("/api/user-catalog/services/{$forkId}", ['title' => 'Mi API'])->assertOk();

        $response = $this->getJson("/api/user-catalog/services/{$forkId}");

        $response->assertOk()
            ->assertJsonPath('title', 'Mi API')
            ->assertJsonPath('value', 150)
            ->assertJsonPath('origin', 'override')
            ->assertJsonPath('overridden_fields', ['title'])
            ->assertJsonPath('status', 'activo')
            ->assertJsonPath('item_type', 'service')
            ->assertJsonPath('parent_fork_id', null);

        $this->assertIsInt($response->json('sort_order'));
    }

    public function test_s2_4_update_explicit_null_removes_override(): void
    {
        $this->owner();
        $service = $this->baseService();
        $service->update(['value' => 200]);
        $forkId = $this->postJson("/api/user-catalog/services/{$service->id}/fork")->assertCreated()->json('id');
        $this->putJson("/api/user-catalog/services/{$forkId}", ['value' => 999])->assertOk();

        $response = $this->putJson("/api/user-catalog/services/{$forkId}", ['value' => null]);

        $response->assertOk()
            ->assertJsonPath('value', 200)
            ->assertJsonPath('origin', 'base')
            ->assertJsonPath('overridden_fields', []);
    }

    public function test_s2_5_update_status_or_unknown_field_is_rejected_and_untouched(): void
    {
        $this->owner();
        $service = $this->baseService();
        $forkId = $this->postJson("/api/user-catalog/services/{$service->id}/fork")->assertCreated()->json('id');
        $this->putJson("/api/user-catalog/services/{$forkId}", ['value' => 999])->assertOk();

        $this->putJson("/api/user-catalog/services/{$forkId}", ['status' => 'desactivado'])
            ->assertStatus(422)->assertJsonValidationErrors('status');
        $this->putJson("/api/user-catalog/services/{$forkId}", ['bogus' => true])
            ->assertStatus(422)->assertJsonValidationErrors('bogus');

        $row = UserCatalogItem::findOrFail($forkId);
        $this->assertSame('activo', $row->status);
        $this->assertSame(['value' => 999], $row->overrides);
    }

    public function test_s2_6_rubro_explicit_null_parent_is_legal_on_store_and_update(): void
    {
        $this->owner();

        $stored = $this->postJson('/api/user-catalog/rubros', ['name' => 'Mío', 'parent_fork_id' => null]);
        $stored->assertCreated();
        $this->assertNull(UserCatalogItem::findOrFail($stored->json('id'))->parent_fork_id);

        $updated = $this->putJson("/api/user-catalog/rubros/{$stored->json('id')}", ['parent_fork_id' => null]);
        $updated->assertOk()->assertJsonPath('parent_fork_id', null);
        $this->assertNull(UserCatalogItem::findOrFail($stored->json('id'))->parent_fork_id);
    }

    public function test_s2_7_rubro_non_null_parent_is_rejected_on_store_and_update(): void
    {
        $user = $this->owner();
        $parent = $this->forkRow($user, 'rubro', null, null, ['name' => 'Padre']);

        $this->postJson('/api/user-catalog/rubros', ['name' => 'Mío', 'parent_fork_id' => $parent->id])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $child = $this->forkRow($user, 'rubro', $this->baseRubro(0, 0)->id);
        $this->putJson("/api/user-catalog/rubros/{$child->id}", ['parent_fork_id' => $parent->id])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $this->assertNull($child->fresh()->parent_fork_id);
        $this->assertSame(1, UserCatalogItem::where('item_type', 'rubro')->whereNull('base_id')->count());
    }

    // --- R3: dedicated status endpoints (S3.1-S3.4) -----------------------

    public function test_s3_1_deactivate_hides_subtree_via_effective_status(): void
    {
        $this->owner();
        $rubro = $this->baseRubro(1, 1);
        $rootId = $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork")->assertCreated()->json('id');
        $categoria = UserCatalogItem::where('parent_fork_id', $rootId)->firstOrFail();
        $service = UserCatalogItem::where('parent_fork_id', $categoria->id)->firstOrFail();

        $this->patchJson("/api/user-catalog/rubros/{$rootId}/deactivate")
            ->assertOk()
            ->assertJsonPath('status', 'desactivado');

        $this->assertSame('desactivado', UserCatalogItem::findOrFail($rootId)->status);
        $this->assertSame('activo', $categoria->fresh()->status);
        $this->assertSame('activo', $service->fresh()->status);

        $this->getJson("/api/user-catalog/categorias/{$categoria->id}")->assertJsonPath('status', 'desactivado');
        $this->getJson("/api/user-catalog/services/{$service->id}")->assertJsonPath('status', 'desactivado');
    }

    public function test_s3_2_reactivate_touches_only_the_selected_item(): void
    {
        $this->owner();
        $rubro = $this->baseRubro(1, 1);
        $rootId = $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork")->assertCreated()->json('id');
        $categoria = UserCatalogItem::where('parent_fork_id', $rootId)->firstOrFail();

        $this->patchJson("/api/user-catalog/rubros/{$rootId}/deactivate")->assertOk();
        $this->patchJson("/api/user-catalog/categorias/{$categoria->id}/deactivate")->assertOk();

        $this->patchJson("/api/user-catalog/rubros/{$rootId}/reactivate")
            ->assertOk()
            ->assertJsonPath('status', 'activo');

        $this->assertSame('activo', UserCatalogItem::findOrFail($rootId)->status);
        $this->assertSame('desactivado', $categoria->fresh()->status);
    }

    public function test_s3_3_reactivate_below_dead_ancestor_still_resolves_desactivado(): void
    {
        $this->owner();
        $rubro = $this->baseRubro(1, 1);
        $rootId = $this->postJson("/api/user-catalog/rubros/{$rubro->id}/fork")->assertCreated()->json('id');
        $categoria = UserCatalogItem::where('parent_fork_id', $rootId)->firstOrFail();
        $service = UserCatalogItem::where('parent_fork_id', $categoria->id)->firstOrFail();

        $this->patchJson("/api/user-catalog/rubros/{$rootId}/deactivate")->assertOk();
        $service->update(['status' => 'desactivado']);

        $this->patchJson("/api/user-catalog/services/{$service->id}/reactivate")
            ->assertOk()
            ->assertJsonPath('status', 'desactivado');

        $this->assertSame('activo', $service->fresh()->status);
    }

    public function test_s3_4_status_via_update_is_rejected(): void
    {
        $this->owner();
        $service = $this->baseService();
        $forkId = $this->postJson("/api/user-catalog/services/{$service->id}/fork")->assertCreated()->json('id');

        $this->putJson("/api/user-catalog/services/{$forkId}", ['status' => 'desactivado'])
            ->assertStatus(422)->assertJsonValidationErrors('status');

        $this->assertSame('activo', UserCatalogItem::findOrFail($forkId)->status);
    }

    public function test_d9_detach_of_categoria_parent_is_rejected_on_update(): void
    {
        $user = $this->owner();
        $rubro = Rubro::create(['name' => 'Informática']);
        $rubroFork = $this->forkRow($user, 'rubro', $rubro->id);
        $categoria = Categoria::create(['rubro_id' => $rubro->id, 'name' => 'Web']);
        $child = $this->forkRow($user, 'categoria', $categoria->id, $rubroFork);

        $this->putJson("/api/user-catalog/categorias/{$child->id}", ['parent_fork_id' => null])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $this->assertSame($rubroFork->id, $child->fresh()->parent_fork_id);
    }

    // --- R7: error contracts (S7.1-S7.6) ----------------------------------

    public function test_s7_1_unauthenticated_requests_return_401_json(): void
    {
        $this->postJson('/api/user-catalog/rubros', ['name' => 'X'])->assertStatus(401);
        $this->postJson('/api/user-catalog/rubros/'.Str::uuid().'/fork')->assertStatus(401);
    }

    public function test_s7_2_cross_owner_and_admin_are_forbidden(): void
    {
        $owner = User::factory()->create();
        $fork = $this->forkRow($owner, 'service', $this->baseService()->id);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/user-catalog/services/{$fork->id}")->assertStatus(403);
        $this->putJson("/api/user-catalog/services/{$fork->id}", ['value' => 1])->assertStatus(403);
        $this->patchJson("/api/user-catalog/services/{$fork->id}/deactivate")->assertStatus(403);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson("/api/user-catalog/services/{$fork->id}")->assertStatus(403);
    }

    public function test_s7_3_unknown_or_soft_deleted_ids_return_404(): void
    {
        $user = $this->owner();

        $this->getJson('/api/user-catalog/services/'.Str::uuid())->assertNotFound();
        $this->postJson('/api/user-catalog/services/'.Str::uuid().'/fork')->assertNotFound();

        $fork = $this->forkRow($user, 'service', $this->baseService()->id);
        $fork->delete();

        $this->getJson("/api/user-catalog/services/{$fork->id}")->assertNotFound();
        $this->putJson("/api/user-catalog/services/{$fork->id}", ['value' => 1])->assertNotFound();
        $this->patchJson("/api/user-catalog/services/{$fork->id}/reactivate")->assertNotFound();
    }

    public function test_s7_4_duplicate_service_fork_returns_409_from_domain_exception(): void
    {
        $user = $this->owner();
        $service = $this->baseService();
        $this->postJson("/api/user-catalog/services/{$service->id}/fork")->assertCreated();
        $before = UserCatalogItem::where('user_id', $user->id)->count();

        $response = $this->postJson("/api/user-catalog/services/{$service->id}/fork");

        $response->assertStatus(409)->assertJsonStructure(['message']);
        $this->assertSame($before, UserCatalogItem::where('user_id', $user->id)->count());
    }

    public function test_s7_5_index_race_renders_the_same_409_shape(): void
    {
        $user = $this->owner();
        $service = $this->baseService();

        // Simulate a duplicate that slips past the engine's identity check: a
        // competing row is inserted after the check but before the insert, so
        // only the partial unique index can reject it.
        $injected = false;
        UserCatalogItem::creating(function (UserCatalogItem $model) use (&$injected): void {
            if ($injected) {
                return;
            }
            $injected = true;

            DB::table('user_catalog_items')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $model->user_id,
                'item_type' => $model->item_type,
                'base_id' => $model->base_id,
                'parent_fork_id' => null,
                'overrides' => '{}',
                'status' => 'activo',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $response = $this->postJson("/api/user-catalog/services/{$service->id}/fork");
        } finally {
            UserCatalogItem::flushEventListeners();
        }

        $response->assertStatus(409)->assertJsonStructure(['message']);
        $this->assertSame(0, UserCatalogItem::where('user_id', $user->id)->count());
    }

    public function test_s7_6_invalid_payloads_return_422_with_field_errors(): void
    {
        $user = $this->owner();
        $service = $this->baseService();

        $this->postJson('/api/user-catalog/rubros', ['item_type' => 'rubro', 'name' => 'X', 'bogus' => 'nope'])
            ->assertStatus(422)->assertJsonValidationErrors('bogus');

        $this->postJson('/api/user-catalog/services', ['item_type' => 'service', 'base_id' => $service->id, 'title' => 'X', 'value' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('base_id');

        $rubroFork = $this->forkRow($user, 'rubro', $this->baseRubro(0, 0)->id);
        $this->postJson('/api/user-catalog/services', ['item_type' => 'service', 'parent_fork_id' => $rubroFork->id, 'title' => 'X', 'value' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('parent_fork_id');

        $this->assertSame(1, UserCatalogItem::count());
    }

    /**
     * JD4-3: the QueryException 409 branch is scoped to the user_catalog_items
     * live-identity constraint (migration 2026_09_14_000000). Any OTHER unique
     * violation must fall through to the default handler. Here `Rule::unique`
     * passes and a competing insert (event hook, same trick as S7.5) makes the
     * admin rubro store trip rubros' own name-unique index. On PostgreSQL an
     * unscoped 23505 match currently renders the duplicate-fork message; after
     * scoping both engines fall through to the default 500 with no fork text.
     */
    public function test_s7_7_foreign_unique_violation_does_not_render_the_fork_message(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $injected = false;
        Rubro::creating(function () use (&$injected): void {
            if ($injected) {
                return;
            }
            $injected = true;

            DB::table('rubros')->insert([
                'id' => (string) Str::uuid(),
                'name' => 'Race Rubro',
                'status' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $response = $this->postJson('/api/rubros', ['name' => 'Race Rubro']);
        } finally {
            Rubro::flushEventListeners();
        }

        $response->assertStatus(500);
        $this->assertStringNotContainsString('live fork', (string) $response->getContent());
    }
}

# Design: Catalog Slice 5 — Base Seeders

## Technical Approach

Add three small, event-silent Laravel seeders for the canonical HU-024 catalog and wire them into the existing `DatabaseSeeder` immediately after its inline users and before the existing company/client calls. Each seeder owns one table, resolves prerequisites before writing, and performs one transactional explicit-key upsert batch against the existing D5 unique index. The implementation uses the exact 22-row dataset from `docs/flujos/seeders-catalogo.md`; no schema, model, route, frontend, CompanySeeder, or ClientSeeder changes are included.

The design satisfies R1–R8 by making IDs and timestamps deterministic, restoring `deleted_at` to `null` on every canonical write, resolving category parents by the `(rubro, categoria)` pair, and testing both normal idempotency and soft-delete revival on SQLite and PostgreSQL.

## Architecture Decisions

### D1 — Explicit-key transactional upsert

**Choice**: Use `DB::table(...)->upsert($rows, $uniqueBy, $update)` once per seeder, inside `DB::transaction(...)`.

For every row, `values` contains the complete canonical payload:

```php
[
    'id' => $deterministicId,
    // parent/key columns for the table,
    'name' => '...', // or title
    'description' => '...',
    'order' => 1, // category only
    'value' => 300000, // service only
    'tags' => '["frontend","fullstack"]', // JSON text at query-builder level
    'status' => 'activo',
    'created_at' => '2026-08-29 00:00:00',
    'updated_at' => '2026-08-29 00:00:00',
    'deleted_at' => null,
]
```

The exact conflict keys are the D5 indexes: `['name']` for `rubros`, `['rubro_id', 'name']` for `categorias`, and `['categoria_id', 'title']` for `services`. The update list contains every mutable canonical column, including `id` only by omission (the stable conflict row keeps its primary key), `description`, parent/value/order/tags fields as applicable, `status`, `updated_at`, and `deleted_at`. `deleted_at => null` is explicitly included so a trashed canonical row is revived rather than duplicated.

Each seeder has its own transaction. Parent resolution and construction of the complete batch happen before the `upsert`; a missing prerequisite throws `firstOrFail()` before any dependent row is written. The transaction therefore protects against partial writes within a seeder, while the dependency order protects the chain. There is intentionally no new cross-seeder transaction in `DatabaseSeeder`.

### D2 — Stable UUIDs and timestamps bypass model generation safely

**Choice**: Store fixed, pre-generated UUIDv7 values as constants in the three seeder data arrays, one per canonical natural key, and use one fixed UTC timestamp literal for all canonical rows.

**Alternatives considered**: UUIDv5 derived at runtime from natural keys; random `HasUuids` generation; model `create`/`updateOrCreate`.

**Rationale**: Fixed UUIDv7 values preserve the repository's UUIDv7 convention while making snapshots byte-stable across runs and engines. Explicit `id` values are passed directly to the query builder, so `HasUuids` does not generate or replace them; no DBAL conversion or model event is involved. Fixed timestamps are required because `now()` would violate R3 even when the row values otherwise match. The data arrays remain the single source for IDs and canonical values, avoiding a new ID registry abstraction.

### D3 — Revival smoke test and controlled fallback

**Choice**: Treat `BaseCatalogSeederTest::testTrashedRowsAreRevived` as the acceptance smoke test for the inferred `upsert` + unconditional unique-index behavior. Run it under both PHPUnit configurations. The test seeds, soft-deletes one rubro, one category, and one service, mutates a canonical service to `desactivado`, re-seeds the affected chain, and asserts one live canonical row per natural key plus unchanged 3/7/12 counts.

**Fallback trigger**: If that test is RED on either SQLite or PostgreSQL because the selected Laravel/database combination does not update the soft-deleted conflict row as designed, replace the persistence helper in all three seeders with the pre-agreed fallback: locate the natural-key row with `withTrashed()`, call `restore()` when needed, then call `updateOrCreate()` with the complete canonical payload. Resolve all parent rows first and keep the operation inside the same per-seeder transaction. Use `Model::withoutTimestamps` or an equivalent explicit timestamp-preserving path so R3 remains byte-stable.

**Rationale**: `upsert` is the atomic and race-safe design for the unconditional indexes. The fallback is deliberately limited to a verified engine incompatibility; it preserves R1–R8 outcomes (including revival, convergence, stable IDs/timestamps, event silence, and zero partial dependent writes) but documents the loss of atomic conflict handling rather than silently accepting it.

### D4 — Service description wording is pinned to the template

**Choice**: Use the `generateDescription()` template from the flow document, not the shorter table descriptions:

```text
Servicio profesional: {title}. Valor referencial, precio final sujeto a conversación según requerimientos.
```

Verbatim examples:

```text
Servicio profesional: E-commerce básico. Valor referencial, precio final sujeto a conversación según requerimientos.
Servicio profesional: App móvil React Native. Valor referencial, precio final sujeto a conversación según requerimientos.
```

The feature test asserts the exact generated value for representative services and the same pattern for all 12.

**Alternatives considered**: The `Descripción Corta` values in the flow-doc table.

**Rationale**: The accepted proposal already leaned toward the reference implementation's template, and it guarantees every service has a non-empty, consistently generated description without adding another per-row wording source. The design resolves the spec's S1.5 ambiguity before implementation.

### D5 — One focused seeder per table with inline data

**Choice**: Create `RubroSeeder`, `CategoriaSeeder`, and `ServiceSeeder`, each using `WithoutModelEvents`. Keep the small canonical arrays inline in their respective classes; do not add a data DTO or shared catalog-data class.

`CategoriaSeeder` resolves each rubro by `Rubro::where('name', ...)->firstOrFail()`. `ServiceSeeder` resolves each service parent through the explicit chain `rubro name -> Rubro id -> categoria name under that rubro -> Categoria id`; it never indexes categories by bare name. All required rubros/categories are resolved before the corresponding upsert begins.

**Alternatives considered**: A shared data class or importing the flow document at runtime; `firstOrCreate`; a category map keyed only by name.

**Rationale**: Inline arrays are the simplest reviewable representation for 22 fixed rows and keep each table's ownership obvious. A data class would add indirection without reducing this slice's risk. Runtime document parsing is brittle. `firstOrCreate` is unsafe with `SoftDeletes` and unconditional unique indexes, and bare category names fail the `Identidad visual` disambiguation scenario.

### D6 — DatabaseSeeder insertion point preserves current behavior

**Choice**: Add the ordered catalog call immediately after the two inline users and before the existing Company/Client calls. The current and modified chain are:

```diff
         User::updateOrCreate(/* admin */);
         User::updateOrCreate(/* regular user */);

+        $this->call(RubroSeeder::class);
+        $this->call(CategoriaSeeder::class);
+        $this->call(ServiceSeeder::class);

         $this->call(CompanySeeder::class);
         $this->call(ClientSeeder::class);
```

The actual existing inline user payloads remain unchanged. No `UserSeeder` is introduced. Company/Client seeders remain untouched; consequently, a second full `db:seed` may still hit their existing uniqueness behavior, which is outside this slice.

### D7 — Feature-test contract covers all normative scenarios

**Choice**: Add `backend/tests/Feature/BaseCatalogSeederTest.php` using `RefreshDatabase`, with explicit `$this->seed([RubroSeeder::class, CategoriaSeeder::class, ServiceSeeder::class])` calls. Do not use `#[Seed]` or `#[Seeder(...)]` attributes.

Planned test methods and scenario coverage:

| Test method | Spec coverage |
|---|---|
| `test_catalog_has_canonical_counts_status_and_null_deletes` | R1/S1.1, S1.4 |
| `test_categories_have_canonical_parentage_and_order` | R1/S1.2, R2/S2.1–S2.2 |
| `test_services_have_exact_values_tags_and_descriptions` | R1/S1.3, S1.5 |
| `test_service_lookup_uses_rubro_and_category_pair` | R2/S2.3 |
| `test_dependent_seeder_fails_before_writing_without_parent` | R2/S2.4 |
| `test_second_seed_is_byte_equal_to_first_snapshot` | R3/S3.1–S3.3, R7/S7.2 |
| `test_trashed_rows_are_revived_and_deactivation_converges` | R4/S4.1–S4.4 |
| `test_standalone_commands_match_explicit_seed_path` | R5/S5.1–S5.2 |
| `test_catalog_seeders_emit_no_model_events` | R5/S5.3 |
| `test_database_seeder_wires_catalog_before_company_and_client` | R6/S6.1–S6.3 |
| `test_seeded_rows_do_not_leak_between_tests` | R7/S7.1 |

Standalone parity is exercised with `$this->artisan('db:seed', ['--class' => RubroSeeder::class])` (then category and service in order), while the primary canonical assertions use `$this->seed([...])`. The test records snapshots after the first run and compares IDs, canonical scalars, decoded tags, descriptions, and fixed timestamps after the second. Event listeners are registered for all three models and assert zero observed events. Mass-assignment is verified only through stored `status`, `order`, `value`, and `tags`, not by asserting an internal guard implementation.

The dual-engine sequence is:

```text
SQLite:     docker compose exec backend php artisan test --filter BaseCatalogSeederTest
PostgreSQL: ./test-pg.sh tests/Feature/BaseCatalogSeederTest.php
Full SQLite:     docker compose exec backend php artisan test
Full PostgreSQL: ./test-pg.sh
```

The expected baseline is 219 existing tests untouched, plus the new feature coverage. This is backend-only; no frontend lint or Jest command is needed.

### D8 — Formatting and CodeGraph verification

**Choice**: Format only the new/modified PHP files with Laravel Pint, then run the repository's backend verification commands. CodeGraph was consulted before source exploration; after implementation, rely on watcher synchronization and run `codegraph sync && codegraph status` only if the watcher reports stale files or is disabled.

Verification commands are:

```text
docker compose exec backend php artisan test
./test-pg.sh
docker compose exec backend vendor/bin/pint database/seeders/RubroSeeder.php database/seeders/CategoriaSeeder.php database/seeders/ServiceSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/BaseCatalogSeederTest.php
codegraph sync && codegraph status
```

The frontend commands from the repository instructions are explicitly not applicable because this change has no frontend files. No new Composer dependency is needed.

### D9 — Scope fences and rollback

**Choice**: The change is additive plus one `DatabaseSeeder` call-site diff. It does not touch `CompanySeeder.php`, `ClientSeeder.php`, migrations, models, routes, fork/personalization code, or frontend files.

Rollback is clean: remove the three catalog seeder files and `BaseCatalogSeederTest.php`, then remove the three catalog calls from `DatabaseSeeder`. If seeds have already run in PostgreSQL or a developer database, the code rollback does not delete data; optionally truncate the three base tables in dependency order (`services`, `categorias`, `rubros`) or reset with `migrate:fresh` in disposable environments. No migration rollback or production data backfill is required.

### D10 — Review size remains one slice

**Choice**: Keep the implementation under the 400 changed-line review budget and deliver it as one review slice.

| File | Estimated authored changed lines |
|---|---:|
| `backend/database/seeders/RubroSeeder.php` | 30–40 |
| `backend/database/seeders/CategoriaSeeder.php` | 40–55 |
| `backend/database/seeders/ServiceSeeder.php` | 65–85 |
| `backend/database/seeders/DatabaseSeeder.php` | 3–6 |
| `backend/tests/Feature/BaseCatalogSeederTest.php` | 150–190 |
| **Estimated total** | **288–376** |

This is a single-slice forecast; the tasks phase owns the final delivery-strategy decision. No chain is currently recommended because the estimate stays below 400 lines, while the test class carries the required dual-engine and scenario evidence.

## Data Flow

```text
DatabaseSeeder
  └─ inline users
      └─ RubroSeeder ──transaction/upsert──> rubros
            └─ CategoriaSeeder ──resolve by rubro name
                  └─ transaction/upsert ──> categorias
                        └─ ServiceSeeder ──resolve by (rubro, category)
                              └─ transaction/upsert ──> services
                                    └─ CompanySeeder → ClientSeeder
```

For a standalone dependent invocation, all parent lookups occur before its write. A missing parent raises `ModelNotFoundException`; the transaction rolls back and the dependent table remains unchanged. For a re-seed, the database unique index finds the existing live or trashed row, canonical mutable columns are replaced, and `deleted_at` is cleared.

## File Changes

| File | Action | Description |
|---|---|---|
| `backend/database/seeders/RubroSeeder.php` | Create | Three canonical rubros, deterministic UUIDs/timestamps, transactional upsert on `name`, event silence. |
| `backend/database/seeders/CategoriaSeeder.php` | Create | Seven categories, rubro-name resolution, canonical descriptions/order, transactional upsert on `(rubro_id, name)`. |
| `backend/database/seeders/ServiceSeeder.php` | Create | Twelve services, pair-based parent resolution, generated descriptions, CLP/tags, transactional upsert on `(categoria_id, title)`. |
| `backend/database/seeders/DatabaseSeeder.php` | Modify | Insert ordered catalog calls after inline users and before Company/Client. |
| `backend/tests/Feature/BaseCatalogSeederTest.php` | Create | R1–R8 feature coverage, explicit second seed, revival smoke test, standalone parity, event silence, and leakage assertions. |
| `openspec/changes/catalog-slice-5-base-seeders/design.md` | Create | This technical design. |

No `CompanySeeder.php`, `ClientSeeder.php`, migration, model, route, frontend, or Composer file is changed.

## Interfaces / Contracts

Each seeder exposes Laravel's existing contract:

```php
final class RubroSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void;
}
```

The table contracts are:

```text
RubroSeeder:    upsert(rubros, uniqueBy=['name'])
CategoriaSeeder: upsert(categorias, uniqueBy=['rubro_id', 'name'])
ServiceSeeder:  upsert(services, uniqueBy=['categoria_id', 'title'])
```

All three write `status='activo'`, fixed timestamps, and `deleted_at=null`. Service tags are JSON-encoded before query-builder persistence and are decoded through the existing `Service::$casts` in assertions. `HasUuids` remains on the models but is not responsible for seeded IDs.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Feature | Canonical 3/7/12 dataset, FK parentage, category order, CLP values, tags, descriptions, placeholder `X` | `RefreshDatabase`, explicit trio seed, database assertions and model reads |
| Feature | Double-seed equality and `n ≡ 1` behavior | Snapshot canonical rows after run one; run the same trio twice more; compare all stable values |
| Feature | Revival and canonical convergence | Soft-delete rows, mutate status, re-seed, assert one live canonical row and unchanged counts |
| Feature | Dependency failure | Invoke dependent seeder on empty prerequisites and assert exception plus zero dependent rows |
| Feature | Standalone parity and event silence | Invoke each class through Artisan in dependency order; attach model listeners and assert no events |
| Feature | DatabaseSeeder ordering and stored mass-assignment outcome | Run full seeder on a fresh database; observe ordered calls and assert canonical columns are present |
| Regression | Existing backend behavior | Run complete SQLite and PostgreSQL suites; expected 219 existing tests remain green |

## Threat Matrix

The applicability matrix is not required for this change: it introduces no routing, shell-command implementation, subprocess orchestration, VCS/PR automation, executable-file classification, or process-integration boundary. Artisan commands appear only as test invocations of existing Laravel seeder entry points; they do not change command parsing or execution behavior.

| Boundary | Applicability | Reason |
|---|---|---|
| Documentation-like paths | N/A | No executable documentation or file classification is changed. |
| Git repository selection | N/A | No Git automation is added. |
| Commit state | N/A | No commit/staging behavior is changed. |
| Push state | N/A | No remote operation is added. |
| PR commands | N/A | No PR command composition is added. |

## Migration / Rollout

No migration required. Deploying the additive seeders is safe for empty databases and converges existing canonical rows. Developers should use `docker compose exec backend php artisan migrate:fresh --seed` for a clean full reset. A normal second full `db:seed` is not claimed to be idempotent because the pre-existing Company/Client seeders remain outside scope.

## Open Questions

None. The description wording, UUID strategy, revival verification/fallback, transaction scope, file structure, wiring, test contract, rollback, and review-size forecast are settled for the tasks phase.

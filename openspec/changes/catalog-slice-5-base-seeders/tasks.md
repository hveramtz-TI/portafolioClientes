# Tasks: Catalog Slice 5 — Base-Catalog Idempotent Seeders

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 288–376 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR (design forecast stays below 400 lines) |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Canonical 22-row catalog + idempotency + revival + dual-engine test coverage | PR 1 | `docker compose exec backend php artisan test --filter=BaseCatalogSeederTest` + `./test-pg.sh tests/Feature/BaseCatalogSeederTest.php` | N/A (DB transaction, `RefreshDatabase` rollback) | Delete `RubroSeeder.php`, `CategoriaSeeder.php`, `ServiceSeeder.php`, `BaseCatalogSeederTest.php`, revert `DatabaseSeeder.php` call-site |

## Phase 1: RED — Write Failing Tests for the Catalog Trio

- [x] 1.1 RED: Create `backend/tests/Feature/BaseCatalogSeederTest.php` with class skeleton, `RefreshDatabase` trait, and imports for `RubroSeeder`, `CategoriaSeeder`, `ServiceSeeder`, `Rubro`, `Categoria`, `Service`
- [x] 1.2 RED: Write `test_catalog_has_canonical_counts_status_and_null_deletes` — seeds trio once, asserts counts 3/7/12, every row `status=activo`, `deleted_at` null (covers R1/S1.1, S1.4)
- [x] 1.3 RED: Write `test_categories_have_canonical_parentage_and_order` — asserts each category's `rubro_id` matches flow-doc parent and `order` values are canonical (covers R1/S1.2, R2/S2.1–S2.2)
- [x] 1.4 RED: Write `test_services_have_exact_values_tags_and_descriptions` — asserts exact CLP values per title, exact tag sets within D4 whitelist, non-empty description matching pinned template pattern (covers R1/S1.3, S1.5)
- [x] 1.5 RED: Write `test_service_lookup_uses_rubro_and_category_pair` — creates extra `Identidad visual` under Informática, asserts service lands under Diseño's `Identidad visual` via pair resolution (covers R2/S2.3)
- [x] 1.6 RED: Write `test_dependent_seeder_fails_before_writing_without_parent` — runs `CategoriaSeeder` on empty `rubros` and `ServiceSeeder` on empty `categorias`, expects exception and zero dependent rows (covers R2/S2.4)
- [x] 1.7 RED: Write `test_second_seed_is_byte_equal_to_first_snapshot` — seeds trio, snapshots all canonical columns (ids, timestamps, status, order, value, tags, descriptions), seeds again, asserts row-for-row equality; runs third time and asserts `n ≡ 1` (covers R3/S3.1–S3.3, R7/S7.2)
- [x] 1.8 RED: Write `test_trashed_rows_are_revived_and_deactivation_converges` — seeds, soft-deletes one rubro, one category, one service, mutates one service to `desactivado`, re-seeds trio, asserts one live canonical row per natural key and 3/7/12 counts (covers R4/S4.1–S4.4) — **this is the dual-engine revival smoke gate**
- [x] 1.9 RED: Write `test_standalone_commands_match_explicit_seed_path` — runs each seeder via `artisan db:seed --class=...` in dependency order on fresh DB, asserts stored state equals `$this->seed([...])` reference; re-runs standalone on chain-seeded DB and asserts byte-equal (covers R5/S5.1–S5.2)
- [x] 1.10 RED: Write `test_catalog_seeders_emit_no_model_events` — registers listeners on Rubro/Categoria/Service, runs each seeder standalone and via chain, asserts zero events observed (covers R5/S5.3)
- [x] 1.11 RED: Write `test_database_seeder_wires_catalog_before_company_and_client` — runs full `DatabaseSeeder` on fresh DB, asserts call order (users → catalog trio → Company/Client), asserts canonical columns persisted through chain (covers R6/S6.1–S6.3)
- [x] 1.12 RED: Write `test_seeded_rows_do_not_leak_between_tests` — ensures seed tests see 3/7/12 and catalog-free tests see 0 base rows under `RefreshDatabase` (covers R7/S7.1)
- [x] 1.13 RED: Verify `#[Seed]` / `#[Seeder(` grep returns zero matches in new files (covers R7/S7.3)

## Phase 2: GREEN — Implement the Three Catalog Seeders

- [x] 2.1 GREEN: Create `backend/database/seeders/RubroSeeder.php` — `final class RubroSeeder extends Seeder { use WithoutModelEvents; public function run(): void { ... } }` with inline array of 3 rubros, deterministic UUIDv7 `id` constants, fixed UTC timestamp `2026-08-29 00:00:00`, `DB::transaction` wrapping `DB::table('rubros')->upsert($rows, ['name'], ['status','description','updated_at','deleted_at'])`, explicit `deleted_at => null` in update list
- [x] 2.2 GREEN: Create `backend/database/seeders/CategoriaSeeder.php` — resolves 3 parent rubros by `name` via `Rubro::where('name', ...)->firstOrFail()` before batch, inline array of 7 categories with `rubro_id`, `name`, `order`, `description`, deterministic UUIDv7 `id` constants, same fixed timestamp, transactional `upsert` on `['rubro_id','name']` with update list including `deleted_at => null`
- [x] 2.3 GREEN: Create `backend/database/seeders/ServiceSeeder.php` — resolves 7 parent categories via explicit `(rubro name → Rubro id → category name under that rubro → Categoria id)` chain (never bare name), inline array of 12 services with `categoria_id`, `title`, `value`, `tags` (JSON-encoded), generated description via `generateDescription()` template `"Servicio profesional: {$title}. Valor referencial, precio final sujeto a conversación según requerimientos."`, deterministic UUIDv7 `id` constants, same fixed timestamp, transactional `upsert` on `['categoria_id','title']` with update list including `deleted_at => null`
- [x] 2.4 GREEN: Modify `backend/database/seeders/DatabaseSeeder.php` — insert `$this->call([RubroSeeder::class, CategoriaSeeder::class, ServiceSeeder::class]);` immediately after inline user seeding and before `CompanySeeder`/`ClientSeeder` calls

## Phase 3: REFACTOR — Code Quality & Formatting

- [x] 3.1 REFACTOR: Run Pint on all new/modified PHP files: `docker compose exec backend vendor/bin/pint database/seeders/RubroSeeder.php database/seeders/CategoriaSeeder.php database/seeders/ServiceSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/BaseCatalogSeederTest.php`
- [x] 3.2 REFACTOR: Verify no unused imports, no PHPCS warnings, consistent code style with existing seeders
- [x] 3.3 REFACTOR: Ensure each seeder file is small and focused (< 100 lines each); test class is well-organized with clear method separation

## Phase 4: VERIFY — Dual-Engine Test Suite & Regression

- [x] 4.1 VERIFY: Run SQLite catalog test: `docker compose exec backend php artisan test --filter=BaseCatalogSeederTest`
- [x] 4.2 VERIFY: Run PostgreSQL catalog test: `./test-pg.sh tests/Feature/BaseCatalogSeederTest.php`
- [x] 4.3 VERIFY: Run full SQLite suite: `docker compose exec backend php artisan test` (expect 219 baseline + new tests green)
- [x] 4.4 VERIFY: Run full PostgreSQL suite: `./test-pg.sh` (expect 219 baseline + new tests green)
- [x] 4.5 VERIFY: Run Pint check on touched files: `docker compose exec backend vendor/bin/pint --test database/seeders/RubroSeeder.php database/seeders/CategoriaSeeder.php database/seeders/ServiceSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/BaseCatalogSeederTest.php`
- [x] 4.6 VERIFY: Sync CodeGraph index: `codegraph sync && codegraph status` (only if watcher reports stale files)
- [x] 4.7 VERIFY: Run `grep -r '#\[Seed\]' backend/tests/Feature/BaseCatalogSeederTest.php backend/database/seeders/` — confirm zero matches (R7/S7.3)

## Phase 5: FALLBACK BRANCH — Conditional Revival Fallback (Activates Only if 4.1 or 4.2 RED on Revival Test)

- [x] 5.1 FALLBACK: If `test_trashed_rows_are_revived_and_deactivation_converges` fails on SQLite or PostgreSQL, replace `upsert` persistence in all three seeders with the pre-agreed fallback: `withTrashed()` lookup by natural key → `restore()` if trashed → `updateOrCreate()` with complete canonical payload, inside same per-seeder transaction, using `Model::withoutTimestamps` or explicit timestamp preservation for R3 byte-stability
- [x] 5.2 FALLBACK: Re-run verification steps 4.1–4.6 after fallback changes

## Phase 6: Close-Out — Progress Registry & Commit

- [x] 6.1 Update `docs/planning/planning3.md` — add Slice 5 progress entry with status, linked HU-024, deliverable summary (3 seeders, test class, DatabaseSeeder wiring)
- [x] 6.2 Mark HU-024 status as **En Revisión** in `docs/historias/HU-024.md` (per repo convention: HUs move to En Revisión, not Implementada, until frontend integration in Slices 6–7)
- [x] 6.3 Commit work unit using Conventional Commit: `feat(catalog): add idempotent base-catalog seeders (R1–R8)` with tests and DatabaseSeeder wiring — all files in one commit per work-unit-commits skill
- [x] 6.4 Document Notion mirror is orchestrator-owned post-apply (no task for this change)

## Out-of-Scope Guard (Explicit — Do Not Implement)

- [x] 7.1 GUARD: No edits to `CompanySeeder.php` or `ClientSeeder.php` — their non-idempotency is accepted out of scope (S8.1)
- [x] 7.2 GUARD: No migrations or model changes (S8.5)
- [x] 7.3 GUARD: No `UserSeeder.php` class introduced — users remain inline in `DatabaseSeeder` (S8.4)
- [x] 7.4 GUARD: No frontend files, routes, controllers, or cache warmup logic
- [x] 7.5 GUARD: No modifications to fork-api or personalization capabilities (R8 scope fence)
- [x] 7.6 GUARD: No assertions about second full `db:seed` outcome — catalog trio double-seed equality only (S8.2)

## Dependencies

- Phase 1 (RED) has no internal dependencies — all test methods can be written in parallel
- Phase 2 (GREEN) depends on Phase 1 being complete (tests exist and fail)
- Phase 2 tasks 2.1 → 2.2 → 2.3 have sequential FK dependency (RubroSeeder must exist before CategoriaSeeder can resolve; CategoriaSeeder before ServiceSeeder)
- Phase 3 depends on Phase 2 complete
- Phase 4 depends on Phase 3 complete
- Phase 5 (fallback) is conditional — only triggered if Phase 4 revival test fails on either engine
- Phase 6 depends on Phase 4 (or 5) passing

## Verification Commands Reference

| Purpose | Command |
|---------|---------|
| SQLite catalog test | `docker compose exec backend php artisan test --filter=BaseCatalogSeederTest` |
| PostgreSQL catalog test | `./test-pg.sh tests/Feature/BaseCatalogSeederTest.php` |
| Full SQLite suite | `docker compose exec backend php artisan test` |
| Full PostgreSQL suite | `./test-pg.sh` |
| Pint check (touched files) | `docker compose exec backend vendor/bin/pint --test database/seeders/RubroSeeder.php database/seeders/CategoriaSeeder.php database/seeders/ServiceSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/BaseCatalogSeederTest.php` |
| CodeGraph sync | `codegraph sync && codegraph status` |
| Grep for forbidden Seed attribute | `grep -r '#\[Seed\]' backend/tests/Feature/BaseCatalogSeederTest.php backend/database/seeders/` |

## Rollback Plan (Reiterated for Apply Phase)

Purely additive change + one call-site diff. To rollback:
1. Delete `backend/database/seeders/RubroSeeder.php`
2. Delete `backend/database/seeders/CategoriaSeeder.php`
3. Delete `backend/database/seeders/ServiceSeeder.php`
4. Delete `backend/tests/Feature/BaseCatalogSeederTest.php`
5. Remove the three catalog `$this->call(...)` lines from `backend/database/seeders/DatabaseSeeder.php`
6. Optionally truncate base tables in dependency order: `services`, `categorias`, `rubros` (or `migrate:fresh` in disposable environments)

No migrations, no model changes, no data backfills required. Nothing downstream depends on these seeders existing.
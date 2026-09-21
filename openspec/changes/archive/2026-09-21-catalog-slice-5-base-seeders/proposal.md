# Proposal: Catalog Slice 5 — Base-Catalog Idempotent Seeders

## Intent

planning3 Slice 5 must load the approved 22-row base catalog (D11 / HU-024) so development and demo environments have a deterministic catalog that users can fork from (Slice 4 API is live; the base tables are currently empty outside manual admin calls). Today `DatabaseSeeder` has no catalog seeders at all.

The flow doc's reference pattern (`firstOrCreate`) is unsafe as-is: models use `SoftDeletes` while the D5 unique indexes are unconditional, so a re-seed after an admin soft-deletes a base row crashes on a unique violation (docs-backed research, RQ1). The real requirement is therefore not "seed once" but "seed repeatedly, deterministically, on both engines".

## Scope

### In Scope
- Three seeders in `backend/database/seeders/`: `RubroSeeder`, `CategoriaSeeder`, `ServiceSeeder`. Content is exactly the approved HU-024 set in `docs/flujos/seeders-catalogo.md` (3 rubros / 7 categorías incl. placeholder `X` with zero services / 12 servicios with exact CLP values and D4-whitelist tags = 22 rows, all `status=activo`). Not restated here; the flow doc is the data source of truth.
- Deterministic idempotency mechanism (technical direction, final mechanics owned by design): explicit-key `upsert()` per seeder with `uniqueBy` = the D5 index columns exactly — `rubros(name)`, `categorias(rubro_id,name)`, `services(categoria_id,title)` — values carrying deterministic UUID `id`s + explicit timestamps, update arrays including `deleted_at => null` so a re-seed resurrects trashed rows.
- Each catalog seeder carries its own `WithoutModelEvents` (standalone `--class` parity; parent propagation alone is asymmetric per research RQ2).
- `DatabaseSeeder` wiring: `$this->call([...])` catalog chain inserted after the inline users, before `CompanySeeder`/`ClientSeeder`, per planning order.
- HU-024 feature test: `$this->seed([RubroSeeder, CategoriaSeeder, ServiceSeeder])` inside a `RefreshDatabase` test; assert 3/7/12 counts, FK linkage, CLP values, tags; **call the seeders a second time and assert identical counts — double-seed equality IS the idempotency contract**. No `#[Seed]` attribute.
- Mandatory dual-engine (SQLite + PostgreSQL) smoke test of trashed-row revival via `upsert()` (inferred behavior, Medium risk). Agreed fallback if revival fails on any engine: `withTrashed()` lookup + `restore()` + `updateOrCreate` (non-atomic, acceptable for dev seeding).

### Out of Scope (explicit)
- `CompanySeeder`/`ClientSeeder` idempotency — untouched. A second full `db:seed` may still abort on `companies.rut` uniqueness; accepted. The documented dev reset path is `migrate:fresh --seed`.
- Carried Slice 4 debt: HTTP-level 403 cross-owner DELETE test, JD4-5 naming asymmetry, 11-file legacy Pint debt — recorded as follow-ups, not Slice 5 deliverables.
- Frontend catalog pages (Slices 6–7), Redis cache warmup (planning design-note, optional), rate limiting, status-audit logging.
- No `UserSeeder` class is introduced: it does not exist in the repo (flow-doc drift); users stay inline in `DatabaseSeeder`.

**Affected HUs**: HU-024 (seed set + idempotency acceptance). Per the Slice 4 progress-registry rule, HU-013–HU-025 remain **open** — end-to-end operability still requires the frontend.

## Capabilities

### New Capabilities
- `base-catalog-seeding`: initial load of the 22-row base catalog — exact data set, seeder ordering/FK resolution, deterministic idempotency (double-seed equality), soft-deleted-row revival, standalone-run parity, and the seed-testing contract.

### Modified Capabilities
- None. `user-catalog-fork-api` and `user-catalog-personalization` requirements do not change; the `DatabaseSeeder` wiring is operational, not spec-level behavior.

## Approach

- **Upsert pattern**: `DB`-level `upsert()` per seeder keyed on the D5 columns (all backed by real unique indexes on both engines — verified against migrations). The trashed row is the ON CONFLICT target, closing both the soft-delete hole and the find-then-create race that `firstOrCreate` leaves open.
- **FK resolution**: `CategoriaSeeder` resolves rubros by `name`; `ServiceSeeder` resolves categorias by **(rubro, name) pairs, not bare names** (bare `name` is only unique per rubro — exploration gotcha).
- **Service descriptions**: reference-code generated-template variant (`"Servicio profesional: {title}. …"` derived from the flow doc's `generateDescription()`); final wording confirmed in design. Non-blocking.
- **Testing**: strict TDD; feature test seeded inside `RefreshDatabase` (transactional rollback, no leakage); run on SQLite (`phpunit.xml`) and PostgreSQL (`phpunit-pg.xml` / `test-pg.sh`); Pint on new files.
- **Review-budget forecast**: 3 seeders ≈ 120 LOC + 1 test class → comfortably a **single slice under the 400-line review budget**; no stacked PRs needed.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `backend/database/seeders/RubroSeeder.php` | New | 3 rubros, upsert on `name` |
| `backend/database/seeders/CategoriaSeeder.php` | New | 7 categorías, upsert on `(rubro_id,name)`, rubro resolved by name |
| `backend/database/seeders/ServiceSeeder.php` | New | 12 servicios, upsert on `(categoria_id,title)`, categoria resolved by (rubro,name), CLP + tags |
| `backend/database/seeders/DatabaseSeeder.php` | Modified | Catalog chain call after users, before Company/Client |
| `backend/tests/Feature/` (e.g. `BaseCatalogSeederTest.php`) | New | Counts, FK linkage, CLP, tags, double-seed idempotency |
| `docs/planning/planning3.md` progress registry | Modified (at close) | Slice 5 entry; not a deliverable of this change's code |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| `upsert()` revival of trashed rows is inferred, not doc-explicit | Med | Mandatory SQLite+PG smoke test in this change; pre-agreed non-atomic fallback (`withTrashed` + `restore` + `updateOrCreate`) |
| Engine divergence (SQLite FK enforcement off by default; ON CONFLICT details) | Low-Med | Dual-engine test run is part of the success criteria; `uniqueBy` columns are real indexes on both |
| Second full `db:seed` aborts on `companies.rut` (non-idempotent legacy) | Certain, accepted | Explicitly out of scope; dev workflow is `migrate:fresh --seed`; noted so CI/devs are not surprised |
| Standalone `--class` runs bypass parent `WithoutModelEvents` | Low | Each catalog seeder declares its own trait |
| Doc drift (flow doc references a non-existent `UserSeeder`; "SoftDeletes no aplica en seeders" note is superseded by the revival contract) | Low | Proposal follows verified repo state; flow-doc corrections belong to archive/close-out, not new scope |

## Rollback Plan

Purely additive change: delete the three seeder files and the new test class, and revert the one-call-site diff in `DatabaseSeeder` (prior chain restored). No migrations, no model changes, no data backfills. Seeded dev rows need no cleanup (idempotent inserts); optionally truncate the three base tables. Nothing downstream (Slices 1–4) depends on the seeders existing.

## Dependencies

- Slices 1–4 on `main` (models + D5-indexed migrations exist and are verified; fork API consumes base rows).
- Existing dual-engine test infrastructure (`phpunit.xml`, `phpunit-pg.xml`, `./test-pg.sh`, Docker Compose).

## Success Criteria

- [ ] `php artisan db:seed --class=…` per catalog seeder (standalone) and via `DatabaseSeeder` chain both yield exactly 3/7/12 rows (22 total, all `status=activo`).
- [ ] Double-seed equality: a second run of the trio leaves counts **and** row values identical — on SQLite **and** PostgreSQL.
- [ ] Soft-delete case proven: a trashed base row is resurrected deterministically by re-seeding (or the fallback is applied and proven) — dual-engine smoke test included in the change.
- [ ] HU-024 feature test asserts counts, FK linkage (categorias under correct rubros, services under correct categorias), exact CLP integers, and tags within the D4 whitelist.
- [ ] Full suite green (baseline 219 tests preserved) on both engines; Pint clean on all new/modified files.
- [ ] Single review slice ≤ 400 changed lines.

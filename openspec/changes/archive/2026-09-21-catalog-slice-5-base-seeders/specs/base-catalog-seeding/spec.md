# Base Catalog Seeding Specification

## Purpose

Deterministic, idempotent initial load of the approved HU-024 base catalog (3 rubros / 7 categorías / 12 services = 22 rows) via three dedicated seeders wired into `DatabaseSeeder`, so dev/demo environments always converge on the known base set users fork from. Normative dataset: `docs/flujos/seeders-catalogo.md` (Set Aprobado HU-024). Planning: `docs/planning/planning3.md` Slice 5 (D2/D4/D5/D9/D11). ADDED-only capability: no existing requirement changes.

## Requirements

### Requirement: R1 Approved 22-Row Dataset

The catalog seeders MUST persist exactly the HU-024 canonical set defined in `docs/flujos/seeders-catalogo.md`, copied verbatim (Spanish strings are data, not translated): 3 rubros — `Informática`, `Diseño`, `Consultoría`; 7 categorías with their parent rubro and canonical per-rubro `order` (Informática: `Sitios web y presencia digital`, `Aplicaciones a medida`, `Mantenimiento y soporte`; Diseño: `Identidad visual`, `UX/UI`; Consultoría: `Arquitectura y estrategia`, `X`); 12 services with exact `title`s, non-negative integer CLP `value`s (300000, 450000, 800000, 1200000, 900000, 200000, 400000, 600000, 350000, 500000, 500000, 400000) and `tags` ⊆ D4 whitelist {`frontend`, `backend`, `fullstack`, `devops`, `mobile`}. Every row MUST persist with `status = 'activo'` and `deleted_at = null`. D11 structural invariants are normative: Informática = 3 categorías / 6 servicios; 22 total rows. Every service MUST carry a non-empty `description`; the exact wording is design-owned (flow-doc table variant vs. `generateDescription()` template MUST be reconciled in design, then pinned by tests). Primary keys and timestamps MUST be stable across runs (observable via R3, not mandated as a mechanism here).

#### Scenario: S1.1-counts-and-status

- GIVEN migrated empty catalog tables
- WHEN the catalog trio is seeded once
- THEN `assertDatabaseCount` = 3 rubros / 7 categorías / 12 services; every row `status=activo`, `deleted_at` null

#### Scenario: S1.2-categoria-parentage-and-order

- GIVEN seeded catalog
- WHEN inspecting categorías
- THEN each row's `rubro_id` matches its flow-doc parent rubro; canonical `order` values match the flow-doc table (Informática 1–3, Diseño 1–2, Consultoría 1–2)

#### Scenario: S1.3-service-values-and-tags

- GIVEN seeded catalog
- WHEN inspecting services
- THEN each title maps to its exact CLP integer (e.g. `E-commerce básico` = 800000, `App móvil React Native` = 1200000) and its exact tag set (e.g. `['frontend','backend','fullstack']`, `['mobile','frontend']`); no tag outside the D4 whitelist

#### Scenario: S1.4-placeholder-X-empty

- GIVEN seeded catalog
- WHEN counting services under categoría `X` (rubro Consultoría)
- THEN 0; every other categoría holds ≥ 1 service

#### Scenario: S1.5-descriptions-pinned

- GIVEN design has fixed the description wording variant
- WHEN services are seeded
- THEN each of the 12 carries a non-empty `description` matching the pinned wording pattern (test asserts the pinned value, not the TBD choice)

### Requirement: R2 Seeder Order and FK Resolution

Catalog seeding MUST run in dependency order `rubros → categorías → services`. `CategoriaSeeder` MUST resolve each parent rubro by rubro `name` (globally unique per D5). `ServiceSeeder` MUST resolve each parent categoría by the (rubro `name`, categoría `name`) PAIR — a bare categoría `name` MUST NOT be a lookup key, because categoría names are unique only within a rubro. A dependent seeder run before its prerequisite rows exist MUST fail explicitly and persist ZERO rows (no partial catalog, no FK-orphan writes on either engine).

#### Scenario: S2.1-chain-order-integrity

- GIVEN empty catalog tables
- WHEN seeders run in Rubro→Categoria→Service order
- THEN all `categorias.rubro_id` and `services.categoria_id` FKs resolve to live rows with the correct parent identity

#### Scenario: S2.2-rubro-resolution-by-name

- GIVEN rubros seeded with fresh UUIDs
- WHEN CategoriaSeeder runs
- THEN each categoría lands under the rubro whose `name` matches its flow-doc parent, independent of actual id values

#### Scenario: S2.3-pair-disambiguation

- GIVEN an admin-created extra categoría `Identidad visual` under Informática (outside the canonical set)
- WHEN ServiceSeeder runs
- THEN `Logo + brand guide` persists under Diseño's `Identidad visual` (pair match), never under Informática's — a bare-name lookup would mis-resolve

#### Scenario: S2.4-out-of-order-aborts-clean

- GIVEN empty `rubros`
- WHEN CategoriaSeeder is run standalone
- THEN it fails loudly and `categorias` remains at 0 rows (ServiceSeeder under `categorias`-empty behaves identically)

### Requirement: R3 Idempotency — Double-Seed Equality

Re-running the catalog seeders any number of times MUST leave the three tables identical to the state after the first run: counts 3/7/12 unchanged AND every stored row value byte-equal (primary keys, `status`, `order`, `value`, `tags`, timestamps) — no unique-constraint errors, no duplicate rows, no drift. The contract MUST hold on BOTH SQLite and PostgreSQL and applies to runs via the `DatabaseSeeder` chain or via standalone `--class` invocations, with no external mutation between runs.

#### Scenario: S3.1-second-run-counts-identical

- GIVEN catalog seeded once
- WHEN the same seeders run a second time
- THEN counts remain 3/7/12; no error raised

#### Scenario: S3.2-second-run-values-identical

- GIVEN a full snapshot of the three tables after run 1
- WHEN run 2 completes
- THEN re-snapshot equals run 1 row-for-row, all columns (ids and timestamps included)

#### Scenario: S3.3-n-runs-equals-one

- GIVEN catalog seeded 3+ times consecutively
- WHEN compared against a single-run reference database
- THEN stored state is identical (any n ≡ 1)

#### Scenario: S3.4-dual-engine

- GIVEN the idempotency tests
- WHEN executed under `phpunit.xml` (SQLite) and `phpunit-pg.xml`/`./test-pg.sh` (PostgreSQL)
- THEN green on both engines

### Requirement: R4 Soft-Deleted Base Row Revival

If a base catalog row carries `deleted_at` (via admin action or direct DB manipulation), re-running its seeder MUST restore the canonical row: exactly ONE live row, canonical values, `deleted_at = null`, count invariant preserved (no fresh duplicate beside the trashed row). Re-seeding MUST also restore canonical values mutated between runs — including an admin-set `status='desactivado'` — so the approved row set always converges to canonical state (D2: base rows are seeder/admin property). The outcome is mechanism-agnostic: whichever persistence design picks — and the pre-agreed non-atomic fallback (trashed-visible lookup + restore + update) — the stored result MUST equal S4.1–S4.3 on both engines.

#### Scenario: S4.1-trashed-rubro-revived

- GIVEN rubro `Diseño` soft-deleted (trashed row present)
- WHEN the catalog trio re-runs
- THEN exactly one `Diseño` row exists, live (`deleted_at` null), canonical values; `rubros` count = 3

#### Scenario: S4.2-trashed-child-rows-revived

- GIVEN a categoría and a service individually trashed
- WHEN their seeders re-run
- THEN both restored live and canonical, FK parentage intact; counts 7/12 hold

#### Scenario: S4.3-deactivation-overwritten

- GIVEN admin set a base service `status='desactivado'` after seeding
- WHEN the trio re-runs
- THEN the row is back to `status='activo'` (canonical convergence)

#### Scenario: S4.4-revival-both-engines

- GIVEN the revival scenarios above
- WHEN executed on SQLite and PostgreSQL
- THEN identical stored outcome on both (mandatory smoke evidence for the inferred mechanism or its fallback)

### Requirement: R5 Standalone Parity

Each of `RubroSeeder`, `CategoriaSeeder`, `ServiceSeeder` MUST persist the same rows and values when run standalone via `php artisan db:seed --class=<Seeder>` (prerequisites seeded in R2 order) as via the `DatabaseSeeder` chain. Model-event silence MUST be declared by each catalog seeder itself: seeding MUST emit no observable model events in EITHER invocation mode; silence MUST NOT rely solely on parent-seeder propagation (standalone runs bypass the parent).

#### Scenario: S5.1-standalone-trio-full-set

- GIVEN fresh migrated tables
- WHEN the three seeders are invoked standalone in flow-doc command order
- THEN stored state equals the chain-seeded reference (22 canonical rows, values identical)

#### Scenario: S5.2-chain-vs-standalone-equal

- GIVEN DB seeded via `DatabaseSeeder`
- WHEN the same trio is re-run standalone on a chain-seeded DB and, separately, standalone on a fresh DB
- THEN both paths yield byte-equal stored rows

#### Scenario: S5.3-event-silence-both-modes

- GIVEN a model event listener registered on Rubro/Categoria/Service
- WHEN any catalog seeder runs standalone or through the chain
- THEN the listener records zero model events

### Requirement: R6 DatabaseSeeder Integration

`DatabaseSeeder` MUST call the three catalog seeders as an ordered chain Rubro→Categoria→Service, placed after the inline user seed and before `CompanySeeder`/`ClientSeeder` (planning order). A full `php artisan db:seed` on a freshly migrated DB MUST complete, persisting the inline users, the 22-row catalog, companies and clients; inline-user and Company/Client stored outcomes MUST be unchanged from today's behavior. All canonical column values MUST persist through the chain regardless of model `$fillable` (mass-assignment protection is a framework guarantee during seeding — this spec asserts the stored outcome only, not the guard mechanism).

#### Scenario: S6.1-full-seed-completes

- GIVEN `migrate:fresh` DB
- WHEN `php artisan db:seed` runs once
- THEN inline users + 3/7/12 catalog rows + companies + clients all present, zero errors

#### Scenario: S6.2-wiring-order-observed

- GIVEN seeder-start events recorded during a full run
- WHEN inspecting the sequence
- THEN the catalog trio appears after inline user seeding, before `CompanySeeder`/`ClientSeeder`, in dependency order

#### Scenario: S6.3-no-column-silently-dropped

- GIVEN rows seeded via the full chain
- WHEN comparing every canonical column (status, order, value, tags, timestamps) against the flow-doc dataset
- THEN all persisted (nothing lost to fillable restrictions)

### Requirement: R7 Seed-Testing Contract

The HU-024 feature test MUST invoke the trio explicitly via `$this->seed([...])` inside a `RefreshDatabase` test. Seeded state MUST NOT leak across tests — each test observes only its own seeding. The test MUST execute an explicit SECOND seed run with equality assertions (double-seed equality is pinned in code, never assumed). The `#[Seed]`/`#[Seeder]` auto-run attributes MUST NOT be used anywhere in this change. The test suite MUST pass on both engines (`phpunit.xml` and `phpunit-pg.xml`/`./test-pg.sh`).

#### Scenario: S7.1-no-cross-test-leakage

- GIVEN seed tests and catalog-free tests interleaved in one run
- WHEN each executes
- THEN seed tests see exactly 3/7/12 and catalog-free tests see 0 base rows (transactional rollback)

#### Scenario: S7.2-second-run-asserted-explicitly

- GIVEN the HU-024 test
- WHEN inspected and executed
- THEN it contains a second `$this->seed(...)` call followed by equality assertions, green on both engines

#### Scenario: S7.3-no-seed-attribute

- GIVEN all files added/modified by this change
- WHEN grepped for `#[Seed]` / `#[Seeder(`
- THEN zero matches (framework auto-seeding not engaged)

#### Scenario: S7.4-dual-engine-suite-green

- GIVEN the new test class
- WHEN run under SQLite config and PostgreSQL config
- THEN green both, existing baseline suite untouched and green

### Requirement: R8 Scope Fences (Negative Requirements)

This change MUST NOT modify `CompanySeeder` or `ClientSeeder` — their non-idempotency is explicitly accepted (a second FULL `db:seed` MAY abort on `companies.rut`; the documented dev reset remains `migrate:fresh --seed`). It MUST NOT alter any requirement of capabilities `user-catalog-fork-api` or `user-catalog-personalization` (this delta is ADDED-only; wiring is operational, not spec-level behavior). It MUST NOT introduce a `UserSeeder` class (flow-doc drift — users stay inline in `DatabaseSeeder`). It MUST NOT add migrations or model changes.

#### Scenario: S8.1-company-client-seeders-untouched

- GIVEN base vs. change branch
- WHEN diffing `CompanySeeder.php` / `ClientSeeder.php`
- THEN zero differences

#### Scenario: S8.2-full-run-second-seed-unasserted

- GIVEN the acceptance suite
- WHEN inspected
- THEN it asserts double-seed equality for the CATALOG TRIO only and makes no assertion about a second full `db:seed` outcome

#### Scenario: S8.3-existing-capabilities-unregressed

- GIVEN fork-api and personalization suites (219-test baseline)
- WHEN run on the change branch
- THEN green on both engines; their spec files show no MODIFIED/REMOVED sections from this delta

#### Scenario: S8.4-no-user-seeder

- GIVEN `backend/database/seeders/` after the change
- WHEN listed
- THEN contains no `UserSeeder.php`; `DatabaseSeeder` still seeds users inline

#### Scenario: S8.5-schema-and-models-frozen

- GIVEN base vs. change branch
- WHEN diffing `database/migrations/` and `app/Models/`
- THEN zero differences

## Out-of-scope (non-requirement notes)

Company/Client seeder idempotency; carried Slice 4 debt (HTTP-level 403 cross-owner DELETE test, JD4-5 visible-name asymmetry, 11-file legacy Pint) — recorded follow-ups, not Slice 5 deliverables; frontend catalog pages (Slices 6–7); optional Redis cache warmup (planning design-note); rate limiting; status-audit logging.

## Traceability

HU-024 (exact set + idempotency acceptance); planning3 decisions D2, D4, D5, D9, D11; proposal `catalog-slice-5-base-seeders` capability contract; dataset source of truth `docs/flujos/seeders-catalogo.md`; research basis Engram `sdd/catalog-slice-5-base-seeders/research` (soft-delete/upsert trap, RQ1–RQ3).

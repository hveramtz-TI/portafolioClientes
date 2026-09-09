# Apply Progress — catalog-slice-3-personalization-engine

## Work Unit: 3a-relation-policy (R1, R2; S1.1, S1.2, S2.1, S2.2)

- **Mode**: Strict TDD (RED → GREEN → REFACTOR)
- **Engine**: Laravel 13.26.0, PHPUnit 12, SQLite (default) + PostgreSQL (test-pg.sh)

## Completed Tasks (this work unit)

- [x] 2.1 RED: `UserCatalogItemPolicyTest.php` + `UserCatalogItemRelationTest.php`
- [x] 2.2 GREEN: `base()` morphTo in `UserCatalogItem`
- [x] 2.3 GREEN: morph map in `AppServiceProvider::boot()`
- [x] 2.4 GREEN: `UserCatalogItemPolicy`
- [x] 2.5 REFACTOR: Pint clean

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 2.1/2.4 | `tests/Feature/UserCatalogItemPolicyTest.php` | Feature | 90/90 | ✅ Written (owner+deny fail: no policy) | ✅ Passed | ✅ 5 cases (3 owner + other + admin) | ✅ Clean |
| 2.1/2.2/2.3 | `tests/Feature/UserCatalogItemRelationTest.php` | Feature | 90/90 | ✅ Written (base() undefined → null) | ✅ Passed | ✅ 4 cases (service/rubro/categoria + null) | ✅ Clean |

## RED Evidence (first failing run, bounded)

```
Tests:    6 failed, 3 passed (13 assertions)

FAILED  Tests\Feature\UserCatalogItemRelationTest > service fork resolves base to service
  Failed asserting that null is an instance of class App\Models\Service.
FAILED  Tests\Feature\UserCatalogItemRelationTest > rubro fork resolves base to rubro
  Failed asserting that null is an instance of class App\Models\Rubro.
FAILED  Tests\Feature\UserCatalogItemRelationTest > categoria fork resolves base to categoria
  Failed asserting that null is an instance of class App\Models\Categoria.
```

The 3 "passed" in RED were the negative/null assertions (deny + null base) passing trivially
before `base()`/policy existed; the 6 positive assertions failed for the expected reason.

## GREEN Evidence

Focused (SQLite): `docker compose exec backend php artisan test --filter='UserCatalogItemPolicyTest|UserCatalogItemRelationTest'`
→ **9 passed (16 assertions)**.

Focused (PostgreSQL): `./test-pg.sh --filter='UserCatalogItemPolicyTest|UserCatalogItemRelationTest'`
→ **OK (9 tests, 16 assertions)**.

Full suite (SQLite): `docker compose exec backend php artisan test`
→ **99 passed (250 assertions)** (baseline 90/234 → +9 tests, +16 assertions).

Pint: `./vendor/bin/pint --test <5 files>` → **PASS, 5 files** (converged, no changes).

## Work Unit Evidence

| Evidence | Value |
|---|---|
| Focused test command + result | `php artisan test --filter='UserCatalogItemPolicyTest\|UserCatalogItemRelationTest'` → 9 passed (16 assertions) |
| Runtime harness | N/A (unit/feature, no external deps; domain engine only, no routes) |
| Rollback boundary | Revert `UserCatalogItem.php`, `AppServiceProvider.php`, `UserCatalogItemPolicy.php`, `UserCatalogItemPolicyTest.php`, `UserCatalogItemRelationTest.php` |

## API Decision (morph map registration)

- **Used**: `Relation::morphMap([...])` (non-enforcing) in `AppServiceProvider::boot()`.
- **Alternative considered**: `Relation::enforceMorphMap([...])` — rejected: it additionally enables
  strict morph-map mode app-wide (`Relation::requireMorphMap()`), which is unnecessary for this
  read-only `base()` relation and could surface `ClassMorphViolationException` on any future
  unmapped polymorphic type.
- **morphTo signature**: `morphTo(__FUNCTION__, 'item_type', 'base_id')` → `morphTo('base', 'item_type', 'base_id')`.
- **Policy discovery**: `App\Models\UserCatalogItem` → `App\Policies\UserCatalogItemPolicy` (auto-discovery,
  no manual `Gate::policy` registration).
- **Doc source**: Context7 `/laravel/docs` → `eloquent-relationships.md` ("Custom Polymorphic Types",
  "Customize Polymorphic Key Conventions") and `authorization.md` ("Policy Discovery"). All three APIs
  also verified against vendored `laravel/framework` source (Relation.php:542/556/592; HasRelationships.php:416;
  Gate.php:326/338/859).

## Deviations

- Relation test placed in `tests/Feature/UserCatalogItemRelationTest.php` (not `tests/Unit/` as tasks.md 2.1
  wrote) — orchestrator instruction: DB-touching tests belong in `tests/Feature` extending `Tests\TestCase`,
  matching existing convention. tasks.md 2.1 path corrected accordingly.

## Risks

- `Relation::morphMap` is non-enforcing; if a future code path stores a full class name in `item_type`,
  resolution would fall back to the class name (no aliasing). Current check constraint limits `item_type`
  to `rubro|categoria|service`, so no practical risk.

## Work Unit: 3a-requests (R3, R4; S3.1, S3.2, S3.3, S4.1, S4.2)

- **Mode**: Strict TDD (RED → GREEN → REFACTOR)
- **Engine**: Laravel 13.26.0, PHPUnit 12, SQLite (default) + PostgreSQL (test-pg.sh)

## Completed Tasks (this work unit)

- [x] 3.1 RED: `UserCatalogItemRequestTest.php` (41 tests, all scenarios)
- [x] 3.2 GREEN: `StoreUserCatalogItemRequest.php`
- [x] 3.3 GREEN: `UpdateUserCatalogItemRequest.php` (+ `mergedOverrides()`)
- [x] 3.4 REFACTOR: shared trait `Concerns/ValidatesUserCatalogItem` + Pint clean
- [x] 3.5 VERIFY: SQLite + PostgreSQL + Pint (evidence below)

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 3.1/3.2/3.3 | `tests/Feature/UserCatalogItemRequestTest.php` | Feature | 99/99 (full suite, 250 assertions) | ✅ Written (41 failed: classes not found) | ✅ Passed | ✅ 41 cases (all S3.x/S4.x scenarios) | ✅ Trait extracted + Pint clean |

## RED Evidence (first failing run, bounded)

```
Class "App\Http\Requests\UpdateUserCatalogItemRequest" not found

  at tests/Feature/UserCatalogItemRequestTest.php:91
     89▕     private function makeRequest(string $class, array $payload, ?User $user, ...)
  ➜  91▕         $request = $class::create(

Tests:    41 failed (0 assertions)
Duration: 0.35s
```

## GREEN Evidence

Focused (SQLite): `docker compose exec backend php artisan test --filter=UserCatalogItemRequestTest`
→ **41 passed (63 assertions)**, no risky.

Focused (PostgreSQL): `./test-pg.sh --filter=UserCatalogItemRequestTest`
→ **OK (41 tests, 63 assertions)**.

Full suite (SQLite): `docker compose exec backend php artisan test`
→ **140 passed (313 assertions)** (baseline 99/250 → +41 tests, +63 assertions, zero regression).

Pint: `./vendor/bin/pint --test <4 files>` → **PASS, 4 files** (converged after first run).

## Work Unit Evidence

| Evidence | Value |
|---|---|
| Focused test command + result | `php artisan test --filter=UserCatalogItemRequestTest` → 41 passed (63 assertions), SQLite + PG |
| Runtime harness | N/A (no routes exist; requests exercised via `FormRequest::validateResolved()` with JSON input, container, user resolver, and a bound `Route` param — full request lifecycle incl. `#[FailOnUnknownFields]` hook) |
| Rollback boundary | Revert `StoreUserCatalogItemRequest.php`, `UpdateUserCatalogItemRequest.php`, `Concerns/ValidatesUserCatalogItem.php`, `UserCatalogItemRequestTest.php` |

## API Decisions (verified against vendored laravel/framework source)

- **Unknown-key rejection**: `#[FailOnUnknownFields]` class attribute (Laravel 13 first-class) →
  `FormRequest::getValidatorInstance()` registers an `after()` hook calling `validateNoUnknownFields()`,
  which adds a `validation.prohibited` error for any top-level input key absent from `rules()` — real 422
  (ValidationException::$status = 422), NOT prepareForValidation unsetting. Source:
  `vendor/laravel/framework/src/Illuminate/Foundation/Http/FormRequest.php` (getValidatorInstance:122-146,
  shouldFailOnUnknownFields:216-227, validateNoUnknownFields:230-246) +
  `src/Illuminate/Foundation/Http/Attributes/FailOnUnknownFields.php`.
- **Omitted-vs-null (update)**: `nullable` per display field (present-null passes and stays in
  `validated()`), `array_key_exists($field, $this->validated())` distinguishes omitted (absent) from
  explicit null (present), `mergedOverrides()` sets on present, unsets on null. Verified in
  `Validator::validated()` (returns data value when key present incl. null, skips missing keys:
  `src/Illuminate/Validation/Validator.php:647-673`) and `isNotNullIfMarkedAsNullable` (typed rules run
  on present-null unless `nullable` is set: Validator.php:858+).
- **Fork identity**: `Rule::unique('user_catalog_items', 'base_id')->where(closure)` with
  `where('user_id')->where('item_type')->whereNull('deleted_at')` (Unique is implicit; added only when
  `base_id` is a non-empty string to avoid the null→IS NULL trap). `Route::setParameter()` (Route.php:456)
  + `setRouteResolver()` used in tests to bind the model for `authorize()`.
- **Custom rules**: closure rules (non-implicit → skip on null/absent) for sibling-name uniqueness,
  parent coherence, and rubro-parent prohibition; all DB reads through Eloquent (no raw SQL).

## Decisions (required/optional fields, tied to scenarios)

| # | Decision | Scenario |
|---|---|---|
| D-a | Personal rubro create requires `name` | S3.2 rubro variant + R4 |
| D-b | Personal categoria create requires `name` + `parent_fork_id` (rubro fork) | S3.3 + S4.2 |
| D-c | Personal service create requires `title` + `value` (≥0) + `parent_fork_id` (categoria fork) | S4.2 (value required), S3.2 service, S3.3 |
| D-d | Fork-of-base create (base_id set): no display fields required, all optional | S3.1 fork valid + R3 inheritance |
| D-e | Sibling name uniqueness applies only to personal items (base_id null), same user+type+parent, ignoring soft-deleted siblings | S3.2 |
| D-f | Parent coherence unconditional (type rubro→none, categoria→rubro fork, service→categoria fork, same user, not trashed) | S3.3 |
| D-g | `status` never writable (store+update, 422); unknown top-level keys rejected (422) | S4.2 |
| D-h | Update = override personalization only: display fields nullable; structural keys (item_type/base_id/parent_fork_id) rejected | D-3/D13, R5 |

## Test Methods (41)

Store: `test_store_valid_service_fork_passes`, `test_store_duplicate_fork_is_rejected`,
`test_store_duplicate_fork_against_soft_deleted_fork_passes`, `test_store_duplicate_fork_allowed_for_other_user`,
`test_store_personal_service_title_unique_per_parent`, `test_store_personal_service_distinct_title_passes`,
`test_store_personal_service_same_title_different_parent_passes`, `test_store_personal_service_title_clash_ignores_soft_deleted_sibling`,
`test_store_personal_rubro_name_unique`, `test_store_personal_rubro_distinct_name_passes`,
`test_store_personal_categoria_name_unique_per_parent`, `test_store_personal_categoria_same_name_different_parent_passes`,
`test_store_service_parent_must_be_categoria_fork`, `test_store_categoria_parent_must_be_rubro_fork`,
`test_store_rubro_with_parent_is_rejected`, `test_store_personal_categoria_with_rubro_fork_parent_passes`,
`test_store_personal_service_with_categoria_fork_parent_passes`, `test_store_parent_owned_by_another_user_is_rejected`,
`test_store_forked_categoria_with_rubro_fork_parent_passes`, `test_store_service_tags_whitelist_passes`,
`test_store_service_tags_outside_whitelist_fails`, `test_store_status_key_is_rejected`,
`test_store_unknown_top_level_key_is_rejected`, `test_store_negative_value_is_rejected`,
`test_store_personal_service_without_value_is_rejected`, `test_store_personal_service_without_title_is_rejected`,
`test_store_personal_rubro_without_name_is_rejected`, `test_store_personal_categoria_without_name_is_rejected`,
`test_store_personal_categoria_without_parent_is_rejected`, `test_store_authenticated_user_is_authorized`,
`test_store_guest_is_denied`.

Update: `test_update_owner_is_authorized`, `test_update_non_owner_is_denied`, `test_update_guest_is_denied`,
`test_update_omitted_field_keeps_existing_override`, `test_update_present_value_sets_override`,
`test_update_explicit_null_removes_override`, `test_update_status_is_rejected`, `test_update_unknown_key_is_rejected`,
`test_update_personal_service_renamed_to_duplicate_sibling_title_is_rejected`,
`test_update_personal_service_keeping_own_title_passes`.

## Deviations

- Update request validates ONLY override display fields (structural keys rejected) — spec/design leave
  parent/base/type moves undefined; per-field override semantics (D-3, D13) cover R5 S5.2/S5.3, so
  immutability is encoded instead of inventing a move contract. Note for Slice 4 when routes land.
- `#[FailOnUnknownFields]` attribute used instead of a custom withValidator/after hook — the Laravel 13
  first-class mechanism (verified in vendored source) that produces the same 422 semantics S4.2 demands.
- Test file is larger than the 280–340 line forecast (675 lines): the brief mandated 30+ named scenarios
  each as its own test method. Recommend `size:exception` for this work unit's test file.

## Risks

- `Rule::unique` on `base_id` uses `= null → IS NULL` conversion if ever added for null base_id; the
  current code only attaches it when `base_id` is a non-empty string, so personal items are safe.
- Sibling uniqueness is checked in PHP over the user's personal siblings (small per-user set, KISS);
  if personalization scales past thousands of siblings per user, move the JSON path comparison into the
  query (`overrides->name`) on both engines.

## Work Unit: 3b-resolver (R5, R6; S5.1, S5.2, S5.3, S6.1, S6.2, S6.3)

- **Mode**: Strict TDD (RED → GREEN → REFACTOR)
- **Engine**: Laravel 13.26.0, PHPUnit 12, SQLite (default) + PostgreSQL (test-pg.sh)

## Completed Tasks (this work unit)

- [x] 6.1 RED: `tests/Feature/CatalogResolverTest.php` (14 tests, all S5.x/S6.x scenarios + N+1 guards)
- [x] 6.2 GREEN: `app/Services/CatalogResolver.php` — resolve/origin/effectiveStatus per D-4
- [x] 6.3 REFACTOR: Pint clean
- [x] 6.4 VERIFY: focused SQLite + PG + full suite + Pint (evidence below)
- [ ] 6.5 Commit (orchestrator-owned)
- [ ] 6.6 Settle ledger (orchestrator-owned)

## Follow-up: recursive effective status (S6.4, pre-commit amendment)

- Spec amended by orchestrator: R6 now states the recursive rule and adds S6.4
  (`specs/user-catalog-personalization/spec.md`), grounded in
  `docs/flujos/rubro-categoria-servicio-lifecycle.md` ("Rubro base desactivado
  bloquea el árbol → forks afectados quedan efectivamente ocultos"; "Estado
  efectivo: propio + base + padres fork"; "AND de toda la cadena").
- **Change**: `effectiveStatus()` is now recursive — own status ∨ own base
  status ∨ `effectiveStatus(parentFork)` — so an ancestor fork counts as
  desactivado through its OWN effective status (its base, its ancestors).
- **Graph extension**: `loadResolutionGraph()` now also eager-loads each
  ancestor fork's base (`parentFork.base`, `parentFork.parentFork.base`); the
  preloaded-collection contract updated to the same list. Recursion adds ZERO
  queries on a loaded chain (loadMissing is a no-op there) and terminates at a
  null parent (chains strictly shallower by construction; no memoization needed).
- Test premise gotcha: `Model::create()` does not pull DB column defaults back
  into memory — `$fork->status` is null in-memory right after create while the
  row is 'activo'; premise assertions use `->fresh()->status`.

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 6.1/6.2/6.3/6.4 | `tests/Feature/CatalogResolverTest.php` | Feature | 140/140 (full suite, 313 assertions) | ✅ Written (12 failed: class not found) | ✅ Passed | ✅ 14 cases (all S5.x/S6.x + origin matrix + N+1) | ✅ Pint clean |
| S6.4 follow-up | `tests/Feature/CatalogResolverTest.php` | Feature | 152/152 (full suite) | ✅ Written (2 failed: 'desactivado' vs 'activo') | ✅ Passed | ✅ 2 cases (rubro base → depth-3; categoria base mid-chain) | ✅ Pint clean |

## RED Evidence (first failing run, bounded)

```
Tests:    12 failed (0 assertions)
FAILED  Tests\Feature\CatalogResolverTest > resolve of fresh item…
  Error  Class "App\Services\CatalogResolver" not found
  at tests/Feature/CatalogResolverTest.php:35
```

## RED Evidence (S6.4 follow-up, flat implementation, bounded)

```
FAILED  Tests\Feature\CatalogResolverTest > s6 4 deactivated rubro base cascades to deep fork
  Failed asserting that two strings are identical.
  -'desactivado'
  +'activo'
  at tests/Feature/CatalogResolverTest.php:337
FAILED  Tests\Feature\CatalogResolverTest > s6 4 deactivated categoria base cascades to service fork
  Failed asserting that two strings are identical.
  -'desactivado'
  +'activo'
  at tests/Feature/CatalogResolverTest.php:354
```

## GREEN Evidence

Focused (SQLite): `docker compose exec backend php artisan test --filter=CatalogResolverTest`
→ **14 passed (60 assertions)**.

Focused (PostgreSQL): `./test-pg.sh --filter=CatalogResolverTest`
→ **OK (14 tests, 60 assertions)**.

Full suite (SQLite): `docker compose exec backend php artisan test`
→ **154 passed (373 assertions)** (baseline 140/313 → +14 tests, +60 assertions, zero regression).

Pint: `./vendor/bin/pint --test app/Services/CatalogResolver.php tests/Feature/CatalogResolverTest.php`
→ **PASS, 2 files** (converged).

## resolve() Sample Output (real dump, test_s5_2_override_wins_and_is_listed)

```json
{
    "id": "01a087b8-44f2-73c2-9e28-a534e1b6a1f4",
    "base_id": "01a087b8-44f1-70c0-8941-ad5283795291",
    "title": "API",
    "description": null,
    "value": 350000,
    "tags": null,
    "status": "activo",
    "origin": "override",
    "overridden_fields": ["value"]
}
```

## Work Unit Evidence

| Evidence | Value |
|---|---|
| Focused test command + result | `php artisan test --filter=CatalogResolverTest` → 14 passed (60 assertions), SQLite + PG |
| Runtime harness | N/A (unit/feature, no external deps; domain engine only, no routes) |
| Rollback boundary | Delete `CatalogResolver.php`, `CatalogResolverTest.php` |
| Line counts | `CatalogResolver.php` 143, `CatalogResolverTest.php` 426 (total 569; forecast 260–330) |

## N+1 Guard Decision (design D-4)

Two tests, both via `DB::listen` query counting:

1. `test_resolve_over_preloaded_chain_adds_no_queries` — 5 independent depth-3 trees (service→categoria→rubro
   fork) fetched once with `with(['base','parentFork.base','parentFork.parentFork.base',
   'parentFork.parentFork.parentFork'])`, then `resolve()` each item:
   **0 additional queries**. This is the exact contract D-4 protects: a collection consumer preloads the
   graph, and the resolver reuses it (`loadMissing` is idempotent) instead of lazy-loading per level (which
   would be 5×5 = 25 queries). The preload list includes each ancestor fork's base because the recursive
   effective status (S6.4) reads them; recursion itself adds zero queries on a loaded chain.
2. `test_resolve_of_fresh_item_has_bounded_eager_queries` — resolver on a NOT preloaded item is
   self-sufficient: personal item → 0 queries; depth-2 fork → ≤ 3 (base + parent fork + parent base);
   depth-3 fork → ≤ 5 (+2 for the second fork level and its base); depth-3 ≤ depth-2 + 2
   (constant eager budget of one query per fork level and per ancestor base).

Rejected alternatives: counting exact per-level queries (brittle to Eloquent internals), and asserting a
linear bound across collections of N items (per-item eager loading IS linear and acceptable per the brief).
Verified against vendored `laravel/framework` source: `BelongsTo::getResults()` returns the default (null)
without querying when the FK is null (BelongsTo.php:119-124); `BelongsTo::getEagerModelKeys()` filters null
keys and `initRelation()` marks every model's relation loaded (default null), so the ancestor walk never
triggers lazy loads; `MorphTo::addEagerConstraints()` skips null morph keys (MorphTo.php:117). The chain
walk terminates on the data itself (null `parent_fork_id`), so no query fires at the top of the chain.

## API Decisions

- **Type→field map**: resolver owns a private `FIELDS` const mirroring
  `ValidatesUserCatalogItem::displayFields()` (service: title/description/value/tags; rubro/categoria:
  name/description). Duplication is deliberate — hard constraint forbade editing the requests/trait; noted
  as a coupling risk for future field additions.
- **`base_id` guard**: `$item->base_id !== null ? $item->base : null` — never touches the morph relation on
  personal items (MorphTo eager load skips null keys, so the relation stays unloaded; lazy access on a null
  morph id returns null without a query, but the guard makes it explicit).
- **Soft-deleted bases/forks**: relation queries apply the SoftDeletingScope — trashed bases resolve to
  null and trashed ancestor forks end the chain; no resurrection code needed (a trashed base costs one
  bounded query per resolve via MorphTo lazy access, never N+1).
- **Effective status scope — RESOLVED**: `effectiveStatus()` is recursive (own ∨ own base ∨
  `effectiveStatus(parentFork)`), per the amended R6/S6.4 and the flow-doc cascade rule: a deactivated
  rubro base cascades to service forks two levels below, and a deactivated base mid-chain (categoria base)
  deactivates the service forks under its fork. No memoization needed: chains are strictly shallower by
  construction, recursion terminates at a null parent, and loadMissing keeps it query-free on loaded graphs.

## Test Methods (14)

`test_s5_1_service_fork_reflects_live_base_edit`, `test_s5_2_override_wins_and_is_listed`,
`test_s5_3_null_override_restores_inheritance`, `test_origin_matches_personal_override_and_base_states`,
`test_s6_1_deactivated_parent_fork_resolves_desactivado`,
`test_s6_1_deactivated_grandparent_fork_resolves_desactivado`,
`test_s6_2_deactivated_service_base_resolves_desactivado`,
`test_s6_2_deactivated_rubro_base_resolves_desactivado`,
`test_s6_3_full_active_chain_resolves_activo`, `test_s6_3_personal_item_own_status_decides`,
`test_s6_4_deactivated_rubro_base_cascades_to_deep_fork`,
`test_s6_4_deactivated_categoria_base_cascades_to_service_fork`,
`test_resolve_over_preloaded_chain_adds_no_queries`, `test_resolve_of_fresh_item_has_bounded_eager_queries`.

## Deviations

- Test file lives in `tests/Feature/` (not `tests/Unit/` as tasks.md 6.1 originally wrote) — DB-touching
  tests follow the slice-3a convention (RefreshDatabase, Model::create); tasks.md 6.1 corrected.
- Test file is 426 lines vs the 260–330 forecast for the whole 3b slice: the brief mandated every
  S5.x/S6.x scenario as its own test method plus two query-counting N+1 guards (and the S6.4 follow-up
  added two more). Implementation is 143 lines (well under). Recommend `size:exception` for the test file.

## Risks

- `FIELDS` const duplicates the trait's `displayFields()`; if display fields ever diverge per type the two
  lists must change together (both read the same base columns, so drift would surface as a failing test).
- MorphTo does not mark unmatched (trashed) bases as loaded (`matchToMorphParents` only sets matched
  models), so a resolve on a fork whose base is soft-deleted issues one extra query per resolve — bounded
  and documented, never N+1.
- **RESOLVED (was: effective status did not fold ancestor forks' base statuses)** — the recursive rule
  per the flow-doc cascade ("AND de toda la cadena") is implemented and covered by S6.4; orchestrator
  decision grounded in `docs/flujos/rubro-categoria-servicio-lifecycle.md`, spec amended accordingly,
  not new scope.

## Work Unit: 3b-cascade (R7; S7.1, S7.2, S7.3, S7.4)

- **Mode**: Strict TDD (RED → GREEN → REFACTOR)
- **Engine**: Laravel 13.26.0, PHPUnit 12, SQLite (default) + PostgreSQL (test-pg.sh)

## Completed Tasks (this work unit)

- [x] 7.2 RED: `tests/Feature/CascadeForkServiceTest.php` (9 tests, all S7.x scenarios + identity + not-found)
- [x] 7.3 GREEN: `app/Services/CascadeForkService.php` — forkRubro/forkCategoria/forkService, one `DB::transaction` each
- [x] 7.4 REFACTOR: Pint clean
- [ ] 7.5 VERIFY (orchestrator loop): evidence below, ready for full verification

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 7.2/7.3/7.4 | `tests/Feature/CascadeForkServiceTest.php` | Feature | 154/154 (full suite, 373 assertions) | ✅ Written (9 failed: class not found) | ✅ Passed | ✅ 9 cases (S7.1–S7.4 + identity ×2 + forkService + not-found ×2) | ✅ Pint clean |

## RED Evidence (first failing run, bounded)

```
FAILED  Tests\Feature\CascadeForkServiceTest > fork rubro trashed…
  Error  Class "App\Services\CascadeForkService" not found
  at tests/Feature/CascadeForkServiceTest.php:36

Tests:    9 failed (0 assertions)
```

## GREEN Evidence

Focused (SQLite): `docker compose exec backend php artisan test --filter=CascadeForkServiceTest`
→ **9 passed (94 assertions)**.

Focused (PostgreSQL): `./test-pg.sh --filter=CascadeForkServiceTest`
→ **OK (9 tests, 94 assertions)**.

Full suite (SQLite): `docker compose exec backend php artisan test`
→ **163 passed (467 assertions)** (baseline 154/373 → +9 tests, +94 assertions, zero regression).

Pint: `./vendor/bin/pint --test app/Services/CascadeForkService.php tests/Feature/CascadeForkServiceTest.php`
→ **PASS, 2 files** (2 `single_blank_line_at_eof` fixes applied, re-verified green).

## Work Unit Evidence

| Evidence | Value |
|---|---|
| Focused test command + result | `php artisan test --filter=CascadeForkServiceTest` → 9 passed (94 assertions), SQLite + PG |
| Runtime harness | N/A (unit/feature, no external deps; domain engine only, no routes) |
| Rollback boundary | Delete `CascadeForkService.php`, `CascadeForkServiceTest.php` |
| Line counts | `CascadeForkService.php` 193, `CascadeForkServiceTest.php` 356 (total 549; forecast 260–330) |

## Domain Signal Choices (documented)

- **Duplicate fork identity** → `\DomainException` (SPL, no new exception class) with message
  `"El usuario ya tiene un fork de este {item_type}."` — the simplest idiomatic domain rule signal,
  checked INSIDE the transaction, non-trashed only (SoftDeletes scope excludes trashed rows, so
  re-forking after a soft delete is allowed).
- **Missing / trashed base** → `Illuminate\Database\Eloquent\ModelNotFoundException` via
  `Model::findOrFail($baseId)` — the idiomatic Eloquent not-found signal; the SoftDeletingScope makes
  trashed bases miss too. No rows are created in either case.

## sort_order Decision

- Rubro: no ordering column → positional index (0 — single root fork).
- Categoria: `order` column exists → carried from `$categoria->order`.
- Service: no ordering column → positional index within its categoria's copied services (collection
  position). Test asserts the permutation property (each categoria's service forks hold exactly
  0..n-1 once each) instead of a specific service→index map, because PostgreSQL returns unordered
  rows without an ORDER BY.

## Documented Assumptions (Slice 4 follow-ups)

1. **Standalone categoria fork has parent_fork_id null** — per the flujo, a categoria may be forked
   without forking its rubro; the categoria fork is created with no rubro parent. Linking it into a
   rubro tree happens via Update (parent change) in Slice 4, which will also need to guard against
   orphaned roots.
2. **forkService creates the fork with parent_fork_id null** — a standalone service fork has nothing
   to attach to; attachment (parent_fork_id) happens via Update / parent change in Slice 4.

## Test Methods (9)

`test_s7_1_rubro_cascade_creates_full_tree_with_links`,
`test_s7_2_mid_copy_failure_rolls_back_everything`,
`test_s7_3_standalone_categoria_cascade_copies_services`,
`test_s7_4_deactivated_descendants_copied_and_resolve_desactivado`,
`test_fork_rubro_twice_for_same_user_is_rejected`,
`test_fork_against_soft_deleted_prior_fork_is_allowed`,
`test_fork_service_creates_single_item_with_null_parent`,
`test_fork_rubro_missing_base_is_not_found`,
`test_fork_rubro_trashed_base_is_not_found`.

## Deviations

- Test file lives in `tests/Feature/` (not `tests/Unit/` as tasks.md 7.2 originally wrote) — DB-touching
  tests follow the slice-3a/3b convention (RefreshDatabase, Model::create); tasks.md 7.2 corrected.
- S7.2 failure hook: a `creating` model-event listener on `UserCatalogItem` that throws on the Nth
  create (simplest reliable mid-copy hook; `DB::transaction` catches, rolls back, rethrows).
- Test file is 356 lines vs the 260–330 forecast for the whole 3b slice: the brief mandated every
  S7.x scenario plus identity and not-found signals as named test methods. Implementation is 193 lines
  (within the per-unit budget). Recommend `size:exception` for the test file, consistent with the two
  previous 3b units.

## Risks

- Positional `sort_order` for services is collection-order dependent (services have no ordering
  column); if Slice 4 adds ordering to services, carry it from the new column instead.
- Identity is app-level (no DB unique constraint on user+item_type+base_id): the request layer already
  enforces the same rule, and the service re-checks inside its transaction, but the race window
  accepted in D-3/D-5 still applies at the application level.

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

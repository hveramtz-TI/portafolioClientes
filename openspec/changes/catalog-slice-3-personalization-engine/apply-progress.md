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

# Apply Progress: catalog-slice-4-fork-api — Unit 1 (4a-fork-api-contracts)

**Mode**: Strict TDD (RED → GREEN → REFACTOR)
**Branch**: `feat/catalog-slice-4a-fork-api` (from `feat/catalog-rubros-categorias-servicios` @ a9e8661)
**Delivery**: feature-branch-chain, Unit 1 = PR 1
**Ledger**: work unit `4a-fork-api-contracts`, evidence goal `unit1-tests-green-dual-engine`, max changed lines 450 (attempt 1)

## Completed Tasks

| Task | Status |
|------|--------|
| 1.1 Acquire ledger token | ✅ (token already active at apply start; status `running`, ordinal 1) |
| 1.2 RED S1.1–S1.6 | ✅ |
| 1.3 RED S2.1–S2.7 | ✅ |
| 1.4 RED S3.1–S3.4 | ✅ |
| 1.5 RED S7.1–S7.6 | ✅ |
| 1.6 Migration (partial unique index) | ✅ |
| 1.7 Routes (6 endpoints) | ✅ |
| 1.8 ForkController | ✅ |
| 1.9 StoreController | ✅ |
| 1.10 ShowController | ✅ |
| 1.11 UpdateController | ✅ |
| 1.12 StatusController | ✅ |
| 1.13 DeleteController | ⛔ DEFERRED to Unit 2 (calls `CascadeDeleteUserCatalogService`, created by task 2.8) |
| 1.14 TreeController | ⛔ DEFERRED to Unit 2 (delegates to `UserCatalogTreeService`, created by task 2.7) |
| 1.15 StoreUserCatalogItemRequest | ✅ |
| 1.16 UpdateUserCatalogItemRequest | ✅ |
| 1.17 Policy `create` | ✅ |
| 1.18 `withExceptions` 409 render map | ✅ |
| 1.19 REFACTOR shared concern + Pint | ✅ |
| 1.20 REFACTOR migration both engines + `migrate:fresh --seed` | ✅ |
| 1.21 VERIFY SQLite filter | ✅ |
| 1.22 VERIFY PostgreSQL filter | ✅ |
| 1.23 VERIFY Slice 3 regression (both engines) | ✅ |
| 1.24 VERIFY Pint | ✅ |
| 1.25 Commit work unit | ✅ |

**Phase 1 checkboxes: 23/25** (1.13, 1.14 deferred — see Contradictions).

## Files Changed

| File | Action | What Was Done |
|------|--------|---------------|
| `backend/app/Support/UserCatalogType.php` | Created | Plural route segment → singular `item_type` map (single source of truth). |
| `backend/database/migrations/2026_09_14_000000_add_user_catalog_live_identity_unique_index.php` | Created | Reversible raw partial unique index `(user_id,item_type,base_id) WHERE base_id IS NOT NULL AND deleted_at IS NULL`; guarded to sqlite/pgsql; `down()` drops it. |
| `backend/app/Http/Controllers/UserCatalog/Concerns/ResolvesUserCatalogItem.php` | Created | Route type resolution, `{type}` ↔ `item_type` coherence (422), policy authorization (403), resolved-view helper. |
| `backend/app/Http/Controllers/UserCatalog/ForkController.php` | Created | Fork passthrough to `CascadeForkService`; normalizes summary to `{id,item_type,counts,ids}`; 201. |
| `backend/app/Http/Controllers/UserCatalog/StoreController.php` | Created | Creates personal item (`base_id` null, own `activo`); 201 resolved view. |
| `backend/app/Http/Controllers/UserCatalog/ShowController.php` | Created | 200 resolved view with structural keys. |
| `backend/app/Http/Controllers/UserCatalog/UpdateController.php` | Created | Merged overrides + accepted `parent_fork_id`; 200 resolved view. |
| `backend/app/Http/Controllers/UserCatalog/StatusController.php` | Created | Dedicated deactivate/reactivate writing only the selected row's `status`; 200 resolved view. |
| `backend/routes/api.php` | Modified | 6 `user-catalog` routes inside the stateful `auth:sanctum` group, outside `role:admin`, `{type}` constrained. Incidental Pint normalization. |
| `backend/bootstrap/app.php` | Modified | Single render map: `DomainException` + live-identity `QueryException` (PG `23505`, SQLite unique) → 409 `{message}`. Incidental Pint normalization. |
| `backend/app/Http/Requests/Concerns/ValidatesUserCatalogItem.php` | Modified | Route-first type resolution, route-type coherence rule, `base_id` forbidden rule, `parentForkUpdateRule` (D-9 detach + coherence + self-cycle). Removed dead fork-identity/referential rules. |
| `backend/app/Http/Requests/StoreUserCatalogItemRequest.php` | Modified | Rejects non-null `base_id` pointing to the fork endpoint; `personalOverrides()` builder. |
| `backend/app/Http/Requests/UpdateUserCatalogItemRequest.php` | Modified | Accepts `parent_fork_id` (`sometimes`) with D-9/J4 semantics. |
| `backend/app/Policies/UserCatalogItemPolicy.php` | Modified | `create()` ability (regular owner only, admin denied). |
| `backend/app/Services/CatalogResolver.php` | Modified | `resolve()` now exposes `item_type`, `parent_fork_id`, `sort_order` (D-4). |
| `backend/tests/Feature/UserCatalogForkApiCrudTest.php` | Created | 23 HTTP scenarios (S1/S2/S3/S7) + D-9 test. |
| `backend/tests/Feature/UserCatalogItemRequestTest.php` | Modified | Store contract flipped to "base_id rejected"; added update `parent_fork_id`/detach tests; removed superseded fork-via-store tests. |
| `backend/tests/Feature/CatalogResolverTest.php` | Modified | Added S5.4 structural-keys test; fixed an origin-matrix fixture that violated the new identity index. |
| `openspec/changes/catalog-slice-4-fork-api/tasks.md` | Modified | Phase 1 checkboxes. |

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 1.2 (S1.1–S1.6) | `UserCatalogForkApiCrudTest` | Feature/HTTP | ✅ 172 passed | ✅ Written (pre-impl 404) | ✅ Passed | ✅ 6 cases (rubro/categoria/dup root/dup descendant/unknown/deactivated) | ✅ Clean |
| 1.3 (S2.1–S2.7) | `UserCatalogForkApiCrudTest` | Feature/HTTP | ✅ 172 passed | ✅ Written | ✅ Passed | ✅ 7 cases (store/show/update/null/status/unknown/J4/non-null) | ✅ Clean |
| 1.4 (S3.1–S3.4) | `UserCatalogForkApiCrudTest` | Feature/HTTP | ✅ 172 passed | ✅ Written | ✅ Passed | ✅ 4 cases + D-9 detach | ✅ Clean |
| 1.5 (S7.1–S7.6) | `UserCatalogForkApiCrudTest` | Feature/HTTP | ✅ 172 passed | ✅ Written | ✅ Passed | ✅ 6 cases (401/403/404/409-domain/409-index/422) | ✅ Clean |
| 1.6 Migration | exercised by `RefreshDatabase` + S7.5 | Integration/DB | N/A (new) | ✅ S7.5 written first | ✅ Passed | ✅ SQLite + PostgreSQL | ✅ Clean |
| 1.11 Update parent | `UserCatalogItemRequestTest` | Unit | ✅ 172 passed | ✅ Written | ✅ Passed | ✅ rubro null / rubro non-null / detach / omitted (4 cases) | ✅ Clean |
| D-4 structural keys | `CatalogResolverTest` | Unit | ✅ 172 passed | ✅ Written | ✅ Passed | ✅ 2 cases (categoria + service) | ✅ Clean |

## Work Unit Evidence

| Evidence | Required value |
|---|---|
| Focused test command and exact result | `docker compose exec backend php artisan test --filter='UserCatalogForkApiCrudTest\|UserCatalogItemRequestTest\|CatalogResolverTest'` → **84 passed (289 assertions)**. PG focused: covered by `./test-pg.sh` full run (195 passed). |
| Runtime harness command/scenario and exact result | `N/A` — no shell/subprocess/network boundary; the unit is an HTTP-over-DB surface exercised end-to-end by `actingAs` + `postJson/getJson/putJson/patchJson` against SQLite and PostgreSQL. |
| Rollback boundary | Revert `backend/app/Http/Controllers/UserCatalog/*`, `backend/app/Support/UserCatalogType.php`, the index migration, the `user-catalog` route block, the policy `create`, the `CatalogResolver` structural keys, the two request changes, the `bootstrap/app.php` render map, and the three test files. No Unit 2 service is touched. |

## Test Summary

- **Total tests written**: 29 (23 feature + 5 request + 1 resolver), superseding 8 removed obsolete request tests.
- **Total tests passing**: 195 (up from the 172 baseline).
- **Layers used**: Unit (request/resolver), Integration/Feature (HTTP over DB).
- **Approval tests** (refactoring): None — no production-code refactoring tasks; the request changes are spec-mandated behavior changes.
- **Pure functions created**: 1 (`UserCatalogType::fromRoute`).

## RED / GREEN Evidence

- **RED** (pre-implementation): `docker compose exec backend php artisan test --filter='UserCatalogForkApiCrudTest|UserCatalogItemRequestTest|CatalogResolverTest'` → **25 failed, 59 passed (161 assertions)**. All 23 HTTP scenarios 404'd (routes absent) and the new store/update request contracts failed.
- **GREEN** (post-implementation): same filter → **84 passed (289 assertions)**.
- **Full SQLite**: `docker compose exec backend php artisan test` → **195 passed (640 assertions)**.
- **Full PostgreSQL**: `./test-pg.sh` → **OK (195 tests, 640 assertions)**.
- **All 20 Slice 3 spec scenarios**: green on both engines (CatalogResolverTest, CascadeForkServiceTest, UserCatalogItemPolicyTest, UserCatalogItemRequestTest).
- **Combined-output sha256**: `e6228432eff496f7705056f55db87c5928568d02274f6d911a6fc49ecb6b2fab` (`{ cat sqlite.log pg.log; } | sha256sum`).

## Decisions Applied

| ID | Applied as |
|----|-----------|
| D-1 | `UserCatalogType` maps plural `{type}`; store validates body `item_type` vs route (422); show/update/status enforce bound `item_type` vs route (422). |
| D-2 | Routes live in the existing stateful `auth:sanctum` group, outside `role:admin`. |
| D-3 | One `withExceptions` map renders `DomainException` and live-identity `QueryException` (PG `23505`, SQLite `23000` + message) as the same 409 `{message}`. |
| D-4 | `resolve()` exposes `item_type`, `parent_fork_id`, `sort_order`. |
| D-7 | Reversible raw partial unique index; verified up/down on SQLite and PostgreSQL. |
| D-8 | Policy `create` added; `viewAny` intentionally omitted. |
| D-9 | Custom validation rejects explicit `parent_fork_id: null` for categoria/service; rubro null persists legally (J4). |
| D-12 | Fork remains the only fork-creation path; store always rejects non-null `base_id`. |

## Deviations from Design

1. **Task 1.16's "cycle guards"** — the full descendant-cycle and destination-sibling guards (S5.3/S5.4) belong to Unit 2 (task 2.11). Unit 1 implements only self-parent exclusion (`whereKeyNot($item->id)`) plus type-coherence/ownership/live-parent, which is what S2.6/S2.7/D-9 require. Documented here so Unit 2 completes the cycle/destination checks.
2. **Task 1.20** — `migrate:fresh --seed` was run against the test databases (`:memory:` for SQLite, `portafolio_test` for PostgreSQL) instead of the dev database, to avoid destroying local dev data. Both runs green.

## Contradictions Hit (flagged, not papered over)

1. **Phase 1 lists `DeleteController` (1.13) and `TreeController` (1.14) but their services are created in Phase 2 (tasks 2.7/2.8).** A Unit 1 branch that created these controllers would reference classes that do not exist yet. The tasks' own Suggested Work Units table and the ledger goal (`R1,R2,R3,R7,S1-S3,S7`) put cascade delete (R4) and tree (R6) in Unit 2. **Resolution**: deferred 1.13/1.14 to Unit 2, following the ledger goal and the orchestrator's "Unit 2 is OUT" instruction. The orchestrator prompt's Unit 1 scope line ("delete of user catalog items") conflicts with its own "cascade delete is Unit 2" statement.
2. **`CatalogResolver` structural-key extension is listed as task 2.6 (Phase 2) but is required by Unit 1's S2.3/S3.x resolved views** and is named in the orchestrator's Unit 1 scope. **Resolution**: implemented in Unit 1; Unit 2 task 2.6 is now already satisfied (no-op/verification only).
3. **Task 1.1 `--max-changed-lines 400` vs the session budget 450.** Ledger shows 450 (`max_changed_lines_source: explicit`); treated 450 as authoritative.

## Budget

- Honest authored diff for Unit 1 is **~1,100 changed lines** (insertions+deletions, excluding the incidental Pint normalization), well above the 450 ceiling — the 23 mandated HTTP scenarios, 6 thin controllers, request-contract flip and resolver/index/render-map changes cannot land inside 450 without deleting tests or minifying. No code was minified to fit. **Recommendation: `size:exception`** with the unit accepted as a single PR, or re-slice Unit 1 into 4a-1 (fork+index+render map) and 4a-2 (CRUD+status+request contracts).

## Commands Run

```bash
# baseline (safety net)
docker compose exec -T backend php artisan test                 # 172 passed (490 assertions)

# RED
docker compose exec -T backend php artisan test --filter='UserCatalogForkApiCrudTest|UserCatalogItemRequestTest|CatalogResolverTest'
# 25 failed, 59 passed (161 assertions)

# GREEN + regression
docker compose exec -T backend php artisan test                 # 195 passed (640 assertions)
./test-pg.sh                                                    # OK (195 tests, 640 assertions)

# migration up/down, both engines
docker compose exec -T backend ./vendor/bin/pint --test <changed+new files>   # PASS 18 files
docker compose exec -T -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: backend php artisan migrate:fresh --seed --force
docker compose exec -T -e DB_DATABASE=portafolio_test backend php artisan migrate:fresh --seed --force
docker compose exec -T -e DB_DATABASE=portafolio_test backend sh -c "php artisan migrate:rollback --step=1 --force && php artisan migrate --force"
docker compose exec -T -e DB_CONNECTION=sqlite -e DB_DATABASE=/tmp/sdd4a.sqlite backend sh -c "touch /tmp/sdd4a.sqlite && php artisan migrate:fresh --force >/dev/null 2>&1 && php artisan migrate:rollback --step=1 --force && php artisan migrate --force"
```

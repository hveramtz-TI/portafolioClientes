# Tasks: Catalog Slice 4 — User-Catalog Fork HTTP API

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines (Unit 1) | 320–380 |
| Estimated changed lines (Unit 2) | 350–420 |
| 400-line budget risk | Medium |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (Unit 1) → PR 2 (Unit 2) |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Passthrough CRUD + contracts + index | PR 1 | `docker compose exec backend php artisan test --filter=UserCatalogForkApiCrudTest` + `./test-pg.sh --filter=UserCatalogForkApiCrudTest` | N/A (DB transaction, no external deps) | Revert routes, controllers, requests, policy, migration, render map, tests |
| 2 | Tree + cascade delete + move/attach | PR 2 | `docker compose exec backend php artisan test --filter=UserCatalogTreeMoveTest` + `./test-pg.sh --filter=UserCatalogTreeMoveTest` | N/A (DB transaction, no external deps) | Revert tree service, cascade delete service, resolver extension, move validation, tree tests |

## Phase 1: Unit 1 — Passthrough CRUD, Contracts, Index Migration (PR 1)

> Note: tasks 1.13 (DeleteController) and 1.14 (TreeController) are deferred to
> Unit 2 — both delegate to services created in Phase 2 (`CascadeDeleteUserCatalogService`,
> `UserCatalogTreeService`), so they cannot land in this unit without pulling
> Unit 2 scope forward. The ledger goal for this unit is R1/R2/R3/R7/S1-S3/S7.

### Setup
- [x] 1.1 Acquire ledger token for Unit 1: `gentle-ai sdd-attempt acquire --cwd /home/hgvm/Documentos/GitHub/portafolioClientes --change catalog-slice-4-fork-api --request-id u1-crud --work-unit u1-crud --evidence-goal "R1,R2,R3,R7,S1-S3,S7 green on SQLite+PG" --max-attempts 3 --max-changed-lines 400` (token already active at apply start)

### RED: Write failing tests for Unit 1
- [x] 1.2 RED: Create `backend/tests/Feature/UserCatalogForkApiCrudTest.php` covering S1.1 (rubro cascade summary), S1.2 (categoria cascade), S1.3 (duplicate root 409), S1.4 (duplicate descendant 409), S1.5 (unknown base 404), S1.6 (fork deactivated base)
- [x] 1.3 RED: Add tests for S2.1 (store personal), S2.2 (store base_id rejected 422), S2.3 (show resolved with structural keys), S2.4 (update null removes override), S2.5 (status/unknown field 422), S2.6 (J4 rubro null parent legal), S2.7 (rubro non-null parent 422)
- [x] 1.4 RED: Add tests for S3.1 (deactivate hides subtree), S3.2 (reactivate only selected), S3.3 (reactivate below dead ancestor), S3.4 (status via update rejected)
- [x] 1.5 RED: Add tests for S7.1 (401 unauthenticated), S7.2 (403 cross-owner), S7.3 (404 missing/soft-deleted), S7.4 (409 duplicate service path), S7.5 (409 index race path both engines), S7.6 (422 validation)

### GREEN: Implement Unit 1 infrastructure
- [x] 1.6 GREEN: Create migration `backend/database/migrations/XXXX_XX_XX_XXXXXX_add_live_identity_unique_index.php` with reversible raw SQL partial unique index `(user_id,item_type,base_id) WHERE base_id IS NOT NULL AND deleted_at IS NULL`; `down()` drops it
- [x] 1.7 GREEN: Add routes to `backend/routes/api.php` for all 7 endpoints under `auth:sanctum` outside `role:admin` with `{type}` constraint `rubros|categorias|services`
- [x] 1.8 GREEN: Create `backend/app/Http/Controllers/UserCatalog/ForkController.php` — `fork()` delegates to `CascadeForkService`, normalizes summary, returns 201
- [x] 1.9 GREEN: Create `backend/app/Http/Controllers/UserCatalog/StoreController.php` — `store()` validates route type, creates personal item (`base_id` null, `status` activo), rejects non-null `base_id` 422
- [x] 1.10 GREEN: Create `backend/app/Http/Controllers/UserCatalog/ShowController.php` — `show()` returns resolved view with `item_type`, `parent_fork_id`, `sort_order`
- [x] 1.11 GREEN: Create `backend/app/Http/Controllers/UserCatalog/UpdateController.php` — `update()` merges overrides (explicit null removes), rejects `status`/unknown fields 422, rubro `parent_fork_id: null` legal, non-null 422
- [x] 1.12 GREEN: Create `backend/app/Http/Controllers/UserCatalog/StatusController.php` — `deactivate()`/`reactivate()` write only own `status`, return resolved view; reactivate touches only selected item (D7)
- [ ] 1.13 GREEN: Create `backend/app/Http/Controllers/UserCatalog/DeleteController.php` — `destroy()` calls `CascadeDeleteUserCatalogService`, returns 204 (DEFERRED to Unit 2 — service is created by task 2.8)
- [ ] 1.14 GREEN: Create `backend/app/Http/Controllers/UserCatalog/TreeController.php` — `tree()` validates `status|origin` filters, delegates to `UserCatalogTreeService`, returns nested graph (DEFERRED to Unit 2 — service is created by task 2.7)
- [x] 1.15 GREEN: Modify `backend/app/Http/Requests/StoreUserCatalogItemRequest.php` — add route-type coherence, reject non-null `base_id` with fork-endpoint message, allow `parent_fork_id: null` for rubro only
- [x] 1.16 GREEN: Modify `backend/app/Http/Requests/UpdateUserCatalogItemRequest.php` — add `parent_fork_id` as accepted update key with type-coherence/ownership/cycle guards, reject `status`/unknown fields
- [x] 1.17 GREEN: Modify `backend/app/Policies/UserCatalogItemPolicy.php` — add `create()` ability (owner only, admin denied)
- [x] 1.18 GREEN: Modify `backend/bootstrap/app.php` — add `withExceptions` map: `DomainException` + `QueryException` (PG 23505, SQLite unique) → identical 409 JSON `{message}`

### REFACTOR: Clean up Unit 1
- [x] 1.19 REFACTOR: Extract shared controller base if duplication > 15 lines; ensure Pint passes
- [x] 1.20 REFACTOR: Verify migration runs clean on both engines; `php artisan migrate:fresh --seed` green

### VERIFY: Unit 1 dual-engine + regression
- [x] 1.21 VERIFY: Run `docker compose exec backend php artisan test --filter=UserCatalogForkApiCrudTest` (SQLite)
- [x] 1.22 VERIFY: Run `./test-pg.sh --filter=UserCatalogForkApiCrudTest` (PostgreSQL)
- [x] 1.23 VERIFY: Run `docker compose exec backend php artisan test --filter=CatalogResolverTest,CascadeForkServiceTest,UserCatalogItemPolicyTest,UserCatalogItemRequestTest` (Slice 3 regression, both engines)
- [x] 1.24 VERIFY: Run `docker compose exec backend ./vendor/bin/pint --test`
- [x] 1.25 Commit work unit: `feat(catalog): add fork HTTP API — CRUD, contracts, index (R1,R2,R3,R7)` with tests

## Phase 2: Unit 2 — Tree, Cascade Delete, Move/Attach (PR 2)

### Setup
- [ ] 2.1 Acquire ledger token for Unit 2: `gentle-ai sdd-attempt acquire --cwd /home/hgvm/Documentos/GitHub/portafolioClientes --change catalog-slice-4-fork-api --request-id u2-tree-move --work-unit u2-tree-move --evidence-goal "R4,R5,R6,S4-S6,S8 green on SQLite+PG" --max-attempts 3 --max-changed-lines 450`

### RED: Write failing tests for Unit 2
- [ ] 2.2 RED: Create `backend/tests/Feature/UserCatalogTreeMoveTest.php` covering S4.1 (subtree soft-deleted), S4.2 (other users intact), S4.3 (re-fork after delete), S4.4 (deleted is 404)
- [ ] 2.3 RED: Add tests for S5.1 (move service between categorias), S5.2 (attach orphan), S5.3 (cycle rejected), S5.4 (destination title clash), S5.5 (type-incoherent parent), S5.6 (rubro never parented), S5.7 (foreign parent), S5.8 (move under deactivated)
- [ ] 2.4 RED: Add tests for S6.1 (tree shape and order), S6.2 (effective status filter), S6.3 (origin filter), S6.4 (query budget N vs 2N), S6.5 (invalid filter 422)
- [ ] 2.5 RED: Add tests for S8.1 (index blocks raw duplicate both engines), S8.2 (null and deleted exempt)

### GREEN: Implement Unit 2 services
- [ ] 2.6 GREEN: Extend `backend/app/Services/CatalogResolver.php` — add `item_type`, `parent_fork_id`, `sort_order` to resolved payload (D-4)
- [ ] 2.7 GREEN: Create `backend/app/Services/UserCatalogTreeService.php` — eager-load owner roots + bounded `children.base` paths, resolve/filter/order in memory, `status` filters on **effective** status, `origin` filters on resolved origin, query budget test-pinned
- [ ] 2.8 GREEN: Create `backend/app/Services/CascadeDeleteUserCatalogService.php` — owner-scoped parent map, bulk soft-delete subtree in one transaction, base/foreign rows untouched
- [ ] 2.9 GREEN: Wire `TreeController::tree()` to `UserCatalogTreeService` with filter validation
- [ ] 2.10 GREEN: Wire `DeleteController::destroy()` to `CascadeDeleteUserCatalogService`
- [ ] 2.11 GREEN: Add move/attach validation to `UpdateUserCatalogItemRequest` (cycle detection, type coherence, ownership, deleted-parent, sibling name uniqueness at destination)

### REFACTOR: Clean up Unit 2
- [ ] 2.12 REFACTOR: Optimize tree service eager-load paths; ensure query budget constant; Pint passes
- [ ] 2.13 REFACTOR: Verify cascade delete transaction isolation; no N+1 in tree

### VERIFY: Unit 2 dual-engine + full regression
- [ ] 2.14 VERIFY: Run `docker compose exec backend php artisan test --filter=UserCatalogTreeMoveTest` (SQLite)
- [ ] 2.15 VERIFY: Run `./test-pg.sh --filter=UserCatalogTreeMoveTest` (PostgreSQL)
- [ ] 2.16 VERIFY: Run full suite both engines: `docker compose exec backend php artisan test` + `./test-pg.sh` (all 20 Slice 3 scenarios + Slice 4 scenarios green)
- [ ] 2.17 VERIFY: Run `docker compose exec backend ./vendor/bin/pint --test`
- [ ] 2.18 VERIFY: Run `codegraph sync && codegraph status`
- [ ] 2.19 Commit work unit: `feat(catalog): add tree, cascade delete, move/attach (R4,R5,R6)` with tests

## Phase 3: Docs Mirror & Close-Out

- [ ] 3.1 Update `docs/planning/planning3.md` — amend D5 row to reflect DB partial unique index (per user approval), append Slice 4 progress log
- [ ] 3.2 Notion mirror of `planning3.md` updated (orchestrator-owned at close-out)

## Dependencies
- Unit 2 **depends on** Unit 1 contracts: `CatalogResolver` structural keys, `CascadeForkService` tree summary, `Store/Update` request rules, policy `create`, 409 render map, live-identity index migration must be applied first
- Both units require Slice 3 engine on `main` (verified 172 tests / 490 assertions green on SQLite + PostgreSQL)
- Dual-engine test infra (`phpunit.xml` / `phpunit-pg.xml`, `./test-pg.sh`) must be operational

## Risks
- Effective-status filter N+1: mitigated by eager-load + query-budget test (S6.4)
- Partial index divergence SQLite vs PG: mitigated by raw migration verified both engines, `down()` included
- Error-contract drift: single render map in `bootstrap/app.php` covers both `DomainException` and `QueryException`
- Race 409 simulation: direct duplicate inserts tested on both engines (S7.5, S8.1)
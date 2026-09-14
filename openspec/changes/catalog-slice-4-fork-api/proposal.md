# Proposal: Catalog Slice 4 — User-Catalog Fork HTTP API

## Intent

Slice 3 shipped the engine with zero route diff. Slice 4 exposes `/api/user-catalog/...` (planning3) and absorbs its delegated debt: J4, D5 race, move/attach (D8), and the S4.2-anticipated status endpoints.

## Scope

### In Scope
`auth:sanctum`, outside `role:admin`:
- `POST /api/user-catalog/{type}/{baseId}/fork` (CascadeForkService passthrough); store/update; show (`resolve()`); deactivate/reactivate (reuse `update` ability, D3/D7); DELETE; GET tree/list.
- Locked: rubro `parent_fork_id: null` ≡ absent (J4); Store rejects non-null `base_id` → 422 — forking only via dedicated endpoint (D12); duplicate `DomainException` → 409; D5 **amended**: partial unique index `(user_id,item_type,base_id) WHERE base_id IS NOT NULL AND deleted_at IS NULL`, `QueryException` → 409 (planning3 D5 row + Notion mirror updated); move/attach via `parent_fork_id` update with cycle/orphan guards; tree `status=all|activo|desactivado` filters on **effective** status (R6, post-resolve).
- `resolve()` gains `item_type`, `parent_fork_id`, `sort_order`; policy gains `create`; lists scoped by `user_id` in-query; `FailOnUnknownFields` preserved.
- New logic: recursive cascade soft-delete (D9); tree/list eager downward graph + query-count guard (D-4 budget); move/attach validation.

### Out of Scope
Pagination, sorting beyond `sort_order`, HTTP caching, rate limiting (planning silent → deferred), frontend (Slices 6–7), seeders (Slice 5), base-catalog changes.

## Capabilities

### New Capabilities
- `user-catalog-fork-api`: HTTP surface — endpoints, authorization, response shape, tree filters, cascade delete, 409/422 contracts.

### Modified Capabilities
- `user-catalog-personalization`: R3/R4 gain move/attach (type-coherent, cycle-safe; J4); duplicates surface as 409; identity gains DB protection (amended D5).

## Approach

Thin controllers; engine passes through unchanged; one render map unifies 409. Forecast **2 chained work units** vs 400-line budget: (1) passthrough CRUD + contracts; (2) tree + delete + move. Strict TDD, both engines, Pint.

## Affected Areas

| Area | Impact |
|---|---|
| `backend/routes/api.php` | Modified — `/api/user-catalog/*` |
| `backend/app/Http/Controllers/UserCatalog/*` | New |
| `backend/app/Http/Requests/{Store,Update}UserCatalogItemRequest.php` + trait | Modified |
| `backend/app/Policies/UserCatalogItemPolicy.php` | Modified — `create` |
| `backend/app/Services/` | New — delete cascade, tree builder, resolver extension |
| `backend/bootstrap/app.php` | Modified — 409 render map |
| `backend/database/migrations/*` | New — D5 index |
| `backend/tests/Feature/` | New |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Effective-status filter reintroduces N+1 | Med | eager-load + test-asserted query budget |
| Partial index diverges SQLite vs PG | Low | raw migration verified both; `down()` |
| Error-contract drift (409/422/500) | Med | single render map; per-path tests |

## Rollback Plan

Additive surface: revert unit-2 then unit-1 commits; the index migration ships `down()`. No backfill, no engine changes — post-revert Slice 3 stands intact.

## Dependencies

Slice 3 engine on `main`; dual-engine test infra (`phpunit.xml` / `phpunit-pg.xml`).

## Success Criteria

- [ ] Duplicate fork → 409 on both engines (service path + index-race path).
- [ ] Store `base_id` non-null → 422; rubro explicit-null parent persists as null.
- [ ] Tree query count bounded (test-asserted); status filter acts on effective status.
- [ ] DELETE cascade soft-deletes only the owner's subtree; base rows untouched.
- [ ] Move/attach: cycle rejected, type-coherent target, orphan fork attachable.
- [ ] All 20 slice-3 scenarios green; suite green on SQLite + PG; Pint clean.

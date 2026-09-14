# Design: Catalog Slice 4 — User-Catalog Fork HTTP API

## Technical Approach

Add a feature-first `UserCatalog` HTTP surface over the unchanged Slice 3 engine. Thin controllers validate the plural route type, authorize the owner, call the existing and new focused services, and return plain arrays. Keep the stateful `web` + `auth:sanctum` group outside `role:admin`.

## Architecture Decisions

| ID | Choice | Rationale |
|---|---|---|---|
| D-1 | Route `{type}` is `rubros|categorias|services`, mapped to singular values. Store/update validate route type against the body/bound item; mismatch is 422. Fork has no body type. | URL identity is authoritative while body-driven requests remain reusable. |
| D-2 | Use the existing stateful Sanctum group outside `role:admin`. | Matches `api.php`; owner policy preserves admin denial. |
| D-3 | One `withExceptions` map handles `DomainException` and live-identity unique `QueryException`, returning `{message}` 409. Detect PostgreSQL `23505` and SQLite unique/index context. | `bootstrap/app.php` already owns API JSON policy; both duplicate paths become identical. |
| D-4 | Extend `resolve()` with `item_type`, `parent_fork_id`, `sort_order`; tree reuses it. | Plain arrays are house style and prevent show/tree drift. |
| D-5 | `UserCatalogTreeService` loads the owner scope in ONE flat query, eager-loads each node's `base`, and wires the `parentFork` nesting in memory; resolution/filters/ordering run post-resolve. (As implemented — reconciles the original bounded `children.base` eager-path text after the fix round; independently confirmed constant budget: queries(N)==queries(2N), observed 4, asserted ≤6, filters add 0.) | O(1) queries with unbounded depth; filters add no queries and preserve retained nesting. |
| D-6 | `CascadeDeleteUserCatalogService` collects the actor's live subtree via an owner-scoped parent map and bulk soft-deletes it in one transaction. | Atomic owner-only writes leave bases/foreign rows untouched; deleted rows can be re-forked. |
| D-7 | Reversible raw migration, both engines: `CREATE UNIQUE INDEX user_catalog_items_live_identity_unique ON user_catalog_items (user_id,item_type,base_id) WHERE base_id IS NOT NULL AND deleted_at IS NULL`; `down()` drops it. | Implements amended D5 without blocking personal/deleted rows. |
| D-8 | Add policy `create`; omit `viewAny` because list/tree scope `user_id` in SQL. | Matches owner-only policy and controller conventions. |
| D-9 | Custom validation rejects explicitly present null for categoria/service; rubro null persists legally. | Pins locked detach behavior and J4 without changing status/overrides. |

### Endpoint inventory

| Method/path | Middleware | Ability | Request | Code |
|---|---|---|---|---|
| POST `/api/user-catalog/{type}/{baseId}/fork` | stateful + `auth:sanctum` | create | route/base lookup | 201 |
| POST `/api/user-catalog/{type}` | same | create | Store | 201 |
| GET `/api/user-catalog/{type}/{forkId}` | same | view | none | 200 |
| PUT `/api/user-catalog/{type}/{forkId}` | same | update | Update | 200 |
| PATCH `/api/user-catalog/{type}/{forkId}/deactivate\|reactivate` | same | update | none | 200 |
| DELETE `/api/user-catalog/{type}/{forkId}` | same | delete | none | 204 |
| GET `/api/user-catalog/tree` | same | owner query | filter validation | 200 |

Fork summary keys are `id`, `item_type`, `counts` (`rubro`, `categoria`, `service`), and `ids` (all created fork IDs); the controller normalizes the existing service result. Show/tree nodes are the resolver payload plus the three structural keys.

## File Changes

| File | Action | Description |
|---|---|---|
| `backend/routes/api.php` | Modify | User-catalog routes. |
| `backend/app/Http/Controllers/UserCatalog/*` | Create | Thin controllers. |
| `backend/app/Http/Requests/*` + trait | Modify | Route coherence and move guards. |
| `backend/app/Policies/UserCatalogItemPolicy.php` | Modify | `create`. |
| `backend/app/Services/*` | Modify/Create | Resolver, tree, cascade delete. |
| `backend/bootstrap/app.php` | Modify | 409 map. |
| `backend/database/migrations/*live_identity_unique*` | Create | Partial unique index. |
| `backend/tests/Feature/*` | Create/Modify | S1–S8 dual-engine coverage. |
| `docs/planning/planning3.md` + Notion mirror | Update | Amended D5 work unit. |

## Testing Strategy

Feature tests map to S1.1–S7.6 and S8.1–S8.2 on SQLite and `./test-pg.sh`. Use `DB::listen` as in `CatalogResolverTest`, comparing N and 2N trees. Simulate races with direct duplicate inserts and assert HTTP 409 on both engines. Fixtures cover cycles, self/descendant moves, foreign/deleted parents, clashes, status isolation, and rollback. Preserve all 20 Slice 3 scenarios and run Pint.

## Threat Matrix

N/A — no shell, subprocess, VCS/PR automation, or executable-file classification boundary; HTTP routing is covered by the endpoint and error-contract tests above.

## Migration / Rollout

Add migration, routes, services, and tests in two chained work units. Unit 1: passthrough CRUD, contracts, index migration, policy, render map. Unit 2: tree, cascade delete, move/attach, and docs mirror. Each is independently testable and reversible; no backfill or feature flag.

## Open Questions

- None. Status endpoints return 200 resolved views; origin filters are `personal|override|base`; detach and D5 behavior are pinned above.

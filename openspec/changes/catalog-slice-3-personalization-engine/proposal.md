# Proposal: Catalog Slice 3 — Personalization Engine

## Intent

Slices 1–2 shipped the admin base catalog; users still cannot fork base items, override fields, or resolve effective status without altering the base. This change delivers that domain engine (policy, validation, resolver, cascade fork) with no HTTP surface: endpoints are Slice 4.

## Scope

### In Scope
- `UserCatalogItemPolicy`: owner-only; admin never touches others' forks (R1)
- `Store`/`UpdateUserCatalogItemRequest`: fork identity, visible-name uniqueness, `parent_fork_id` type coherence, field whitelist, `status` rejected 422 (R3, R4; D3/D4/D5)
- `base()` morph-to relation on `UserCatalogItem` (R2)
- `CatalogResolver`: per-field inheritance, origin `personal|override|base`, effective status (R5, R6; D6/D13)
- `CascadeForkService`: atomic fork with descendants (R7; D12)
- Tests: policy via Gate, rules via `Validator`, unit + service-level

### Out of Scope
- `/api/user-catalog` routes/controllers (Slice 4); deactivate/reactivate endpoints; seeders; frontend; public-profile/orders consumption

## Capabilities

### New Capabilities
- `user-catalog-personalization`: fork ownership, base morph relation, identity/structural uniqueness, override validation, per-field inheritance with origin, effective-status cascade, atomic cascade forking (R1–R7).

### Modified Capabilities
- None — base-catalog requirements unchanged; shared code gains only an additive model relation.

## Approach

Domain-only increment: no migrations or new dependencies. The resolver walks the `parentFork` + `base()` chain computing `override ?? current base value` and ANDs own/base/ancestor `status`. The cascade fork runs in one transaction, creating forks with `status='activo'` and empty overrides for every descendant **regardless of base status**; failure rolls back everything, base untouched (D12, user-confirmed). Strict TDD; two stacked PRs (≤400 lines each): **3a** relation + policy + requests (R1–R4) → tracker; **3b** resolver + cascade (R5–R7) → 3a.

## Affected Areas

- `backend/app/Policies/UserCatalogItemPolicy.php` (new, R1)
- `backend/app/Http/Requests/StoreUserCatalogItemRequest.php` (new, R3/R4)
- `backend/app/Http/Requests/UpdateUserCatalogItemRequest.php` (new, R4)
- `backend/app/Services/CatalogResolver.php` (new, R5/R6)
- `backend/app/Services/CascadeForkService.php` (new, R7)
- `backend/app/Models/UserCatalogItem.php` (modified: `base()` morph-to, R2)
- `backend/tests/` (new: R1–R7 coverage)

## Risks

- Cascade drifts from confirmed D12 semantics (Med): tests cover deactivated base items + rollback
- App-layer uniqueness races, no DB unique index per D5 (Low): validated in-transaction; accepted for personal app
- Resolver N+1 (Low): eager-load; depth fixed at 3

## Rollback Plan

All deliverables are new files plus one additive model method; no migrations or routes. Revert PR 3b then 3a (or drop the stacked branches). Base untouched by design; no data recovery needed.

## Dependencies

- Slices 1–2 merged on `main` (`user_catalog_items` migration, base catalog API)
- Docker harness: `docker compose exec backend php artisan test`, `./test-pg.sh`, Laravel Pint

## Success Criteria

- [ ] R1–R7 covered by failing-first tests; green on SQLite **and** PostgreSQL; Pint clean
- [ ] HU-013/017/021/025 rules enforced without endpoints (D3, D5, D6, D12, D13)
- [ ] Duplicate fork rejected; `status`/unknown fields never persisted (422)
- [ ] `backend/routes/api.php` unchanged; each PR diff ≤ 400 lines

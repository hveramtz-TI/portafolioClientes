# Judgment Day — Review Ledger (Slice 3 stack)

target_identity: 0163d0d...18808b2 (tree d73312da41385d283e2fa460aa0fe11dfdfb68c6), branch feat/catalog-slice-3b
round: 1
judges: jd-judge-a, jd-judge-b (blind, parallel, identical scope 12 files + normative context)

## Findings

| ID | Location | A | B | Disposition |
|----|----------|---|---|-------------|
| J1 | CascadeForkService.php:43-172 (assertNoExistingFork only top-level; createCategoriaFork/createServiceFork unchecked; test 276-301 shows 20 withTrashed rows) | — | CRITICAL | suspect (single judge) → REQUIRED RED-repro before fix |
| J2 | CatalogResolver.php:57-125 (trashed base/ancestor → null-safe fallback resolves fields null + status activo) | CRITICAL (deterministic) | WARNING (inferential, latent: controllers forceDelete+fork-guard only) | observation corroborated, severity split → parent decision: fix via spec-faithful reading (activo only when chain provably active) |
| J3 | StoreUserCatalogItemRequest.php:43-54 + trait:112-120 (base_id nullable|uuid only; no exists/type-coherence vs item_type; R2 orphan forks) | WARNING | WARNING | confirmed by both at WARNING → fix (deterministic referential hole, spec R2) |
| J4 | trait:127-132 rubroParentForbiddenRule fails on explicit null parent_fork_id | — | SUGGESTION | info → Slice 4 follow-up (frontend null-serialization contract undecided) |

## Parent notes
- No contradictions (no claim asserted present by one and absent by other).
- Fix policy: J1-J3 each land RED reproduction test first (converts suspect→evidence), then minimal fix; one atomic work unit per finding; pint + both engines per unit.
- J4 documented, not fixed (rule: INFO not auto-fixed; Slice 4 decides wire contract).

## Fix work units (round 1)

| ID | Fix | Status |
|----|-----|--------|
| J1 | Assert non-trashed fork identity for EVERY descendant row being copied inside each cascade transaction (rubro: categorias+services; categoria: services); violation → DomainException + full rollback | fixed |
| J2 | Resolver: non-null `base_id` with null base relation (trashed/missing) OR non-null `parent_fork_id` with null parent → effective status `desactivado`; fields resolve null as today | fixed |
| J3 | Store: `base_id` exists-rule scoped to the base model resolved from `item_type` (Rubro/Categoria/Service, non-trashed) | fixed |
| J4 | trait rubroParentForbiddenRule fails on explicit null parent_fork_id | info — Slice 4 follow-up (unchanged) |

## Round 1 fix results (TDD, RED→GREEN verified on SQLite + PostgreSQL)

- **J1** — cascade descendant identity.
  - RED: 3 new tests in CascadeForkServiceTest (standalone categoria fork → forkRubro; standalone service fork → forkRubro; standalone service fork → forkCategoria) — all failed "Duplicate descendant fork should be rejected" (cascades silently duplicated descendants).
  - GREEN: identity assert moved into `createCategoriaFork`/`createServiceFork` (same DomainException, full rollback). Adjacent existing test `test_fork_against_soft_deleted_prior_fork_is_allowed` corrected to soft-delete the whole tree (it only trashed the rubro root while its own docblock said "fork tree" — that leftover-descendant scenario is now exactly the J1 violation).
  - Files: backend/app/Services/CascadeForkService.php (~L65-116, 143-178, 193-202 docblock), backend/tests/Feature/CascadeForkServiceTest.php (~L303-380 new + ~L276-301 tree-delete fix).
- **J2** — resolver null-base/null-parent fail-safe.
  - RED: 3 new tests in CatalogResolverTest (missing-base UUID, soft-deleted base, dangling parent_fork_id after child-of-deleted-parent incl. recursive grandchild) — all resolved 'activo' under the null-safe chain AND.
  - GREEN: effectiveStatus returns 'desactivado' when base_id non-null ∧ base resolves null, or parent_fork_id non-null ∧ parentFork resolves null. Approach: NO new lookups — the existing `loadResolutionGraph` loadMissing already loads base + parentFork chain and Eloquent caches the null resolution, so the D-4 preload contract test stays green at zero extra queries (no withTrashed anywhere).
  - Files: backend/app/Services/CatalogResolver.php (effectiveStatus checks + docblock), backend/tests/Feature/CatalogResolverTest.php (~L358-419).
- **J3** — Store base_id exists + type coherence.
  - RED: 3 new tests in UserCatalogItemRequestTest (nonexistent UUID, rubro UUID under item_type=service, trashed service base) — all passed validation under `nullable|uuid` alone.
  - GREEN: new trait rule `baseExistsRule()` = `Rule::exists(rubros|categorias|services, 'id')->whereNull('deleted_at')`, table picked from the validated item_type (existence AND type coherence in one rule); wired in Store before the identity-unique rule, guarded by `in_array($type, ITEM_TYPES)`. Update request untouched. Positive case guarded by existing test_store_valid_service_fork_passes (green before and after).
  - Files: backend/app/Http/Requests/Concerns/ValidatesUserCatalogItem.php (baseExistsRule + message), backend/app/Http/Requests/StoreUserCatalogItemRequest.php (rules wiring), backend/tests/Feature/UserCatalogItemRequestTest.php (~L198-243).
- Verification: focused classes SQLite 73 passed (64→73, +9); PG `./test-pg.sh --filter='CascadeForkServiceTest|CatalogResolverTest|UserCatalogItemRequestTest'` OK 73/73; full suite `php artisan test` 172 passed / 490 assertions (163→172, zero regressions); `pint --test` PASS on all 7 touched files.

scoped_rejudgment: approved (round-1 re-judgment, both judges findings:[])
terminal_state: approved
independent_final_verification: 172 passed / 490 assertions SQLite AND PostgreSQL at 7b7d38a; routes/api.php diff vs tracker base = 0
JUDGMENT: APPROVED
skill_resolution: paths-injected (.opencode/command/verify-laravel.md, spec+design+flows)

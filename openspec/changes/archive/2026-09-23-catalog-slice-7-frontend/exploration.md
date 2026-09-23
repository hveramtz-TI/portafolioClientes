# Exploration — Catalog Slice 7 (Frontend Category/Service Management & HU Closure)

**Date:** 2026-09-21 · **Store:** openspec (dispatcher-authoritative) · **Primary source:** `docs/planning/planning3.md` (Slices 1–6) · **Backend contracts:** `openspec/specs/user-catalog-fork-api/spec.md` (R1–R7), `openspec/specs/user-catalog-personalization/spec.md` (R1–R8)

## Current State

Slice 6 (uncommitted in the working tree) delivered the first operable catalog surface:

- **Page** `frontend/src/app/(dashboard)/catalogo/page.tsx`: tabs "Base Catalog" (active base rubros + Select/fork + New personal rubro) and "My Catalog" (tree + status/origin filters). All mutations wired: fork, create (rubro only), edit (generic), deactivate/reactivate, recursive delete via `window.confirm`.
- **Typed API** `frontend/src/modules/catalog/api.ts`: covers the full `/api/user-catalog/*` surface (tree, fork any type, store/update/status/delete), plus `GET /api/rubros`.
- **Components**: `catalog-tree.tsx` (nested render, edit/status/delete per node), `catalog-badges.tsx` (effective status + origin), `catalog-item-dialog.tsx` (name/description/value; no tags, no parent).
- **Backend**: user-catalog HTTP surface complete (fork-api R1–R7); `GET /api/rubros` newly readable by any authenticated user (uncommitted diff + `BaseCatalogReadAccessTest`); base mutations and base `categorias`/`services` reads remain admin-only.
- **Tests**: 1 tree-render Jest test + 2 API serialization tests; backend suites verified through Slice 5 (231 tests).

The backend already supports everything Slice 7's UI needs **except** one point (see Gap G6): standalone `categoria`/`service` forks created via the cascade endpoint are born with `parent_fork_id = null` (orphan roots in the tree) and `parentFork === null` fail-safes effective status to `desactivado` — only the R5 attach (`PUT {parent_fork_id}`) can rehabilitate them, and no UI exposes it.

## Affected Areas

- `frontend/src/app/(dashboard)/catalogo/page.tsx` — orchestration: type-aware create intents, differentiated delete confirmations, 422/409 error surfacing, orphan/attach affordances.
- `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` — becomes type-aware (rubro/categoria/service fields, tags editor, destination-category selector, revert-to-base null semantics, changed-fields-only submit).
- `frontend/src/modules/catalog/components/catalog-tree.tsx` — per-node "Add category"/"Add service" affordances, orphan indicators, per-field override display (`overridden_fields` data already in nodes).
- `frontend/src/modules/catalog/api.ts` — extend `updatePersonalItem` input typing (`parent_fork_id`, explicit-null override clearing); error payload typing for Laravel 422 `errors` map.
- `frontend/src/modules/catalog/**/__tests__` + new page/dialog tests — planning3 mandates Jest per page/component (tree, badges, filters, create/edit/deactivate flows).
- Backend: expected **0 diff** (contracts complete); only touched if a real read-access gap is approved (G6 note below).

## Gap Analysis (planning3 + HU-013–HU-025 vs. as-built)

| # | Gap | Source | Backend support |
|---|-----|--------|-----------------|
| G1 | Cannot create personal **categoria** under a rubro fork or **service** under a categoria fork (store requires `parent_fork_id`; dialog hardcodes `rubro`) | HU-017, HU-021 | ✅ POST + validation (`name` / `title`+`value` required, sibling uniqueness) |
| G2 | No **tags** editor for services (allowed list `frontend|backend|fullstack|devops|mobile`) | HU-021, D4 | ✅ validated in Store/Update requests |
| G3 | Editing a node sends **all** displayed fields → silently sets overrides on untouched fields (origin flips to "Override"); no way to **revert a field to base** (explicit null) | D13, HU-025 flujo step 10 | ✅ PUT merged-overrides semantics (omitted = untouched, null = clear) |
| G4 | No **move service** (destination categoria picker via `parent_fork_id`) and no **attach orphan** flows; no surfacing of 422 cycle/coherence/uniqueness errors | HU-022, D8, R5 | ✅ full move/attach guard set (JD4-2 destination-scoped rename) |
| G5 | Delete confirmation is a raw `window.confirm` with fixed wording; planning3 mandates dialog confirmations differentiated by relations/descendants | planning3 UI, lifecycle flujo | ✅ tree data (`children`) suffices client-side for forks |
| G6 | **Orphan fork roots**: standalone categoria/service forks (parent null) render as roots and resolve `desactivado`; no UI to attach them | Slice 3 documented debt, R5 | ✅ attach; ⚠️ user base-browsing is limited: `GET /rubros/{id}/categorias` is admin-only, so users cannot list base categories to fork individually — rubro cascade is the supported discovery path. No contract widening is required by planning3; do NOT invent it. |
| G7 | No page-level Jest coverage (filters, create/edit/deactivate flows) | planning3 "Tests Jest por página/componente" | n/a |
| G8 | Admin visiting `/catalogo`: fork/create is policy-denied (`create` requires non-admin) → confusing 403 errors | D2, policy | decision needed: role-gate personalization affordances (design call, not a new requirement) |

## Approaches

1. **Evolve the existing single page + one type-aware dialog** (extend `CatalogItemDialog` with per-type fields, parent/move selector, tags, revert-to-base; node-level "add child" buttons; shadcn `Dialog` confirmation with differentiated wording).
   - Pros: matches established pattern (clients module: plain controlled forms, manual validation, no form lib), zero new routes, minimal surface, reuses tree/filter work.
   - Cons: page component grows; needs decomposition discipline to stay reviewable.
   - Effort: Medium
2. **Split into per-type views/tabs (Rubros/Categorías/Servicios management screens)** mirroring the clients table pattern.
   - Pros: simpler forms per screen.
   - Cons: fragments the tree UX planning3 defines ("árbol fork rubro → categorías → servicios"), duplicates navigation state, more new code than option 1.
   - Effort: High
3. **Backend-first detour** (add user-readable base children endpoints before UI).
   - Pros: enables per-category base browsing.
   - Cons: planning3 never asks for it; fork cascade already materializes children in the tree. Violates "do not invent requirements".
   - Effort: Medium — rejected.

## Recommendation

**Option 1**, delivered as two chained PR slices (400-line budget, `ask-on-risk`):

- **7a — Category/service CRUD forms**: type-aware dialog (fields per G1–G3), changed-fields-only submit + revert-to-base controls, tags editor, add-child affordances in the tree, Laravel 422 field-error surfacing, dialog-based differentiated delete confirmation. Tests RED-first per flow.
- **7b — Move/attach + closure polish**: destination-category move (service) and orphan-attach flows (G4/G6), per-field override display from `overridden_fields`, page-level Jest suite (G7), role-gating decision for admin view (G8), HU-013–HU-025 status transitions to *En Revisión* + Notion mirror per AGENTS.md.

All of it is client-side consumption of the frozen R1–R7 contracts; no backend diff is expected. Strict TDD (`npm test` RED→GREEN) applies; `Next.js 16` docs must be consulted before writing page code per project rules.

## Risks

- **Slice 6 is uncommitted and undocumented**: worktree holds all Slice 6 files; no `openspec/changes/` folder, its task file sits in `odd/tasks/`, and `planning3.md` Registro de progreso stops at Slice 5. Slice 7 must not branch from a dirty tree — commit/close Slice 6 (and decide its spec-sync for the `GET /rubros` auth change) first.
- **Review budget**: combined G1–G8 UI + tests will likely exceed 400 authored lines → chained slices are the mitigation (7a/7b split above).
- **Semantics trap (G3)**: without changed-fields-only submits, every edit silently converts `base`-origin forks into `override` — a correctness/UX regression already present; fixing it changes API payload shape emitted by the client, so it needs its own tests.
- **Orphan dead-end (G6)**: users can create forks that are permanently `desactivado` until attached; without 7b's attach flow this is a user-facing dead-end. Acceptable only if 7a and 7b ship together in the epic close.
- **Admin affordances (G8)**: policy denies admin fork operations; unaddressed, admins see error banners instead of a coherent page.
- **Notion mirror obligation (AGENTS.md)**: HU status transitions must be mirrored to Notion when the epic closes.

## Ready for Proposal

**Yes.** Scope is fully grounded in planning3's frontend section, HU-013–HU-025 acceptance criteria, and the frozen backend contracts; nothing needs invention. The orchestrator should tell the user: (1) Slice 6 must be committed/closed (tree is dirty, records missing) before Slice 7 branches; (2) recommend proposing as 7a/7b chained PRs under the 400-line budget; (3) one design decision to confirm: admin role-gating of personalization affordances on `/catalogo`.

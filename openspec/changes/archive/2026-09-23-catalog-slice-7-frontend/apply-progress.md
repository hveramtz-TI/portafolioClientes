# Apply Progress: catalog-slice-7-frontend (cumulative)

**Artifact store**: native status reports `openspec` → authoritative artifacts are this file and `openspec/changes/catalog-slice-7-frontend/tasks.md`. Any Engram observation is supplemental.
**Mode**: Strict TDD active (`openspec/config.yaml` → `rules.apply.tdd: true`). This batch exercised the full RED → GREEN → REFACTOR cycle for every Phase 4 work unit; the evidence table below is mandatory and complete.
**Chain strategy**: `stacked-to-main` (resolved in `tasks.md`; `Decision needed before apply: No`).
**Delivery strategy**: `ask-on-risk`. PR 1 (`2669905`) and PR 2 (`ffd963f`) are committed; PR 3, PR 4, and PR 5 are the uncommitted slices. PR 1–3 are the Slice 7a chain; PR 4–5 are Slice 7b. `size:exception` was accepted by the user for PR 2, PR 3, and PR 4 (see Workload / PR Boundary).
**Boundary of this batch (PR 5)**: starts after the PR 4 batch (Phase 4 complete and green, uncommitted) with Phase 5 (5.1–5.9) unchecked. Ends with Phase 5.1–5.8 complete and their gates green; 5.9 remains open (no PR exists, remote mutation prohibited). The user subsequently reported the requested manual admin/non-admin `/catalogo` validation as passed. No PR created, nothing pushed, no remote mutation.

## Batch start state (recorded honestly)

### PR 1–2 batches

At the start of the PR 1–2 batch the authoritative `tasks.md` still showed **0.3 and 0.4 as unchecked and unannotated**, and **no `apply-progress.md` existed**. Prior Engram observations (`#886`, `#891`, `#895`) described those artifact writes as done, but the repository did not reflect them. That batch worked against the real on-disk state instead of trusting the observations.

### This batch (PR 3)

Start state was read from the on-disk artifacts, not from memory: Phase 0–2 checked with evidence, Phase 3 (3.1–3.9) unchecked, and no catalog source implementing child-add affordances, lifecycle confirmations, or page mutation wiring (`window.confirm` still present in `frontend/src/app/(dashboard)/catalogo/page.tsx`; `catalog-tree.tsx` had no `onAddChild`; no `confirm-dialog.tsx`). The batch re-derived the work from that state.

### This batch (PR 4)

Start state was read from the on-disk artifacts, not from memory: Phase 0–3 checked with evidence (PR 3 uncommitted), Phase 4 (4.1–4.13) unchecked, and no catalog source implementing move-service, orphan-attach, per-field override display, or admin role gating (`catalog-item-dialog.tsx` had no `tree` prop or destination selector; no `attach-dialog.tsx`; `catalog-tree.tsx` had no `onAttach`; `catalog-badges.tsx` rendered only status/origin; `page.tsx` did not consume `useAuth`). The batch re-derived the work from that state.

## Completed Tasks (cumulative)

### Phase 0 — PR 1 base (`2669905`)
- [x] 0.1 Base worktree clean; `77f1165 feat(catalog): add initial catalog frontend` confirmed as ancestor.
- [x] 0.2 Chain strategy (`stacked-to-main`) resolved; first branch created from the clean post-Slice 6 base.
- [x] 0.3 Non-admin development account confirmed and owner-path scenario passed; the PR-description credential/session recording was **explicitly waived by the user** (documentation-only evidence; no credential material requested, seen, or written; no remote mutation). Recorded in `tasks.md`.
- [x] 0.4 Bundled Next.js 16.2.12 App Router docs re-read (read-only); constraints for the `/catalogo` page recorded in `tasks.md`.

### Phase 1 — PR 1 (`2669905`)
- [x] 1.1–1.6 `ApiError` (carrying `status` + optional Laravel `errors` map) thrown from `apiFetch` with the message text preserved; `CreatePersonalItemInput` widened for explicit `null` values and `parent_fork_id`; `LaravelValidationError` / `LaravelConflictError` types added. Focused Jest 19/19; scoped lint clean.

### Phase 2 — PR 2 (`ffd963f`)
- [x] 2.1–2.12 Type-aware dialog (per-type fields + titles), constrained tags editor (5 allowed values), changed-fields-only submit, per-field revert-to-base (explicit `null`), 422 field-level / 409 form-level / 500 generic error surfacing, helpers extracted. Focused Jest 21/21; full suite 8 suites / 65 tests; scoped lint clean. User manually validated `/catalogo` with a non-admin session — all checks passed. `size:exception` accepted for 639 authored lines.

### Phase 3 — PR 3 (this batch, uncommitted)
- [x] 3.1 (RED) *Tree Node Child-Add Affordances* cases added to `catalog-tree.test.tsx`; observed failing first (5 failed / 2 passed).
- [x] 3.2 (GREEN) `onAddChild` prop + "Add category"/"Add service" buttons in `catalog-tree.tsx`; 7/7 focused tests pass. REFACTOR: child type computed once per node; display-name helper extracted.
- [x] 3.3 (RED) `confirm-dialog.test.tsx` created; observed failing first (suite could not resolve the missing component).
- [x] 3.4 (GREEN) `confirm-dialog.tsx` created with descendant-aware wording, item display name, confirm/cancel callbacks, shadcn `Dialog`; 8/8 focused tests pass. REFACTOR: shared `catalogNodeLabel` reused by the tree.
- [x] 3.5 (RED) `catalog-page.test.tsx` created; observed failing first (7 failed / 1 passed).
- [x] 3.6 (GREEN) Page wiring: `createIntent` state, `onAddChild`, create-vs-edit routing by intent type, `ConfirmDialog` replacing `window.confirm` for delete and deactivate.
- [x] 3.7 (GREEN) `ApiError` mapping in the page (`422` → `validationErrors`, `409` → server `message`, other → generic), dialog kept open on failure, no global banner for validation failures.
- [x] 3.8 No production `window.confirm` left in `frontend/src`; focused Jest 3 suites / 23 tests green; scoped catalog lint clean (exit 0).
- [x] 3.9 Slice 7a exit gate: full frontend `npm test` 10 suites / 87 tests green; backend `php artisan test` 235 passed and `./test-pg.sh` OK (235 tests / 885 assertions); `git diff --stat -- backend` empty.

### Phase 4 — PR 4 (this batch, uncommitted)
- [x] 4.1 (RED) *Move-Service Flow* cases added to `catalog-item-dialog.test.tsx`; observed failing first (3 failed / 22 passed).
- [x] 4.2 (GREEN) Destination categoria selector in `catalog-item-dialog.tsx` fed by the `tree` prop; `parent_fork_id` sent only when the destination changes; 25/25 focused tests pass. REFACTOR: candidate resolution extracted to `catalog-node-candidates.ts`.
- [x] 4.3 (RED) `attach-dialog.test.tsx` created; observed failing first (suite could not resolve the missing component).
- [x] 4.4 (GREEN) `attach-dialog.tsx` created with type-coherent parent candidates, confirm/cancel callbacks, and an optional error slot; 4/4 focused tests pass.
- [x] 4.5 (RED) *Orphan-Attach* tree cases added; observed failing first (part of the 4 failed / 10 passed run).
- [x] 4.6 (GREEN) Orphan indicator + "Attach" action in `catalog-tree.tsx` with the `onAttach` prop; a parentless rubro is never flagged; 14/14 tree tests pass.
- [x] 4.7 (RED) Page-level attach + move cases added to `catalog-page.test.tsx`; observed failing first (3 failed / 8 passed).
- [x] 4.8 (GREEN) Page wiring for `onAttach`, the `tree` prop, and the attach error path; page suite 11/11.
- [x] 4.9 (RED) *Per-Field Override Display* tree cases added; observed failing first.
- [x] 4.10 (GREEN) Per-field override badges in `catalog-badges.tsx`; 14/14 tree tests pass.
- [x] 4.11 (RED) *Admin Role-Coherent Rendering* page cases added; observed failing first (1 failed / 12 passed).
- [x] 4.12 (GREEN) `useAuth`-driven `isAdmin` gate in `page.tsx` (base fork/create hidden, informational My Catalog state); page suite 13/13. REFACTOR: My Catalog branch re-indented for reviewability.
- [x] 4.13 Focused Jest 2 suites / 18 tests green; scoped catalog lint clean (exit 0); full suite 11 suites / 107 tests; production build clean; zero backend diff.

### Phase 5 — PR 5 (this batch, uncommitted)
- [x] 5.1 (RED/guard) 9 page-level coverage cases added to `catalog-page.test.tsx` (nested hierarchy, badges, status+origin filter re-fetch with the exact query, Base Catalog fork, personal-rubro create, reactivate, 409 form-level, page-level revert explicit-null, child-create reload+close); suite 13 → 22. All observed passing; they exercise behavior implemented in Phases 2–4, so no failing RED was reproducible — recorded honestly as guards.
- [x] 5.2 (GREEN) No implementation gap exposed by 5.1 — no production change needed; focused suite 22/22.
- [x] 5.3 Spec scenario audit walked; every scenario maps to an assertion. Intentional non-Jest gaps: the two HU-doc scenarios (5.7/5.8) and component-covered leaf-wording / orphan-service candidates. Mapping table below.
- [x] 5.4 Full frontend gate: `npm test` 11 suites / 116 tests green; scoped catalog lint exit 0. Full `npm run lint` still red on the same 7 pre-existing problems / 6 untouched files (no catalog file).
- [x] 5.5 Backend gates: `php artisan test` 235 passed (885 assertions); `./test-pg.sh` OK (235 / 885); `git diff --stat -- backend` empty.
- [x] 5.6 `codegraph sync` already up to date; `codegraph status` → index up to date.
- [x] 5.7 HU-013–HU-023 and HU-025 transitioned to *En Revisión* in `docs/historias/`; HU-024 left unchanged; README index rows updated (stale HU-024 index row corrected to match its file).
- [x] 5.8 All 12 HU pages mirrored to *En Revisión* in the Notion HU database; re-query confirms HU-013–HU-025 all `En Revisión`, equal to `docs/historias/`.
- [ ] 5.9 PR description update — **pending, cannot complete**: no PR exists and remote GitHub mutation is prohibited in this phase. The user later reported the requested manual browser validation as passed; that evidence must be copied into the PR description when a PR exists.

## TDD Cycle Evidence (Strict TDD hard gate)

| Task | RED (test written first, observed failing) | GREEN (implementation, observed passing) | REFACTOR |
|---|---|---|---|
| 3.1 / 3.2 | `catalog-tree.test.tsx` child-add cases → **5 failed / 2 passed** (`onAddChild` prop and buttons missing) | `onAddChild` + conditional add buttons → **7/7 pass** | `childTypeFor()` computed once per node; `nodeLabel` moved to `catalog-node-label.ts` |
| 3.3 / 3.4 | `confirm-dialog.test.tsx` → **suite failed to run**: `Cannot find module '../components/confirm-dialog'` | `confirm-dialog.tsx` → **8/8 pass** | tree refactored onto the shared `catalogNodeLabel` helper (DRY) |
| 3.5 / 3.6 / 3.7 | `catalog-page.test.tsx` → **7 failed / 1 passed** (no dialog opened, no confirm dialog, no add-child handler) | page wiring + `ApiError` mapping → **8/8 pass** | page JSX re-wrapped from one dense line into reviewable multi-line form (no behavior change); `ConfirmDialog` rendered conditionally instead of with a fallback item |
| 4.1 / 4.2 | `catalog-item-dialog.test.tsx` move cases → **3 failed / 22 passed** (no destination selector, no move payload, no 422 field slot) | `tree` prop + destination selector + edit-mode `parent_fork_id` → **25/25 pass** | candidate resolution extracted to `catalog-node-candidates.ts`, reused by the attach dialog |
| 4.3 / 4.4 | `attach-dialog.test.tsx` → **suite failed to run**: `Cannot find module '../components/attach-dialog'` | `attach-dialog.tsx` → **4/4 pass** | candidate helper reused from `catalog-node-candidates.ts` (DRY) |
| 4.5 / 4.6 | orphan tree cases → **failing** (no `Unattached` indicator, no `Attach` action) | `isOrphan` + indicator + `onAttach` action → **14/14 tree tests pass** | orphan predicate centralized in one `isOrphan()` helper; rubro root explicitly excluded |
| 4.7 / 4.8 | page attach + move cases → **3 failed / 8 passed** (no attach affordance, no move payload) | `onAttach` wiring + `tree` prop + `toAttachError` → **11/11 pass** | `toAttachError` reuses `toDialogErrors` instead of duplicating the 422/409 mapping |
| 4.9 / 4.10 | per-field override cases → **failing** (no override badges) | dashed per-field badges from `overridden_fields` → **14/14 pass** | field-label map keeps the badge text declarative and testable |
| 4.11 / 4.12 | admin rendering case → **1 failed / 12 passed** (admin still saw fork/create affordances) | `useAuth` → `isAdmin` gate + informational My Catalog state → **13/13 pass** | My Catalog branch re-indented inside its fragment (formatting only) |
| 5.1 / 5.2 | 9 page-level coverage cases written first → **22/22 pass on first run**; no failing RED was reproducible because the behavior already exists from Phases 2–4 | 5.2 required no production change (nothing to implement) | N/A — no production code touched |

No Phase 3–4 task was completed without its RED test observed failing first. Phase 5.1 is a coverage-completion task, not a behavior change: its 9 cases exercise behavior already shipped in Phases 2–4, so they pass on first run and are recorded honestly as **guard cases**, not as RED. Guard cases that already passed before implementation (unchanged-destination omission, "no indicators when nothing is overridden", non-admin affordances, and every 5.1 case) are likewise recorded as guards. No fallback to Standard Mode occurred.

## Work Unit Evidence (Phase 3 = PR 3)

| Evidence | Value |
|---|---|
| Focused test command and exact result | `cd frontend && npx jest src/modules/catalog/components/catalog-tree.test.tsx src/modules/catalog/__tests__/confirm-dialog.test.tsx src/modules/catalog/__tests__/catalog-page.test.tsx` → **3 suites / 23 tests passed** |
| Runtime harness command/scenario and exact result | `cd frontend && npm run build` (real Next.js 16.2.12 production compile) → **✓ Compiled successfully; Finished TypeScript in 2.9s; `/catalogo` prerendered**. The tasks' browser scenario (add category under a rubro, delete a node with children, cancel then confirm) was **NOT executed**: it needs an authenticated non-admin browser session, and no credentials/session were supplied to this batch (not invented). The page-level Jest suite drives the real page → tree → dialog → confirm-dialog integration with only the transport layer mocked. |
| Rollback boundary | `frontend/src/modules/catalog/components/catalog-tree.tsx`, `catalog-tree.test.tsx`, `confirm-dialog.tsx`, `catalog-node-label.ts`, `confirm-dialog.test.tsx`, `catalog-page.test.tsx`, `frontend/src/app/(dashboard)/catalogo/page.tsx`. Reverting these restores the pre-PR-3 tree (no add-child affordances) and the `window.confirm` delete path; no API or backend coordination required. Phase 1–2 files are untouched by this batch. |

## Work Unit Evidence (Phase 4 = PR 4)

| Evidence | Value |
|---|---|
| Focused test command and exact result | `cd frontend && npx jest src/modules/catalog/__tests__/attach-dialog.test.tsx src/modules/catalog/components/catalog-tree.test.tsx` → **2 suites / 18 tests passed** (exit 0). Per-file during TDD: dialog move suite 25/25, attach dialog 4/4, tree 14/14, page 13/13. |
| Runtime harness command/scenario and exact result | `cd frontend && npm run build` (real Next.js 16.2.12 production compile) → **✓ Compiled successfully; Finished TypeScript in 3.0s; `/catalogo` prerendered**. Full `npm test` → **11 suites / 107 tests passed**. The tasks' browser scenario (`npm run dev` as admin and as non-admin on `/catalogo`) was **NOT executed**: it needs authenticated sessions for both roles, and no credentials/session were supplied to this batch (not invented). The page-level Jest suite drives the real page → tree → dialog/attach-dialog integration with only the transport layer and `useAuth` mocked. |
| Rollback boundary | `frontend/src/modules/catalog/components/attach-dialog.tsx`, `catalog-node-candidates.ts`, `catalog-badges.tsx`, `catalog-tree.tsx`, `catalog-item-dialog.tsx`, `catalog-item-dialog.helpers.ts`, `frontend/src/app/(dashboard)/catalogo/page.tsx`, `frontend/jest.setup.ts` + the four test files. Reverting these restores the pre-PR-4 surface (no move selector, no attach flow, no per-field badges, no admin gate); no API or backend coordination required. Phases 1–3 files are otherwise untouched by this batch. |

## Work Unit Evidence (Phase 5 = PR 5)

| Evidence | Value |
|---|---|
| Focused test command and exact result | `cd frontend && npx jest src/modules/catalog/__tests__/catalog-page.test.tsx` → **1 suite / 22 tests passed** (exit 0) |
| Runtime harness command/scenario and exact result | `cd frontend && npm test` (full Jest) → **11 suites / 116 tests passed**, 1 snapshot. Backend runtime gates (5.5) → `php artisan test` 235 passed (885 assertions) and `./test-pg.sh` OK (235 / 885). The user subsequently reported the requested manual `/catalogo` browser pass as passed for the non-admin owner flow and admin rendering. The page-level Jest suite drives the real page → tree → dialog/confirm-dialog/attach-dialog integration with only the transport layer and `useAuth` mocked. |
| Rollback boundary | `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx` (test-only additions), `docs/historias/HU-013.md`–`HU-025.md` (HU-024 untouched), `docs/historias/README.md`, and the Notion HU database mirror. Reverting these restores the pre-PR-5 test file and the prior HU statuses. **No production source changed in this batch**, so the rollback is test + docs only. |

## Spec Scenario Coverage Audit (task 5.3)

Read-only walk of `specs/catalog-frontend-management/spec.md`. Every behavior scenario maps to an assertion; the two documentation scenarios are intentionally docs-only.

| Spec requirement | Assertion location |
|---|---|
| Type-Aware Catalog Item Dialog (5 scenarios) | `catalog-item-dialog.test.tsx` (type-aware fields); page suite covers create-categoria / create-service / edit-categoria |
| Service Tags Editor (4) | `catalog-item-dialog.test.tsx` — service tags editor block |
| Changed-Fields-Only Submit Semantics (4) | `catalog-item-dialog.test.tsx` — changed-fields block; page suite "updates an existing node…" |
| Revert-to-Base Controls (4) | `catalog-item-dialog.test.tsx` — revert block; **page suite (added 5.1)** — explicit-null through `updatePersonalItem` |
| Tree Node Child-Add Affordances (6) | `catalog-tree.test.tsx` (3 button cases); page suite create flows + **added** reload/close |
| Differentiated Lifecycle Confirmations (6) | `confirm-dialog.test.tsx` (wording, leaf, callbacks, no `window.confirm`); page suite descendant delete/deactivate + cancel/confirm |
| Server Error Surfacing (5) | `catalog-item-dialog.test.tsx` (422 single/multi, 409, 500, move 422); page suite 422 field-level + **added** 409 form-level |
| Move-Service Flow (4) | `catalog-item-dialog.test.tsx` move block; page suite move payload + reload |
| Orphan-Attach Flow (5) | `catalog-tree.test.tsx` (indicators); `attach-dialog.test.tsx` (categoria + service candidates, confirm/cancel); page suite attach payload + 422 |
| Per-Field Override Display (3) | `catalog-tree.test.tsx` override-display block |
| Admin Role-Coherent Rendering (3) | page suite admin/non-admin blocks |
| API Client Contract Extensions (4) | `src/modules/catalog/__tests__/api.test.ts`, `src/lib/__tests__/api.test.ts` |
| Page-Level Jest Coverage (9) | page suite: **added** tree rendering; create-categoria; changed-fields; revert (**added**); descendant delete; 422; admin; move; orphan-attach |
| HU Status Transition Documentation (2) | **Intentional non-Jest gap** — docs-only (tasks 5.7 local, 5.8 Notion mirror) |

No scenario was silently omitted. Component-covered scenarios (leaf wording, orphan-service candidates) are asserted in their component suites; the remaining two are documentation scenarios.

## Prerequisite 0.3 — CLOSED (user-approved waiver)

- The non-admin account existence and the complete owner-path manual validation were confirmed earlier (recorded against task 2.12) and remain valid.
- The user **explicitly waived** recording credential/session details in the PR description. This is documentation-only evidence: no credential material was requested, seen, or written anywhere, and no remote GitHub mutation was performed.
- `tasks.md` records 0.3 as `[x]` with that waiver. Not reopened.

## Prerequisite 0.4 — DONE (read-only)

- Re-read the bundled Next.js 16.2.12 App Router docs: `05-server-and-client-components.md`, `use-client.md`, `page.md`, `route-groups.md`.
- Cross-checked with Context7 `/vercel/next.js/v16.2.9` (closest published version to installed 16.2.12) — same rules; bundled docs authoritative.
- Concrete result for editing `frontend/src/app/(dashboard)/catalogo/page.tsx`: keep `'use client'` first; a page may be a Client Component; Client Components are required for state/effects/handlers/browser APIs and React context (`useAuth` for the 4.12 admin gate — context is unsupported in Server Components); `(dashboard)` is a route group not present in the URL; callbacks between the page and child Client Components are fine (the serializable-props rule only constrains Server→Client props); the page takes no `params`/`searchParams`, so the v15+ promise-prop change does not apply.
- No application source modified by that prerequisite.

## Files Changed (cumulative)

### This batch (PR 5)

| File | Action | What |
|---|---|---|
| `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx` | Modified | +9 page-level cases (nested hierarchy, badges, filter re-fetch, Base Catalog fork, personal-rubro create, reactivate, 409 form-level, revert explicit-null, child-create reload+close); imports `forkBaseItem` |
| `docs/historias/HU-013.md` … `HU-023.md`, `HU-025.md` | Modified | `**Estado:**` → `En Revisión` (12 files) |
| `docs/historias/README.md` | Modified | Index rows for HU-013–HU-023 and HU-025 → `En Revisión`; stale HU-024 row corrected to `En Revisión` to match `HU-024.md` |
| Notion HU database (*Historias de Usuario Portafolio de Clientes*) | Modified | 12 HU pages → `En Revisión`; HU-024 already `En Revisión` |

**No production source changed in this batch.** No file under `backend/` was touched. No commit, PR, push, or remote mutation was performed.

### PR 4 (previous batch)

| File | Action | What |
|---|---|---|
| `frontend/src/modules/catalog/components/attach-dialog.tsx` | Created | shadcn `Dialog` orphan-attach picker: type-coherent parent candidates, confirm/cancel callbacks, optional in-dialog error slot |
| `frontend/src/modules/catalog/components/catalog-node-candidates.ts` | Created | `parentTypeFor` / `collectNodesByType` / `parentCandidatesFor` shared type-coherent candidate resolution |
| `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` | Modified | `tree` prop, destination categoria selector for service edits, `parent_fork_id` on submit, `parent_fork_id` field-error slot |
| `frontend/src/modules/catalog/components/catalog-item-dialog.helpers.ts` | Modified | Edit-mode payload appends `parent_fork_id` only when a destination was picked |
| `frontend/src/modules/catalog/components/catalog-badges.tsx` | Modified | Per-field override badges driven by `overridden_fields`, keeping status/origin badges |
| `frontend/src/modules/catalog/components/catalog-tree.tsx` | Modified | `onAttach` prop, `isOrphan()` predicate, "Unattached" indicator, "Attach" action |
| `frontend/src/app/(dashboard)/catalogo/page.tsx` | Modified | `useAuth` → `isAdmin` gate, attach state/handler + `toAttachError`, `tree` prop to the dialog, `AttachDialog` rendering |
| `frontend/src/modules/catalog/__tests__/attach-dialog.test.tsx` | Created | Candidate coherence, confirm/cancel callbacks |
| `frontend/src/modules/catalog/__tests__/catalog-item-dialog.test.tsx` | Modified | Move-service destination selector, move payload, 422 move error |
| `frontend/src/modules/catalog/components/catalog-tree.test.tsx` | Modified | Orphan attach affordances + per-field override display |
| `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx` | Modified | Orphan attach + move flows, attach 422 surfacing, admin/non-admin rendering |
| `frontend/jest.setup.ts` | Modified | Pointer-capture / `scrollIntoView` / `ResizeObserver` stubs so Radix primitives are interactive under jsdom |

No file under `backend/` was touched. No commit, PR, push, or remote mutation was performed.

### PR 3 (unchanged, listed for completeness)

| File | Action | What |
|---|---|---|
| `frontend/src/modules/catalog/components/catalog-tree.tsx` | Modified | `onAddChild` prop, per-node "Add category"/"Add service" buttons, `childTypeFor()` guard; uses the shared label helper |
| `frontend/src/modules/catalog/components/catalog-tree.test.tsx` | Modified | Child-add affordance cases (rubro/categoria/service) + callback assertions |
| `frontend/src/modules/catalog/components/catalog-node-label.ts` | Created | Shared `catalogNodeLabel(node)` display-name helper |
| `frontend/src/modules/catalog/components/confirm-dialog.tsx` | Created | shadcn `Dialog` confirmation with descendant-aware delete/deactivate wording |
| `frontend/src/modules/catalog/__tests__/confirm-dialog.test.tsx` | Created | Wording (descendant vs leaf), display name, confirm/cancel, no `window.confirm` |
| `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx` | Created | Page-level: add-child create flows, differentiated delete/deactivate confirmation, cancel/confirm, 422 field mapping, edit routing |
| `frontend/src/app/(dashboard)/catalogo/page.tsx` | Modified | `createIntent` state, `onAddChild`, create-vs-edit routing by intent type, `ConfirmDialog` replacing `window.confirm`, `ApiError` → dialog error mapping, JSX re-wrapped for reviewability |

### PR 1–2 (unchanged, listed for completeness)

| File | Action |
|---|---|
| `frontend/src/lib/api.ts` + `frontend/src/lib/__tests__/api.test.ts` | Modified |
| `frontend/src/modules/catalog/api.ts` + `frontend/src/modules/catalog/__tests__/api.test.ts` | Modified |
| `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` + `catalog-item-dialog.helpers.ts` + `__tests__/catalog-item-dialog.test.tsx` | Modified / Created |
| `openspec/changes/catalog-slice-7-frontend/tasks.md`, `apply-progress.md` | Modified / Created |

## Deviations from Design

1. **Shared label helper (`catalog-node-label.ts`) is not in the design's File Changes table.** The confirm dialog needs the same display-name logic the tree already had; extracting it avoids duplicating `nodeLabel` in two components. One extra 6-line file, no behavior change.
2. **Reactivation does not open a confirmation.** The design says to replace `window.confirm` "for all delete/deactivate confirmations". Reactivation is not a cascading or destructive action, so `changeStatus` only routes to `ConfirmDialog` when the node is `activo` (i.e. deactivating). The deactivate path is confirmed; reactivation stays a direct action.
3. **The `/catalogo` page was re-wrapped from a single dense JSX line into multi-line form.** Inserting handlers and new props into one line would have been unreviewable. This is a formatting-only change to pre-existing markup and it inflates the raw diff (~105 of the 154 added lines); no existing behavior changed.
4. **Page test mocks the module through a relative path.** In this Jest setup `jest.mock('@/modules/catalog/api', …)` fails module resolution even though `@/` imports resolve fine, so the mock is registered via the equivalent `../api` path (same resolved module, so the page's aliased import receives the mock). A comment in the test records this. PR 4 uses the same pattern for `useAuth` (`../../../hooks/useAuth`).
5. **`frontend/jest.setup.ts` gains jsdom stubs (PR 4).** Radix UI primitives (the project's `Select`/`Dialog`) need `hasPointerCapture`/`setPointerCapture`/`releasePointerCapture`, `scrollIntoView`, and `ResizeObserver` to be interactive under jsdom. Without them, opening a `Select` in a test silently does nothing. The stubs are 21 lines of test infrastructure with no production impact; they are not in the design's File Changes table.
6. **Move destination selector renders only when an eligible destination exists (PR 4).** The spec says the dialog "MUST provide a destination categoria selector when editing a service". Rendering an empty dropdown when the user owns no other categoria would be a dead control, so the selector renders when `parentCandidatesFor(tree, 'service', currentParent)` is non-empty — which is exactly the spec scenario ("lists other categoria forks owned by the user").
7. **`AttachDialog` gains an optional `error` prop beyond the design's `AttachDialogProps` (PR 4).** The spec's *Attach failure shows error* scenario needs the server 422 message displayed; keeping the dialog open with an in-dialog message is the coherent surface. The design contract listed only `open`/`orphan`/`tree`/`onAttach`/`onCancel`.
8. **The two indicator kinds live in different components (PR 4), as the tasks specify.** The orphan indicator + "Attach" action are in `catalog-tree.tsx` (task 4.6); the per-field override badges are in `catalog-badges.tsx` (task 4.10). The design's prose bundled per-field indicators with the tree node rendering; the tasks split them, and the tasks were followed.
9. **Attach requires an explicit parent choice (PR 4).** The dialog's "Attach" button stays disabled until a candidate is selected, instead of defaulting to the first candidate. This avoids an accidental attach on a quick confirm.
10. **Admin gating replaces the My Catalog content instead of rendering a read-only tree (PR 4).** The design says admins get an informational message in the My Catalog tab; the implementation swaps the filters + tree for that state rather than adding a read-only tree mode (smaller, and no policy-denied actions can be reached). The tree fetch itself is unchanged.

11. **`docs/historias/README.md` index corrected (PR 5).** The index table is part of `docs/historias/` and AGENTS.md requires Notion to equal it. Its HU-024 row said `Aprobada` while `HU-024.md` said `En Revisión`; task 5.7 requires leaving HU-024 at its *current status* (the file's). The index row was corrected to `En Revisión` so the docs — and therefore the Notion mirror — are internally consistent. This is an index correction, not an HU transition. Same fix applied to the 12 transitioned rows.

## Issues Found

1. **Artifact/reality drift (PR 1–2 batch)**: prior Engram observations claimed 0.3/0.4 were written and `apply-progress.md` existed, but the repository did not reflect any of it. Re-derived from the on-disk state.
2. **Full frontend lint is not green (pre-existing debt)**: `npm run lint` reports 7 problems in files untouched by this change (`dashboard/page.tsx`, `components/ui/sidebar.tsx`, `context/AuthContext.tsx`, `hooks/use-mobile.ts`, `modules/clients/components/delete-client-dialog.tsx`, `modules/companies/components/delete-company-dialog.tsx`). `design.md` explicitly scopes this debt out and gates on scoped catalog lint; the scoped lint is clean and no new problem was introduced.
3. **Stale generated type error**: `npx tsc --noEmit` reports one error in the generated `.next/dev/types/validator.ts` (a stale reference to `src/app/page.js`). The real production build regenerates types and type-checks cleanly, so this is generated-output noise, not a source defect.
4. **Design note (carried from PR 1)**: `frontend/src/lib/api.ts` is not in `design.md`'s File Changes table yet the *API Client Contract Extensions* requirement needs it; the change is backward-compatible and flagged for review.
5. **The user reported the PR 3 browser harness as passed** (add category under a rubro, delete a node with children, cancel then confirm).
6. **The user reported the PR 4 and epic-close browser harness as passed** (non-admin move/attach/override flows and admin rendering without personalization affordances or 403 banners).
7. **PR 4 exceeds the 400-line review budget (PR 4).** The authored slice is ~624 lines (tracked additions attributable to PR 4 plus three new files plus the additions to the untracked `catalog-page.test.tsx`). The PR 4 work unit is cohesive — move + attach + override display + admin gating share the dialog/tree/page surfaces, and Strict TDD puts tests inside the unit — so it cannot shrink without splitting a single reviewable flow. A `size:exception` acceptance for PR 4 (like PR 3's) is recommended; no PR was created and no split was attempted.
8. **Full frontend lint remains red on the same 6 untouched files (PR 4).** See issue 2; the scoped catalog lint (the Slice 7 gate) is clean and `jest.setup.ts` is included in that scoped run.
9. **Phase 5.1 produced no reproducible RED (PR 5).** The 9 new page-level cases exercise behavior shipped in Phases 2–4, so they passed on first run. Recorded honestly as guard cases rather than manufacturing a failing state; task 5.2 consequently required no production change. This is a coverage-completion task, not a behavior change.
10. **Task 5.9 cannot be completed in this phase (PR 5).** There is no PR to update: nothing was committed or pushed and remote GitHub mutation is prohibited in the apply phase. The user later supplied the manual browser result; it must be copied into the PR description when the PR is opened. Left `[ ]`.
11. **Manual browser verification is user-reported as passed.** The report covers the PR 3 and PR 4 harnesses plus the epic-close `/catalogo` pass (non-admin owner flow, admin rendering, and no 403 banners).

## Remaining Tasks

- [ ] 5.9 Update the PR description with manual verification evidence (blocked: no PR exists and remote mutation is prohibited in this phase)
- [x] Manual non-admin browser pass for the PR 3 harness — user reported passed (add category under a rubro, delete a node with children, cancel then confirm)
- [x] Manual admin + non-admin browser pass for the PR 4 harness and the epic-close `/catalogo` pass — user reported passed (move a service, attach an orphan, confirm per-field override badges, admin sees no personalization affordances and no 403 banners)
- [ ] Commit the chained PR slices (7a: PR 1–3 already partially committed; PR 3/4/5 still uncommitted) — not part of the apply phase

## Workload / PR Boundary

- **Mode**: stacked PR slice (`stacked-to-main`). **`size:exception` is accepted by the user for PR 3 and PR 4** (both above the 400-line budget, as recorded in this artifact). PR 5 is the closing slice.
- **Current work unit**: PR 5 — page-level Jest coverage completion (9 cases), full frontend + backend gates, CodeGraph sync, HU status transitions in `docs/historias/` + README index, and the Notion HU-database mirror.
- **Boundary**: starts from the PR 4 batch (Phase 4 complete, uncommitted) and ends with Phase 5.1–5.8 complete + gates green. Task 5.9 (PR-description update) stays pending because no PR exists. PR 5 changes are test-only plus docs/Notion, so the slice is independently revertable.
- **Why it cannot shrink further**: PR 5 is already minimal — one test file plus documentation status edits. No production source is touched. The only unbounded part is the HU transition set, which the tasks fixed at HU-013–HU-023 and HU-025.

### PR 4 (previous batch, unchanged)

- **Mode**: stacked PR slice (`stacked-to-main`), **`size:exception` accepted by the user** — PR 4's authored diff is ~624 lines, above the 400-line budget.
- **Current work unit**: PR 4 — move-service selector + payload, orphan-attach flow (tree action + dialog + page wiring), per-field override badges, admin role-coherent page rendering, focused checks.
- **Boundary**: starts from the PR 3 batch (Phase 3 complete, uncommitted) and ends with Phase 4 complete + its focused checks green.
- **Why it cannot shrink further**: move + attach + override display + admin gating are one coherent Slice 7b work unit across the dialog, tree, badges, and page; the page wiring for attach cannot land without the tree action and the dialog, and the admin gate lives on the same page. Strict TDD makes tests part of the unit (3 of the 12 changed files are new test files).

### PR 3 (previous batch, unchanged)

- **Mode**: stacked PR slice (`stacked-to-main`), **`size:exception` accepted by the user** — the authored diff is ~665 lines (260 added / 14 deleted in tracked files, plus 391 lines in 4 new files), above the 400-line budget.
- **Current work unit**: PR 3 — tree child-add affordances, differentiated lifecycle confirmations, page mutation wiring, page-level error mapping, focused checks, Slice 7a exit gate.
- **Boundary**: starts from `ffd963f` (Phase 2 complete) and ends with Phase 3 complete + Slice 7a exit gate green. Phase 4/5 files are untouched, so the slice is independently revertable.
- **Why it cannot shrink further**: the slice is one cohesive work unit (tree affordance → confirm dialog → page wiring must land together; a tree button without the page handler is dead UI). Tests are part of the unit under Strict TDD: 3 of the 4 new files are tests, and the page must be edited regardless.

## Status

PR 1 done (`2669905`), PR 2 done (`ffd963f`), **PR 3, PR 4, and PR 5 complete and green but uncommitted**. Prerequisites 0.3 (waived) and 0.4 are closed. Phases 0–5 (0.1–4.13 and 5.1–5.8) are checked in `tasks.md`; **5.9 is the only open task** (blocked: no PR to update and remote mutation is prohibited in this phase). Frontend 11 suites / 116 tests green; scoped catalog lint clean; backend 235 tests / 885 assertions on both gates; zero backend diff; CodeGraph up to date. HU-013–HU-023 and HU-025 are `En Revisión` in `docs/historias/` and mirrored identically to Notion. Next: open the chained PRs and complete 5.9, then archive.

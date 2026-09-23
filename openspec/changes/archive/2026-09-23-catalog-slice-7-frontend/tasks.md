# Tasks: Catalog Slice 7 — Frontend Category/Service Management & HU Closure

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~1,350–1,600 (additions + deletions, tests included) |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (API client) → PR 2 (dialog core) → PR 3 (tree + confirmations + page wiring) → PR 4 (move/attach + override display + admin gating) → PR 5 (page-level coverage + epic closure) |
| Delivery strategy | ask-on-risk |
| Chain strategy | stacked-to-main |
| PR 3 size exception | Accepted by the user before Phase 4 continuation |
| PR 4 size exception | Accepted by the user before Phase 5 continuation |

Decision needed before apply: No (resolved: stacked-to-main)
Chained PRs recommended: Yes
Chain strategy: stacked-to-main
400-line budget risk: High

**Why High**: Slice 7 touches 6 production files (2 modified heavily, 2 created) plus 6 test files, and Strict TDD makes tests part of every work unit. A single PR lands well above 400 authored lines. The design already anticipated this ("If 7a exceeds 400 lines, consider splitting it further"), so the split below keeps Slice 7a as PR 1–3 and Slice 7b as PR 4–5.

**Chain strategy resolved**: the parent-confirmed session preflight selected **stacked-to-main** (`ask-on-risk`); PR 1 targets `main`, later slices target the previous PR's branch (or `main` after the previous merges). `design.md` line 321 assumed the split was approved; the chain strategy itself is now decided.

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | API client contract: Laravel 422/409 typing, `parent_fork_id`, explicit `null` | PR 1 | `cd frontend && npx jest src/modules/catalog/__tests__/api.test.ts src/lib/__tests__/api.test.ts` | N/A — pure transport/typing layer, no renderable surface; verified through the PR 2–4 harnesses | `frontend/src/lib/api.ts`, `frontend/src/modules/catalog/api.ts` + the two test files; `git revert` restores prior client with no API coordination |
| 2 | Type-aware dialog: fields, tags editor, changed-fields-only submit, revert-to-base, error display | PR 2 | `cd frontend && npx jest src/modules/catalog/__tests__/catalog-item-dialog.test.tsx` | `cd frontend && npm run dev` → `/catalogo` → My Catalog → edit a service, change one field, save (non-admin account) | `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` + its test; page wiring untouched so revert restores prior dialog |
| 3 | Tree child-add affordances, differentiated lifecycle dialog, page mutation wiring | PR 3 | `cd frontend && npx jest src/modules/catalog/components/catalog-tree.test.tsx src/modules/catalog/__tests__/confirm-dialog.test.tsx` | `npm run dev` → add category under a rubro, delete a node with children (confirm dialog wording), cancel and confirm | `frontend/src/modules/catalog/components/catalog-tree.tsx`, `confirm-dialog.tsx`, `catalogo/page.tsx` + tests; delete confirmation behavior is the only shared surface |
| 4 | Move-service, orphan-attach, per-field override display, admin role gating | PR 4 | `cd frontend && npx jest src/modules/catalog/__tests__/attach-dialog.test.tsx` | `npm run dev` as admin on `/catalogo` (no personalization buttons) and as non-admin (move + attach flows) | `attach-dialog.tsx`, `catalog-badges.tsx`, `catalog-tree.tsx`, `catalogo/page.tsx` + tests; independent of PR 2/3 internals |
| 5 | Page-level Jest suite completion + HU/Notion epic closure | PR 5 | `cd frontend && npx jest src/modules/catalog/__tests__/catalog-page.test.tsx` | `npm test` + `npm run lint` full gates; browser pass through every spec scenario | `catalog-page.test.tsx`, `docs/historias/**`, Notion mirror; docs-only revert for the HU transitions |

**Threat matrix**: N/A per `design.md` — this change touches no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. Therefore no threat-matrix RED tasks exist for this change; every RED task below maps to a spec scenario.

## Phase 0: Preconditions & Branch Base

- [x] 0.1 Verify the base worktree is clean and Slice 6 is committed: `git status --porcelain` shows no catalog source changes and `77f1165 feat(catalog): add initial catalog frontend` is an ancestor of the new branch base (read-only: `git status`, `git log`)
- [x] 0.2 Confirm the chain strategy decided by the user and create the first branch from the clean post-Slice 6 base (do not branch from a dirty tree)
- [x] 0.3 Confirm a non-admin development account exists for owner-path validation; record credentials/session in the PR description (never commit them)
   - **Confirmed (account existence + owner-path validation)**: the user ran the complete manual `/catalogo` validation with a non-admin session and reported all checks passed (recorded with the PR 2 batch, task 2.12). No account name, email, identifier, browser, or timestamp was supplied, and none is invented here.
   - **User-approved waiver**: the user explicitly waived recording credential/session details in the PR description. This is documentation-only evidence; no credential material is requested, seen, or written anywhere, and no remote GitHub mutation is performed.
- [x] 0.4 Re-read `frontend/node_modules/next/dist/docs/` guidance for the App Router client components used on `/catalogo` before editing `frontend/src/app/(dashboard)/catalogo/page.tsx` (read-only)
  - **Bundled sources read (installed Next.js 16.2.12)**: `01-app/01-getting-started/05-server-and-client-components.md`; `01-app/03-api-reference/01-directives/use-client.md`; `01-app/03-api-reference/03-file-conventions/page.md`; `01-app/03-api-reference/03-file-conventions/route-groups.md`.
  - **Context7 cross-check**: `/vercel/next.js/v16.2.9` (closest published version to the installed 16.2.12) — same rules; the bundled docs remain authoritative.
  - **Concrete constraints for editing `frontend/src/app/(dashboard)/catalogo/page.tsx`**:
    - `'use client'` MUST stay the first line, before imports (`use-client.md` L14). The file already starts with it.
    - A `page` may be a Client Component (`page.md` L31); Client Components are required for state, event handlers, effects, and browser APIs (`05-server-and-client-components.md` L19–L24) — the page already uses `useState`/`useEffect`/`onClick`.
    - React context is NOT supported in Server Components; consuming `useAuth` requires a Client Component (`05-server-and-client-components.md` L349). The `(dashboard)/layout.tsx` Server Component wraps children in `AuthProvider` (`@/context/AuthContext`, itself `'use client'`), so the page can consume `useAuth` for the 4.12 admin gate.
    - Callbacks passed between the page and child Client Components are fine; the serializable-props rule only constrains Server→Client props (`use-client.md` L50).
    - `(dashboard)` is a route group: the parentheses are organizational and are NOT part of the URL (`route-groups.md` L10–L12), so the route stays `/catalogo`.
    - The page takes no `params`/`searchParams`, so the v15+ promise-prop change (`page.md` L239) does not apply; if a Client page ever needs `searchParams`, it must read it with React's `use()` (`page.md` L205).
  - Read-only: no application source was modified.

## Phase 1: API Client Contract Extensions (PR 1 — Slice 7a)

Requirement coverage: *API Client Contract Extensions*.

- [x] 1.1 (RED) Extend `frontend/src/lib/__tests__/api.test.ts` with failing cases: a 422 JSON body `{ message, errors: { name: ["The name has already been taken."] } }` rejects with an error exposing `status === 422` and `errors` as `Record<string, string[]>`; a 409 body rejects with `status === 409` and the server `message`; the existing `rejects.toThrow('Unauthorized')` case still passes
- [x] 1.2 (GREEN) Add an exported `ApiError extends Error` in `frontend/src/lib/api.ts` carrying `status: number` and optional `errors?: Record<string, string[]>`; throw it from `apiFetch` while preserving the current `message` text so existing callers and tests keep working
- [x] 1.3 (RED) Extend `frontend/src/modules/catalog/__tests__/api.test.ts` with failing cases: `updatePersonalItem('service', 'id', { parent_fork_id: 'cat-1' })` sends `parent_fork_id`; `updatePersonalItem('service', 'id', { title: null })` sends JSON `null` (not omitted); `createPersonalItem('categoria', { name: 'Cat', parent_fork_id: 'rubro-1' })` sends `parent_fork_id`
- [x] 1.4 (GREEN) Extend `CreatePersonalItemInput` in `frontend/src/modules/catalog/api.ts` to `string | null` for `name`/`title`/`description`, `number | null` for `value`, `string[] | null` for `tags`, keeping `parent_fork_id?: string | null`; add the `LaravelValidationError` and `LaravelConflictError` types
- [x] 1.5 (REFACTOR) Confirm no client under `frontend/src/modules/clients/**` breaks from the `ApiError` change (message text unchanged) and that `updatePersonalItem`/`createPersonalItem` bodies serialize explicit `null` correctly
- [x] 1.6 Run `cd frontend && npx jest src/modules/catalog/__tests__/api.test.ts src/lib/__tests__/api.test.ts` and `cd frontend && npm run lint` — both green

## Phase 2: Type-Aware Dialog, Tags, Changed-Fields-Only & Revert (PR 2 — Slice 7a)

Requirements coverage: *Type-Aware Catalog Item Dialog*, *Service Tags Editor*, *Changed-Fields-Only Submit Semantics*, *Revert-to-Base Controls*, *Server Error Surfacing* (dialog half).

- [x] 2.1 (RED) Create `frontend/src/modules/catalog/__tests__/catalog-item-dialog.test.tsx` with failing cases for the *Type-Aware Catalog Item Dialog* scenarios: create categoria under a rubro shows title "New category" with name + description and no value/tags; create service under a categoria shows "New service" with title + description + value + tags; edit service pre-fills title/description/value/tags; edit rubro and edit categoria show name + description only and title "Edit rubro"/"Edit category"
- [x] 2.2 (GREEN) Refactor `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` to accept the `type` and `createIntent` props from the design interface contract, render fields conditionally per type, and set the dialog title from type + mode; pre-fill edit values from the node
- [x] 2.3 (RED) Add failing *Service Tags Editor* cases to `frontend/src/modules/catalog/__tests__/catalog-item-dialog.test.tsx`: exactly five options (`frontend`, `backend`, `fullstack`, `devops`, `mobile`); `tags: ["frontend", "mobile"]` round-trips as selected; empty selection submits `tags: []` without blocking; selecting `frontend` + `devops` submits `tags: ["frontend", "devops"]`
- [x] 2.4 (GREEN) Add the constrained multi-select tags editor to `frontend/src/modules/catalog/components/catalog-item-dialog.tsx`, restricted to the allowed list, sending a JSON array (checkbox-based, no free-text entry)
- [x] 2.5 (RED) Add failing *Changed-Fields-Only Submit Semantics* cases: editing only `title` sends `{ title: "Brand Logo" }` and omits `value`/`description`; submitting with no changes sends an empty payload but still calls the API; changing title + adding a tag sends exactly those two fields; create mode sends all type-relevant fields plus `parent_fork_id`
- [x] 2.6 (GREEN) Implement the initial-values snapshot on dialog open and diff-on-submit in `frontend/src/modules/catalog/components/catalog-item-dialog.tsx`; keep create mode sending all type-relevant fields
- [x] 2.7 (RED) Add failing *Revert-to-Base Controls* cases: with `overridden_fields: ["title", "value"]` a revert control is rendered for title and value but not description; activating revert yields `{ title: null }` and displays the base/inheritance placeholder; a reverted-but-equal-to-base field still sends `null`; revert then manual edit sends the manual value (not `null`)
- [x] 2.8 (GREEN) Render per-field revert controls driven by the node's `overridden_fields` and make the submit diff treat an explicit revert as a changed field (`null`); visually distinguish revert from clearing the input
- [x] 2.9 (RED) Add failing *Server Error Surfacing* cases in the dialog: a 422 `errors` map renders the message under the matching field and keeps the dialog open; two 422 fields render simultaneously; a 409 `message` renders as a form-level error and keeps the dialog open; a 500 renders the generic message "The item could not be saved."
- [x] 2.10 (GREEN) Accept `validationErrors?: Record<string, string[]>` and `formError?: string` props in `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` and render them as field-level and form-level messages, with no hardcoded backend text
- [x] 2.11 (REFACTOR) Extract the field-diff and error-mapping helpers so the dialog stays small and readable; keep files focused per project conventions
- [x] 2.12 Run `cd frontend && npx jest src/modules/catalog/__tests__/catalog-item-dialog.test.tsx` and `cd frontend && npm run lint` — both green (recorded in the PR 2 batch: focused Jest 21/21, scoped lint clean); verify untouched fields keep their origin badge on reload (manual check on `/catalogo`) — user-verified on a non-admin session: type-aware dialog, allowed tags, changed-fields-only save, explicit-null revert, 422/409 error display, and reload persistence all passed

## Phase 3: Tree Child-Add Affordances, Lifecycle Confirmations & Page Wiring (PR 3 — Slice 7a)

Requirements coverage: *Tree Node Child-Add Affordances*, *Differentiated Lifecycle Confirmations*, plus the page-side wiring of *Server Error Surfacing*.

- [x] 3.1 (RED) Extend `frontend/src/modules/catalog/components/catalog-tree.test.tsx` with failing *Tree Node Child-Add Affordances* cases: a rubro node renders an "Add category" button; a categoria node renders an "Add service" button; a service node renders no add-child button; `onAddChild(parent, 'categoria')` fires with the rubro node and `onAddChild(parent, 'service')` with the categoria node
  - **RED observed**: 5 failed / 2 passed before implementation (missing `onAddChild` prop + missing buttons).
- [x] 3.2 (GREEN) Add the per-node "Add category" / "Add service" buttons and the `onAddChild` prop to `frontend/src/modules/catalog/components/catalog-tree.tsx`, rendering add buttons only for rubro and categoria nodes
  - **GREEN observed**: 7/7 focused tests pass. `childTypeFor(node)` derives the only legal child type (`rubro → categoria`, `categoria → service`, `service → null`), so a service node can never render an add button.
  - **REFACTOR**: the child type is computed once per node; the display-name helper was extracted to `catalog-node-label.ts` and reused by both the tree and the confirm dialog.
- [x] 3.3 (RED) Create `frontend/src/modules/catalog/__tests__/confirm-dialog.test.tsx` with failing *Differentiated Lifecycle Confirmations* cases: a node with children shows wording that mentions the descendants will be deactivated/permanently deleted; a leaf node shows wording without descendants; the item display name is shown; cancel does not call `onConfirm`; confirm calls `onConfirm`; the dialog is a shadcn `Dialog` and no `window.confirm` is called
  - **RED observed**: suite failed to run — `Cannot find module '../components/confirm-dialog'`.
- [x] 3.4 (GREEN) Create `frontend/src/modules/catalog/components/confirm-dialog.tsx` per the design's `ConfirmDialogProps`, computing descendant-aware wording from `item.children` and rendering the item display name (mirror the existing `frontend/src/modules/clients/components/delete-client-dialog.tsx` pattern)
  - **GREEN observed**: 8/8 focused tests pass, including the `window.confirm` spy assertion (never called) and `role="dialog"` presence.
- [x] 3.5 (RED) Create `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx` with failing page-level cases: clicking "Add category" on a rubro opens the dialog in create mode for `categoria` and submitting calls `createPersonalItem('categoria', { ..., parent_fork_id })`; clicking "Add service" does the same for `service`; delete of a node with children opens the confirm dialog (not `window.confirm`) and only confirms after the user approves; cancel makes no API call; confirm calls `deletePersonalItem` and reloads
  - **RED observed**: 7 failed / 1 passed before the page wiring (no dialog opened, no confirm dialog rendered, no `onAddChild` handler).
- [x] 3.6 (GREEN) Wire `frontend/src/app/(dashboard)/catalogo/page.tsx`: add `createIntent: { type, parentForkId } | null` state, connect `onAddChild`, route create vs edit through `createPersonalItem`/`updatePersonalItem` using the intent type (replacing the hardcoded `'rubro'`), and replace `window.confirm` with `ConfirmDialog` for both delete and deactivate
  - **GREEN observed**: page suite 8/8. Delete and deactivate both open `ConfirmDialog`; reactivation (a non-cascading action) proceeds without a confirmation. `window.confirm` is gone from the catalog surface.
- [x] 3.7 (GREEN) Map `ApiError` in the page's save/mutation handlers: pass 422 `errors` as `validationErrors` and 409/generic messages as `formError` to `CatalogItemDialog`; keep the dialog open on validation failure
  - **GREEN observed**: the 422 page-level case asserts the field message renders, the dialog title is still present, and no global `role="alert"` banner appears. `toDialogErrors` maps 422 → field map, 409 → server message, anything else → "The item could not be saved."; the rejected promise is re-thrown so the dialog does not close.
- [x] 3.8 Verify no `window.confirm` remains in `frontend/src/modules/catalog/**` and run `cd frontend && npx jest src/modules/catalog/components/catalog-tree.test.tsx src/modules/catalog/__tests__/confirm-dialog.test.tsx src/modules/catalog/__tests__/catalog-page.test.tsx` plus `cd frontend && npm run lint` — all green
  - **Verified**: `grep -rn "window.confirm" frontend/src` returns only the spy assertion inside `confirm-dialog.test.tsx`; no production usage anywhere in `frontend/src`.
  - **Focused Jest**: 3 suites / 23 tests passed.
  - **Lint (scoped, the Slice 7 gate per `design.md` Open Questions)**: `npx eslint "src/modules/catalog/**/*.{ts,tsx}" "src/app/(dashboard)/catalogo/page.tsx"` → exit 0, clean.
  - **Full lint is NOT green**: `npm run lint` reports 7 pre-existing problems in files untouched by this change (`dashboard/page.tsx`, `components/ui/sidebar.tsx`, `context/AuthContext.tsx`, `hooks/use-mobile.ts`, `modules/clients/components/delete-client-dialog.tsx`, `modules/companies/components/delete-company-dialog.tsx`). Full lint-debt cleanup is explicitly out of Slice 7 scope; no new problem was introduced.
- [x] 3.9 **Slice 7a exit gate**: run `cd frontend && npm test` (full Jest suite green) and confirm the backend suites are untouched: `docker compose exec backend php artisan test` and `./test-pg.sh` hold the ≥231-test baseline; `git diff --stat -- backend` is empty
  - **Frontend**: `npm test` → 10 suites / 87 tests passed (baseline after PR 2 was 8 suites / 65 tests; +2 suites / +22 tests, all catalog).
  - **Backend**: `docker compose exec backend php artisan test` → 235 passed (885 assertions); `./test-pg.sh` → OK (235 tests, 885 assertions). Baseline ≥231 held on both.
  - **Zero backend diff**: `git diff --stat -- backend` is empty.
  - **Type check**: `npx tsc --noEmit` reports one pre-existing error in the generated `.next/dev/types/validator.ts` (stale reference to `src/app/page.js`); no error in any file touched by this batch.

## Phase 4: Move-Service, Orphan-Attach, Override Display & Admin Gating (PR 4 — Slice 7b)

Requirements coverage: *Move-Service Flow*, *Orphan-Attach Flow*, *Per-Field Override Display*, *Admin Role-Coherent Rendering*.

- [x] 4.1 (RED) Add failing *Move-Service Flow* cases to `frontend/src/modules/catalog/__tests__/catalog-item-dialog.test.tsx`: editing a service shows a destination categoria selector listing the user's categoria forks and excluding the current parent; selecting another categoria sends `parent_fork_id`; leaving the selector unchanged omits `parent_fork_id`; a 422 on move surfaces the server message
  - **RED observed**: 3 failed / 22 passed (no destination selector: `getByLabelText('Destination category')` and the 422 `parent_fork_id` message both missing). The "unchanged omits `parent_fork_id`" guard already passed pre-implementation and is retained as a regression guard.
- [x] 4.2 (GREEN) Add the destination categoria selector to `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` (fed by the `tree` prop), sending `parent_fork_id` only when the destination changes
  - **GREEN observed**: dialog suite 25/25. The selector renders only for a service edit that has at least one other categoria; `buildCatalogInput` now appends `parent_fork_id` in edit mode only when a destination was picked.
  - **REFACTOR**: type-coherent candidate resolution extracted to `catalog-node-candidates.ts` (`parentTypeFor`, `collectNodesByType`, `parentCandidatesFor`), reused by the move selector and the attach dialog.
- [x] 4.3 (RED) Create `frontend/src/modules/catalog/__tests__/attach-dialog.test.tsx` with failing *Orphan-Attach Flow* cases: an orphan categoria lists rubro forks as candidates; an orphan service lists categoria forks; confirming calls `onAttach(parentForkId)`; cancel does not
  - **RED observed**: suite failed to run — `Cannot find module '../components/attach-dialog'`.
- [x] 4.4 (GREEN) Create `frontend/src/modules/catalog/components/attach-dialog.tsx` per the design's `AttachDialogProps`, resolving type-coherent parent candidates from the tree
  - **GREEN observed**: 4/4 focused tests pass. Rubro candidates for an orphan categoria, categoria candidates for an orphan service, confirm calls `onAttach(parentForkId)`, cancel calls `onCancel` and never `onAttach`. The dialog also accepts an optional `error` prop so a rejected attach stays visible in context.
- [x] 4.5 (RED) Extend `frontend/src/modules/catalog/components/catalog-tree.test.tsx` with failing cases: a node with `parent_fork_id: null` renders an unattached/orphan indicator and an "Attach" action; a node with a parent renders neither; `onAttach(node)` fires on click
  - **RED observed**: 4 failed / 10 passed across the orphan + override cases (missing indicator, action, and per-field badges).
- [x] 4.6 (GREEN) Add the orphan indicator and "Attach" action to `frontend/src/modules/catalog/components/catalog-tree.tsx` with the `onAttach` prop
  - **GREEN observed**: tree suite 14/14. `isOrphan(node)` is `item_type !== 'rubro' && parent_fork_id === null`, so a root rubro (legitimately parentless) is never flagged; the guard test covers it.
- [x] 4.7 (RED) Add failing page-level cases to `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx`: attach on an orphan categoria calls `updatePersonalItem('categoria', <id>, { parent_fork_id: 'rubro-1' })` and reloads; move-service sends `parent_fork_id` and reloads with the node nested under the new categoria; attach failure surfaces the server 422 message
  - **RED observed**: 3 failed / 8 passed (no attach affordance wired, no move payload).
- [x] 4.8 (GREEN) Wire `onAttach` and the move payload in `frontend/src/app/(dashboard)/catalogo/page.tsx`, reusing the existing reload counter
  - **GREEN observed**: page suite 11/11. `attach()` calls `updatePersonalItem(orphan.item_type, orphan.id, { parent_fork_id })`, closes the dialog and reloads; a rejected attach keeps the dialog open with `toAttachError(error)`. The move rides the existing `save()` → `updatePersonalItem` path because the dialog now emits `parent_fork_id`.
- [x] 4.9 (RED) Add failing *Per-Field Override Display* cases to `frontend/src/modules/catalog/components/catalog-tree.test.tsx`: with `overridden_fields: ["title"]` the title renders an override indicator; with `overridden_fields: []` no per-field indicators render and the origin badge still reflects the overall origin; with `["title", "value", "tags"]` an indicator appears for each
  - **RED observed**: covered by the 4 failed / 10 passed run in 4.5.
- [x] 4.10 (GREEN) Extend `frontend/src/modules/catalog/components/catalog-badges.tsx` (and its node rendering usage) to render a per-field override indicator for each entry in `overridden_fields`, keeping the existing status/origin badges
  - **GREEN observed**: tree suite 14/14. Each overridden field renders a dashed outline badge (`Title override`, `Value override`, `Tags override`), distinct from the exact-text origin badge (`Base` / `Override` / `Personal`); `overridden_fields: []` renders none.
- [x] 4.11 (RED) Add failing *Admin Role-Coherent Rendering* cases to `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx`: with an `admin` user the Base Catalog tab renders base rubros, the "Select" fork button and "New personal rubro" button are absent; the My Catalog tab renders an informational message and no error banners; no edit/delete/add-child/status-toggle/move/attach buttons are visible; with a non-admin user the affordances are present
  - **RED observed**: 1 failed / 12 passed (admin still saw the fork/create affordances). The non-admin guard passed pre-implementation and is retained.
- [x] 4.12 (GREEN) Add the page-level role check in `frontend/src/app/(dashboard)/catalogo/page.tsx` using `useAuth` from `frontend/src/hooks/useAuth.ts`; hide personalization affordances for `admin` and render the informational My Catalog state (no backend or policy change)
  - **GREEN observed**: page suite 13/13. `isAdmin = user?.role === 'admin'` gates the base-tab fork button and "New personal rubro", and replaces the My Catalog filters + tree with an informational `EmptyState` ("Personalization is for non-admin users. The base catalog remains available to browse."). No backend or policy change.
  - **REFACTOR**: the My Catalog branch was re-indented inside its fragment for reviewability (formatting only, no behavior change).
- [x] 4.13 Run `cd frontend && npx jest src/modules/catalog/__tests__/attach-dialog.test.tsx src/modules/catalog/components/catalog-tree.test.tsx` and `cd frontend && npm run lint` — green
  - **Focused Jest**: 2 suites / 18 tests passed (exit 0).
  - **Lint (scoped, the Slice 7 gate per `design.md` Open Questions)**: `npx eslint "src/modules/catalog/**/*.{ts,tsx}" "src/app/(dashboard)/catalogo/page.tsx" jest.setup.ts` → exit 0, clean.
  - **Full lint is NOT green**: `npm run lint` reports the same 7 pre-existing problems in the same 6 files untouched by this change (`dashboard/page.tsx`, `components/ui/sidebar.tsx`, `context/AuthContext.tsx`, `hooks/use-mobile.ts`, `modules/clients/components/delete-client-dialog.tsx`, `modules/companies/components/delete-company-dialog.tsx`). Full lint-debt cleanup is explicitly out of Slice 7 scope; no new problem was introduced.
  - **Supporting regression checks (not required by 4.13, run to confirm no regression)**: full frontend `npm test` → 11 suites / 107 tests passed; `npm run build` → compiled successfully, `/catalogo` prerendered; `npx tsc --noEmit` → only the pre-existing generated `.next/dev/types/validator.ts` error; `git diff --stat -- backend` → empty.


## Phase 5: Page-Level Coverage & Epic Closure (PR 5 — Slice 7b)

Requirements coverage: *Page-Level Jest Coverage*, *HU Status Transition Documentation*.

- [x] 5.1 (RED) Close the remaining *Page-Level Jest Coverage* gaps in `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx`: full nested-hierarchy tree rendering, status/origin badge display, status and origin filter changes triggering a re-fetch with the right query, create rubro via Base Catalog fork, deactivate/reactivate flow, and the 409 form-level error case
  - **Coverage added**: 9 page-level cases — nested rubro → categoria → service hierarchy; effective status/origin/per-field override badges; status + origin filter re-fetch asserting the exact query object; Base Catalog fork; personal-rubro create; reactivate; 409 form-level error; page-level revert → explicit `null`; successful child-create closes the dialog and reloads.
  - **RED observation (honest)**: all 9 wrote first and were observed passing immediately (suite 13 → 22). They exercise behavior implemented in Phases 2–4, so no failing RED state was reproducible; recorded as guard cases, exactly as the earlier batches recorded pre-passing guards.
- [x] 5.2 (GREEN) Implement whatever page-level gaps 5.1 exposes in `frontend/src/app/(dashboard)/catalogo/page.tsx` and its components, then re-run the focused suite until green
  - **No implementation gap exposed**: 5.1's cases all pass against the existing page/components, so 5.2 required no production change. Focused suite `catalog-page.test.tsx` → **22/22 passed**.
- [x] 5.3 Walk the spec's scenario list in `openspec/changes/catalog-slice-7-frontend/specs/catalog-frontend-management/spec.md` (read-only) and confirm every scenario has a corresponding Jest assertion; document any intentional gap in the PR description rather than silently omitting it
  - **Audit**: every spec scenario maps to at least one assertion across the API/dialog/tree/confirm/attach/page suites (mapping table in `apply-progress.md`).
  - **Intentional non-Jest gaps (documented, no silent omission)**: the two *HU Status Transition Documentation* scenarios are docs/Notion-only (tasks 5.7/5.8); leaf-node confirmation wording and the orphan-*service* candidate list are asserted by the component suites (`confirm-dialog.test.tsx`, `attach-dialog.test.tsx`) rather than the page suite. No PR exists yet (see 5.9), so this audit lives in `apply-progress.md`.
- [x] 5.4 Run the full frontend gates: `cd frontend && npm test` and `cd frontend && npm run lint` — green; confirm no new full-suite failures and that the scoped catalog lint is clean
  - **Observed**: `npm test` → **11 suites / 116 tests passed** (baseline after PR 4: 11 / 107; +9 from this batch, all catalog). Scoped catalog lint → **exit 0, clean**.
  - **Full `npm run lint` is NOT green**: the same **7 pre-existing problems in the same 6 untouched files** (`dashboard/page.tsx`, `components/ui/sidebar.tsx`, `context/AuthContext.tsx`, `hooks/use-mobile.ts`, `modules/clients/components/delete-client-dialog.tsx`, `modules/companies/components/delete-company-dialog.tsx`). No catalog file, no new problem. `design.md` Open Questions scope this debt out and gate Slice 7 on the scoped catalog lint.
- [x] 5.5 Run the backend regression gates: `docker compose exec backend php artisan test` and `./test-pg.sh` — green at the ≥231-test baseline; confirm `git diff --stat -- backend` is empty (zero backend diff)
  - **Observed**: `php artisan test` → **235 passed (885 assertions)**; `./test-pg.sh` → **OK (235 tests, 885 assertions)**. Baseline ≥231 held on both. `git diff --stat -- backend` → **empty**.
- [x] 5.6 Sync the CodeGraph index (`codegraph sync && codegraph status`) so the closing PR reflects the final structure
  - **Observed**: `codegraph sync` → *Already up to date*; `codegraph status` → *✓ Index is up to date*.
- [x] 5.7 Transition HU-013, HU-014, HU-015, HU-016, HU-017, HU-018, HU-019, HU-020, HU-021, HU-022, HU-023, and HU-025 to *En Revisión* in `docs/historias/`; leave HU-024 at its current status (already *En Revisión* since Slice 5) and leave stories requiring profile/public catalog or work orders untouched
  - **Done**: the 12 files now read `**Estado:** En Revisión`; HU-024 untouched (already En Revisión). `docs/historias/README.md` index rows updated for the 12 transitions; the stale HU-024 index row (said *Aprobada* while `HU-024.md` said *En Revisión*) was corrected to *En Revisión* so the docs are internally consistent. No profile/public-catalog or work-order stories were touched.
- [x] 5.8 Mirror the HU status transitions to Notion per AGENTS.md, keeping Notion exactly equal to `docs/historias/` (no invented content; Mermaid flows stay in the repo)
  - **Done**: the 12 HU pages in the Notion database *Historias de Usuario Portafolio de Clientes* were set to `En Revisión`; a re-query confirms **HU-013–HU-025 all `En Revisión`**, matching `docs/historias/`. HU-024 was already `En Revisión` and left as-is. No content was invented and no Mermaid flow was added to Notion.
- [ ] 5.9 Update the PR description with the manual verification evidence: non-admin owner flow (create categoria/service, tags, changed-fields-only, revert, move, attach) and admin `/catalogo` rendering with no 403 banners
  - **Pending — cannot complete in this phase**: no PR exists (nothing was committed or pushed, and remote GitHub mutations are prohibited in the apply phase), and the manual browser evidence for the non-admin owner flow and admin rendering was not supplied to this batch. Updating a PR description requires a PR; this must be completed when the PR is opened. No evidence was invented.

## Dependency Order

Phase 0 gates everything. Phase 1 (API client + error typing) is a hard prefix for Phase 2's error-display tasks and Phase 3's page-level error mapping. Phase 2 (dialog) must precede Phase 3, which wires the dialog into the page. Phase 4 extends the dialog and tree shipped in Phases 2–3. Phase 5 closes coverage and the epic after Phases 1–4 are green.

## Risks

- **Design gap — `frontend/src/lib/api.ts` is not in the design's File Changes table** but the *API Client Contract Extensions* requirement (field-level `errors` parsing) cannot be satisfied without it: today `apiFetch` throws `new Error(error.message)` and discards the 422 `errors` map and the HTTP status. Phase 1 therefore touches this shared file. The change is backward compatible (message preserved), but it is a cross-module edit that the design did not list — flag it in the PR and keep the existing `frontend/src/lib/__tests__/api.test.ts` assertions green.
- **Chain strategy undecided**: the forecast needs 5 PRs; the user must choose the chain strategy before apply (`ask-on-risk`).
- **Payload-shape regression (G3)**: changed-fields-only submit alters what the client sends; Phases 2 and 5 carry dedicated RED cases for touched/untouched/reverted combinations against the merged-overrides semantics.
- **Proposal precondition is stale**: `openspec/changes/catalog-slice-7-frontend/proposal.md` states Slice 6 is entirely uncommitted, but `git log` shows `77f1165 feat(catalog): add initial catalog frontend` on `main` and `git status --porcelain` shows only untracked SDD artifacts plus registry caches. Phase 0 records the real state instead of blocking on a resolved precondition.
- **7a shipped without 7b leaves orphan dead-ends (G6)**: the attach repair path lives in PR 4; both slices must ship in the same epic close.
- **Notion mirror drift**: Phase 5.8 keeps the docs and Notion statuses identical.

# Tasks: Catalog Slice 6 — Frontend Catalog Surface & Authenticated Base Read

> **As-built traceability record.** Slice 6 was implemented and verified in the
> working tree before this SDD trail was opened (`odd/tasks/catalog-slice-6-frontend.md`,
> T1–T4 complete; session handoff in Engram #855/#857/#858). Every task below is
> marked `[x]` because the delivered artifact exists and the recorded verification
> already passed — this is not authorization for new work. No source, test, config,
> or ODD file is an edit target for this change: every source path is annotated
> `(read-only)` deliberately. Slice 7 owns gaps G1–G8 and must not be pulled in here.

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ≈373 authored lines for the slice: ≈323 code/tests (12 modified + 311 new) + ≈50 ODD record. The SDD closeout markdown (proposal/specs/design/tasks, ≈960 lines) is documentation, not implementation review surface |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single reviewable commit/PR (proposal: "authored additions ≈ 330 lines fit the 400-line budget, so a single commit/PR is acceptable") |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

**Why "No" on the apply decision.** This is a retroactive closeout: the
implementation and verification already happened before the proposal existed, so
there is no pending `sdd-apply` work left to authorize. The remaining gates are
verification and archive. The slice commit itself is still outstanding by design
(the change stays uncommitted until a later explicit commit step).

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Authenticated base read + typed catalog client + `/catalogo` Base/My Catalog surface, with the authorization guard pinned by tests | PR 1 (single unit) | `cd frontend && npm test` then `docker compose exec backend php artisan test` | Pending — non-admin manual `/catalogo` browse/fork review has NOT been performed (proposal Known Limitations); recorded as an open item, not a fabricated pass | Single `git revert` of the slice commit: route line returns to the `role:admin` group, `src/modules/catalog/`, `catalogo/` page, sidebar entry, and the new tests are removed. No migrations, no data writes, no dependency changes |

Threat matrix: **N/A** — design.md records no routing/shell/subprocess/VCS/executable-classification/process-integration boundary in Slice 6. No threat-matrix RED tests are owed. The one negative-authorization boundary that matters here (admin-only base mutations) is covered by `BaseCatalogReadAccessTest`, not by a threat-matrix row.

TDD note: `openspec/config.yaml` sets `apply.tdd: true` (STRICT TDD). Test artifacts were delivered alongside their implementation as recorded in the ODD progress notes and handoff; the working tree alone does not independently prove per-task RED-before-GREEN chronology, so tasks are listed test-first but no fabricated RED evidence is claimed.

## Phase 1: Backend — Authenticated Base Read, Admin Mutations Preserved (T1)

- [x] 1.1 Added `backend/tests/Feature/BaseCatalogReadAccessTest.php` (read-only) — normal user reads active base rubros (200, active-only default), admin read parity, and unauthenticated request → 401. Evidence: 33-line test file present in the working tree; covers `base-catalog-read-access` scenarios "Normal user reads active base rubros", "Admin user reads base rubros", "Unauthenticated user cannot read base rubros".
- [x] 1.2 Added the mutation-forbidden negative coverage in `backend/tests/Feature/BaseCatalogReadAccessTest.php` (read-only) — non-admin `store`/`update`/`destroy`/`deactivate`/`reactivate` → 403, and the `?status=all` / default active-filter scenarios. Evidence: satisfies `base-catalog-read-access` requirements "Admin-Only Base Mutations Preserved" and "Default Active Filter".
- [x] 1.3 Widened the stale expectation in `backend/tests/Feature/RubroApiTest.php` (read-only) — `test_regular_user_is_forbidden` renamed to `test_regular_user_can_read_base_rubros`, assertion changed from 403 to `assertOk()`. Evidence: diff `-403 / +assertOk()`.
- [x] 1.4 Moved the base read route in `backend/routes/api.php` (read-only) — `Route::get('/rubros', [RubroController::class, 'index'])` now sits in the `auth:sanctum` scope ahead of the admin group, reusing the controller's existing default-active filter. Evidence: 5-line insertion with explanatory comment.
- [x] 1.5 Narrowed the admin group in `backend/routes/api.php` (read-only) — `apiResource('rubros', ...)` reduced to `only(['store', 'update', 'destroy'])`, leaving `GET /rubros/{rubro}/categorias`, `deactivate`, and `reactivate` inside `role:admin` so child listing stays admin-only. Evidence: 1-line modification; satisfies "Admin-Only Base Child-Listing Routes".

## Phase 2: Frontend Foundation — Typed API Client & Protected Route (T2)

- [x] 2.1 Added `frontend/src/modules/catalog/__tests__/api.test.ts` (read-only) — filter serialization (`/api/user-catalog/tree?status=desactivado&origin=override`), empty-filter omission, and the fork route contract (`/api/user-catalog/rubros/base-id/fork`, `POST`). Evidence: 27-line Jest suite present.
- [x] 2.2 Added `frontend/src/modules/catalog/api.ts` (read-only) — `BaseRubro`/`CatalogNode`/`CatalogFilters` types plus `getBaseRubros`, `getUserCatalogTree`, `forkBaseItem`, `createPersonalItem`, `updatePersonalItem`, `updatePersonalItemStatus`, `deletePersonalItem`, all over the existing `apiFetch` CSRF client; `getBaseRubros()` calls `/api/rubros?status=activo`. Evidence: 90-line module implementing the `Typed Catalog API Client` contract.
- [x] 2.3 Added the protected page `frontend/src/app/(dashboard)/catalogo/page.tsx` (read-only) inside the authenticated dashboard shell — "Catalog" header, "Base Catalog" / "My Catalog" tabs, status/origin filter selects, refresh counter, and error banners. Evidence: 36-line page; satisfies `Protected Catalog Route`.
- [x] 2.4 Added the sidebar entry in `frontend/src/components/shared/app-sidebar.tsx` (read-only) — `{ label: 'Catalog', href: '/catalogo', icon: FolderTree }` plus the `FolderTree` import. Evidence: 2-line insertion.

## Phase 3: Catalog Views & Lifecycle (T3)

- [x] 3.1 Added `frontend/src/modules/catalog/components/catalog-badges.tsx` (read-only) — `StatusBadge` (Active/secondary, Inactive/destructive) and `OriginBadge` (Base/Override/Personal, outline). Evidence: 15-line component matching the effective status/origin badge scenarios.
- [x] 3.2 Added `frontend/src/modules/catalog/components/catalog-tree.tsx` (read-only) — recursive rubro → category → service rendering with per-node actions; renders server-provided nodes directly so filtered descendants render without their ancestors. Evidence: 50-line component; satisfies "My Catalog View with Effective Tree" including the no-ancestor-assumption scenario.
- [x] 3.3 Added `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` (read-only) — create/edit modes, per-type fields (rubro/category: name+description; service: title+description+value), save disabled while saving. Evidence: 35-line dialog; satisfies `Catalog Item Dialog`.
- [x] 3.4 Wired the Base Catalog tab in `frontend/src/app/(dashboard)/catalogo/page.tsx` (read-only) — rubro cards with "Base" badge and description, "Select" → `forkBaseItem('rubro', id)` with My Catalog reload on success, "New personal rubro" opening the dialog, plus skeleton / empty ("No base rubros are available.") / error banner ("The base catalog could not be loaded."). Evidence: satisfies `Base Catalog View` and the Base Catalog loading/empty/error scenarios.
- [x] 3.5 Wired the My Catalog tab in `frontend/src/app/(dashboard)/catalogo/page.tsx` (read-only) — server-side status/origin filters that omit `all`, per-node edit / activate-deactivate / delete lifecycle actions over the user-catalog endpoints with `window.confirm` on delete, refresh-counter reload, and the "Your catalog could not be loaded." error banner. Evidence: satisfies `Server-Side Status and Origin Filters`, `Personal Rubro Lifecycle`, and the My Catalog loading/error scenarios.

## Phase 4: Tests & Verification (T4)

- [x] 4.1 Added `frontend/src/modules/catalog/components/catalog-tree.test.tsx` (read-only) — descendant rendering with a rubro containing a service child, and effective status ("Inactive") / origin ("Override", "Base") badge assertions. Evidence: 25-line Jest suite; satisfies `Focused Jest Tests`.
- [x] 4.2 Ran `cd frontend && npm test` → passed: 7 suites / 35 tests.
- [x] 4.3 Ran the Next production build `npm run build` → passed.
- [x] 4.4 Ran the backend suite `docker compose exec backend php artisan test` → passed: 235 tests / 885 assertions.
- [x] 4.5 Ran focused ESLint scoped to the catalog files → clean. The full `npm run lint` still reports pre-existing failures in files unrelated to Slice 6 (dashboard/sidebar/AuthContext/mobile/delete-dialog); none introduced, none fixed here — carried honestly.
- [x] 4.6 Ran `codegraph sync && codegraph status` → passed.
- [x] 4.7 Reviewed `git diff` for scope sanity → only the Slice 6 files plus the intentionally-dirty `.atl/*` and `opencode.jsonc`; no unrelated edits.

## Phase 5: Closeout Traceability

- [x] 5.1 ODD task record `odd/tasks/catalog-slice-6-frontend.md` (read-only) — T1–T4 checked with progress notes; "Next step" names the manual UX review, then Slice 7 planning. Evidence: 50-line record present.
- [x] 5.2 As-built SDD trail `openspec/changes/catalog-slice-6-frontend/proposal.md` (read-only), `openspec/changes/catalog-slice-6-frontend/design.md` (read-only), `openspec/changes/catalog-slice-6-frontend/specs/base-catalog-read-access/spec.md` (read-only), `openspec/changes/catalog-slice-6-frontend/specs/catalog-frontend/spec.md` (read-only) — records the delivered scope and the `GET /api/rubros` widening decision with no aspirational requirements.
- [x] 5.3 Known limitations carried into the record without fabrication: G1–G8 stay Slice 7's; the pre-existing full-lint failures stand documented; the non-admin manual `/catalogo` UX review remains pending.
- [x] 5.4 Prepared Slice 7's blocking precondition — this change folder still needs its commit + archive; both the slice commit and the archive remain outstanding and are NOT performed by this task artifact.

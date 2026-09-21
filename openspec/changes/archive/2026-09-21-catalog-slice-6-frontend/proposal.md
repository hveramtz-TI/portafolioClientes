# Proposal: Catalog Slice 6 — Frontend Catalog Surface & Authenticated Base Read (As-Built Closeout)

> **Status: as-built closeout, not authorization for new work.** Slice 6 was already
> implemented and verified in the working tree before this document existed
> (`odd/tasks/catalog-slice-6-frontend.md`, T1–T4 complete; session handoff recorded in
> Engram #855/#857/#858). This proposal retroactively captures the actual delivered
> scope, the `GET /api/rubros` authorization-widening spec-sync decision, and the known
> limitations, so the change can run through the spec/archive phases and close cleanly
> before Slice 7 branches. Creating unimplemented category/service management flows is
> explicitly out of scope: `openspec/changes/catalog-slice-7-frontend/proposal.md` owns
> gaps G1–G8 and names this closeout as its blocking precondition.

## Intent

planning3's catalog epic was backend-complete through Slice 5 (frozen contracts
`user-catalog-personalization` R1–R8, `user-catalog-fork-api` R1–R7, canonical
`base-catalog-seeding` R1–R8), yet the catalog was not user-operable: no frontend
surface existed, and every base-catalog read required `role:admin`, so a normal user
could not even browse base rubros to select/fork one (HU-025 entry path). Slice 6
delivers the first user-operable slice of the planning3 frontend scope: an authenticated
read-only base-rubro listing plus the protected `/catalogo` dashboard with Base Catalog
and My Catalog views, so users can browse, fork, and manage their personal rubros.

## Scope

### In Scope (delivered, as-built)

- **T1 — Authenticated read-only base read, admin-only mutations preserved.**
  `GET /api/rubros` moved out of the `role:admin` group to the authenticated scope
  (active rubros by default via the existing controller filter); `store`, `update`,
  `destroy`, `deactivate`, `reactivate` and child-listing routes stay admin-only.
  Backend coverage: `BaseCatalogReadAccessTest` (normal user reads active rubros;
  normal user mutation → 403) and the widened `RubroApiTest` expectation
  (`test_regular_user_can_read_base_rubros`).
- **T2 — Typed API client + protected `/catalogo` page.** `frontend/src/modules/catalog/api.ts`
  types `BaseRubro`/`CatalogNode` (effective `status`, `origin`, `overridden_fields`,
  nested `children`) and wraps base listing, filtered tree fetch, fork, personal
  create/update/status/delete through the existing `apiFetch` CSRF client. New route
  `frontend/src/app/(dashboard)/catalogo/page.tsx` inside the authenticated dashboard
  shell, with "Catalog" sidebar entry.
- **T3 — Base/My Catalog views.** Base Catalog tab: rubro cards with "Base" badge and
  Select (cascade-fork) action, loading/empty/error states, "New personal rubro".
  My Catalog tab: recursive rubro→category→service tree with effective status/origin
  badges, status + origin filters (server-side), and per-node edit / activate-deactivate /
  delete lifecycle actions over the user-catalog endpoints with a dialog for create/edit.
- **T4 — Focused tests + verification.** Jest suites for the typed API (filter
  serialization, fork route contract) and tree rendering (descendants + effective
  status/origin badges); Next production build; full backend suite; focused lint;
  CodeGraph sync/status.

### Out of Scope (Slice 7 owns these — do not add here)

- Personal `categoria`/`service` creation forms and the dialog's hardcoded `rubro`
  create type (G1); service `tags` editor (G2); changed-fields-only submit and
  revert-to-base controls (G3, current dialog submits all displayed fields);
  move/attach and orphan-root recovery flows (G4/G6); shadcn differentiated lifecycle
  confirmations replacing `window.confirm` (G5); page-level Jest suite (G7); admin-role
  view coherence on `/catalogo` (G8).
- User-readable base-children endpoints (`GET /rubros/{id}/categorias` stays
  admin-only; rubro cascade fork remains the supported discovery path).
- Any backend contract change beyond the single documented read-widening; orders,
  portfolio PDF, public profile consumption of the catalog.

## Capabilities

### New Capabilities

- `base-catalog-read-access`: authenticated non-admin users MAY read the base rubro
  listing (defaulting to active rows) while every base mutation, status transition, and
  child-listing route MUST remain admin-only. Pins the widening as explicit spec rather
  than route-file accident, with the 403 guard as a negative requirement.
- `catalog-frontend`: the protected `/catalogo` user-operable surface — Base Catalog
  browse/fork selection, My Catalog effective tree with status/origin badges and
  server-side filters, personal rubro create/edit/status/delete lifecycle, and
  loading/empty/error state rendering. Spec'd as page/component behavior scenarios;
  scoped to what Slice 6 actually ships (rubro-level personalization).

### Modified Capabilities

- None. No existing canonical requirement (R1–R8 in `user-catalog-personalization`,
  R1–R7 in `user-catalog-fork-api`, R1–R8 in `base-catalog-seeding`) changes. See the
  Spec-Sync Decision below for how the widening relates to the
  `user-catalog-fork-api` preamble.

## Spec-Sync Decision — `GET /api/rubros` authorization widening

The widening is a real, intentional contract change (T1 authorized it; D2 in planning3
restricts only base **mutations** to admin, never base reads). Its prior admin-only
state was never a numbered requirement in any canonical spec — it lived in the route
file and in the stale `RubroApiTest` 403 expectation. The one textual tension is the
`user-catalog-fork-api` overview line "Base-catalog surface unchanged", which is a
Slice-4 scope fence, not a standing authorization mandate. Decision:

1. Do **not** emit a MODIFIED delta against `user-catalog-fork-api`; its requirements
   are untouched.
2. Pin the new contract via the `base-catalog-read-access` spec (ADDED at archive):
   authenticated read + admin-only mutation guard, with the `BaseCatalogReadAccessTest`
   scenarios as the behavioral record.
3. The archive-phase spec sync must carry a one-line clarification in that new spec's
   context noting it supersedes the pre-Slice-6 admin-only-read status quo.
4. planning3 D2 already reads consistently with this as-built behavior; no planning
   rewrite required. `RubroApiTest` was updated in-code and is the traceability link.

## Approach (as-built)

Backend stays thin: a single `Route::get('/rubros', ...)` line moved ahead of the admin
group in `routes/api.php`, leaving `apiResource(...)->only(['store','update','destroy'])`
inside `role:admin` and reusing the existing controller's status filtering. Frontend
follows the established clients-module pattern: feature-local module
(`src/modules/catalog/`) with a typed function-per-endpoint API over `apiFetch`, plain
controlled state (no form library), shadcn/ui primitives (Tabs/Select/Dialog/Badge),
and the page composing Base and My Catalog tabs with independent fetch lifecycles and an
error banner. Tree rendering is recursive over the server-provided nested structure and
deliberately renders whatever the filtered tree returns — filtered results may promote
descendants without every ancestor, so no client-side ancestor assumption exists.
Lifecycle actions reload via a refresh counter. Verification followed project gates:
frontend Jest/build, backend PHPUnit, focused lint, CodeGraph sync.

## Verification Evidence (recorded from the implementation session — Engram #855/#857/#858)

| Gate | Command | Result |
|------|---------|--------|
| Frontend tests | `cd frontend && npm test` | ✅ 7 suites / 35 tests passed |
| Frontend build | `npm run build` (Next production) | ✅ passed |
| Backend suite | `docker compose exec backend php artisan test` | ✅ 235 tests / 885 assertions passed |
| Catalog lint (focused) | ESLint scoped to catalog files | ✅ clean |
| Full frontend lint | `npm run lint` | ⚠️ Pre-existing failures in files unrelated to Slice 6 (dashboard/sidebar/AuthContext/mobile/delete-dialog); none introduced by this slice, none fixed here |
| CodeGraph | `codegraph sync && codegraph status` | ✅ passed |
| Diff sanity | git diff review | ✅ only Slice 6 files + intentionally-dirty `.atl/*`, `opencode.jsonc` |

## Affected Areas (as-delivered; all currently uncommitted in the working tree)

| Area | Impact | Description |
|------|--------|-------------|
| `backend/routes/api.php` | Modified | `GET /api/rubros` moved to authenticated scope; admin `apiResource` narrowed to mutations |
| `backend/tests/Feature/BaseCatalogReadAccessTest.php` | New | Read-allowed / mutation-forbidden authorization tests |
| `backend/tests/Feature/RubroApiTest.php` | Modified | Stale 403-on-read expectation widened to 200 for normal users |
| `frontend/src/app/(dashboard)/catalogo/page.tsx` | New | Protected Base/My Catalog page, filters, lifecycle orchestration |
| `frontend/src/modules/catalog/api.ts` | New | Typed catalog API client (base list, tree+filters, fork, personal CRUD/status/delete) |
| `frontend/src/modules/catalog/components/` | New | `catalog-tree`, `catalog-badges`, `catalog-item-dialog` + tree Jest test |
| `frontend/src/modules/catalog/__tests__/api.test.ts` | New | Jest contract tests for filter serialization and fork route |
| `frontend/src/components/shared/app-sidebar.tsx` | Modified | "Catalog" navigation entry |
| `odd/tasks/catalog-slice-6-frontend.md` | New | ODD task record; T1–T4 checked with progress notes |
| `openspec/changes/catalog-slice-6-frontend/` | New | This closeout trail (proposal → specs → archive) |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Whole Slice 6 diff (incl. `odd/`) is still uncommitted; Slice 7 branching could mix diffs and orphan the auth widening | High | Commit/close Slice 6 as its own reviewable unit **before** any 7a branch (Slice 7 proposal lists this as blocking precondition); authored additions ≈ 330 lines fit the 400-line budget, so a single commit/PR is acceptable |
| Retroactive doc drift: any pre-commit tweak to Slice 6 files silently invalidates this as-built record | Med | Treat this proposal as frozen-at-archive; if the tree changes before commit, update the Scope/Affected tables in this file during the spec phase |
| Widening misread as weakening base authorization | Low | `BaseCatalogReadAccessTest` mutation 403 guard + `base-catalog-read-access` spec make the boundary explicit |
| Pre-existing full-lint debt masks a new catalog lint failure in future runs | Med | Focused catalog lint baseline is clean and recorded above; Slice 7 gates on the same scoped check plus the full-suite delta |
| Known UI defects (G1–G8) mistaken for Slice 6 acceptance failures | Med | They are out-of-scope by decision (Slice 7 owns them) and mirrored in the Slice 7 proposal; the known limitations stand as documented, not silently carried |

## Rollback Plan

This artifact is docs-only; rollback of the proposal itself is removing the change
folder / Engram mirror. For the underlying slice (pre-integration, working tree):

1. **Code**: revert the single Slice 6 commit (routes line, tests, `src/modules/catalog/`,
   `catalogo/` page, sidebar entry). No migrations, no data writes, no dependency
   changes — revert is clean and restores admin-only base reads.
2. **Contract**: if only the widening must be rolled back while keeping the UI, move
   `GET /api/rubros` back into the `role:admin` group and restore the 403 expectation in
   `RubroApiTest` (the Base Catalog tab then degrades to admin-visible; documented as a
   known-affected view, fail-fast error banner already in place).
3. **Spec**: never archive an unverified sync — if archive surfaced a contradiction,
   un-archive per `sdd-archive` guidance and reopen the spec phase.

## Dependencies

- `frontend/src/lib/api.ts` CSRF client (pre-existing) and the frozen `/api/user-catalog/*`
  contract consumed as-is.
- Dashboard auth shell + sidebar (`app-layout`) for route protection.
- Non-admin dev account for the owner-path manual UX review still pending.
- Slice 7 closeout depends on **this** change committing and archiving first (precondition
  stated in its proposal).

## Known Limitations (accepted, documented — not defects of this closeout)

- Create dialog is rubro-scoped; category/service authoring, tags editing, move/attach,
  override-revert controls, and dialog-based lifecycle confirmations ship in Slice 7.
- Editing any field submits all displayed fields, which can silently turn `base`-origin
  values into `override` (Slice 7 G3 fix).
- `window.confirm` is used for deletion instead of planning3's differentiated dialog
  wording (Slice 7 G5).
- Admin users on `/catalogo` can hit 403 banners on personalization actions (Slice 7 G8).
- Full frontend lint carries pre-existing unrelated failures (see Verification table).
- Manual browser UX review of `/catalogo` has not been performed yet.

## Success Criteria

- [ ] This proposal exists at `openspec/changes/catalog-slice-6-frontend/proposal.md` and is mirrored in Engram (`sdd/catalog-slice-6-frontend/proposal`); no source file is touched by this change.
- [ ] Spec phase produces `base-catalog-read-access` and `catalog-frontend` specs matching the as-built behavior exactly (no aspirational requirements).
- [ ] The `GET /api/rubros` widening is pinned by spec + tests with the admin-mutation guard explicit (Spec-Sync Decision executed at archive).
- [ ] Slice 6 working-tree changes are committed as one reviewable unit within the 400-line budget, traceable to this change folder and the ODD task record.
- [ ] Archive syncs both new capabilities into `openspec/specs/` and the change folder moves to `openspec/changes/archive/`, satisfying Slice 7's precondition.
- [ ] Known limitations and the full-lint pre-existing warning are carried honestly into the archived record (no fabricated green).

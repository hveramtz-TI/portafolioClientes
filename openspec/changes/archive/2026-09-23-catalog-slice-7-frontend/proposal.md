# Proposal: Catalog Slice 7 — Frontend Category/Service Management & HU Closure

## Intent

Slice 6 shipped the first operable catalog surface (`/catalogo`), but it only manages **rubros** at the personal level: the create dialog hardcodes `rubro`, categories and services cannot be created or fully edited, and three correctness gaps make the surface misleading in daily use:

- **G1** — no way to create a personal `categoria` under a rubro fork or a `service` under a categoria fork (HU-017, HU-021 blocked in UI).
- **G2** — no `tags` editor for services (HU-021, D4).
- **G3** — every edit submits all displayed fields, silently converting untouched `base`-origin forks into `override`; there is no revert-a-field-to-base control (D13, HU-025).
- **G4/G6** — no move-service or orphan-attach flows, so standalone categoria/service forks (born with `parent_fork_id = null` via cascade gaps) are a user-facing dead-end rendered `desactivado` (HU-022, D8, R5).
- **G5** — delete confirmation is a raw `window.confirm`; planning3 mandates differentiated dialog confirmations ("desactivará el elemento y sus hijos" vs "se eliminará permanentemente").
- **G7/G8** — no page-level Jest coverage; admins on `/catalogo` hit policy-denied 403 banners instead of a coherent view.

The backend already implements every contract Slice 7 needs (`user-catalog-fork-api` R1–R7, `user-catalog-personalization` R1–R8, frozen). This change closes the gap between the as-built UI and planning3's frontend scope so HU-013–HU-025 can transition to *En Revisión* as end-to-end user-operable stories. The catalog is treated as **user-owner functionality**: no admin permission expansion.

## Scope

### In Scope

**Slice 7a — Category/Service CRUD forms (chained PR #1):**
- Type-aware `CatalogItemDialog`: per-type fields (rubro name/description; categoria name/description; service title/description/value/tags), destination-parent selector, tags editor constrained to `frontend|backend|fullstack|devops|mobile` (G1, G2).
- Changed-fields-only submit semantics + explicit revert-to-base (null-clear) controls per overridden field (G3, D13).
- Tree node affordances: "Add category" under rubro forks, "Add service" under categoria forks (G1).
- Differentiated lifecycle confirmations via shadcn `Dialog` using tree data (descendants/relations wording) (G5).
- Laravel 422 `errors` map and 409 surfacing as field-level and form-level messages (G4 prerequisite).
- Strict-TDD Jest tests per flow (RED→GREEN), `npm test` + `npm run lint` green.

**Slice 7b — Move/attach + closure polish (chained PR #2):**
- Move-service flow (destination categoria picker via `parent_fork_id`, R5/D8) and orphan-attach flow for fork roots (G4, G6).
- Per-field override display driven by `overridden_fields` already present on tree nodes (G3 visibility, D13 badges).
- Page-level Jest suite: filters, create/edit/deactivate flows per planning3 "Tests Jest por página/componente" (G7).
- Admin view coherence (G8): role-gate personalization affordances on `/catalogo` (design decision to be resolved in sdd-design; no policy/backend change, no admin expansion).
- HU-013–HU-025 status transitions to *En Revisión* (where end-to-end supported) + docs/Notion mirror per AGENTS.md.

**Precondition (not a deliverable of this change):** Slice 6 must be committed/closed on `main` first — the working tree currently holds all Slice 6 files uncommitted (see Risks).

### Out of Scope

- Any backend contract change or new endpoints (expected 0-diff on `backend/`), including user-readable base-children endpoints (`GET /rubros/{id}/categorias` stays admin-only; rubro cascade remains the supported discovery path — G6, do not invent).
- Admin/seeded management of the base catalog beyond what Slice 6 already exposes.
- Base-catalog mutation UI for non-admin users (D2 forbids it).
- Admin permission expansion of any kind.
- Profile/public-facing catalog consumption and work orders (later epics).
- Rate limiting, status-audit logging, Redis cache warmup (planning3 open notes, deferred to design or later).

## Capabilities

### New Capabilities

- `catalog-frontend-management`: the `/catalogo` user-owner management surface — type-aware fork CRUD forms (rubro/categoria/service), tags editing, changed-fields-only override semantics with revert-to-base, per-node child-add affordances, move/attach flows with orphan recovery, differentiated lifecycle confirmations, server-error surfacing, and role-coherent admin rendering. Spec'd as behavior scenarios at the page/component level.

### Modified Capabilities

- None. `user-catalog-fork-api` (R1–R7) and `user-catalog-personalization` (R1–R8) are consumed as frozen contracts; no requirement at the spec level changes. `base-catalog-seeding` is untouched. Slice 6's uncommitted `GET /rubros` auth widening belongs to Slice 6's own close-out (see Risks), not to this change.

## Approach

Adopt the exploration's **Option 1**: evolve the existing single page and one dialog rather than splitting per-type screens. The tree UX ("árbol fork rubro → categorías → servicios") is what planning3 defines, the clients module already establishes the pattern this follows (plain controlled forms, manual validation, no form library, shadcn/ui dialogs), and option 1 keeps zero new routes and the smallest diff.

Key mechanics:

- **Type-aware dialog** driven by the create-intent context (which node "Add category"/"Add service" was pressed) or the node type being edited; `api.ts` extends `updatePersonalItem` typing for `parent_fork_id` and explicit-null override clearing, plus a typed Laravel 422 `errors` map.
- **Changed-fields-only submit**: dialog tracks initial resolved values per field; only fields the user touched (or explicitly reverted → `null`) are sent. This aligns the client with the backend's already-correct merged-overrides semantics (omitted = untouched, null = clear) and is the payload-shape fix that stops override inflation.
- **Move/attach** reuses the R5 update path (`PUT {parent_fork_id}`) with destination-scoped uniqueness already enforced backend-side (JD4-2); orphan roots get an explicit "Attach" action in the tree.
- **Lifecycle confirmations** computed client-side from tree `children` data; wording follows planning3's differentiated rule.
- **Delivery as 7a → 7b chained PRs** under the 400-line review budget (`ask-on-risk`), 7a targeting the clean branch base after Slice 6 is committed.
- **Strict TDD**: every flow gets a failing Jest test first (`cd frontend && npm test`); backend suites (`php artisan test`, `./test-pg.sh`) act as regression gates that must stay green at the 231-test baseline. Next.js 16 App Router docs re-checked before writing page code per project rules.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `frontend/src/app/(dashboard)/catalogo/page.tsx` | Modified | Type-aware create intents, 422/409 error surfacing, orphan/attach affordances, admin role-gating render (7b) |
| `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` | Modified | Type-aware fields, tags editor, destination-category selector, revert-to-base controls, changed-fields-only submit |
| `frontend/src/modules/catalog/components/catalog-tree.tsx` | Modified | Per-node "Add category"/"Add service", orphan indicators + attach action, per-field override display |
| `frontend/src/modules/catalog/components/catalog-badges.tsx` | Modified | Override/origin badge refinement from `overridden_fields` |
| `frontend/src/modules/catalog/api.ts` | Modified | `parent_fork_id` + explicit-null typing, Laravel 422 error payload typing |
| `frontend/src/modules/catalog/**/__tests__/` | New/Modified | RED-first Jest suites per flow (7a) + page-level suite (7b) |
| `docs/historias/HU-013..HU-025*.md` + Notion mirror | Modified | Status transitions to *En Revisión* at epic close (7b) |
| `backend/**` | None | Expected 0-diff; frozen R1–R8 contracts consumed as-is |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| **Dirty worktree**: Slice 6 is entirely uncommitted on `main` (catalog page/module, `routes/api.php` read-access widening, tests) and its SDD close-out is missing — Slice 7 branching from this tree risks mixed diffs and an untraceable `GET /rubros` auth change | High | **Blocking precondition**: commit/close Slice 6 (including its spec-sync decision for the base-read widening) before any 7a branch is created. This change never modifies Slice 6 source |
| **Orphan dead-end (G6)**: if 7a ships without 7b, users can still create unattached fork roots stuck `desactivado` | Med | 7a and 7b ship together within the epic close; 7b's attach flow is the repair path |
| **Payload-shape regression (G3)**: changed-fields-only submit alters what the client sends; a bug could silently drop legitimate overrides | Med | Dedicated RED tests for touched/untouched/reverted field combinations against PUT merged-overrides semantics |
| **Review budget**: G1–G8 UI + tests will likely exceed 400 authored lines combined | High | Chained 7a/7b PR slices per `ask-on-risk`; sdd-tasks must forecast and confirm the split |
| **Admin experience (G8)**: unaddressed, admins on `/catalogo` see 403 error banners on personalization actions | Med | Unresolved design decision surfaced to sdd-design — role-gate affordances; explicitly no admin permission expansion. No backend policy change |
| **Notion mirror drift**: HU transitions mandated by AGENTS.md could be forgotten | Low | Mirror task bundled into 7b close-out checklist |

## Rollback Plan

All work is frontend-only on feature branches chained 7b→7a→clean `main`:

1. **Per-slice revert**: each PR (7a, 7b) is independently revertable (`git revert` of the merge commit); no migrations, no data writes, no backend diff to unwind.
2. **G3 payload semantics rollback**: the changed-fields-only submit degrades safely — the backend's merged-overrides semantics tolerate the legacy full-field payload (it just reproduces today's known override-inflation behavior, without corruption), so reverting the dialog logic alone restores the prior client without any API coordination.
3. **HU status rollback**: if the epic close is invalidated, revert the *En Revisión* transitions in `docs/historias/` and their Notion mirrors (docs-only change, last commit of 7b).
4. Slice 6's own commits are untouched by this change, so a full Slice 7 revert lands cleanly on top of them.

## Dependencies

- **Slice 6 committed and closed on `main`** (precondition; includes deciding how its uncommitted `GET /rubros` auth widening and `BaseCatalogReadAccessTest` get spec-synced — that reconciliation belongs to Slice 6's close-out, not here).
- Frozen backend contracts: `openspec/specs/user-catalog-fork-api/spec.md` (R1–R7), `openspec/specs/user-catalog-personalization/spec.md` (R1–R8) — consumption only.
- Non-admin development account (exists; user already continued with it) for owner-flow validation.
- Existing frontend toolchain: Jest 30 + `npm run lint`; `frontend/src/lib/api.ts` CSRF client.

## Success Criteria

- [ ] A logged-in owner can create personal `categoria` items under rubro forks and `service` items under categoria forks from the UI (HU-017, HU-021) — covered by RED→GREEN Jest tests.
- [ ] Service tags editor round-trips values restricted to the allowed list; invalid combinations are impossible to submit (D4).
- [ ] Editing one field of a fork sends only that field: untouched fields keep their origin badge; revert-to-base sends explicit `null` and clears the override (G3, D13, HU-025).
- [ ] Move-service and orphan-attach flows work against R5 and surface backend 422 (cycle/coherence/uniqueness) and 409 errors as readable messages (HU-022, D8, G4/G6).
- [ ] Delete/deactivate confirmation dialogs differentiate "descendants will be deactivated" vs "permanently removed" per planning3 (G5).
- [ ] `cd frontend && npm test` and `npm run lint` green with page-level coverage of tree, badges, filters, and create/edit/deactivate flows (G7).
- [ ] Backend suites unchanged and green: `docker compose exec backend php artisan test` and `./test-pg.sh` hold the ≥231-test baseline (0-diff on `backend/`).
- [ ] `/catalogo` renders coherently for the admin role per the sdd-design G8 decision — no dead-end 403 banners, no expanded admin capabilities.
- [ ] HU-013–HU-025 (as end-to-end supported) transitioned to *En Revisión* in `docs/historias/` and mirrored to Notion.

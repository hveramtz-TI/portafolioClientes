# Design: Catalog Slice 6 — Frontend Catalog Surface & Authenticated Base Read

> **As-built closeout design.** This document describes the architecture that was
> already implemented and verified in the working tree before the SDD trail was
> opened. It is a retroactive record, not a forward plan. Slice 7 owns the gaps
> (G1–G8) and this closeout is its blocking precondition.

## Technical Approach

Slice 6 delivers the first user-operable surface of the planning3 catalog epic.
The approach is minimal and symmetric with the rest of the frontend:

1. **Backend**: a single route-line move in `routes/api.php` widens `GET /api/rubros`
   from admin-only to authenticated scope. All base mutations and child-listing
   routes stay inside the `role:admin` middleware group. No controller changes, no
   new middleware, no migrations.
2. **Frontend**: a feature-first module (`src/modules/catalog/`) follows the
   established clients-module pattern — typed function-per-endpoint API over the
   existing `apiFetch` CSRF client, plain controlled state (no form library),
   shadcn/ui primitives (Tabs/Select/Dialog/Badge), and a page composing two tabs
   with independent fetch lifecycles.
3. **Authorization contract**: the widening is pinned by `BaseCatalogReadAccessTest`
   (positive: normal user reads active rubros; negative: normal user mutation → 403)
   and the updated `RubroApiTest` expectation, so the spec-sync decision is
   traceable to code, not only to prose.

## Architecture Decisions

### Decision: Route-placement authorization over controller-level role checks

**Choice**: `GET /api/rubros` lives outside the `Route::middleware('role:admin')`
group in `routes/api.php`. Authorization is granted by route placement, not by a
role check inside `RubroController::index`.

**Alternatives considered**: (a) Add an `if ($user->isAdmin)` branch inside the
controller — rejected because it duplicates what the middleware already does and
makes the authorization boundary harder to audit. (b) Create a new
`role:authenticated` middleware — rejected because `auth:sanctum` already covers
this; adding a second middleware for the same concept is YAGNI.

**Rationale**: The route file is the single source of truth for "who can call
what." A reader scanning `api.php` sees the authenticated block and the admin
block and immediately understands the boundary. Controller code stays focused on
business logic. This matches how every other resource in the project gates its
routes.

### Decision: Feature-first catalog module over layered structure

**Choice**: `frontend/src/modules/catalog/` contains `api.ts`, `components/`, and
`__tests__/` co-located. The page at `app/(dashboard)/catalogo/page.tsx` imports
from the module.

**Alternatives considered**: (a) Put API functions in a global `lib/catalog.ts`
and components in `components/catalog/` — rejected because it scatters a single
feature across three directories and makes it harder to see the full blast radius
of a catalog change. (b) Use a form library (react-hook-form) for the dialog —
rejected because the dialog has at most three fields; controlled state is simpler
and consistent with the rest of the dashboard.

**Rationale**: Feature-first matches the pattern established by earlier modules.
Co-locating the API client, components, and tests means a future developer can
delete or refactor the catalog feature by removing one directory.

### Decision: Recursive tree rendering without client-side ancestor assumptions

**Choice**: `catalog-tree.tsx` renders whatever the server returns. If a filtered
response promotes a service whose rubro ancestor is absent, the service still
renders at the top level.

**Alternatives considered**: (a) Client-side ancestor reconstruction — rejected
because it duplicates server logic, can disagree with the server's filter
semantics, and adds complexity for a case the server already handles correctly.
(b) Require the server to always include ancestors — rejected because it would
force the server to return nodes the user explicitly filtered out.

**Rationale**: The server is the source of truth for the tree shape. The client
renders the server's answer faithfully. This keeps the component simple and
correct for every filter combination.

### Decision: Rubro cascade fork as the sole base-discovery path

**Choice**: Users discover base catalog content by forking a rubro (which brings
its categories and services). `GET /rubros/{id}/categorias` stays admin-only.

**Alternatives considered**: (a) Expose base children to authenticated users —
rejected because it widens the attack surface without a current use case; the
fork already returns the full nested structure the user needs. (b) Build a
read-only base-tree explorer — rejected because it duplicates the fork result and
adds UI surface before the personalization flows are stable.

**Rationale**: The fork endpoint returns the complete nested rubro the user will
personalize. Exposing base children separately would create two discovery paths
for the same data, increasing maintenance cost and the risk of divergence.

### Decision: Refresh-counter reload over optimistic updates

**Choice**: Every lifecycle action (create/edit/status/delete) invalidates the
tree via a refresh counter (`key` prop or `useEffect` dependency), triggering a
full re-fetch.

**Alternatives considered**: (a) Optimistic UI with rollback — rejected because
the tree is small, the server is the source of truth for effective status/origin,
and optimistic updates would need to recompute derived fields (effective status,
origin) that the server calculates. (b) React Query / SWR cache invalidation —
rejected because the project does not use these libraries; adding one for a
single page is YAGNI.

**Rationale**: A refresh counter is three lines of code, always correct, and
consistent with how other dashboard pages reload data after mutations.

## Data Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│  /catalogo page                                                     │
│                                                                     │
│  ┌──────────────────────┐      ┌──────────────────────────────┐    │
│  │  Base Catalog tab    │      │  My Catalog tab              │    │
│  │                      │      │                              │    │
│  │  getBaseRubros() ────┼──┐   │  getUserCatalogTree(filters)─┼──┐ │
│  │                      │  │   │  status / origin selects     │  │ │
│  │  [card] [card] ...   │  │   │                              │  │ │
│  │  Select → fork ──────┼──┼──▶│  catalog-tree (recursive)    │  │ │
│  │                      │  │   │  catalog-badges              │  │ │
│  │  New personal ───────┼──┼──▶│  catalog-item-dialog         │  │ │
│  └──────────────────────┘  │   │  edit / status / delete      │  │ │
│                            │   └──────────────────────────────┘  │ │
│                            │                                      │ │
│                            └──────▶ apiFetch (CSRF) ──────────────┘ │
│                                      │                              │
└──────────────────────────────────────┼──────────────────────────────┘
                                       │
                    ┌──────────────────┼──────────────────┐
                    │  Laravel API     │                  │
                    │                  ▼                  │
                    │  GET /api/rubros (authenticated)    │
                    │  GET /api/user-catalog/tree (auth)  │
                    │  POST /api/user-catalog/.../fork    │
                    │  POST/PUT/PATCH/DELETE personal     │
                    │  GET /api/rubros/{id}/categorias    │
                    │     └── role:admin only             │
                    │  POST/PUT/PATCH/DELETE base rubros  │
                    │     └── role:admin only             │
                    └─────────────────────────────────────┘
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `backend/routes/api.php` | Modify | `GET /api/rubros` moved to authenticated scope (line 104); admin `apiResource` narrowed to `store`, `update`, `destroy` (line 114); child-listing and status-transition routes stay admin-only (lines 111–113). |
| `backend/tests/Feature/BaseCatalogReadAccessTest.php` | Create | Authorization tests: normal user reads active rubros (200), normal user mutation → 403 (store/update/destroy/deactivate/reactivate), unauthenticated → 401. |
| `backend/tests/Feature/RubroApiTest.php` | Modify | Stale 403-on-read expectation widened to 200 for normal users (`test_regular_user_can_read_base_rubros`). |
| `frontend/src/modules/catalog/api.ts` | Create | Typed API client (90 lines): `BaseRubro`/`CatalogNode`/`CatalogFilters` types, `getBaseRubros`, `getUserCatalogTree`, `forkBaseItem`, `createPersonalItem`, `updatePersonalItem`, `updatePersonalItemStatus`, `deletePersonalItem`. All routed through `apiFetch`. |
| `frontend/src/modules/catalog/components/catalog-badges.tsx` | Create | `StatusBadge` (Active/Inactive with secondary/destructive variants) and `OriginBadge` (Base/Override/Personal with outline variant). |
| `frontend/src/modules/catalog/components/catalog-tree.tsx` | Create | Recursive tree component rendering `CatalogNode` hierarchy (rubro → category → service) with effective status/origin badges and per-node action buttons. |
| `frontend/src/modules/catalog/components/catalog-tree.test.tsx` | Create | Jest test: descendant rendering and effective status/origin badge display. |
| `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` | Create | Dialog for create/edit: adapts fields by item type (rubro/category: name+description; service: title+description+value). Save button disabled during submission. |
| `frontend/src/modules/catalog/__tests__/api.test.ts` | Create | Jest contract tests: filter serialization (`status=desactivado&origin=override`), fork route contract (`/api/user-catalog/rubros/base-id/fork` POST). |
| `frontend/src/app/(dashboard)/catalogo/page.tsx` | Create | Protected page (36 lines) inside dashboard shell: two tabs (Base Catalog / My Catalog), status/origin filter selects, refresh counter, error banners, loading skeletons, empty states. |
| `frontend/src/components/shared/app-sidebar.tsx` | Modify | "Catalog" navigation entry added (label: "Catalog", href: "/catalogo", icon: FolderTree). |

## Interfaces / Contracts

### Backend — Authorization Boundary

```
Authenticated scope (auth:sanctum):
  GET  /api/rubros                    → BaseRubro[]  (default: status=activo)
  GET  /api/rubros?status=all         → BaseRubro[]  (includes deactivated)
  GET  /api/user-catalog/tree         → CatalogNode[] (with ?status=&origin= filters)
  POST /api/user-catalog/{type}/{id}/fork
  POST /api/user-catalog/rubros
  PUT  /api/user-catalog/rubros/{id}
  PATCH /api/user-catalog/rubros/{id}/deactivate
  PATCH /api/user-catalog/rubros/{id}/reactivate
  DELETE /api/user-catalog/rubros/{id}
  (same shape for categorias and services)

Admin-only scope (auth:sanctum + role:admin):
  POST   /api/rubros                  (store)
  PUT    /api/rubros/{id}             (update)
  DELETE /api/rubros/{id}             (destroy)
  PATCH  /api/rubros/{id}/deactivate
  PATCH  /api/rubros/{id}/reactivate
  GET    /api/rubros/{id}/categorias  (child listing)
  GET    /api/categorias/{id}/services (child listing)
  (full CRUD for categorias and services)
```

### Frontend — Typed API Client

```typescript
// Types
type CatalogItemType = 'rubro' | 'categoria' | 'service';
type CatalogStatus = 'activo' | 'desactivado' | 'all';
type CatalogOrigin = 'personal' | 'override' | 'base';

interface BaseRubro {
  id: string;
  name: string;
  description: string;
  status: 'activo' | 'desactivado';
}

interface CatalogNode {
  id: string;
  base_id: string | null;
  item_type: CatalogItemType;
  parent_fork_id: string | null;
  sort_order: number;
  name: string;           // rubro, categoria
  title: string;          // service
  description: string;
  value: number | null;   // service only
  tags: string[];         // service only
  status: 'activo' | 'desactivado';
  origin: CatalogOrigin;
  overridden_fields: string[];
  children: CatalogNode[];
}

interface CatalogFilters {
  status?: CatalogStatus;
  origin?: CatalogOrigin;
}

// Functions
getBaseRubros(status?: CatalogStatus): Promise<BaseRubro[]>
getUserCatalogTree(filters?: CatalogFilters): Promise<CatalogNode[]>
forkBaseItem(type: CatalogItemType, baseId: string): Promise<unknown>
createPersonalItem(type: CatalogItemType, input: CreatePersonalItemInput): Promise<CatalogNode>
updatePersonalItem(type: CatalogItemType, id: string, input: CreatePersonalItemInput): Promise<CatalogNode>
updatePersonalItemStatus(type: CatalogItemType, id: string, status: 'activo' | 'desactivado'): Promise<CatalogNode>
deletePersonalItem(type: CatalogItemType, id: string): Promise<void>
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit (Frontend) | API client filter serialization, fork route contract | Jest with mocked `apiFetch`; assert exact URL/method/body. |
| Unit (Frontend) | Tree descendant rendering, badge display | Jest + React Testing Library; render with fixture data, assert text content. |
| Unit (Backend) | Authenticated read allowed, mutation forbidden | `BaseCatalogReadAccessTest`: actingAs normal user, assert 200 on GET, 403 on POST/PUT/DELETE/PATCH. |
| Unit (Backend) | Widened read expectation | `RubroApiTest::test_regular_user_can_read_base_rubros`: assert 200 instead of 403. |
| Integration | Full backend suite | `php artisan test` — 235 tests / 885 assertions, all passing. |
| E2E | Manual browser UX | Not yet performed; documented as pending. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. Slice 6 is a CRUD authorization widening and a frontend surface; it does not touch any of the threat-matrix boundaries.

## Migration / Rollout

No migration required. The change is:
- One route-line move (no data migration, no config change).
- New frontend files (no existing behavior broken).
- New tests (no existing tests broken; one stale 403 expectation updated to 200).

Rollback is a single `git revert` of the Slice 6 commit: the route moves back
into the admin group, the frontend files are deleted, and the sidebar entry is
removed. No database changes to reverse.

## Slice 7 Out-of-Scope Boundaries (preserved)

The following are explicitly **not** part of this design. Slice 7
(`openspec/changes/catalog-slice-7-frontend/proposal.md`) owns them:

- **G1**: Personal `categoria`/`service` creation forms; dialog is rubro-scoped
  in Slice 6.
- **G2**: Service `tags` editor.
- **G3**: Changed-fields-only submit and revert-to-base controls; Slice 6 dialog
  submits all displayed fields.
- **G4/G6**: Move/attach and orphan-root recovery flows.
- **G5**: shadcn differentiated lifecycle confirmations replacing `window.confirm`.
- **G7**: Page-level Jest suite for `/catalogo`.
- **G8**: Admin-role view coherence on `/catalogo` (admin users can hit 403
  banners on personalization actions).
- User-readable base-children endpoints (`GET /rubros/{id}/categorias` stays
  admin-only).
- Any backend contract change beyond the single documented read-widening.

## Known Limitations (accepted, documented)

- Create dialog is rubro-scoped; category/service authoring ships in Slice 7.
- Editing any field submits all displayed fields, which can silently turn
  `base`-origin values into `override` (Slice 7 G3 fix).
- `window.confirm` is used for deletion instead of planning3's differentiated
  dialog wording (Slice 7 G5).
- Admin users on `/catalogo` can hit 403 banners on personalization actions
  (Slice 7 G8).
- Manual browser UX review of `/catalogo` has not been performed yet.

## Known Full-Lint Warning

The full frontend lint suite (`npm run lint`) carries pre-existing failures in
files unrelated to Slice 6 (dashboard, sidebar, AuthContext, mobile,
delete-dialog). None were introduced by this slice; none were fixed here. The
focused catalog lint (scoped to `src/modules/catalog/` and
`app/(dashboard)/catalogo/`) is clean and recorded in the verification evidence.
Slice 7 gates on the same scoped check plus the full-suite delta.

## Open Questions

- [ ] Non-admin dev account for the owner-path manual UX review (pending).
- [ ] Full frontend lint debt cleanup (out of scope for Slice 6; may be a
      separate housekeeping change).

# Design: Catalog Slice 7 — Frontend Category/Service Management & HU Closure

## Technical Approach

Slice 7 closes the planning3 frontend scope by evolving the existing Slice 6 surface (single page, single dialog, refresh-counter reload) rather than splitting into per-type screens. The approach is **Option 1 from the exploration**: extend the current `CatalogItemDialog` to be type-aware, add changed-fields-only submit semantics with explicit-null revert, introduce service tags editing, wire per-node child-add affordances, replace `window.confirm` with differentiated shadcn lifecycle dialogs, parse Laravel 422/409 errors into field-level and form-level messages, implement move-service and orphan-attach flows via the existing `PUT parent_fork_id` contract, render per-field override indicators from `overridden_fields`, and gate personalization affordances on the admin role to eliminate 403 banners.

The architecture preserves Slice 6's established patterns: feature-first module (`src/modules/catalog/`), plain controlled state (no form library), shadcn/ui primitives, typed API client over `apiFetch`, and refresh-counter tree reload. All backend contracts (`user-catalog-fork-api` R1–R7, `user-catalog-personalization` R1–R8) are consumed as frozen — zero backend diff.

**Delivery strategy**: chained PRs under `ask-on-risk` (400-line review budget). Slice 7a ships core CRUD forms + correctness fixes (type-aware dialog, tags editor, changed-fields-only submit, revert-to-base, child-add affordances, differentiated confirmations, server error surfacing, API client extensions). Slice 7b ships polish flows + coverage + closure (move-service, orphan-attach, per-field override display, admin rendering, page-level Jest, HU transitions). Each PR is independently revertable.

## Architecture Decisions

### Decision: Single type-aware dialog over per-type dialogs

**Choice**: One `CatalogItemDialog` component that adapts its form fields based on the item type being created or edited. The dialog receives a `type` prop (inferred from the node being edited or the create-intent context) and conditionally renders fields: rubro/categoria show name + description; service shows title + description + value + tags.

**Alternatives considered**: (a) Three separate dialog components (`RubroDialog`, `CategoriaDialog`, `ServiceDialog`) — rejected because it triples the dialog surface, duplicates the save/cancel/error-handling logic, and makes the create-intent wiring more complex (page must decide which dialog to open). (b) Dynamic form schema library (react-jsonschema-form) — rejected because the form has at most four fields; a schema engine is YAGNI and inconsistent with the project's plain-controlled-state pattern.

**Rationale**: A single dialog with conditional rendering is ~50 lines of field-switching logic, keeps the save/cancel/error flow in one place, and matches the exploration's Option 1. The type is always known at open time (from the node's `item_type` for edits, from the create-intent context for creates), so the dialog never needs to infer it. This is the smallest diff that closes G1 and G2.

### Decision: Initial-values snapshot for changed-fields-only submit

**Choice**: When the dialog opens for editing, capture a snapshot of the node's resolved values (`{ title, description, value, tags }` for service; `{ name, description }` for rubro/categoria). On submit, compare current state to the snapshot and include only fields whose value differs. For create operations, all type-relevant fields are sent (no snapshot comparison).

**Alternatives considered**: (a) Dirty-field tracking via `onChange` flags — rejected because it requires per-field dirty state, doesn't handle revert-to-base cleanly (revert then re-edit), and is more complex than a snapshot comparison. (b) Send all fields, rely on backend to detect no-ops — rejected because the backend's merged-overrides semantics treat every sent field as an override, reproducing the G3 override-inflation bug this slice fixes.

**Rationale**: A snapshot is three lines of code (`const initial = { ... }` at dialog open), the comparison is a pure function (`Object.entries(current).filter(([k, v]) => v !== initial[k])`), and it aligns the client with the backend's already-correct semantics (omitted = untouched, null = clear). This is the payload-shape fix that stops override inflation. The snapshot approach also handles the revert-then-re-edit case naturally: if the user reverts a field (sets it to null) then manually edits it, the current value differs from the initial snapshot, so the manual edit is sent (not null).

### Decision: Explicit-null revert controls for override clearing

**Choice**: For each field listed in the node's `overridden_fields` array, render a revert control (button or icon) next to the field input. Activating the revert control sets that field's value to `null` in the form state. On submit, if the field's current value is `null` and it was in `overridden_fields`, include `{ field: null }` in the payload. The revert control is visually distinct from simply clearing the field (e.g., a "Revert to base" button with an undo icon, not just an empty input).

**Alternatives considered**: (a) Clearing the input and relying on the user to submit — rejected because an empty input is ambiguous (does the user want to clear the override, or just hasn't filled it yet?), and the backend interprets omitted fields as "untouched," not "clear." (b) Separate "revert all overrides" button — rejected because it's too coarse; users may want to revert one field but keep others.

**Rationale**: Per-field revert controls give users fine-grained control over which overrides to clear. The explicit-null payload aligns with the backend's `user-catalog-personalization` R5 (S5.3: null removes override, restoring inheritance). The visual distinction between "revert" and "clear" prevents user confusion. This closes G3 and D13.

### Decision: Tags editor as constrained multi-select

**Choice**: For service dialogs, render a tags editor that presents the five allowed options (`frontend`, `backend`, `fullstack`, `devops`, `mobile`) as checkboxes or a multi-select dropdown. The editor enforces the constraint client-side (users cannot select tags outside the list), and the selected tags are sent as a JSON array in the payload. Empty selection sends `tags: []`.

**Alternatives considered**: (a) Free-text tag input with client-side validation — rejected because the backend's whitelist (personalization R4, S4.1) already enforces the constraint; a free-text input would just surface more 422 errors. (b) Tag input with autocomplete from the allowed list — rejected because the list is small (5 items) and static; a multi-select is simpler and makes all options visible.

**Rationale**: A constrained multi-select is ~20 lines of code (render 5 checkboxes, collect selected values), prevents invalid submissions client-side, and matches the backend's whitelist. This closes G2.

### Decision: Destination-category selector for move-service flow

**Choice**: When editing a service, render a destination categoria selector (dropdown) that lists the user's categoria forks (from the tree data) excluding the current parent. If the user selects a different categoria, include `parent_fork_id: <selected-id>` in the submit payload. If the user does not change the selector, omit `parent_fork_id` (unchanged parent = omitted per changed-fields-only semantics).

**Alternatives considered**: (a) Separate "Move" button on the service node that opens a dedicated move dialog — rejected because it duplicates the edit dialog's save flow and splits the move/edit decision across two UI surfaces. (b) Inline drag-and-drop reordering — rejected because it requires a tree library, adds complexity, and the backend's `PUT parent_fork_id` contract is already sufficient.

**Rationale**: Embedding the destination selector in the edit dialog keeps the move flow in one place, reuses the changed-fields-only submit logic, and aligns with the backend's R5 contract (move via `PUT parent_fork_id`). The selector is populated from the tree data already in memory (no additional API call). This closes G4.

### Decision: Orphan-attach action in tree node

**Choice**: For nodes with `parent_fork_id: null` (orphan roots), render an "Attach" button next to the edit/delete buttons. Clicking "Attach" opens a dialog (or inline selector) that lists type-coherent parent candidates (rubro forks for orphan categorias, categoria forks for orphan services). Confirming the attach calls `updatePersonalItem` with `parent_fork_id` set to the chosen parent.

**Alternatives considered**: (a) Automatic orphan detection and repair — rejected because it hides the problem from the user; orphans are a correctness gap (G6) that should be visible and repairable. (b) Separate "Orphans" tab — rejected because it fragments the tree view; orphans should be repairable in context.

**Rationale**: An "Attach" button on the orphan node makes the dead-end visible and repairable in context. The attach flow reuses the existing `updatePersonalItem` path (R5 contract), so no new API surface is needed. This closes G6.

### Decision: shadcn Dialog for differentiated lifecycle confirmations

**Choice**: Replace `window.confirm` with a shadcn `Dialog` component for all delete/deactivate confirmations. The dialog's message is computed client-side from the node's `children` data: if the node has descendants, the message mentions "the element and its descendants will be deactivated/permanently deleted"; if the node has no children, the message mentions only the element. The dialog shows the item's display name.

**Alternatives considered**: (a) Keep `window.confirm` with static wording — rejected because planning3 mandates differentiated wording based on descendants, and `window.confirm` cannot be styled or localized. (b) Toast-based confirmation — rejected because it's ephemeral and easy to miss; a modal dialog is more deliberate.

**Rationale**: A shadcn Dialog is already in the project's component library, supports rich content (item name, descendant wording), and is accessible. The descendant check is a simple `node.children?.length > 0` computation. This closes G5 and aligns with planning3's UI requirements.

### Decision: Laravel 422/409 error parsing at the API client layer

**Choice**: Extend the API client's error types to include the Laravel 422 shape (`{ message: string, errors: Record<string, string[]> }`) and the 409 shape (`{ message: string }`). In the dialog's submit handler, catch errors and parse them: for 422, map `errors` to field-level error state (displayed below the corresponding input); for 409, display the message as a form-level error. For other errors (500, network), display a generic message.

**Alternatives considered**: (a) Hardcode error messages — rejected because the backend's error messages are already user-readable and may change; hardcoding duplicates the backend's logic. (b) Parse errors in the page component — rejected because it duplicates parsing logic across multiple mutation handlers (create, edit, move, attach); the API client is the single point of error handling.

**Rationale**: Typing the Laravel error shape in the API client makes error parsing type-safe and reusable. Field-level error mapping is a pure function (`Object.entries(errors).forEach(([field, messages]) => setFieldError(field, messages[0]))`). This closes G4's error-surfacing prerequisite.

### Decision: Admin role-gating at the page level

**Choice**: In the `/catalogo` page component, check the user's role (from the auth context) and conditionally render personalization affordances (create buttons, edit/delete/add-child buttons, status toggle, fork button on base cards). For admin users, hide all personalization buttons and display an informational message in the "My Catalog" tab (e.g., "Personalization is for non-admin users"). The Base Catalog tab renders normally for admins (read-only).

**Alternatives considered**: (a) Role-gating at the component level (each button checks the role) — rejected because it scatters the role check across multiple components; a single page-level check is simpler and easier to audit. (b) Backend-driven affordance flags — rejected because the backend already denies admin access (403); the frontend just needs to avoid showing buttons that will fail.

**Rationale**: A page-level role check is one `if (user.role === 'admin')` branch that gates all personalization UI. This eliminates the 403 banner experience for admins (G8) without expanding admin permissions (no backend change). The Base Catalog tab remains readable because `GET /api/rubros` is authenticated-scope (Slice 6 widening).

### Decision: Per-field override indicators from `overridden_fields`

**Choice**: In the tree node rendering, for each field listed in the node's `overridden_fields` array, display a visual indicator (e.g., a small badge or icon) next to the field's value. Fields not in `overridden_fields` display with their normal origin indicator (Base/Personal). The indicators are rendered by the `CatalogBadges` component, which already receives the node.

**Alternatives considered**: (a) Per-field origin badges (e.g., "Title: Override, Value: Base") — rejected because it's too verbose; a small indicator is sufficient. (b) Color-coded field values — rejected because it's not accessible (colorblind users) and inconsistent with the project's badge-based origin display.

**Rationale**: Per-field indicators make override visibility granular (G3 visibility, D13 badges). The `overridden_fields` array is already provided by the tree API (personalization R5), so no new data is needed. The indicators are rendered by the existing `CatalogBadges` component, keeping the change localized.

### Decision: Page-level Jest suites per flow

**Choice**: Write Jest test suites that cover the full `/catalogo` page and its components: tree rendering, badge display, filter interactions, create flows (rubro via Base Catalog, categoria via add-child, service via add-child with tags), edit flows (changed-fields-only submit, revert-to-base), deactivate/reactivate flows, delete flows with differentiated confirmation, move-service flow, orphan-attach flow, server error surfacing (422 field-level, 409 form-level), and admin role-coherent rendering. All tests follow RED→GREEN TDD.

**Alternatives considered**: (a) Unit tests per component only — rejected because page-level tests cover the integration between components (dialog opens from tree, save triggers reload, errors display in dialog). (b) E2E tests (Playwright) — rejected because the project uses Jest for frontend testing; E2E is deferred to a later epic.

**Rationale**: Page-level Jest tests cover the user-facing flows end-to-end (within the frontend), catch integration bugs, and align with planning3's "Tests Jest por página/componente" requirement (G7). The RED→GREEN approach ensures every flow has test coverage before implementation.

## Data Flow

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  /catalogo page                                                              │
│                                                                              │
│  ┌──────────────────────┐      ┌──────────────────────────────────────────┐ │
│  │  Base Catalog tab    │      │  My Catalog tab                          │ │
│  │                      │      │                                          │ │
│  │  getBaseRubros() ────┼──┐   │  getUserCatalogTree(filters) ────────────┼─┐
│  │                      │  │   │  status / origin selects                 │ │ │
│  │  [card] [card] ...   │  │   │                                          │ │ │
│  │  Select → fork ──────┼──┼──▶│  catalog-tree (recursive)                │ │ │
│  │  (hidden for admin)  │  │   │  ├─ per-node: edit/status/delete/add-child│ │ │
│  │                      │  │   │  ├─ orphan nodes: attach button          │ │ │
│  │  New personal ───────┼──┼──▶│  └─ catalog-badges (status/origin/override)│ │
│  │  (hidden for admin)  │  │   │                                          │ │ │
│  └──────────────────────┘  │   │  catalog-item-dialog                     │ │ │
│                            │   │  ├─ type-aware fields                    │ │ │
│                            │   │  ├─ tags editor (service)                │ │ │
│                            │   │  ├─ destination selector (move-service)  │ │ │
│                            │   │  ├─ revert-to-base controls              │ │ │
│                            │   │  └─ changed-fields-only submit           │ │ │
│                            │   │                                          │ │ │
│                            │   │  confirm-dialog (delete/deactivate)      │ │ │
│                            │   │  ├─ descendant-aware wording             │ │ │
│                            │   │  └─ shadcn Dialog (not window.confirm)   │ │ │
│                            │   │                                          │ │ │
│                            │   │  attach-dialog (orphan recovery)         │ │ │
│                            │   │  └─ type-coherent parent picker          │ │ │
│                            │   └──────────────────────────────────────────┘ │ │
│                            │                                                │ │
│                            └──────▶ apiFetch (CSRF) ────────────────────────┘ │
│                                      │                                        │
│                                      ▼                                        │
│                            ┌──────────────────────────────────────┐          │
│                            │  Error parsing                       │          │
│                            │  ├─ 422: field-level errors          │          │
│                            │  ├─ 409: form-level error            │          │
│                            │  └─ 500: generic error               │          │
│                            └──────────────────────────────────────┘          │
│                                                                              │
│  Admin role check:                                                           │
│  ├─ if (user.role === 'admin') hide personalization buttons                 │
│  ├─ if (user.role === 'admin') show info message in My Catalog tab          │
│  └─ if (user.role === 'admin') render Base Catalog tab normally (read-only) │
└──────────────────────────────────────────────────────────────────────────────┘
                                       │
                    ┌──────────────────┼──────────────────┐
                    │  Laravel API     │                  │
                    │                  ▼                  │
                    │  GET /api/rubros (authenticated)    │
                    │  GET /api/user-catalog/tree (auth)  │
                    │  POST /api/user-catalog/.../fork    │
                    │  POST /api/user-catalog/{type}      │
                    │  PUT  /api/user-catalog/{type}/{id} │
                    │    ├─ parent_fork_id (move/attach)  │
                    │    ├─ explicit null (revert)        │
                    │    └─ changed-fields-only payload   │
                    │  PATCH /api/user-catalog/.../status │
                    │  DELETE /api/user-catalog/{type}/{id}│
                    │                                     │
                    │  Error contracts (R7):              │
                    │  ├─ 422: { errors: { field: [msg] } }│
                    │  ├─ 409: { message: "..." }         │
                    │  └─ 403/404/500: JSON               │
                    └─────────────────────────────────────┘
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `frontend/src/modules/catalog/api.ts` | Modify | Extend `CreatePersonalItemInput` to accept `parent_fork_id` (string \| null) and explicit `null` values for override-clearing fields (title: null, value: null, description: null, tags: null). Add `LaravelValidationError` type (`{ message: string, errors: Record<string, string[]> }`) and `LaravelConflictError` type (`{ message: string }`). Update `updatePersonalItem` and `createPersonalItem` signatures to accept the extended input. |
| `frontend/src/modules/catalog/components/catalog-item-dialog.tsx` | Modify | Refactor to type-aware dialog: accept `type` prop (inferred from node or create-intent), conditionally render fields (rubro/categoria: name + description; service: title + description + value + tags). Add tags editor (multi-select of 5 allowed values). Add destination categoria selector for service edits (move-service). Add revert-to-base controls for overridden fields. Implement changed-fields-only submit (snapshot initial values, diff on submit). Add field-level error display (from 422 `errors` map) and form-level error display (from 409 `message`). |
| `frontend/src/modules/catalog/components/catalog-tree.tsx` | Modify | Add per-node child-add affordances: "Add category" button on rubro nodes, "Add service" button on categoria nodes. Add orphan indicator + "Attach" button on nodes with `parent_fork_id: null`. Add per-field override indicators (from `overridden_fields`). Pass `onAddChild` and `onAttach` callbacks to the page. |
| `frontend/src/modules/catalog/components/catalog-badges.tsx` | Modify | Extend to render per-field override indicators: for each field in `overridden_fields`, display a small badge or icon next to the field's value. Keep existing status/origin badges. |
| `frontend/src/modules/catalog/components/confirm-dialog.tsx` | Create | shadcn Dialog for differentiated lifecycle confirmations: accepts `item` (node), `action` ('delete' \| 'deactivate'), computes descendant-aware wording ("the element and its descendants will be..." vs "the element will be..."), displays item name, and calls `onConfirm` or `onCancel`. |
| `frontend/src/modules/catalog/components/attach-dialog.tsx` | Create | Dialog for orphan-attach flow: accepts `orphan` (node), lists type-coherent parent candidates (rubro forks for orphan categorias, categoria forks for orphan services) from the tree data, and calls `onAttach(parentForkId)` on confirm. |
| `frontend/src/app/(dashboard)/catalogo/page.tsx` | Modify | Add create-intent state (`createIntent: { type: CatalogItemType, parentForkId: string } | null`) to track which "Add child" button was clicked. Wire `onAddChild` callback to open the dialog in create mode with the correct type and `parent_fork_id`. Wire `onAttach` callback to open the attach dialog. Replace `window.confirm` with the `ConfirmDialog` component. Add admin role check: if `user.role === 'admin'`, hide personalization buttons (create, edit, delete, add-child, status toggle, fork on base cards) and display an info message in the My Catalog tab. Parse 422/409 errors from the API client and pass them to the dialog for field-level/form-level display. |
| `frontend/src/modules/catalog/__tests__/catalog-page.test.tsx` | Create | Page-level Jest suite: tree rendering (nested hierarchy), badge display (status/origin/per-field override), filter interactions (status/origin select changes trigger re-fetch), create flows (rubro via Base Catalog, categoria via add-child, service via add-child with tags), edit flows (changed-fields-only submit, revert-to-base), deactivate/reactivate flows, delete flows with differentiated confirmation dialog, move-service flow (destination selector sends `parent_fork_id`), orphan-attach flow (attach button sends `parent_fork_id`), server error surfacing (422 field-level, 409 form-level), admin role-coherent rendering (no personalization buttons for admin). All tests RED→GREEN. |
| `frontend/src/modules/catalog/__tests__/catalog-item-dialog.test.tsx` | Create | Component-level Jest suite: type-aware field rendering (rubro/categoria vs service), tags editor (allowed options, round-trip, empty selection), changed-fields-only submit (touched/untouched/reverted fields), revert-to-base controls (explicit null in payload), destination selector (move-service), error display (422 field-level, 409 form-level). |
| `frontend/src/modules/catalog/__tests__/confirm-dialog.test.tsx` | Create | Component-level Jest suite: descendant-aware wording (node with children vs leaf), item name display, confirm/cancel callbacks. |
| `frontend/src/modules/catalog/__tests__/attach-dialog.test.tsx` | Create | Component-level Jest suite: type-coherent parent candidates (rubro for orphan categoria, categoria for orphan service), attach callback with `parent_fork_id`. |
| `docs/historias/HU-013.md` – `HU-025.md` (excluding HU-024) | Modify | Transition status to *En Revisión* for stories end-to-end supported by the catalog epic (Slices 1–7). HU-024 remains at its current status (already *En Revisión* from Slice 5). |
| Notion mirror (via MCP) | Modify | Reflect HU status transitions in the Notion database per AGENTS.md. |
| `backend/**` | None | Expected 0-diff; frozen R1–R8 contracts consumed as-is. |

## Interfaces / Contracts

### API Client Extensions

```typescript
// Extended input type (supports parent_fork_id and explicit null)
export interface CreatePersonalItemInput {
  name?: string | null;
  title?: string | null;
  description?: string | null;
  value?: number | null;
  tags?: string[] | null;
  parent_fork_id?: string | null;
}

// Laravel error types
export interface LaravelValidationError {
  message: string;
  errors: Record<string, string[]>;
}

export interface LaravelConflictError {
  message: string;
}

// Function signatures (unchanged, but input type is extended)
export function createPersonalItem(
  type: CatalogItemType,
  input: CreatePersonalItemInput
): Promise<CatalogNode>;

export function updatePersonalItem(
  type: CatalogItemType,
  id: string,
  input: CreatePersonalItemInput
): Promise<CatalogNode>;
```

### Dialog Props

```typescript
// Type-aware dialog
interface CatalogItemDialogProps {
  open: boolean;
  item?: CatalogNode | null; // null = create mode, undefined = closed
  createIntent?: {
    type: CatalogItemType;
    parentForkId: string;
  } | null; // for create mode
  tree?: CatalogNode[]; // for destination selector (move-service)
  onOpenChange: (open: boolean) => void;
  onSave: (input: CreatePersonalItemInput) => Promise<void>;
  validationErrors?: Record<string, string[]>; // from 422
  formError?: string; // from 409 or generic
}

// Confirm dialog
interface ConfirmDialogProps {
  open: boolean;
  item: CatalogNode;
  action: 'delete' | 'deactivate';
  onConfirm: () => void;
  onCancel: () => void;
}

// Attach dialog
interface AttachDialogProps {
  open: boolean;
  orphan: CatalogNode;
  tree: CatalogNode[]; // to find type-coherent parents
  onAttach: (parentForkId: string) => void;
  onCancel: () => void;
}
```

### Tree Node Callbacks

```typescript
interface CatalogTreeProps {
  nodes: CatalogNode[];
  onEdit: (node: CatalogNode) => void;
  onStatus: (node: CatalogNode) => void;
  onDelete: (node: CatalogNode) => void;
  onAddChild: (parent: CatalogNode, childType: CatalogItemType) => void;
  onAttach: (orphan: CatalogNode) => void;
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit (Frontend) | API client contract extensions (parent_fork_id, explicit null, error typing) | Jest with mocked `apiFetch`; assert exact URL/method/body for `createPersonalItem` and `updatePersonalItem` with extended input. |
| Unit (Frontend) | Type-aware dialog field rendering (rubro/categoria vs service) | Jest + React Testing Library; render dialog with different `type` props, assert field presence/absence. |
| Unit (Frontend) | Tags editor (allowed options, round-trip, empty selection) | Jest + RTL; render tags editor, assert 5 options, assert selected tags, assert empty selection sends `[]`. |
| Unit (Frontend) | Changed-fields-only submit (touched/untouched/reverted fields) | Jest + RTL; open edit dialog, change one field, assert payload contains only that field; revert a field, assert payload contains `{ field: null }`. |
| Unit (Frontend) | Revert-to-base controls (explicit null in payload) | Jest + RTL; open edit dialog for node with `overridden_fields`, activate revert, assert payload contains `{ field: null }`. |
| Unit (Frontend) | Destination selector (move-service) | Jest + RTL; open edit dialog for service, assert destination selector lists categoria forks excluding current parent, assert selecting a destination sends `parent_fork_id`. |
| Unit (Frontend) | Confirm dialog (descendant-aware wording) | Jest + RTL; render confirm dialog for node with children, assert message mentions descendants; render for leaf node, assert message does not mention descendants. |
| Unit (Frontend) | Attach dialog (type-coherent parent candidates) | Jest + RTL; render attach dialog for orphan categoria, assert selector lists rubro forks; render for orphan service, assert selector lists categoria forks. |
| Unit (Frontend) | Server error surfacing (422 field-level, 409 form-level) | Jest + RTL; mock API to return 422 with `errors` map, assert field-level errors display; mock 409, assert form-level error displays. |
| Unit (Frontend) | Admin role-coherent rendering | Jest + RTL; render page with admin user, assert no personalization buttons; render with non-admin user, assert buttons present. |
| Integration (Frontend) | Page-level flows (create/edit/deactivate/delete/move/attach) | Jest + RTL; render full page, simulate user interactions (click "Add category", fill form, submit), assert API calls with correct payloads, assert tree reloads. |
| Integration (Backend) | Full backend suite (regression gate) | `docker compose exec backend php artisan test` and `./test-pg.sh` — must hold the ≥231-test baseline. Zero backend diff expected. |
| E2E | Manual browser UX | Deferred; documented as pending post-merge. |

All frontend tests follow **Strict TDD (RED→GREEN-REFACTOR)**: write a failing test first, implement the minimum code to pass, refactor. The `npm test` command runs the full Jest suite; `npm run lint` gates code style.

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. Slice 7 is a frontend-only evolution of the catalog surface; it does not touch any of the threat-matrix boundaries.

## Migration / Rollout

No migration required. The change is:
- Frontend-only modifications (no backend diff, no data migration, no config change).
- New frontend components (`confirm-dialog.tsx`, `attach-dialog.tsx`) and test files.
- Extended API client types (backward-compatible; existing calls still work).
- HU status transitions in `docs/historias/` and Notion mirror (docs-only change, last commit of 7b).

**Rollback**: Each PR (7a, 7b) is independently revertable via `git revert` of the merge commit. No backend changes to unwind, no data migrations to reverse. The changed-fields-only submit degrades safely: the backend's merged-overrides semantics tolerate the legacy full-field payload (it just reproduces the override-inflation behavior), so reverting the dialog logic alone restores the prior client without API coordination.

**Delivery order**:
1. **Precondition**: Slice 6 must be committed and closed on `main` (including its spec-sync decision for the `GET /rubros` auth widening). Slice 7 branches from the clean post-Slice 6 state.
2. **PR #1 (Slice 7a)**: Type-aware dialog, tags editor, changed-fields-only submit, revert-to-base, child-add affordances, differentiated confirmations, server error surfacing, API client extensions. Targets the clean branch base after Slice 6.
3. **PR #2 (Slice 7b)**: Move-service, orphan-attach, per-field override display, admin rendering, page-level Jest, HU transitions. Targets the merged 7a branch.
4. **Epic close**: After 7b merges, transition HU-013–HU-025 (excluding HU-024) to *En Revisión* in `docs/historias/` and mirror to Notion.

## Workload / Line-Count Forecast

**Forecast**: The combined 7a + 7b work will likely exceed the 400-line review budget if delivered as a single PR. The chained PR strategy (7a → 7b) splits the work into two reviewable slices, each targeting ~300–400 authored lines (additions + deletions, excluding generated goldens).

**Decision needed before apply**: No — the chained PR strategy is already approved in the proposal and design.

**Chained PRs recommended**: Yes — 7a and 7b as separate PRs per the `ask-on-risk` delivery strategy.

**400-line budget risk**: Medium — 7a is foundational (dialog refactor, tags editor, changed-fields-only submit, child-add affordances, confirm dialog, error parsing) and may approach 400 lines. 7b is polish (move/attach flows, per-field override display, admin rendering, page-level tests) and is likely ~300 lines. If 7a exceeds 400 lines, consider splitting it further (e.g., 7a-i: dialog refactor + tags + changed-fields-only; 7a-ii: child-add + confirm + error parsing).

**Test workload**: Page-level Jest suite (7b) is the largest test effort (~150–200 lines of test code). Component-level tests (7a) are ~100–150 lines. All tests are RED-first under Strict TDD.

## Open Questions

- [ ] Non-admin dev account for the owner-path manual UX review (pending from Slice 6; must be resolved before 7a merge).
- [ ] Full frontend lint debt cleanup (out of scope for Slice 7; may be a separate housekeeping change). Slice 7 gates on the scoped catalog lint being clean plus no new full-suite failures.
- [ ] HU-024 status: already *En Revisión* from Slice 5; confirm it should not be re-transitioned (proposal says "HU-024 MUST remain at its current status").

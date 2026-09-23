# Catalog Frontend Management Specification

## Purpose

Defines the user-owner management extensions to the `/catalogo` surface that close planning3's frontend scope (Slices 7a–7b): type-aware category/service CRUD forms, service tags editing, changed-fields-only override semantics with explicit revert-to-base, per-node child-add affordances, move-service and orphan-attach flows, differentiated lifecycle confirmations, server-error surfacing (Laravel 422/409), per-field override visibility, admin role-coherent rendering, and page-level Jest coverage. This spec consumes the frozen backend contracts `user-catalog-fork-api` (R1–R7) and `user-catalog-personalization` (R1–R8) without modifying them. It supersedes the Slice 6 `catalog-frontend` requirement blocks that it explicitly replaces (dialog scope, delete confirmation mechanism, submit semantics); unchanged Slice 6 requirements (protected route, typed API client base, base catalog view, tree rendering, filters, loading/empty/error states) remain in force.

## Requirements

### Requirement: Type-Aware Catalog Item Dialog

The system SHALL provide a dialog component that adapts its form fields to the catalog item type being created or edited. For `rubro` items, the dialog MUST show name and description fields. For `categoria` items, the dialog MUST show name and description fields. For `service` items, the dialog MUST show title, description, value (numeric, min 0), and tags fields. The dialog title MUST reflect the item type and mode (e.g., "New category", "New service", "Edit service"). The dialog MUST accept a `parentForkId` context that determines the item type for create operations and pre-fills `parent_fork_id` in the submit payload.

#### Scenario: Dialog opens for creating a categoria under a rubro fork

- GIVEN a rubro fork node "Informática" is rendered in the tree
- WHEN the user clicks "Add category" on the rubro node
- THEN the dialog MUST open in create mode with title "New category"
- AND the form MUST show name and description inputs
- AND the value and tags inputs MUST NOT be shown
- AND the submit payload MUST include `parent_fork_id` set to the rubro fork's id

#### Scenario: Dialog opens for creating a service under a categoria fork

- GIVEN a categoria fork node "Web Development" is rendered in the tree
- WHEN the user clicks "Add service" on the categoria node
- THEN the dialog MUST open in create mode with title "New service"
- AND the form MUST show title, description, value, and tags inputs
- AND the submit payload MUST include `parent_fork_id` set to the categoria fork's id

#### Scenario: Dialog opens for editing a service with existing values

- GIVEN a service fork node "Logo Design" with value 50000, tags ["design"], description "Brand logo"
- WHEN the user clicks the edit button on the service node
- THEN the dialog MUST open in edit mode with title "Edit service"
- AND the title input MUST be pre-filled with "Logo Design"
- AND the description input MUST be pre-filled with "Brand logo"
- AND the value input MUST be pre-filled with "50000"
- AND the tags selector MUST show "design" as selected

#### Scenario: Dialog opens for editing a rubro

- GIVEN a rubro fork node "My Rubro" with description "Custom area"
- WHEN the user clicks the edit button on the rubro node
- THEN the dialog MUST open in edit mode with title "Edit rubro"
- AND the form MUST show name and description inputs only
- AND the value and tags inputs MUST NOT be shown

#### Scenario: Dialog opens for editing a categoria

- GIVEN a categoria fork node "Consulting" with description "Advisory services"
- WHEN the user clicks the edit button on the categoria node
- THEN the dialog MUST open in edit mode with title "Edit category"
- AND the form MUST show name and description inputs only
- AND the value and tags inputs MUST NOT be shown

### Requirement: Service Tags Editor

The system SHALL provide a tags editor within the service dialog that restricts selections to the allowed list: `frontend`, `backend`, `fullstack`, `devops`, `mobile`. The editor MUST prevent submitting tags outside this list. The editor MUST allow selecting zero or more tags from the allowed list. The selected tags MUST be sent as a JSON array in the submit payload.

#### Scenario: Tags editor shows allowed options

- GIVEN the dialog is open for a service item
- WHEN the tags editor renders
- THEN it MUST present exactly five options: "frontend", "backend", "fullstack", "devops", "mobile"

#### Scenario: Tags editor round-trips existing tags

- GIVEN a service fork with tags ["frontend", "mobile"]
- WHEN the edit dialog opens
- THEN the tags editor MUST show "frontend" and "mobile" as selected
- AND the other three options MUST NOT be selected

#### Scenario: Tags editor prevents invalid submission

- GIVEN the dialog is open for a service item
- WHEN no tags are selected
- THEN the submit payload MUST include `tags: []` (empty array)
- AND the submission MUST NOT be blocked by tag validation

#### Scenario: Tags editor sends selected tags in payload

- GIVEN the dialog is open for a service item
- WHEN the user selects "frontend" and "devops" and submits
- THEN the submit payload MUST include `tags: ["frontend", "devops"]`

### Requirement: Changed-Fields-Only Submit Semantics

The system SHALL track the initial resolved values of each field when the dialog opens for editing. On submit, ONLY fields whose current value differs from the initial value MUST be included in the payload. Untouched fields MUST NOT be sent. This aligns with the backend's merged-overrides semantics (omitted = untouched, null = clear override per `user-catalog-personalization` R5). For create operations, all fields relevant to the type MUST be sent.

#### Scenario: Editing one field sends only that field

- GIVEN a service fork with title "Logo" (override), value 50000 (base), description "Brand logo" (base)
- WHEN the user edits only the title to "Brand Logo" and submits
- THEN the submit payload MUST contain `{ title: "Brand Logo" }`
- AND the payload MUST NOT contain `value` or `description`
- AND after reload, the value and description fields MUST retain their "Base" origin badge

#### Scenario: Editing no fields sends empty payload

- GIVEN a service fork opened for editing
- WHEN the user makes no changes and submits
- THEN the submit payload MUST be empty (no fields)
- AND the API call MUST still be made (the backend handles empty updates gracefully)

#### Scenario: Editing multiple fields sends only touched fields

- GIVEN a service fork with title "Logo" (base), value 50000 (base), tags [] (base)
- WHEN the user changes title to "Brand Logo" and adds tag "frontend"
- THEN the submit payload MUST contain `{ title: "Brand Logo", tags: ["frontend"] }`
- AND the payload MUST NOT contain `value` or `description`

#### Scenario: Create operation sends all type-relevant fields

- GIVEN the dialog is open in create mode for a service
- WHEN the user fills title "New Service", description "Desc", value 10000, tags ["backend"]
- THEN the submit payload MUST contain all four fields: title, description, value, tags
- AND the payload MUST include `parent_fork_id` from the create-intent context

### Requirement: Revert-to-Base Controls

The system SHALL provide an explicit revert control for each field that currently has an override (i.e., fields listed in the node's `overridden_fields` array). Activating the revert control for a field MUST set that field's payload value to explicit `null`, which the backend interprets as "remove the override and restore inheritance" (`user-catalog-personalization` R5, S5.3). The revert control MUST NOT be shown for fields that are not overridden. The revert control MUST be distinguishable from simply clearing the field's value.

#### Scenario: Revert control appears for overridden fields only

- GIVEN a service fork with `overridden_fields: ["title", "value"]`
- WHEN the edit dialog opens
- THEN a revert control MUST be visible next to the title field
- AND a revert control MUST be visible next to the value field
- AND NO revert control MUST be visible next to the description field (not overridden)

#### Scenario: Reverting a field sends explicit null

- GIVEN a service fork with title "My Logo" (override, base is "Logo")
- WHEN the user activates the revert control for the title field
- THEN the title field MUST display the base value "Logo" (or a placeholder indicating inheritance)
- AND the submit payload MUST contain `{ title: null }`

#### Scenario: Reverted field is included even if unchanged from base

- GIVEN a service fork with value 50000 (override, base is also 50000 — override exists but matches)
- WHEN the user activates the revert control for value
- THEN the submit payload MUST contain `{ value: null }`
- AND after reload, the value field MUST show origin "Base"

#### Scenario: Revert and re-edit in same session

- GIVEN a service fork with title "My Logo" (override)
- WHEN the user activates revert for title
- AND then manually changes the title to "New Title"
- THEN the submit payload MUST contain `{ title: "New Title" }` (the manual edit, not null)

### Requirement: Tree Node Child-Add Affordances

The system SHALL render per-node action buttons in the catalog tree that allow adding child items. A rubro fork node MUST display an "Add category" button. A categoria fork node MUST display an "Add service" button. Service nodes MUST NOT display any "Add child" button. Clicking an add button MUST open the dialog in create mode with the correct type and `parent_fork_id` pre-set.

#### Scenario: Rubro node shows Add category button

- GIVEN a rubro fork node "Informática" rendered in the tree
- WHEN the node actions area renders
- THEN an "Add category" button MUST be visible

#### Scenario: Categoria node shows Add service button

- GIVEN a categoria fork node "Web Development" rendered in the tree
- WHEN the node actions area renders
- THEN an "Add service" button MUST be visible

#### Scenario: Service node does not show add child button

- GIVEN a service fork node "Logo Design" rendered in the tree
- WHEN the node actions area renders
- THEN no "Add child" button MUST be present

#### Scenario: Add category opens dialog with correct context

- GIVEN a rubro fork node with id "rubro-1"
- WHEN the user clicks "Add category"
- THEN the dialog MUST open in create mode for type "categoria"
- AND the submit payload MUST include `parent_fork_id: "rubro-1"`

#### Scenario: Add service opens dialog with correct context

- GIVEN a categoria fork node with id "cat-1"
- WHEN the user clicks "Add service"
- THEN the dialog MUST open in create mode for type "service"
- AND the submit payload MUST include `parent_fork_id: "cat-1"`

#### Scenario: Successful child creation reloads tree

- GIVEN the dialog is open for creating a categoria under rubro "rubro-1"
- WHEN the user submits valid data
- THEN `createPersonalItem('categoria', { name: '...', description: '...', parent_fork_id: 'rubro-1' })` MUST be called
- AND on success the dialog MUST close
- AND the tree MUST reload

### Requirement: Differentiated Lifecycle Confirmations

The system SHALL use a shadcn `Dialog` component (not `window.confirm`) for all delete/deactivate confirmations. The confirmation wording MUST differentiate based on the node's descendants: if the node has children, the message MUST state that the element and its descendants will be deactivated (for deactivate) or permanently deleted (for delete). If the node has no children, the message MUST state that the element will be deactivated or permanently deleted without mentioning descendants. The confirmation dialog MUST show the item's display name.

#### Scenario: Delete confirmation for node with descendants

- GIVEN a rubro fork "Informática" with 2 categoria children, each with services
- WHEN the user clicks the delete button
- THEN a shadcn Dialog MUST open (NOT `window.confirm`)
- AND the dialog message MUST mention that "Informática" and its descendants will be permanently deleted
- AND the dialog MUST show "Informática" as the item name

#### Scenario: Delete confirmation for leaf node

- GIVEN a service fork "Logo Design" with no children
- WHEN the user clicks the delete button
- THEN a shadcn Dialog MUST open
- AND the dialog message MUST state that "Logo Design" will be permanently deleted
- AND the dialog message MUST NOT mention descendants

#### Scenario: Deactivate confirmation for node with descendants

- GIVEN a rubro fork "Informática" with children
- WHEN the user clicks the deactivate button
- THEN a shadcn Dialog MUST open
- AND the dialog message MUST mention that "Informática" and its descendants will be deactivated

#### Scenario: Deactivate confirmation for leaf node

- GIVEN a service fork "Logo Design" with no children
- WHEN the user clicks the deactivate button
- THEN a shadcn Dialog MUST open
- AND the dialog message MUST state that "Logo Design" will be deactivated
- AND the dialog message MUST NOT mention descendants

#### Scenario: Confirmation cancel prevents action

- GIVEN any delete or deactivate confirmation dialog is open
- WHEN the user clicks "Cancel" or dismisses the dialog
- THEN the delete/deactivate API call MUST NOT be made

#### Scenario: Confirmation confirm triggers action and reloads

- GIVEN a delete confirmation dialog for "Logo Design"
- WHEN the user clicks "Delete" (confirm)
- THEN `deletePersonalItem('service', <id>)` MUST be called
- AND on success the tree MUST reload

### Requirement: Server Error Surfacing

The system SHALL parse Laravel 422 validation error responses and 409 conflict responses from the backend and display them as user-readable messages. For 422 responses, field-level errors MUST be mapped to the corresponding form fields in the dialog. For 409 responses, a form-level error message MUST be displayed. Error messages MUST be derived from the backend's `errors` map when present, not hardcoded.

#### Scenario: 422 field-level error displayed on dialog field

- GIVEN the dialog is open for creating a categoria
- WHEN the user submits a name that duplicates a sibling
- AND the backend returns 422 with `{ errors: { name: ["The name has already been taken."] } }`
- THEN the name field MUST display the error message "The name has already been taken."
- AND the dialog MUST remain open

#### Scenario: 422 multiple field errors displayed simultaneously

- GIVEN the dialog is open for creating a service
- WHEN the backend returns 422 with errors on both `title` and `value`
- THEN both the title field and the value field MUST display their respective error messages

#### Scenario: 409 conflict displayed as form-level error

- GIVEN the dialog is open for creating a personal rubro
- WHEN the backend returns 409 with `{ message: "Duplicate fork identity." }`
- THEN a form-level error message MUST be displayed containing "Duplicate fork identity."
- AND the dialog MUST remain open

#### Scenario: 422 error on move operation

- GIVEN a move-service operation is in progress
- WHEN the backend returns 422 with `{ errors: { parent_fork_id: ["Destination title clash."] } }`
- THEN an error message MUST be displayed containing "Destination title clash."

#### Scenario: Non-validation error shows generic message

- GIVEN any mutation operation
- WHEN the backend returns a 500 error
- THEN a generic error message MUST be displayed (e.g., "The item could not be saved.")

### Requirement: Move-Service Flow

The system SHALL allow moving a service fork from one categoria fork to another via the edit dialog. The dialog MUST provide a destination categoria selector when editing a service. The selector MUST list the user's categoria forks (from the tree data) excluding the current parent. Selecting a destination MUST include `parent_fork_id` in the submit payload pointing to the chosen categoria fork. The move MUST use the existing `updatePersonalItem` path (PUT), which the backend handles via `user-catalog-fork-api` R5.

#### Scenario: Move service dialog shows destination selector

- GIVEN a service fork "Logo" under categoria "Web" is opened for editing
- WHEN the dialog renders
- THEN a destination categoria selector MUST be visible
- AND the selector MUST list other categoria forks owned by the user
- AND the selector MUST NOT include the current parent "Web"

#### Scenario: Move service sends parent_fork_id

- GIVEN the move dialog for "Logo" with destination "Branding" (id "cat-branding")
- WHEN the user selects "Branding" and submits
- THEN the submit payload MUST include `{ parent_fork_id: "cat-branding" }`
- AND on success the tree MUST reload with "Logo" nested under "Branding"

#### Scenario: Move to same parent sends no parent_fork_id

- GIVEN the edit dialog for "Logo" under "Web"
- WHEN the user does not change the destination selector
- THEN the submit payload MUST NOT include `parent_fork_id` (unchanged parent = omitted)

#### Scenario: Move failure shows error

- GIVEN a move operation targeting a categoria with a name clash
- WHEN the backend returns 422
- THEN the error message from the server MUST be displayed (per Server Error Surfacing requirement)

### Requirement: Orphan-Attach Flow

The system SHALL identify orphan fork roots (categoria or service forks with `parent_fork_id = null`) in the tree and provide an "Attach" action to assign them to a valid parent. The attach action MUST open a dialog or inline selector that lists type-coherent parent candidates (rubro forks for orphan categorias, categoria forks for orphan services). Confirming the attach MUST call `updatePersonalItem` with `parent_fork_id` set to the chosen parent, using the R5 backend contract.

#### Scenario: Orphan categoria is visually identified

- GIVEN a categoria fork with `parent_fork_id: null` rendered in the tree
- WHEN the node renders
- THEN the node MUST display a visual indicator that it is unattached/orphan
- AND an "Attach" action button MUST be visible

#### Scenario: Orphan service is visually identified

- GIVEN a service fork with `parent_fork_id: null` rendered in the tree
- WHEN the node renders
- THEN the node MUST display a visual indicator that it is unattached/orphan
- AND an "Attach" action button MUST be visible

#### Scenario: Attach orphan categoria to rubro

- GIVEN an orphan categoria fork "Consulting" (null parent)
- AND the user owns a rubro fork "My Business" (id "rubro-1")
- WHEN the user clicks "Attach" and selects "My Business"
- THEN `updatePersonalItem('categoria', <consulting-id>, { parent_fork_id: 'rubro-1' })` MUST be called
- AND on success the tree MUST reload with "Consulting" nested under "My Business"

#### Scenario: Attach orphan service to categoria

- GIVEN an orphan service fork "Quick Setup" (null parent)
- AND the user owns a categoria fork "Web" (id "cat-1")
- WHEN the user clicks "Attach" and selects "Web"
- THEN `updatePersonalItem('service', <quick-setup-id>, { parent_fork_id: 'cat-1' })` MUST be called
- AND on success the tree MUST reload with "Quick Setup" nested under "Web"

#### Scenario: Attach failure shows error

- GIVEN an attach operation targeting a foreign parent
- WHEN the backend returns 422
- THEN the error message from the server MUST be displayed

### Requirement: Per-Field Override Display

The system SHALL render per-field override indicators on catalog nodes based on the `overridden_fields` array provided by the tree API. For each field listed in `overridden_fields`, the node display MUST show a visual indicator (e.g., badge, icon, or label) that the field value is an override. Fields NOT in `overridden_fields` MUST display with their normal origin indicator (Base/Personal). This enables users to see at a glance which fields they have personalized.

#### Scenario: Node with overridden title shows override indicator

- GIVEN a service fork with `overridden_fields: ["title"]` and title "My Logo"
- WHEN the node renders in the tree
- THEN the title display MUST include a visual override indicator

#### Scenario: Node with no overrides shows no per-field indicators

- GIVEN a service fork with `overridden_fields: []`
- WHEN the node renders in the tree
- THEN no per-field override indicators MUST be shown
- AND the node's origin badge MUST reflect the overall origin (e.g., "Base")

#### Scenario: Node with multiple overrides shows indicators for each

- GIVEN a service fork with `overridden_fields: ["title", "value", "tags"]`
- WHEN the node renders
- THEN visual override indicators MUST be present for title, value, and tags

### Requirement: Admin Role-Coherent Rendering

The system SHALL render the `/catalogo` page coherently for users with the `admin` role. Since the `UserCatalogItemPolicy` denies admin users access to personalization actions (fork, create, update, delete on other users' forks — and admins have no personal forks by design), the page MUST NOT display personalization affordances (create buttons, edit buttons, delete buttons, add-child buttons) for admin users. The page MUST still display the Base Catalog tab (readable by all authenticated users per `base-catalog-read-access`). The My Catalog tab MUST render an appropriate empty state or informational message for admin users instead of error banners.

#### Scenario: Admin sees Base Catalog tab normally

- GIVEN an authenticated user with role "admin"
- WHEN the admin navigates to `/catalogo`
- THEN the Base Catalog tab MUST render with base rubro cards
- AND the "Select" (fork) button MUST NOT be displayed on base rubro cards for admin users
- AND the "New personal rubro" button MUST NOT be displayed

#### Scenario: Admin sees coherent My Catalog tab

- GIVEN an authenticated user with role "admin"
- WHEN the admin views the My Catalog tab
- THEN the page MUST NOT display error banners from policy-denied actions
- AND the page MUST display an informational message indicating that personalization is for non-admin users
- AND no personalization action buttons (edit, delete, add-child, status toggle) MUST be rendered

#### Scenario: Admin does not see personalization affordances

- GIVEN an authenticated user with role "admin"
- WHEN any part of `/catalogo` renders
- THEN no fork, create, edit, delete, move, attach, or status-toggle buttons MUST be visible

### Requirement: API Client Contract Extensions

The system SHALL extend the typed API client (`src/modules/catalog/api.ts`) to support the Slice 7 contracts. The `updatePersonalItem` function MUST accept `parent_fork_id` (string | null) in its input type. The input type MUST support explicit `null` values for override-clearing fields (title: null, value: null, description: null, tags: null). The client MUST type the Laravel 422 error response shape (`{ message: string, errors: Record<string, string[]> }`) so callers can parse field-level errors. The `createPersonalItem` function MUST accept `parent_fork_id` in its input type.

#### Scenario: updatePersonalItem accepts parent_fork_id

- GIVEN the API client's `updatePersonalItem` function
- WHEN called with input `{ parent_fork_id: 'cat-1' }`
- THEN the PUT body MUST include `parent_fork_id: "cat-1"`

#### Scenario: updatePersonalItem sends explicit null for revert

- GIVEN the API client's `updatePersonalItem` function
- WHEN called with input `{ title: null }`
- THEN the PUT body MUST include `"title": null` (JSON null, not omitted)

#### Scenario: createPersonalItem accepts parent_fork_id

- GIVEN the API client's `createPersonalItem` function
- WHEN called with input `{ name: 'Cat', parent_fork_id: 'rubro-1' }`
- THEN the POST body MUST include `parent_fork_id: "rubro-1"`

#### Scenario: 422 error response is typed

- GIVEN the API client's error types
- WHEN a 422 response is received
- THEN the error object MUST expose `errors` as `Record<string, string[]>` for field-level parsing

### Requirement: Page-Level Jest Coverage

The system SHALL include comprehensive Jest test suites for the `/catalogo` page and its components. The suites MUST cover: tree rendering with nested hierarchy, badge display (effective status + origin), filter interactions (status/origin select changes trigger re-fetch), create flows (rubro via Base Catalog, categoria via add-child, service via add-child with tags), edit flows (changed-fields-only submit, revert-to-base), deactivate/reactivate flows, delete flows with differentiated confirmation dialog, move-service flow, orphan-attach flow, server error surfacing (422 field-level, 409 form-level), and admin role-coherent rendering. All tests MUST use the RED→GREEN TDD approach.

#### Scenario: Page test covers tree rendering

- GIVEN the Jest page-level test suite
- WHEN the tree rendering test runs
- THEN it MUST assert that a rubro with nested categorias and services renders the full hierarchy

#### Scenario: Page test covers create categoria flow

- GIVEN the Jest page-level test suite
- WHEN the create categoria test runs
- THEN it MUST assert that clicking "Add category" on a rubro opens the dialog
- AND it MUST assert that submitting calls `createPersonalItem('categoria', ...)` with correct `parent_fork_id`

#### Scenario: Page test covers changed-fields-only submit

- GIVEN the Jest page-level test suite
- WHEN the changed-fields-only test runs
- THEN it MUST assert that editing only one field sends a payload with only that field

#### Scenario: Page test covers revert-to-base

- GIVEN the Jest page-level test suite
- WHEN the revert-to-base test runs
- THEN it MUST assert that activating revert sends `{ field: null }` in the payload

#### Scenario: Page test covers differentiated delete confirmation

- GIVEN the Jest page-level test suite
- WHEN the delete confirmation test runs for a node with children
- THEN it MUST assert that a shadcn Dialog opens (not `window.confirm`)
- AND it MUST assert the message mentions descendants

#### Scenario: Page test covers 422 error surfacing

- GIVEN the Jest page-level test suite
- WHEN the 422 error test runs
- THEN it MUST assert that field-level errors from the backend are displayed on the corresponding form fields

#### Scenario: Page test covers admin rendering

- GIVEN the Jest page-level test suite
- WHEN the admin rendering test runs
- THEN it MUST assert that personalization buttons are NOT rendered for admin users

#### Scenario: Page test covers move-service flow

- GIVEN the Jest page-level test suite
- WHEN the move-service test runs
- THEN it MUST assert that selecting a destination categoria sends `parent_fork_id` in the payload

#### Scenario: Page test covers orphan-attach flow

- GIVEN the Jest page-level test suite
- WHEN the orphan-attach test runs
- THEN it MUST assert that an orphan node shows an attach indicator
- AND it MUST assert that attaching sends `parent_fork_id` in the payload

### Requirement: HU Status Transition Documentation

The system SHALL document the HU-013–HU-025 status transitions to *En Revisión* in `docs/historias/` for stories that are end-to-end supported by the completed catalog epic (Slices 1–7). The transition MUST be mirrored to Notion per AGENTS.md. Stories that require functionality beyond this epic (profile/public catalog, work orders) MUST NOT be transitioned.

#### Scenario: Supported HUs transition to En Revisión

- GIVEN all catalog slices (1–7) are implemented and verified
- WHEN the epic close-out runs
- THEN HU-013, HU-014, HU-015, HU-016, HU-017, HU-018, HU-019, HU-020, HU-021, HU-022, HU-023, HU-025 MUST transition to *En Revisión* in `docs/historias/`
- AND HU-024 MUST remain at its current status (already *En Revisión* from Slice 5)

#### Scenario: Notion mirror reflects HU transitions

- GIVEN the HU status transitions are complete in `docs/historias/`
- WHEN the Notion mirror task runs
- THEN the Notion database MUST reflect the same statuses as the local files

## Traceability

| Requirement | Gaps | Planning3 Source | Backend Contract |
|-------------|------|-------------------|------------------|
| Type-Aware Catalog Item Dialog | G1, G2 | HU-017, HU-021, Frontend section | fork-api R2 (store), personalization R4 (validation) |
| Service Tags Editor | G2 | HU-021, D4 | personalization R4 (S4.1 tags whitelist) |
| Changed-Fields-Only Submit | G3 | D13, HU-025 flujo step 10 | fork-api R2 (PUT merged-overrides), personalization R5 |
| Revert-to-Base Controls | G3 | D13, HU-025 | personalization R5 (S5.3 null restores inheritance) |
| Tree Node Child-Add Affordances | G1 | HU-017, HU-021 | fork-api R2 (store with parent_fork_id) |
| Differentiated Lifecycle Confirmations | G5 | planning3 UI, lifecycle flujo | fork-api R3 (status), R4 (delete) |
| Server Error Surfacing | G4 | HU-022, D8 | fork-api R7 (error contracts) |
| Move-Service Flow | G4, G6 | HU-022, D8 | fork-api R5 (move/attach) |
| Orphan-Attach Flow | G4, G6 | Slice 3 debt, R5 | fork-api R5 (S5.2 attach-orphan) |
| Per-Field Override Display | G3 | D13 | personalization R5 (overridden_fields) |
| Admin Role-Coherent Rendering | G8 | D2, policy | personalization R1 (ownership) |
| API Client Contract Extensions | G1–G6 | All frontend | fork-api R2, R5 typing |
| Page-Level Jest Coverage | G7 | planning3 "Tests Jest por página/componente" | n/a |
| HU Status Transition Documentation | Epic close | planning3 epic scope | n/a |

## Slice Traceability (7a / 7b)

| Slice | Requirements | Rationale |
|-------|-------------|-----------|
| 7a | Type-Aware Dialog, Tags Editor, Changed-Fields-Only Submit, Revert-to-Base, Child-Add Affordances, Differentiated Confirmations, Server Error Surfacing, API Client Extensions | Core CRUD forms + correctness fixes; foundational for 7b |
| 7b | Move-Service, Orphan-Attach, Per-Field Override Display, Admin Rendering, Page-Level Jest, HU Transitions | Polish flows + coverage + closure |

## Zero-Backend-Diff Boundary

This spec consumes the frozen backend contracts `user-catalog-fork-api` (R1–R7) and `user-catalog-personalization` (R1–R8) without modification. No new endpoints, no contract widening, no admin permission expansion. The `GET /rubros/{id}/categorias` route stays admin-only; rubro cascade fork remains the supported base-discovery path. Any implementation that requires a backend change is out of scope by definition.

# Catalog Frontend Specification

## Purpose

Defines the user-operable frontend surface for the catalog at the protected `/catalogo` route. This spec covers the typed API client, the Base Catalog browse/fork view, the My Catalog effective tree with status/origin badges and server-side filters, the personal rubro create/edit/status/delete lifecycle, dialog-based editing, and loading/empty/error state rendering. Scoped to what Slice 6 actually ships: rubro-level personalization. Category/service creation forms, tags editing, move/attach, override-revert controls, and differentiated confirmations are explicitly out of scope (owned by Slice 7).

## Requirements

### Requirement: Protected Catalog Route

The system SHALL expose the catalog page at `/catalogo` within the authenticated dashboard layout. The route MUST be protected by the existing authentication shell — unauthenticated users MUST NOT access the page. The sidebar navigation MUST include a "Catalog" entry linking to `/catalogo`.

#### Scenario: Authenticated user accesses catalog page

- GIVEN an authenticated user
- WHEN the user navigates to `/catalogo`
- THEN the catalog page MUST render with the "Catalog" page header
- AND the page MUST display two tabs: "Base Catalog" and "My Catalog"

#### Scenario: Unauthenticated user cannot access catalog page

- GIVEN no authenticated session
- WHEN a request navigates to `/catalogo`
- THEN the user MUST be redirected to the login page

#### Scenario: Sidebar contains Catalog entry

- GIVEN an authenticated user viewing the dashboard
- WHEN the sidebar navigation is rendered
- THEN a "Catalog" navigation entry MUST be visible
- AND clicking it MUST navigate to `/catalogo`

### Requirement: Typed Catalog API Client

The system SHALL provide a typed API client module at `src/modules/catalog/api.ts` that wraps all catalog-related HTTP calls through the existing `apiFetch` CSRF client. The client MUST export typed interfaces for `BaseRubro` (id, name, description, status) and `CatalogNode` (id, base_id, item_type, parent_fork_id, sort_order, name/title, description, value, tags, status, origin, overridden_fields, children). The client MUST export functions for: base rubro listing, user catalog tree with filters, cascade fork, personal item create/update/status-change/delete.

#### Scenario: Base rubro listing uses correct endpoint

- GIVEN the API client is called with default parameters
- WHEN `getBaseRubros()` is invoked
- THEN `apiFetch` MUST be called with `/api/rubros?status=activo`

#### Scenario: Tree filter serialization

- GIVEN the API client is called with status "desactivado" and origin "override"
- WHEN `getUserCatalogTree({ status: 'desactivado', origin: 'override' })` is invoked
- THEN `apiFetch` MUST be called with `/api/user-catalog/tree?status=desactivado&origin=override`

#### Scenario: Tree filter omits empty values

- GIVEN the API client is called with no filters
- WHEN `getUserCatalogTree()` is invoked
- THEN `apiFetch` MUST be called with `/api/user-catalog/tree` (no query string)

#### Scenario: Rubro cascade fork uses correct endpoint

- GIVEN the API client is called to fork a base rubro
- WHEN `forkBaseItem('rubro', 'base-id')` is invoked
- THEN `apiFetch` MUST be called with `/api/user-catalog/rubros/base-id/fork` and method `POST`

#### Scenario: Personal item create sends correct payload

- GIVEN the API client is called to create a personal rubro
- WHEN `createPersonalItem('rubro', { name: 'My Rubro', description: 'Desc' })` is invoked
- THEN `apiFetch` MUST be called with `/api/user-catalog/rubros`, method `POST`, and body containing `item_type: 'rubro'`, `name`, and `description`

#### Scenario: Personal item status change routes to correct endpoint

- GIVEN an active personal item of type "service" with id "svc-1"
- WHEN `updatePersonalItemStatus('service', 'svc-1', 'desactivado')` is invoked
- THEN `apiFetch` MUST be called with `/api/user-catalog/services/svc-1/deactivate` and method `PATCH`

### Requirement: Base Catalog View

The system SHALL render a "Base Catalog" tab that displays base rubros as cards. Each card MUST show the rubro name, a "Base" badge (variant "outline"), and a description. Each card MUST provide a "Select" button that triggers a cascade fork of the rubro. The view MUST include a "New personal rubro" button that opens the create dialog.

#### Scenario: Base rubros render as cards with badges

- GIVEN the base rubro listing returns two rubros ("Informática" and "Diseño")
- WHEN the Base Catalog tab renders
- THEN a card MUST be displayed for each rubro
- AND each card MUST show the rubro name with a "Base" badge
- AND each card MUST show the rubro description

#### Scenario: Select triggers cascade fork

- GIVEN the Base Catalog tab is rendered with base rubros
- WHEN the user clicks "Select" on the "Informática" card
- THEN the system MUST call `forkBaseItem('rubro', <informatica-id>)`
- AND on success the My Catalog tree MUST reload

#### Scenario: Fork failure shows error banner

- GIVEN the Base Catalog tab is rendered
- AND the fork endpoint returns an error
- WHEN the user clicks "Select" on a rubro card
- THEN an error banner MUST be displayed with the message "The rubro could not be selected."

#### Scenario: New personal rubro opens dialog

- GIVEN the Base Catalog tab is rendered
- WHEN the user clicks "New personal rubro"
- THEN the catalog item dialog MUST open in create mode

### Requirement: My Catalog View with Effective Tree

The system SHALL render a "My Catalog" tab that displays the user's personal catalog as a recursive tree (rubro → category → service). Each node MUST display effective status and origin badges. The tree MUST render descendants recursively without assuming every ancestor is present (filtered results may promote descendants without their ancestors).

#### Scenario: Tree renders nested hierarchy

- GIVEN the user catalog tree returns a rubro with nested categories and services
- WHEN the My Catalog tab renders
- THEN the rubro MUST be rendered as a top-level node
- AND each category MUST be rendered as a child of the rubro
- AND each service MUST be rendered as a child of its category

#### Scenario: Effective status badge reflects server-computed status

- GIVEN a catalog node with effective status "desactivado"
- WHEN the node is rendered in the tree
- THEN a badge with text "Inactive" and variant "destructive" MUST be displayed

#### Scenario: Effective status badge for active node

- GIVEN a catalog node with effective status "activo"
- WHEN the node is rendered in the tree
- THEN a badge with text "Active" and variant "secondary" MUST be displayed

#### Scenario: Origin badge for base-origin node

- GIVEN a catalog node with origin "base"
- WHEN the node is rendered in the tree
- THEN a badge with text "Base" and variant "outline" MUST be displayed

#### Scenario: Origin badge for override node

- GIVEN a catalog node with origin "override"
- WHEN the node is rendered in the tree
- THEN a badge with text "Override" and variant "outline" MUST be displayed

#### Scenario: Origin badge for personal node

- GIVEN a catalog node with origin "personal"
- WHEN the node is rendered in the tree
- THEN a badge with text "Personal" and variant "outline" MUST be displayed

#### Scenario: Descendants render without ancestors after filter

- GIVEN the tree API returns a service node whose rubro ancestor is not in the response
- WHEN the My Catalog tab renders
- THEN the service node MUST still be rendered
- AND no client-side ancestor assumption MUST be made

### Requirement: Server-Side Status and Origin Filters

The system SHALL provide status and origin filter controls on the My Catalog tab. Filter changes MUST trigger a server-side re-fetch of the tree with the selected filter values. The status filter MUST support values: "all", "activo", "desactivado". The origin filter MUST support values: "all", "personal", "override", "base". Selecting "all" for either filter MUST omit that parameter from the query string.

#### Scenario: Status filter triggers re-fetch

- GIVEN the My Catalog tab is rendered with status "all"
- WHEN the user selects "activo" from the status filter
- THEN the tree MUST re-fetch with `status=activo` in the query string
- AND the tree MUST display a loading state during the re-fetch

#### Scenario: Origin filter triggers re-fetch

- GIVEN the My Catalog tab is rendered with origin "all"
- WHEN the user selects "override" from the origin filter
- THEN the tree MUST re-fetch with `origin=override` in the query string

#### Scenario: All-status omits status parameter

- GIVEN the My Catalog tab is rendered with status "activo"
- WHEN the user selects "all" from the status filter
- THEN the tree MUST re-fetch without a `status` query parameter

#### Scenario: All-origin omits origin parameter

- GIVEN the My Catalog tab is rendered with origin "personal"
- WHEN the user selects "all" from the origin filter
- THEN the tree MUST re-fetch without an `origin` query parameter

### Requirement: Personal Rubro Lifecycle

The system SHALL support personal rubro create, edit, status toggle, and delete operations through the My Catalog view. Create and edit MUST use a dialog with name and description fields. Status toggle MUST call the appropriate activate/deactivate endpoint. Delete MUST use `window.confirm` for confirmation and call the delete endpoint. All lifecycle actions MUST reload the tree on success.

#### Scenario: Create personal rubro via dialog

- GIVEN the catalog item dialog is open in create mode
- WHEN the user fills in name "My Rubro" and description "Desc" and submits
- THEN `createPersonalItem('rubro', { name: 'My Rubro', description: 'Desc' })` MUST be called
- AND on success the dialog MUST close
- AND the tree MUST reload

#### Scenario: Edit personal item via dialog

- GIVEN a catalog node is selected for editing
- WHEN the user clicks the edit button on the node
- THEN the catalog item dialog MUST open in edit mode with the node's current values
- AND on submit `updatePersonalItem` MUST be called with the node's type, id, and updated input

#### Scenario: Status toggle deactivates active node

- GIVEN an active personal rubro node
- WHEN the user clicks the status toggle button
- THEN `updatePersonalItemStatus` MUST be called with status "desactivado"
- AND on success the tree MUST reload

#### Scenario: Status toggle reactivates deactivated node

- GIVEN a deactivated personal rubro node
- WHEN the user clicks the status toggle button
- THEN `updatePersonalItemStatus` MUST be called with status "activo"
- AND on success the tree MUST reload

#### Scenario: Delete with confirmation

- GIVEN a personal rubro node "My Rubro"
- WHEN the user clicks the delete button
- THEN `window.confirm` MUST be called with a message containing "My Rubro"
- AND if the user confirms, `deletePersonalItem` MUST be called with the node's type and id
- AND on success the tree MUST reload

#### Scenario: Delete cancelled by user

- GIVEN a personal rubro node
- WHEN the user clicks the delete button
- AND `window.confirm` returns false
- THEN `deletePersonalItem` MUST NOT be called

#### Scenario: Lifecycle action failure shows error banner

- GIVEN a lifecycle action (create/edit/status/delete) fails
- WHEN the API call rejects
- THEN an error banner MUST be displayed with an appropriate message

### Requirement: Catalog Item Dialog

The system SHALL provide a dialog component for creating and editing personal catalog items. The dialog MUST adapt its fields based on the item type: rubro and category items show name and description; service items show title, description, and value (numeric, min 0). The dialog MUST display "New personal rubro" title in create mode and "Edit personal item" in edit mode. The save button MUST be disabled while saving.

#### Scenario: Dialog in create mode for rubro

- GIVEN the dialog is opened with no existing item
- WHEN the dialog renders
- THEN the title MUST be "New personal rubro"
- AND the form MUST show name and description inputs
- AND the value input MUST NOT be shown

#### Scenario: Dialog in edit mode for service

- GIVEN the dialog is opened with a service node (title "Logo", value 100)
- WHEN the dialog renders
- THEN the title MUST be "Edit personal item"
- AND the name input MUST be pre-filled with "Logo"
- AND the value input MUST be pre-filled with "100"
- AND the value input MUST be of type "number" with min "0"

#### Scenario: Save button disabled during submission

- GIVEN the dialog is open with valid input
- WHEN the user clicks "Save"
- THEN the save button MUST display "Saving…" and be disabled
- AND the button MUST be re-enabled after the save completes (success or failure)

#### Scenario: Dialog closes on cancel

- GIVEN the dialog is open
- WHEN the user clicks "Cancel"
- THEN the dialog MUST close without calling save

### Requirement: Loading, Empty, and Error States

The system SHALL render appropriate states for the Base Catalog and My Catalog views. While data is loading, a skeleton MUST be displayed. When the base rubro listing returns zero items, an empty state message MUST be displayed. When any API call fails, an error banner MUST be displayed at the top of the page.

#### Scenario: Base catalog loading state

- GIVEN the page has just loaded
- AND the base rubro listing has not yet resolved
- WHEN the Base Catalog tab renders
- THEN a table skeleton MUST be displayed

#### Scenario: Base catalog empty state

- GIVEN the base rubro listing returns an empty array
- WHEN the Base Catalog tab renders after loading completes
- THEN an empty state MUST be displayed with message "No base rubros are available."

#### Scenario: My catalog loading state

- GIVEN the page has just loaded or a filter has changed
- AND the tree has not yet resolved
- WHEN the My Catalog tab renders
- THEN a table skeleton MUST be displayed

#### Scenario: Error banner on base catalog failure

- GIVEN the base rubro listing API call fails
- WHEN the Base Catalog tab renders
- THEN an error banner MUST be displayed with role "alert"
- AND the banner MUST contain the message "The base catalog could not be loaded."

#### Scenario: Error banner on tree failure

- GIVEN the user catalog tree API call fails
- WHEN the My Catalog tab renders
- THEN an error banner MUST be displayed with role "alert"
- AND the banner MUST contain the message "Your catalog could not be loaded."

### Requirement: Focused Jest Tests

The system SHALL include focused Jest test suites for the catalog API client and the catalog tree component. The API tests MUST verify filter serialization and the fork route contract. The tree tests MUST verify descendant rendering and effective status/origin badge display.

#### Scenario: API test verifies filter serialization

- GIVEN the Jest test suite for the catalog API
- WHEN the filter serialization test runs
- THEN it MUST assert that `getUserCatalogTree({ status: 'desactivado', origin: 'override' })` calls `apiFetch` with `/api/user-catalog/tree?status=desactivado&origin=override`

#### Scenario: API test verifies fork route contract

- GIVEN the Jest test suite for the catalog API
- WHEN the fork route test runs
- THEN it MUST assert that `forkBaseItem('rubro', 'base-id')` calls `apiFetch` with `/api/user-catalog/rubros/base-id/fork` and method `POST`

#### Scenario: Tree test verifies descendants and badges

- GIVEN the Jest test suite for the catalog tree component
- WHEN the descendant rendering test runs with a rubro node containing a service child
- THEN it MUST assert that both the rubro name and the service title are rendered
- AND it MUST assert that effective status badges ("Inactive") appear for both nodes
- AND it MUST assert that origin badges ("Override" and "Base") appear correctly

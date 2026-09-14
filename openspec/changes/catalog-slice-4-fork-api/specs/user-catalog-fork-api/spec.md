# User Catalog Fork API Specification

## Purpose

HTTP surface over the Slice 3 personalization engine: `/api/user-catalog/*` under `auth:sanctum`, outside `role:admin`, `{type}` ∈ `rubros|categorias|services`. Thin controllers; engine passthrough; one render map owns all error codes. Base-catalog surface unchanged. Planning: `docs/planning/planning3.md` Slice 4.

## Requirements

### Requirement: R1 Fork Endpoints (Cascade)

`POST /api/user-catalog/{type}/{baseId}/fork` MUST delegate to the cascade engine: rubro forks rubro + its categoria/service descendants; categoria forks categoria + its services; service forks the single service (D12, R7 of `user-catalog-personalization`). It MUST be the ONLY fork-creation path. Success MUST return 201 with a tree summary: root fork `id`, `item_type`, created-item `counts` per type, and `ids` of every created fork. A duplicate base identity at root OR at any descendant (J1) MUST render 409. Any failure MUST persist zero rows and MUST leave base rows unmodified. Unknown `baseId` MUST → 404.

#### Scenario: S1.1-rubro-cascade-summary

- GIVEN base rubro with 3 categorias + 6 services, none forked
- WHEN owner POSTs `rubros/{id}/fork`
- THEN 201: root `id`, `item_type=rubro`, counts {rubro:1, categoria:3, service:6}, 10 `ids`, parent/base links correct

#### Scenario: S1.2-categoria-cascade

- GIVEN base categoria with services, none forked
- WHEN owner POSTs `categorias/{id}/fork`
- THEN 201: categoria + service forks persisted atomically; summary lists their ids

#### Scenario: S1.3-duplicate-root-409

- GIVEN user already forked base X
- WHEN re-forking X via endpoint
- THEN 409; zero new rows

#### Scenario: S1.4-duplicate-descendant-409

- GIVEN user holds a fork of one categoria under base rubro R (J1)
- WHEN POST fork R
- THEN 409; zero rows persist (atomic rollback)

#### Scenario: S1.5-unknown-base-404

- GIVEN nonexistent base uuid
- WHEN POST fork
- THEN 404

#### Scenario: S1.6-fork-deactivated-base

- GIVEN base rubro deactivated by admin
- WHEN owner forks it
- THEN 201; copies created, own `status` activo, resolve desactivado via R6

### Requirement: R2 Item CRUD (Store, Show, Update)

`POST /api/user-catalog/{type}` MUST create owner-scoped PERSONAL items only (`base_id` null, own `status` `activo`); a non-null `base_id` MUST be rejected 422 with a message pointing to the fork endpoint. `GET /api/user-catalog/{type}/{forkId}` MUST return 200 with the resolved view: per-field inherited/overridden values, effective `status`, `origin`, `overridden_fields`, plus structural keys `item_type`, `parent_fork_id`, `sort_order`. `PUT /api/user-catalog/{type}/{forkId}` MUST apply merged overrides (explicit null removes an override; omitted fields untouched). Payload with `status` or any unknown field MUST be 422 (nothing persists). For rubro, explicit `parent_fork_id: null` MUST be legal and equivalent to absent (J4, test-pinned), persisting null; non-null parent MUST be 422.

#### Scenario: S2.1-store-personal

- GIVEN authenticated owner posts rubro {name}
- WHEN store succeeds
- THEN 201; row owned by caller, `base_id` null, own `status` activo

#### Scenario: S2.2-store-baseid-rejected

- GIVEN store body with non-null `base_id`
- WHEN POST
- THEN 422 citing the fork endpoint; no row created

#### Scenario: S2.3-show-resolved-with-structural-keys

- GIVEN fork overriding title only
- WHEN GET show
- THEN 200: title=override, value=CURRENT base value, overridden fields listed, effective status, and `item_type`/`parent_fork_id`/`sort_order` present

#### Scenario: S2.4-update-null-removes-override

- GIVEN overridden field
- WHEN PUT it explicit null
- THEN 200; override gone; field inherits base again

#### Scenario: S2.5-status-or-unknown-field-422

- GIVEN PUT body containing `status` or any unknown key
- WHEN validated
- THEN 422; `overrides` and `status` column untouched

#### Scenario: S2.6-j4-rubro-null-parent

- GIVEN store/update rubro with explicit `parent_fork_id: null`
- WHEN processed
- THEN 201/200; `parent_fork_id` persists null (legal, ≡ absent)

#### Scenario: S2.7-rubro-parent-non-null-422

- GIVEN store/update rubro with non-null `parent_fork_id`
- WHEN validated
- THEN 422; nothing persists

### Requirement: R3 Status Endpoints (Dedicated, D3/D7)

`PATCH /api/user-catalog/{type}/{forkId}/deactivate` and `.../reactivate` MUST be the ONLY status-mutation surface; each MUST write only the item's own `status` column (`desactivado`/`activo`) — never `deleted_at`, never `overrides`. Reactivation MUST touch ONLY the selected item (D7). Deactivation MUST NOT change descendants' own status; descendant visibility is blocked via effective status (R6) only. Authorization reuses the owner update ability (non-owner → 403). Both MUST return 200 with the updated resolved view.

#### Scenario: S3.1-deactivate-hides-subtree

- GIVEN rubro fork with activo categoria/service descendants
- WHEN deactivate rubro
- THEN 200; rubro own `status` desactivado; descendants' own `status` still activo but resolve desactivado (R6)

#### Scenario: S3.2-reactivate-only-selected

- GIVEN desactivado rubro fork holding a descendant individually deactivated earlier
- WHEN reactivate rubro
- THEN 200; only rubro flips activo; that descendant stays desactivado (D7)

#### Scenario: S3.3-reactivate-below-dead-ancestor

- GIVEN service activo whose rubro ancestor is desactivado
- WHEN reactivate the service
- THEN 200; own `status` activo; show still resolves desactivado

#### Scenario: S3.4-status-via-update-rejected

- GIVEN PUT body {status: desactivado}
- WHEN validated
- THEN 422; status unchanged (only dedicated endpoints mutate it)

### Requirement: R4 Cascade Soft-Delete (D9)

`DELETE /api/user-catalog/{type}/{forkId}` MUST recursively soft-delete the item and ALL its fork descendants (`deleted_at` set), scoped to the caller's own rows in-query. It MUST NOT touch base rows or any other user's rows. Success MUST return 204. Soft-deleted ids MUST behave as missing (404). Per amended D5, the identity index excludes deleted rows, so re-forking the same base after a cascade delete MUST be legal: new rows are created; old rows stay soft-deleted (no restore).

#### Scenario: S4.1-subtree-soft-deleted

- GIVEN rubro fork with categoria+service descendants
- WHEN owner DELETEs the rubro fork
- THEN 204; every subtree row has `deleted_at` set; base rows unmodified

#### Scenario: S4.2-other-users-intact

- GIVEN another user forked a descendant base of the same rubro
- WHEN caller cascade-deletes their own rubro fork
- THEN 204; the other user's rows remain undeleted

#### Scenario: S4.3-refork-after-delete

- GIVEN rubro fork cascade-deleted (S4.1)
- WHEN same user POSTs fork of the same base rubro
- THEN 201; fresh subtree created; old rows remain deleted (decision 4/D5 amendment)

#### Scenario: S4.4-deleted-is-404

- GIVEN a soft-deleted fork id
- WHEN GET/PUT/PATCH-deactivate/DELETE it
- THEN 404

### Requirement: R5 Move/Attach (parent_fork_id Update)

`PUT` MUST accept `parent_fork_id` to (a) move a fork between type-coherent parents and (b) attach standalone/orphan forks (null parent → valid parent). Every guard violation MUST be 422 with parent unchanged: target type-coherence (categoria under rubro fork; service under categoria fork; rubro NEVER acquires a parent); parent must be an existing, non-deleted fork owned by the caller; no cycles (parent may not be the item itself or any descendant); sibling visible-name uniqueness re-checked at the destination (D8). A move MUST NOT modify `status`; post-move visibility is re-derived by effective status (R6) on the new chain. Absent `parent_fork_id` MUST leave the parent untouched.

#### Scenario: S5.1-move-service-between-categorias

- GIVEN service fork S under categoria fork A
- WHEN PUT S with parent = owner's categoria fork B
- THEN 200; S nested under B in tree; overrides untouched

#### Scenario: S5.2-attach-orphan

- GIVEN standalone categoria fork (null parent, born via fork endpoint)
- WHEN PUT parent = owner's rubro fork
- THEN 200; attached under the rubro

#### Scenario: S5.3-cycle-rejected

- GIVEN fork F
- WHEN PUT parent = F (self/own descendant)
- THEN 422; parent unchanged

#### Scenario: S5.4-destination-title-clash

- GIVEN non-deleted sibling "T" already under target parent
- WHEN move an item titled "T" there
- THEN 422 (D8); item stays under old parent

#### Scenario: S5.5-type-incoherent-parent

- GIVEN service fork
- WHEN PUT parent = rubro fork (or any non-categoria)
- THEN 422; parent unchanged

#### Scenario: S5.6-rubro-never-parented

- GIVEN rubro fork
- WHEN PUT non-null parent (any type)
- THEN 422; parent remains null

#### Scenario: S5.7-foreign-parent

- GIVEN parent id belonging to another user's fork
- WHEN PUT it as parent
- THEN 422; parent unchanged

#### Scenario: S5.8-move-under-deactivated

- GIVEN move target categoria fork own-deactivated
- WHEN service moved there
- THEN 200; service own `status` unchanged; resolves desactivado via R6 on new chain

### Requirement: R6 Tree/List Endpoint

`GET /api/user-catalog/tree` MUST return the caller's complete non-deleted fork hierarchy, nested downward (roots = null parent), scoped by `user_id` in-query. Each node MUST carry the resolved payload (effective `status`, `origin`, `overridden_fields`) plus `item_type`, `parent_fork_id`, `sort_order`; siblings MUST be ordered by `sort_order`. `status=all|activo|desactivado` MUST filter on EFFECTIVE status (recursive R6, post-resolve) without altering nesting of survivors. `origin=personal|override|base` MUST filter on the resolved origin (R5 of personalization). The request MUST execute within a test-pinned CONSTANT query budget independent of item count (D-4 eager downward graph; no N+1). No pagination (full set). Invalid filter value MUST → 422.

#### Scenario: S6.1-tree-shape-and-order

- GIVEN owner's rubro→2 categorias→3 services; other user holds rows
- WHEN GET tree
- THEN 200: only owner's nested graph; nodes include item_type/parent_fork_id/sort_order; siblings ordered by sort_order

#### Scenario: S6.2-effective-status-filter

- GIVEN service own-activo under a desactivado rubro fork
- WHEN GET tree?status=activo
- THEN service excluded; WHEN ?status=desactivado it is included (effective, not own)

#### Scenario: S6.3-origin-filter

- GIVEN personal item, fork-with-overrides, plain fork
- WHEN GET tree?origin=personal / override / base
- THEN each value returns exactly the matching subset

#### Scenario: S6.4-query-budget

- GIVEN trees of N and 2N items
- WHEN GET tree each
- THEN identical query count ≤ pinned budget; effective filter adds no per-node queries

#### Scenario: S6.5-invalid-filter-422

- GIVEN ?status=bogus
- WHEN GET tree
- THEN 422

### Requirement: R7 Error Contracts (Single Render Map)

All failures on this surface MUST render JSON; no scenario below MAY surface as 500. Both duplicate paths — engine `DomainException` AND DB partial-index `QueryException` (race past app checks) — MUST produce the SAME 409 shape via ONE render map (`bootstrap/app.php`).

#### Scenario: S7.1-401-unauthenticated

- GIVEN no session
- WHEN GET tree (any fork route)
- THEN 401 JSON

#### Scenario: S7.2-403-cross-owner

- GIVEN A-owned fork; actor B or admin
- WHEN GET/PUT/PATCH-status/DELETE it
- THEN 403 (admin-denied carryover, R1 personalization)

#### Scenario: S7.3-404-missing-ids

- GIVEN unknown or soft-deleted fork id / unknown base id
- WHEN touched via show/update/status/delete/fork
- THEN 404

#### Scenario: S7.4-409-duplicate-service-path

- GIVEN user re-forks an owned base (engine duplicate check)
- WHEN service throws DomainException
- THEN 409 {message}; zero rows

#### Scenario: S7.5-409-index-race-path

- GIVEN duplicate slipping past app checks hits partial unique index
- WHEN QueryException raises
- THEN same 409 JSON shape, both engines (SQLite + PostgreSQL)

#### Scenario: S7.6-422-validation

- GIVEN any invalid payload (unknown field, status, non-null base_id on store, incoherent parent)
- WHEN submitted
- THEN 422 with field-level errors; nothing persists

## Out-of-scope (non-requirement note — planning silent, deferred)

Pagination; rate limiting; HTTP caching; sorting beyond `sort_order`; status-change auditing. Frontend = Slices 6–7; seeders = Slice 5.

## Traceability

HU-013–HU-025 API surface; decisions D3/D5(amended)/D7/D8/D9/D12; locked user decisions 1–6 (`docs/planning/planning3.md` Slice 4).

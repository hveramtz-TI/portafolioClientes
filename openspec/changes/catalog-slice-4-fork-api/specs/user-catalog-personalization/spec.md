# Delta for User Catalog Personalization

## MODIFIED Requirements

### Requirement: R3 Fork Identity and Structural Uniqueness

Second fork of same base by same user+`item_type` MUST be rejected; at the HTTP surface the rejection MUST render 409 (fork-api R1/R7). Personal items MUST have unique visible name/title among non-deleted siblings per `parent_fork_id`; that uniqueness MUST be re-checked at the destination when `parent_fork_id` changes (D8). `parent_fork_id` MUST be type-coherent: rubro→none, categoria→rubro fork, service→categoria fork, and MUST reference an existing, non-deleted fork of the same owner. `parent_fork_id` becomes WRITABLE via update for type-coherent moves and orphan attachments (D8 + standalone roots), guarded cycle-safe: an item MUST NEVER be reparented into itself or one of its descendants. For rubro, explicit `parent_fork_id: null` MUST be equivalent to absent — legal, persisting null (J4, test-pinned). Identity is bounded to non-deleted rows: after a fork is soft-deleted, forking the same base again MUST be legal.
(Previously: identity enforced app-level only; `parent_fork_id` immutable after creation; rubro explicit-null parent contract unpinned; re-fork-after-delete undefined.)

#### Scenario: S3.1-duplicate-fork-rejected

- GIVEN A forked base X
- WHEN A re-forks X
- THEN rejected (409 over HTTP)

#### Scenario: S3.2-sibling-title-uniqueness

- GIVEN personal "T" under P
- WHEN non-deleted sibling "T" submitted
- THEN rejected; distinct title accepted

#### Scenario: S3.3-wrong-parent-type

- GIVEN categoria stored
- WHEN parent not a rubro fork
- THEN rejected

#### Scenario: S3.4-j4-rubro-null-parent-equivalent

- GIVEN rubro payload with explicit `parent_fork_id: null`
- WHEN validated
- THEN accepted; persists null, identical to omitted

#### Scenario: S3.5-refork-after-soft-delete

- GIVEN A's fork of base X soft-deleted
- WHEN A forks X again
- THEN accepted; new live row; deleted row untouched

#### Scenario: S3.6-move-attach-legal-reparent-rejected

- GIVEN standalone categoria fork (null parent) and owner's rubro fork R
- WHEN update sets parent=R
- THEN parent persists R; WHEN parent is own descendant or foreign/deleted fork → rejected, parent unchanged

### Requirement: R4 Field Validation

`value` MUST be integer ≥0; `tags` MUST be within ['frontend','backend','fullstack','devops','mobile']; `status` MUST NEVER enter overrides; payload with it MUST be rejected (422) — status changes flow exclusively through the dedicated deactivate/reactivate endpoints (fork-api R3); unknown fields MUST NOT persist. Store MUST reject non-null `base_id` with 422: base items are forked ONLY via the dedicated cascade endpoint (D12), never created via store. `parent_fork_id` is now an accepted update key (R3 move/attach contract); `base_id`/`item_type` remain non-writable.
(Previously: store accepted `base_id` as fork-identity input; all structural keys including `parent_fork_id` rejected on update; "later change" endpoints anticipated by S4.2 now exist.)

#### Scenario: S4.1-tags-whitelist

- GIVEN payload `tags`
- WHEN all in-list / any outside
- THEN pass / fail

#### Scenario: S4.2-status-rejected

- GIVEN payload `status`
- WHEN validated
- THEN 422; changes only via dedicated deactivate/reactivate endpoints

#### Scenario: S4.3-store-base-id-rejected

- GIVEN store payload with non-null `base_id`
- WHEN validated
- THEN 422 pointing to fork endpoint; no row persists

### Requirement: R5 Per-Field Inheritance and Origin

Resolved value MUST be the override when present, else CURRENT base value (dynamic: base edits propagate, no re-sync). Origin MUST be personal (null `base_id`) | override (any) | base; overridden fields MUST be exposed. Explicit null MUST remove that override (restoring inheritance); omitted fields MUST remain untouched. The resolved payload MUST additionally expose the structural keys `item_type`, `parent_fork_id`, `sort_order`.
(Previously: resolved payload lacked structural keys; tree/UI consumers were blocked.)

#### Scenario: S5.1-base-edit-propagates

- GIVEN field lacking override
- WHEN base value changes
- THEN new base value, origin base

#### Scenario: S5.2-override-wins

- GIVEN overridden fields
- WHEN resolved
- THEN override wins, origin override, listed

#### Scenario: S5.3-null-restores-inheritance

- GIVEN overridden field
- WHEN updated null
- THEN override removed, inherits

#### Scenario: S5.4-structural-keys-exposed

- GIVEN any fork resolved
- WHEN payload built
- THEN contains item_type, parent_fork_id, sort_order

## ADDED Requirements

### Requirement: R8 Database Identity Guard (Amended D5)

Fork identity (`user_id`,`item_type`,`base_id`) MUST additionally be enforced by a DB partial unique index on `user_catalog_items` `WHERE base_id IS NOT NULL AND deleted_at IS NULL`, on BOTH engines (SQLite + PostgreSQL) via raw guarded, reversible migration. App-level validation remains the first line; a race past it MUST raise an index violation surfaced as `QueryException` (rendered 409 by fork-api R7). Personal items (`base_id` null) and soft-deleted rows MUST stay outside the index. Sibling visible-name uniqueness (JSON `overrides`-derived) remains application-level — not indexable cross-engine.
(Previously: D5 relied on application validation only; documented race accepted.)

#### Scenario: S8.1-index-blocks-raw-duplicate

- GIVEN live row (U,service,B)
- WHEN insert second identical triple directly, bypassing app
- THEN QueryException on both engines

#### Scenario: S8.2-null-and-deleted-exempt

- GIVEN (U,service,B) soft-deleted plus personal rows with null `base_id`
- WHEN re-fork B / insert personals with same name
- THEN index does not block; inserts succeed

## Traceability

Slice 4 amendments to `openspec/specs/user-catalog-personalization/spec.md`; decisions D5(amended)/D8/D12/J4; locked user decisions 1–4, 6.

# User Catalog Personalization Specification

## Purpose

Fork base items into private `user_catalog_items`, override fields without touching the base, resolve a dynamic effective view. Domain engine only; endpoints = Slice 4 → MUST be testable without routes.

## Requirements

### Requirement: R1 Fork Ownership

Owner SHALL be the only actor able to view/modify/delete own `user_catalog_items`; the admin role MUST NOT grant access to others' forks.

#### Scenario: S1.1-owner-allowed

- GIVEN fork owned by A
- WHEN A acts
- THEN allowed

#### Scenario: S1.2-others-forbidden

- GIVEN A-owned fork
- WHEN B or admin acts
- THEN denied (403 semantics)

### Requirement: R2 Base Reference Resolution

Non-null `base_id` MUST resolve `base()` to the model for `item_type` (rubro→Rubro, categoria→Categoria, service→Service); null MUST resolve to none.

#### Scenario: S2.1-fork-resolves-base

- GIVEN service fork, non-null `base_id`
- WHEN `base()` resolves
- THEN Service returned

#### Scenario: S2.2-personal-no-base

- GIVEN null `base_id`
- WHEN `base()` resolves
- THEN null

### Requirement: R3 Fork Identity and Structural Uniqueness

Second fork of same base by same user+`item_type` MUST be rejected; personal items MUST have unique visible name/title among non-deleted siblings per `parent_fork_id`; `parent_fork_id` MUST be type-coherent: rubro→none, categoria→rubro fork, service→categoria fork.

#### Scenario: S3.1-duplicate-fork-rejected

- GIVEN A forked base X
- WHEN A re-forks X
- THEN rejected

#### Scenario: S3.2-sibling-title-uniqueness

- GIVEN personal "T" under P
- WHEN non-deleted sibling "T" submitted
- THEN rejected; distinct title accepted

#### Scenario: S3.3-wrong-parent-type

- GIVEN categoria stored
- WHEN parent not a rubro fork
- THEN rejected

### Requirement: R4 Field Validation

`value` MUST be integer ≥0; `tags` MUST be within ['frontend','backend','fullstack','devops','mobile']; `status` MUST NEVER enter overrides; payload with it MUST be rejected (422); unknown fields MUST NOT persist.

#### Scenario: S4.1-tags-whitelist

- GIVEN payload `tags`
- WHEN all in-list / any outside
- THEN pass / fail

#### Scenario: S4.2-status-rejected

- GIVEN payload `status`
- WHEN validated
- THEN 422; changes only via dedicated endpoints (later change)

### Requirement: R5 Per-Field Inheritance and Origin

Resolved value MUST be the override when present, else CURRENT base value (dynamic: base edits propagate, no re-sync). Origin MUST be personal (null `base_id`) | override (any) | base; overridden fields MUST be exposed. Explicit null MUST remove that override (restoring inheritance); omitted fields MUST remain untouched.

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

### Requirement: R6 Effective Status

Effective status MUST be desactivado if own, base, or ANY ancestor fork is desactivado; an ancestor counts as desactivado by its OWN effective status (recursive: ancestor's own status, its base, its ancestors — full-chain AND per `docs/flujos/rubro-categoria-servicio-lifecycle.md`); activo only when the whole chain is active; no override MAY win against a deactivated ancestor.

#### Scenario: S6.4-deactivated-base-ancestor-cascades

- GIVEN rubro base deactivated by admin; user forks cascade down to a service
- WHEN the service fork resolves
- THEN desactivado (ancestor rubro fork is effectively desactivado through its base)

#### Scenario: S6.1-deactivated-ancestor

- GIVEN categoria fork activo under desactivado rubro fork
- WHEN resolved
- THEN desactivado

#### Scenario: S6.2-base-deactivated

- GIVEN base deactivated by admin
- WHEN its forks resolve
- THEN desactivado

#### Scenario: S6.3-chain-active

- GIVEN item+base+ancestors activo
- WHEN resolved
- THEN activo

### Requirement: R7 Cascade Fork

Forking a rubro MUST atomically create its fork + one per related categoria + one per service, REGARDLESS of base status. Created forks: own `status='activo'`, empty overrides; deactivated items governed by R6 → forks resolve desactivado, independently reactivable (D7). Any failure MUST roll back everything (zero rows); base MUST remain unmodified. Standalone categoria fork MUST copy its services atomically.

#### Scenario: S7.1-rubro-cascade-tree

- GIVEN rubro: 3 categorias, 6 services
- WHEN forked
- THEN 1+3+6 items, correct parent_fork_id/base_id links, empty overrides

#### Scenario: S7.2-mid-copy-rollback

- GIVEN cascade copy fails
- WHEN transaction aborts
- THEN zero fork rows persist

#### Scenario: S7.3-standalone-categoria-cascade

- GIVEN categoria+services forked directly
- WHEN completes
- THEN categoria fork + service forks persisted atomically

#### Scenario: S7.4-deactivated-descendants-copied

- GIVEN rubro with desactivado categoria+services
- WHEN forked
- THEN copied, own status activo, resolve desactivado via R6

## Traceability

HU-013/017/021/025; decisions D3/D5/D6/D12/D13 (`docs/planning/planning3.md`).

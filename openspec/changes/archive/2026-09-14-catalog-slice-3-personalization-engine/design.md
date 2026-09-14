# Design: Catalog Slice 3 — Personalization Engine

## Technical Approach

Implement the domain engine without routes or controllers. Slice 3a adds the
relation, policy, and two Form Requests; Slice 3b adds the resolver and
transactional cascade service. Strict TDD drives each slice on SQLite and PostgreSQL.

## Architecture Decisions

| Decision | Choice | Alternatives rejected | Rationale |
|---|---|---|---|
| D-1 base relation | `UserCatalogItem::base()` uses `morphTo(__FUNCTION__, 'item_type', 'base_id')`; aliases are registered in `AppServiceProvider::boot()` with a morph map: `rubro`, `categoria`, `service`. | Accessor/query switch; class names in `item_type`; model-local mapping. | Laravel documents explicit custom morph columns and morph maps for simple stored aliases ([Eloquent relationships](https://laravel.com/docs/13.x/eloquent-relationships)). Central boot registration is the idiomatic application-wide mapping and keeps the model change additive. |
| D-2 policy | Implement `view`, `update`, and `delete`; each returns true only for the authenticated owner. Omit `viewAny` and `restore` because this engine has no collection authorization endpoint or restore operation. Register `UserCatalogItemPolicy` through Laravel 13 policy discovery (`App\Models` → `App\Policies`); admin is explicitly denied for another user's fork. | Admin bypass; controller-only checks; manual Gate registration. | R1 requires owner-only access, including admins. Conventional discovery avoids configuration and matches Laravel authorization conventions ([Policies](https://laravel.com/docs/13.x/authorization)). |
| D-3 requests | One store/update pair, driven by `item_type`. `authorize()` authenticates store and calls `can('update', item)` for update. Rules enforce scoped identity/name uniqueness, soft-delete exclusion (`withoutTrashed()`), parent type, tags, UUIDs, and `value >= 0`; `status` and unknown fields fail 422. `sometimes|nullable` preserves omitted-vs-null semantics; `array_key_exists` removes an override on null. | Three request pairs; ignoring unknown keys; `nullable` alone. | One pair centralizes the contract. Laravel supports scoped `Rule::unique`, `withoutTrashed`, policy authorization, and unknown-field rejection ([Validation](https://laravel.com/docs/13.x/validation)). `sometimes` prevents null structural values. The application-level race window is accepted per D5. |
| D-4 resolver | Expose `resolve(item): array`, `effectiveStatus(item): string`, and `origin(item): string`; return `id`, `base_id`, `name/title`, `value`, `description`, `tags`, effective `status`, `origin`, and `overridden_fields`. Eager-load `parentFork` and `base` through depth 3. | Recursive loading; Redis/request cache. | The bounded eager graph avoids N+1. Memoization is omitted for KISS because the chain is shallow. |
| D-5 cascade | `CascadeForkService::forkRubro`, `forkCategoria`, and `forkService` each own one `DB::transaction`; duplicate identity is checked inside it. Copy non-deleted children regardless of base status, with `status='activo'`, empty overrides, and correct parent links. Propagate exceptions and return a tree summary. | Per-row transactions; active-only copies; bulk SQL; swallowed failures. | One transaction guarantees zero partial forks and untouched bases. R7 requires deactivated descendants to be copied and resolved through R6. |
| D-6 tests | Unit tests cover resolver inheritance/origin/status matrices, identity guards, and forced rollback. Feature tests cover Gate policy decisions and request rules with `Validator::make`, `RefreshDatabase`, model creation, and Sanctum conventions. Run both engines and Pint. | Route-only tests; factories for catalog bases. | No HTTP surface exists; direct domain tests prove frozen scenarios and match existing conventions. |
| D-7 delivery | 3a: model relation, policy, two requests, and tests (≤400 lines). 3b: resolver, cascade service, and tests (≤400 lines). Shared helpers belong to the first slice that needs them. | One oversized PR; premature interfaces. | Preserves review budget and dependency order while following feature-first, KISS/YAGNI layering. |

## Data Flow

```text
Request → FormRequest rules → UserCatalogItem/Policy
                         ↓
                 Resolver → eager base/parent chain → resolved view
Base ID + User → CascadeForkService → DB transaction → fork tree summary
```

## File Changes

| File | Action | Description |
|---|---|---|
| `backend/app/Models/UserCatalogItem.php` | Modify | Add custom-column `base()` morph relation. |
| `backend/app/Providers/AppServiceProvider.php` | Modify | Register merged `item_type` morph aliases. |
| `backend/app/Policies/UserCatalogItemPolicy.php` | Create | Owner-only authorization, including admin denial. |
| `backend/app/Http/Requests/{Store,Update}UserCatalogItemRequest.php` | Create | Shared type-driven validation and null/omission handling. |
| `backend/app/Services/{CatalogResolver,CascadeForkService}.php` | Create | Resolution and atomic cascade domain services. |
| `backend/tests/Unit/*`, `backend/tests/Feature/*` | Create | R1–R7 and S1.1–S7.4 coverage. |

## Interfaces / Contracts

```php
public function resolve(UserCatalogItem $item): array;
public function effectiveStatus(UserCatalogItem $item): string;
public function origin(UserCatalogItem $item): string;
public function forkRubro(string $baseId, User $user): array;
public function forkCategoria(string $baseId, User $user): array;
public function forkService(string $baseId, User $user): array;
```

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Unit | R2, R3, R5, R6, R7; S2.1–S7.4 | RED-first PHPUnit tests, explicit nulls, deactivated bases/ancestors, forced mid-transaction exception and zero-row assertion. |
| Feature | R1 and R3/R4 request contracts | Gate `allow()` checks plus `Validator::make($data, $request->rules())`; SQLite and PostgreSQL. |
| Formatting | Changed PHP | Laravel Pint. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No migration required. No new dependencies, routes, controllers, or feature flags. Morph registration is additive and base rows remain untouched.

## Open Questions

- None; framework decisions are verified against the cited Laravel 13 documentation.

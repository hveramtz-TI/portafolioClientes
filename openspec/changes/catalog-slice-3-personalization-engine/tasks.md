# Tasks: Catalog Slice 3 — Personalization Engine

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines (3a) | 280–340 |
| Estimated changed lines (3b) | 260–330 |
| 400-line budget risk | Low |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (3a) → PR 2 (3b) |
| Delivery strategy | ask-on-risk |
| Chain strategy | feature-branch-chain |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 3a-relation-policy | Morph relation + owner-only policy + tests | PR 1 | `docker compose exec backend php artisan test --filter=UserCatalogItemPolicyTest` | N/A (unit/feature, no external deps) | Revert `UserCatalogItem.php`, `AppServiceProvider.php`, `UserCatalogItemPolicy.php`, test files |
| 3a-requests | Store/Update requests with validation + tests | PR 1 | `docker compose exec backend php artisan test --filter=UserCatalogItemRequestTest` | N/A (unit/feature, no external deps) | Revert two request files + their tests |
| 3b-resolver | CatalogResolver (resolve/origin/effectiveStatus) + tests | PR 2 | `docker compose exec backend php artisan test --filter=CatalogResolverTest` | N/A (unit, no external deps) | Revert `CatalogResolver.php` + test files |
| 3b-cascade | CascadeForkService (forkRubro/Categoria/Service) + tests | PR 2 | `docker compose exec backend php artisan test --filter=CascadeForkServiceTest` | N/A (unit, DB transaction) | Revert `CascadeForkService.php` + test files |

## Phase 1: Setup & Slice 3a Branch

- [ ] 1.1 Create branch `feat/catalog-slice-3a` from tracker `feat/catalog-rubros-categorias-servicios`
- [ ] 1.2 Acquire ledger token: `gentle-ai sdd-attempt acquire --cwd /home/hgvm/Documentos/GitHub/portafolioClientes --change catalog-slice-3-personalization-engine --request-id 3a-relation-policy --work-unit 3a-relation-policy --evidence-goal "R1,R2 policy+relation green on SQLite+PG" --max-attempts 3 --max-changed-lines 350`

## Phase 2: Slice 3a — Relation, Policy, Requests (TDD per unit)

### Unit 3a-relation-policy (R1, R2; S1.1, S1.2, S2.1, S2.2)

- [x] 2.1 RED: Write failing tests `backend/tests/Feature/UserCatalogItemPolicyTest.php` covering S1.1 (owner allowed), S1.2 (other/admin denied); `backend/tests/Feature/UserCatalogItemRelationTest.php` covering S2.1 (fork resolves base), S2.2 (personal null base)
- [x] 2.2 GREEN: Implement `backend/app/Models/UserCatalogItem.php` — add `base()` morphTo with custom columns `item_type`, `base_id`
- [x] 2.3 GREEN: Implement `backend/app/Providers/AppServiceProvider.php` — register morph map `['rubro' => Rubro::class, 'categoria' => Categoria::class, 'service' => Service::class]` in `boot()`
- [x] 2.4 GREEN: Implement `backend/app/Policies/UserCatalogItemPolicy.php` — `view`, `update`, `delete` return true only for authenticated owner; admin denied
- [x] 2.5 REFACTOR: Clean up, ensure Pint passes
- [ ] 2.6 VERIFY: Run `docker compose exec backend php artisan test --filter=UserCatalogItemPolicyTest,UserCatalogItemRelationTest` (SQLite) + `./test-pg.sh --filter=UserCatalogItemPolicyTest,UserCatalogItemRelationTest` (PostgreSQL) + `docker compose exec backend ./vendor/bin/pint --test`
- [ ] 2.7 Commit work unit: `feat(catalog): add base() morph relation + owner-only policy (R1,R2)` with tests
- [ ] 2.8 Settle ledger (orchestrator-owned): native `gentle-ai sdd-attempt settle` with the exact flags returned by this change's dispatcher phaseInstructions — `--token <acquire-token>`, `--outcome passed|failed`, `--evidence-revision <sha256>`, `--diagnosis`, `--harness-disposition`, `--cleanup-evidence`, `--process-evidence`. Never settle with a bare/illustrated command.

### Unit 3a-requests (R3, R4; S3.1, S3.2, S3.3, S4.1, S4.2)

- [ ] 3.0 Acquire ledger token for this work unit (orchestrator-owned): `gentle-ai sdd-attempt acquire --cwd /home/hgvm/Documentos/GitHub/portafolioClientes --change catalog-slice-3-personalization-engine --request-id 3a-requests --work-unit 3a-requests --evidence-goal "R3,R4 requests green on SQLite+PG" --max-attempts 3 --max-changed-lines 350`; launch only on state `proceed`, retain its opaque `token`.
- [ ] 3.1 RED: Write failing tests `backend/tests/Feature/UserCatalogItemRequestTest.php` covering S3.1 (duplicate fork rejected), S3.2 (sibling title uniqueness), S3.3 (wrong parent type), S4.1 (tags whitelist), S4.2 (status rejected 422, unknown fields dropped)
- [ ] 3.2 GREEN: Implement `backend/app/Http/Requests/StoreUserCatalogItemRequest.php` — `authorize()` authenticates; rules: unique `base_id`+user+`item_type` scoped, title unique per `parent_fork_id` (withoutTrashed), parent type coherence, tags in whitelist, `value >= 0`, `status` forbidden (422), unknown fields rejected
- [ ] 3.3 GREEN: Implement `backend/app/Http/Requests/UpdateUserCatalogItemRequest.php` — `authorize()` calls `can('update', item)`; same validation rules; `sometimes|nullable` for omitted-vs-null; `array_key_exists` removes override on explicit null
- [ ] 3.4 REFACTOR: Extract shared rules trait if duplication > 10 lines; ensure Pint passes
- [ ] 3.5 VERIFY: Run `docker compose exec backend php artisan test --filter=UserCatalogItemRequestTest` (SQLite) + `./test-pg.sh --filter=UserCatalogItemRequestTest` (PostgreSQL) + Pint
- [ ] 3.6 Commit work unit: `feat(catalog): add Store/Update requests with fork identity & validation (R3,R4)` with tests
- [ ] 3.7 Settle ledger (orchestrator-owned): native settle for token `3a-requests` with the full exact flag set (see 2.8), before any merge of PR 3a.

## Phase 3: Slice 3a PR & Review Gate

- [ ] 4.1 Push `feat/catalog-slice-3a`; open PR targeting tracker `feat/catalog-rubros-categorias-servicios`
- [ ] 4.2 Verify PR diff ≤ 400 lines; full local verification green on SQLite, PostgreSQL, Pint (this repo has no CI — verification is local, per slice-2 practice).
- [ ] 4.3 Wait for review approval before proceeding to Slice 3b

## Phase 4: Slice 3b Branch & Setup

- [ ] 5.1 Create branch `feat/catalog-slice-3b` from `feat/catalog-slice-3a`
- [ ] 5.2 Acquire ledger token: `gentle-ai sdd-attempt acquire --cwd /home/hgvm/Documentos/GitHub/portafolioClientes --change catalog-slice-3-personalization-engine --request-id 3b-resolver --work-unit 3b-resolver --evidence-goal "R5,R6 resolver green on SQLite+PG" --max-attempts 3 --max-changed-lines 350`

## Phase 5: Slice 3b — Resolver (TDD)

### Unit 3b-resolver (R5, R6; S5.1, S5.2, S5.3, S6.1, S6.2, S6.3)

- [ ] 6.1 RED: Write failing tests `backend/tests/Unit/CatalogResolverTest.php` covering S5.1 (base edit propagates), S5.2 (override wins + origin override + listed), S5.3 (null restores inheritance), S6.1 (deactivated ancestor → desactivado), S6.2 (base deactivated → desactivado), S6.3 (full active chain → activo)
- [ ] 6.2 GREEN: Implement `backend/app/Services/CatalogResolver.php` — `resolve()` returns id, base_id, name/title, value, description, tags, effective status, origin, overridden_fields; eager-load `parentFork` and `base` depth 3; per-field inheritance: override ?? current base value; origin logic; effective status AND chain
- [ ] 6.3 REFACTOR: Clean up; ensure Pint passes
- [ ] 6.4 VERIFY: Run `docker compose exec backend php artisan test --filter=CatalogResolverTest` (SQLite) + `./test-pg.sh --filter=CatalogResolverTest` (PostgreSQL) + Pint
- [ ] 6.5 Commit work unit: `feat(catalog): add CatalogResolver with per-field inheritance + effective status (R5,R6)` with tests
- [ ] 6.6 Settle ledger (orchestrator-owned): native settle for token `3b-resolver` with the full exact flag set (see 2.8).

## Phase 6: Slice 3b — Cascade Fork (TDD)

- [ ] 7.1 Acquire ledger token: `gentle-ai sdd-attempt acquire --cwd /home/hgvm/Documentos/GitHub/portafolioClientes --change catalog-slice-3-personalization-engine --request-id 3b-cascade --work-unit 3b-cascade --evidence-goal "R7 cascade fork green on SQLite+PG" --max-attempts 3 --max-changed-lines 350`

### Unit 3b-cascade (R7; S7.1, S7.2, S7.3, S7.4)

- [ ] 7.2 RED: Write failing tests `backend/tests/Unit/CascadeForkServiceTest.php` covering S7.1 (rubro cascade tree 1+3+6), S7.2 (mid-copy rollback → zero rows), S7.3 (standalone categoria cascade), S7.4 (deactivated descendants copied, own status activo, resolve desactivado via R6)
- [ ] 7.3 GREEN: Implement `backend/app/Services/CascadeForkService.php` — `forkRubro`, `forkCategoria`, `forkService`; each owns one `DB::transaction`; duplicate identity checked inside; copy non-deleted children regardless of base status; `status='activo'`, empty overrides, correct parent links; propagate exceptions; return tree summary
- [ ] 7.4 REFACTOR: Clean up; ensure Pint passes
- [ ] 7.5 VERIFY: Run `docker compose exec backend php artisan test --filter=CascadeForkServiceTest` (SQLite) + `./test-pg.sh --filter=CascadeForkServiceTest` (PostgreSQL) + Pint
- [ ] 7.6 Commit work unit: `feat(catalog): add CascadeForkService with atomic cascade fork (R7)` with tests
- [ ] 7.7 Settle ledger: `gentle-ai sdd-attempt settle --cwd /home/hgvm/Documentos/GitHub/portafolioClientes --change catalog-slice-3-personalization-engine --request-id 3b-cascade --outcome pass --evidence-revision <commit-sha>`

## Phase 7: Slice 3b PR & Review Gate

- [ ] 8.1 Push `feat/catalog-slice-3b`; open PR targeting `feat/catalog-slice-3a`
- [ ] 8.2 Verify PR diff ≤ 400 lines; full local verification green on SQLite, PostgreSQL, Pint (no CI — local verification only).

## Phase 8: Integration Verification & Close-Out

- [ ] 9.1 Run full test suite both engines: `docker compose exec backend php artisan test` + `./test-pg.sh`
- [ ] 9.2 Verify `backend/routes/api.php` unchanged (assert no diff)
- [ ] 9.3 Run `codegraph sync` (via `.opencode/command/sync-codegraph`)
- [ ] 9.4 Update `docs/planning/planning3.md` progress log (mark Slice 3 complete)
- [ ] 9.5 Note: Notion mirror of planning3.md is a user-visible follow-up within slice completion (not a blocker)

## Phase 9: Tracker Integration (Slice 4+ scope, noted for context)

- [ ] 10.1 Tracker branch `feat/catalog-rubros-categorias-servicios` will merge to main in Slice 4+; out of this change's tasks
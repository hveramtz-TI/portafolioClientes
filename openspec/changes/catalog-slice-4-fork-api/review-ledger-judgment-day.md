# Judgment Day Ledger — catalog-slice-4-fork-api

```yaml
target_identity: a9e8661...500702a86d09e0b7ae73d8265ff95e92be6af555 (frozen tree 9c51733d2a36deefa93103f9421a7a2024ddc23b)
round: 1
method: explicit judgment-day blind dual review (jd-judge-a, jd-judge-b; identical scope and criteria)
severe_findings: 0
confirmed: [JD4-2, JD4-3]
suspect_single_judge: [JD4-1, JD4-4]
contradictions: []
info: [JD4-5]
terminal_state: open (pending maintainer decision on bounded correction)
```

## Findings (merged, de-duplicated)

| ID | Severity | Judges | Location | Claim | Status |
|---|---|---|---|---|---|
| JD4-1 | WARNING | B only | `backend/app/Http/Controllers/UserCatalog/ForkController.php:19-31` | Fork endpoint never authorizes `create`; policy denies admin but Store enforces while Fork does not — admin can create forks via POST .../fork, contradicting design D-2/D-8 ability assignment | SUSPECT (single judge — not auto-fixable per protocol; deterministic proof refs: no Gate::authorize in ForkController; StoreController:23 has it; no S7 fork-create auth test) |
| JD4-2 | WARNING | A + B | `backend/app/Http/Requests/Concerns/ValidatesUserCatalogItem.php:200-207,228-269` + `UpdateUserCatalogItemRequest.php:74-80` | PUT that moves AND renames in one request validates the destination clash against the item's OLD visible name and the rename rule against the OLD parent → duplicate visible names can persist under the destination (R5/D8), and false rejections vs the old parent | CONFIRMED pair (both judges, deterministic, introduced; no combined move+rename test exists) |
| JD4-3 | WARNING | A (WARNING) + B (SUGGESTION) | `backend/bootstrap/app.php:48-50` | PostgreSQL SQLSTATE 23505 accepted unconditionally → any unique violation on any API route renders the user-catalog duplicate-fork 409 message (SQLite branch is properly scoped to user_catalog_items) — misleading wire contract + cross-engine divergence | CONFIRMED pair (max shared severity WARNING, deterministic, introduced) |
| JD4-4 | WARNING | B only | `backend/routes/api.php:91-94` + `backend/bootstrap/app.php:43-58` | No `whereUuid` on `{baseId}`/`{fork}` → malformed ids reach uuid columns; PG raises 22P02 (unmapped → 500) while SQLite 404s. Violates R7 no-500 contract and S7.3 | SUSPECT (single judge — deterministic proof, violates the change's own R7; tests never exercise malformed ids) |
| JD4-5 | SUGGESTION | B only | `ValidatesUserCatalogItem.php:322-353` | Personal-only sibling uniqueness on store/rename vs broader visible-name check on move — known asymmetry, rule is pre-existing (Slice 3) | INFO (pre-existing, documented deviation) |

## Assessment

No CRITICAL findings → the protocol mandates no fix round; all rows are WARNING/SUGGESTION (advisory).
Susceptibility review of the two suspects: JD4-1 and JD4-4 carry deterministic proofs with exact line
evidence and violate contracts THIS change wrote (design D-8 ability map; spec R7 "no scenario may
surface as 500"). They read as genuine introduced defects the other judge simply did not reach, not
noise. Recommended disposition: one bounded correction round over JD4-1..JD4-4 (four small surgical
fixes + pinning tests), then scoped re-judgment over ledger + fix delta. JD4-5 stays as documented
follow-up.

## Decision log

- 2026-09-14 round 1 complete; maintainer decision on correction pending (see next section).

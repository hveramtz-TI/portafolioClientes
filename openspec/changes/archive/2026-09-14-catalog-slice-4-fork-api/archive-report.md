# Archive Report: catalog-slice-4-fork-api

**Archived**: 2026-09-14
**Archive location**: `openspec/changes/archive/2026-09-14-catalog-slice-4-fork-api/`
**Artifact store**: hybrid — openspec (repo-local source of truth) + Engram mirror
**Engram topic key**: `sdd/catalog-slice-4-fork-api/archive-report`

## 1. Closure Status

CLOSED. Task completion gate: the persisted `tasks.md` shows **45/46** checked. The single
unchecked item is **3.2 — "Notion mirror of `planning3.md` updated (orchestrator-owned at
close-out)"**. This is a non-implementation close-out task owned by the orchestrator and being
executed concurrently at archive time; the launch prompt explicitly instructed archive to record it
as such and NOT to block on it. No implementation task is unchecked, so no stale-checkbox
reconciliation of completed work was required or performed. Recording the exceptional-proceed
reason verbatim: **the sole 3.2 checkbox is an explicitly orchestrator-owned, concurrently-executed
documentation mirror — not an implementation deliverable of this change.**

Verification gate passed: `verify-report.md` records **0 CRITICAL findings, 0 blockers**. No CRITICAL
issue exists, so no override path was needed or accepted.

## 2. Spec Sync

| Domain | Action | Details |
|--------|--------|---------|
| user-catalog-fork-api | Created | New capability — full spec (R1–R7 + 40 scenarios) promoted to canonical |
| user-catalog-personalization | Updated | MODIFIED R3/R4/R5 + ADDED R8 (2 scenarios); R1/R2/R6/R7 preserved byte-for-byte |

- **`openspec/specs/user-catalog-fork-api/spec.md`** — no canonical spec existed for this domain, and
  the delta spec is a full spec document (Purpose + Requirements, no `ADDED`/`MODIFIED`/`REMOVED`/
  `RENAMED` sections). Per archive rules the "main spec does not exist" path applied: native shell
  `cp` into a `mktemp` staging file, verified with `diff -r`, then atomic `mv` into place. No model
  Read/Write copy was used. `sdd-archive-compose` was deliberately NOT invoked because there was no
  canonical spec to compose against.
  - Post-sync readback `diff -r` (delta source vs canonical): **empty output, exit 0 — byte-identical** (283 lines).
- **`openspec/specs/user-catalog-personalization/spec.md`** — canonical existed; delta applied via the
  mandated native composer `gentle-ai sdd-archive-compose` (exit 0). The `.compose-tmp` intermediate
  was `mv`-ed into place for an atomic replace.
  - Composer output (212 lines) verified: R1, R2, R6, R7 **byte-identical** to the pre-compose
    canonical (extract + `diff`); R3/R4/R5 replaced by the delta bodies; R8 added.
  - No destructive merge. `openspec/config.yaml` `rules.archive` ("Warn before merging destructive
    deltas") was not triggered — no unrelated requirement was dropped.
  - **Observation (not silently resolved):** the native composer appended the delta's own
    `## Traceability` section, so the canonical now carries two consecutive `## Traceability`
    headings (the new Slice-4 amendments line, then the original HU/decisions line). Content is
    lossless; this is the composer's output shape. Per the archive rules a manual Read/Edit fix of a
    successful composer output is forbidden, so it was left exactly as produced.

## 3. Mechanical Move Evidence

The change directory was moved with `git mv` (mechanical), never through the model Read/Write path.

```
=== MANDATORY diff -r READBACK: pre-move snapshot vs archived destination (empty = PASS) ===
=== diff -r exit status: 0 ===
```

- Pre-move snapshot: `cp -R` of the change directory into a `mktemp -d` snapshot root.
- Archived tree compared against that snapshot after the move: empty `diff -r` output, exit 0.
- Independent confirmation from git: all 8 artifacts staged as `R` (rename) entries.
- Source path `openspec/changes/catalog-slice-4-fork-api/` no longer exists; the active changes
  directory now contains only `archive/`.
- The `archive-report.md` you are reading is additive — it did not exist in the source change folder
  and is therefore excluded from the source/destination comparison by contract.

## 4. Final Integrated State (at close)

Recorded from the orchestrator's confirmed final-state facts (highest-ranked source for
post-verification state) plus repository evidence:

- Fully integrated to `main`: **PR #16** (`4b`→`4a`), **PR #15** (`4a`→tracker), **PR #17**
  (tracker→`main` @ merge `8b56c2e`). `main` also carries the verify-report / design-reconcile commit
  `e7116df` (`docs(sdd): add slice 4 verify report (VERIFIED_WITH_WARNINGS) and reconcile design D-5
  wording`). Not pushed by this archive step.
- This archive commit contains only SDD archive paths (see §5).

### Verification verdict (final)

`VERIFIED_WITH_WARNINGS`:

- Requirements **11/11**, scenarios **55/55** traceable (40 fork-api + 15 personalization delta).
- **219 tests / 795 assertions** green on SQLite **and** PostgreSQL (evidence sha256 `46c1b530…`),
  `test_output_hash` `46c1b530…`, `evidence_revision` `1dc854c0…`.
- Laravel Pint **PASS** (24 files).

### WARNINGs — disposition at close

| WARNING | Disposition |
|---------|-------------|
| D-5 design text load-shape deviation | **Already reconciled** to the as-built flat-load shape in the same slice-4 commit (`e7116df`). |
| Dev migration pending | **Applied** to the dev database. |
| 3.2 Notion mirror of `planning3.md` | **Orchestrator-owned**, executing concurrently at close-out (outside archive scope). |

### Review ledger (judgment day) — terminal state

Round-1 produced **zero CRITICAL** findings. Defects **JD4-1 … JD4-4** fixed in commits
`4073117` / `3484210` / `116943c` / `bac62d8`; scoped re-judgment came back clean from both judges;
ledger terminal state **APPROVED** (`eeb5e62` judgment + `e26fbd6` ledger terminal doc). **JD4-5**
was documented as a **pre-existing INFO** finding (not introduced by this slice).

### Attempt ledger

4 attempts settled passed. Two **maintainer-approved accounting resets** were applied — these are
measurement artifacts of the runtime ledger, **not scope changes**.

## 5. Source Ranking and Contradictions

Reporting follows the final-state authority hierarchy. `apply-progress.md` and `verify-report.md`
inside this archive are intermediate snapshots describing the state when written; their "done"
claims remain true, and their numbers are superseded where later work changed them (e.g. the suite
reached 219 tests / 795 assertions through the judgment-day fixes, above the count in earlier
snapshots). Their "pending/open" claims are not repeated here as current facts.

**Working-tree contradiction (not silently resolved).** Repository evidence at archive time shows
three modified tracked files unrelated to this change:

| Path | State |
|------|-------|
| `.atl/.skill-registry.cache.json` | modified, unstaged |
| `.atl/skill-registry.md` | modified, unstaged |
| `opencode.jsonc` | modified, unstaged |

These belong to the gentle-ai managed-config sync and are outside the archive's scope; they were
deliberately left unstaged and untouched (the skill forbids touching `.atl/*` and `opencode.jsonc`).
This archive commit contains only `openspec/` paths.

**Engram observation IDs.** Not applicable for this run: the SDD artifacts for this change live
repo-locally under `openspec/`, and no Engram observation was read to produce this report. No IDs
are listed because none were read; listing synthetic IDs would be false traceability. Only the
archive report itself is mirrored to Engram (topic key above); `capture_prompt` is `false` because
this is an automated SDD artifact.

## 6. Follow-ups Carried to Slice 5+

1. **S7.2 HTTP-level cross-owner DELETE assertion.** The 403 policy-layer proof exists; an explicit
   HTTP-level cross-owner DELETE assertion is deferred.
2. **Visible-name sibling uniqueness asymmetry (JD4-5).** Pre-existing INFO; the JSON `overrides`-
   derived sibling-name uniqueness remains application-level (not indexable cross-engine).
3. **Project-wide pre-existing Pint debt.** 11 untouched files still carry formatting debt, outside
   this slice's 24-file Pint PASS.

## 7. SDD Cycle

The change was planned, specified, designed, task-broken, implemented, verified, adversarially
reviewed (judgment day), and archived. Ready for the next change.

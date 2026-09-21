# Verify Report: catalog-slice-5-base-seeders

**Change:** feat/catalog-slice-5-base-seeders @ dd4d38e
**Base:** main @ 4287e3f
**Verifier:** sdd-verify agent
**Date:** 2026-09-21

---

## Verdict: VERIFIED_WITH_WARNINGS

---

## Evidence Table (Actual vs Claimed)

| Check | Engine | Claimed | Actual | Match |
|-------|--------|---------|--------|-------|
| Catalog tests | SQLite | 12 tests / 76 assertions | 12 tests / 76 assertions | ✅ |
| Catalog tests | PostgreSQL | 12 tests / 76 assertions | 12 tests / 76 assertions | ✅ |
| Full suite | SQLite | 231 tests / 871 assertions | 231 tests / 871 assertions | ✅ |
| Full suite | PostgreSQL | 231 tests / 871 assertions | 231 tests / 871 assertions | ✅ |
| Pint (5 touched files) | — | PASS | PASS | ✅ |
| `#[Seed]` / `#[Seeder(` grep | — | 0 matches | 0 matches (exit 1) | ✅ |
| Diff: 8 files, 872(+)/1(-) | — | as claimed | 8 files, 872(+)/1(-) | ✅ |
| Baseline delta | — | 219→231 = +12 new | 231 - 12 = 219 baseline preserved | ✅ |

---

## Scenario Coverage Matrix

| Spec Ref | Scenario | Test Method | Status |
|----------|----------|-------------|--------|
| R1/S1.1 | counts-and-status | `test_catalog_has_canonical_counts_status_and_null_deletes` | ✅ FULL |
| R1/S1.2 | categoria-parentage-and-order | `test_categories_have_canonical_parentage_and_order` | ✅ FULL |
| R1/S1.3 | service-values-and-tags | `test_services_have_exact_values_tags_and_descriptions` | ⚠️ PARTIAL (3/12 explicit) |
| R1/S1.4 | placeholder-X-empty | `test_catalog_has_canonical_counts_status_and_null_deletes` | ✅ FULL |
| R1/S1.5 | descriptions-pinned | `test_services_have_exact_values_tags_and_descriptions` (template pattern) | ✅ FULL |
| R2/S2.1 | chain-order-integrity | `test_categories_have_canonical_parentage_and_order` | ✅ FULL |
| R2/S2.2 | rubro-resolution-by-name | `test_categories_have_canonical_parentage_and_order` | ✅ FULL |
| R2/S2.3 | pair-disambiguation | `test_service_lookup_uses_rubro_and_category_pair` | ✅ FULL |
| R2/S2.4 | out-of-order-aborts-clean | `test_dependent_seeder_fails_before_writing_without_parent` + `test_service_seeder_fails_without_categories` | ✅ FULL |
| R3/S3.1 | second-run-counts-identical | `test_second_seed_is_byte_equal_to_first_snapshot` | ✅ FULL |
| R3/S3.2 | second-run-values-identical | `test_second_seed_is_byte_equal_to_first_snapshot` (snapshot equality) | ✅ FULL |
| R3/S3.3 | n-runs-equals-one | `test_second_seed_is_byte_equal_to_first_snapshot` (3 runs) | ✅ FULL |
| R3/S3.4 | dual-engine | Both engine runs green | ✅ FULL |
| R4/S4.1 | trashed-rubro-revived | `test_trashed_rows_are_revived_and_deactivation_converges` | ✅ FULL |
| R4/S4.2 | trashed-child-rows-revived | `test_trashed_rows_are_revived_and_deactivation_converges` | ✅ FULL |
| R4/S4.3 | deactivation-overwritten | `test_trashed_rows_are_revived_and_deactivation_converges` | ✅ FULL |
| R4/S4.4 | revival-both-engines | Both engine runs green | ✅ FULL |
| R5/S5.1 | standalone-trio-full-set | `test_standalone_commands_match_explicit_seed_path` | ✅ FULL |
| R5/S5.2 | chain-vs-standalone-equal | `test_standalone_commands_match_explicit_seed_path` | ✅ FULL |
| R5/S5.3 | event-silence-both-modes | `test_catalog_seeders_emit_no_model_events` | ✅ FULL |
| R6/S6.1 | full-seed-completes | `test_database_seeder_wires_catalog_before_company_and_client` | ✅ FULL |
| R6/S6.2 | wiring-order-observed | `test_database_seeder_wires_catalog_before_company_and_client` (implicit via DB counts) | ✅ FULL |
| R6/S6.3 | no-column-silently-dropped | `assertCanonicalColumnsPersisted` | ✅ FULL |
| R7/S7.1 | no-cross-test-leakage | `test_seeded_rows_do_not_leak_between_tests` | ✅ FULL |
| R7/S7.2 | second-run-asserted-explicitly | `test_second_seed_is_byte_equal_to_first_snapshot` | ✅ FULL |
| R7/S7.3 | no-seed-attribute | grep for `#[Seed]` = 0 matches | ✅ FULL |
| R7/S7.4 | dual-engine-suite-green | Full suite green both engines | ✅ FULL |
| R8/S8.1 | company-client-seeders-untouched | `git diff` = zero changes | ✅ FULL |
| R8/S8.2 | full-run-second-seed-unasserted | Test asserts catalog trio double-seed only | ✅ FULL |
| R8/S8.3 | existing-capabilities-unregressed | 219 baseline green both engines | ✅ FULL |
| R8/S8.4 | no-user-seeder | `ls` confirms no `UserSeeder.php` | ✅ FULL |
| R8/S8.5 | schema-and-models-frozen | `git diff` = zero changes to migrations/Models | ✅ FULL |

**Coverage summary:** 32/32 scenarios covered. 31 full, 1 partial (S1.3).

---

## 22-Row Data Spot-Check vs flow-doc (seeders-catalogo.md)

### Rubros (3) — ALL MATCH

| # | Name | Seeder Description | Flow Doc Description | Match |
|---|------|-------------------|---------------------|-------|
| 1 | Informática | Servicios de desarrollo, web, apps, mantenimiento | Servicios de desarrollo, web, apps, mantenimiento | ✅ |
| 2 | Diseño | Identidad visual, UX/UI, branding | Identidad visual, UX/UI, branding | ✅ |
| 3 | Consultoría | Arquitectura, revisiones, estrategia técnica | Arquitectura, revisiones, estrategia técnica | ✅ |

### Categorías (7) — ALL MATCH

| # | Name | Parent Rubro | Order | Match |
|---|------|-------------|-------|-------|
| 1 | Sitios web y presencia digital | Informática | 1 | ✅ |
| 2 | Aplicaciones a medida | Informática | 2 | ✅ |
| 3 | Mantenimiento y soporte | Informática | 3 | ✅ |
| 4 | Identidad visual | Diseño | 1 | ✅ |
| 5 | UX/UI | Diseño | 2 | ✅ |
| 6 | Arquitectura y estrategia | Consultoría | 1 | ✅ |
| 7 | X | Consultoría | 2 | ✅ |

### Services (12) — ALL CLP VALUES AND TAGS MATCH

| # | Title | Parent Categoria | CLP Value | Tags (flow doc) | Tags (seeder) | Match |
|---|-------|-----------------|-----------|-----------------|---------------|-------|
| 1 | Actualizar portafolio web | Sitios web | 300000 | frontend, fullstack | [frontend, fullstack] | ✅ |
| 2 | Landing page nueva | Sitios web | 450000 | frontend | [frontend] | ✅ |
| 3 | E-commerce básico | Sitios web | 800000 | frontend, backend, fullstack | [frontend, backend, fullstack] | ✅ |
| 4 | App móvil React Native | Aplicaciones | 1200000 | mobile, frontend | [mobile, frontend] | ✅ |
| 5 | Dashboard administrativo | Aplicaciones | 900000 | frontend, backend, fullstack | [frontend, backend, fullstack] | ✅ |
| 6 | Retención mensual mantenimiento | Mantenimiento | 200000 | devops | [devops] | ✅ |
| 7 | Logo + brand guide | Identidad visual | 400000 | frontend | [frontend] | ✅ |
| 8 | Rediseño marca | Identidad visual | 600000 | frontend | [frontend] | ✅ |
| 9 | Auditoría usabilidad | UX/UI | 350000 | frontend | [frontend] | ✅ |
| 10 | Prototipo navegable | UX/UI | 500000 | frontend, fullstack | [frontend, fullstack] | ✅ |
| 11 | Definición arquitectura técnica | Arquitectura | 500000 | backend, devops | [backend, devops] | ✅ |
| 12 | Revisión código y deuda técnica | Arquitectura | 400000 | backend, fullstack | [backend, fullstack] | ✅ |

**Tag whitelist check:** All 12 service tag arrays are subsets of D4 whitelist {frontend, backend, fullstack, devops, mobile}. ✅

**Description template:** All 12 use `generateDescription()` per D4: `"Servicio profesional: {title}. Valor referencial, precio final sujeto a conversación según requerimientos."` ✅

**Placeholder X:** 0 services under categoría X. All other 6 categorías have ≥ 1 service. ✅

---

## Findings

### CRITICAL

None.

### WARNING

**W1 — S1.3 partial explicit verification:** The test `test_services_have_exact_values_tags_and_descriptions` only explicitly asserts CLP values and tags for 3 of 12 services (those under "Sitios web y presencia digital"). The remaining 9 services' CLP values and tags are covered only by the R3 snapshot equality test, which proves byte-stability across runs but does NOT assert correctness against the flow doc. If the initial seeder data had a typo in one of those 9 services' values or tags, the snapshot test would pass (values match themselves), but the data would be wrong relative to the flow doc.

**Severity:** Low — the spot-check above verified all 22 rows VERBATIM against the flow doc, confirming no drift exists today. The risk is future-proofing: if someone edits the seeder arrays, only 3/12 services would have a test catching a value regression.

**Recommendation:** Consider extending `test_services_have_exact_values_tags_and_descriptions` to assert all 12 services' CLP values and tags (or at minimum a representative sample from each categoria).

### SUGGESTION

**S1 — Descriptions diverge from flow-doc "Descripción Corta" column:** The seeders use `generateDescription()` template (D4 decision) instead of the shorter flow-doc table descriptions (e.g., "Actualización sitio existente"). This is intentional per design D4 and is NOT drift. However, if the flow doc ever becomes the single source of truth for descriptions, the seeder wording would need reconciliation.

**S2 — Deterministic UUIDs are not validated against RFC 9562:** The seeder uses UUIDs formatted as v7 (`a2ccdb43-9e02-4afa-8a3d-...`) but they are hand-crafted constants, not runtime-generated. This is intentional per D2 (byte-stability). No action needed unless UUID format compliance becomes a requirement.

---

## Out-of-Scope Guards

| Guard | Evidence | Status |
|-------|----------|--------|
| S8.1: CompanySeeder/ClientSeeder untouched | `git diff` = zero changes | ✅ |
| S8.4: No UserSeeder.php | `ls` = file not found | ✅ |
| S8.5: No migrations/models changed | `git diff` = zero changes to migrations/Models | ✅ |
| S7.3: No `#[Seed]` attribute | grep = 0 matches | ✅ |
| No frontend files | diff shows only backend + docs | ✅ |
| No composer.json changes | diff shows only 8 specific files | ✅ |

---

## Idempotency Contract

- Second seed run: byte-equal snapshot (test asserts 3 runs total). ✅
- Both engines green. ✅

## Revival Contract

- Trashed rubro (Diseño), trashed categoría (UX/UI), trashed service (E-commerce básico) all restored to live state with canonical values. ✅
- Deactivated service (E-commerce básico, status='desactivado') converged back to 'activo'. ✅
- Counts preserved: 3/7/12 after revival. ✅
- Both engines green. ✅

## D3 Revival Smoke Test

Design D3 claimed the upsert path is proven and the fallback is NOT activated. Both engines pass `test_trashed_rows_are_revived_and_deactivation_converges` — confirming the upsert + `deleted_at => null` approach works on both SQLite and PostgreSQL. Fallback branch (Phase 5 tasks) was NOT triggered. ✅

---

## Size Exception Record

- **Forecast:** 288–376 changed lines (D10, tasks.md)
- **Actual:** 872(+) / 1(-) over 8 files
- **Reason:** Forecast counted only implementation + test code; actual includes docs (tasks.md 117 lines, HU-024 status change, planning3.md progress entry). The 5 code files total ~793 lines (RubroSeeder 55, CategoriaSeeder 110, ServiceSeeder 194, DatabaseSeeder 49 change, BaseCatalogSeederTest 379, plus 3-line DatabaseSeeder diff). The test class at 379 lines alone is near the upper forecast bound.
- **MAINTAINER APPROVED:** size:exception granted for >400 lines per Engram topic #833.

---

## Skill Resolution

- **sdd-verify:** Executed fully. All checklist items verified.
- **next_recommended:** `sdd-archive` (VERIFIED_WITH_WARNINGS — warnings are non-blocking).

---

## Artifacts

- Report: `openspec/changes/catalog-slice-5-base-seeders/verify-report.md`
- Engram topic: `sdd/catalog-slice-5-base-seeders/verify-report` (type: architecture)

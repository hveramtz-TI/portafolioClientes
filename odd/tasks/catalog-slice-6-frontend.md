# ODD Tasks — Catalog Slice 6

## Objective

Deliver the first user-operable catalog frontend surface and the authenticated read-only base-catalog contract required to select/fork catalog rubros.

## Authorized scope

- Add authenticated read-only access to base catalog data for normal users; keep base mutations admin-only.
- Add a protected `/catalogo` dashboard page with Base Catalog and My Catalog views.
- Add typed catalog API functions and feature-local UI for tree rendering, origin/status badges, filters, fork/select, and basic personal-item lifecycle actions.
- Add focused frontend and backend tests for the delivered behavior.

## Constraints

- Follow `docs/planning/planning3.md`; do not implement orders, portfolio PDF, or unrelated catalog features.
- Reuse existing dashboard shell and shadcn-style primitives.
- Preserve owner-only authorization for user catalog mutations.
- Use English technical artifacts and UI copy unless existing project conventions require otherwise.

## Checklist

- [x] T1 — Expose authenticated read-only base catalog data without widening mutation permissions.
- [x] T2 — Add typed catalog API client and `/catalogo` protected page with Base/My Catalog views.
- [x] T3 — Add tree rendering, filters, badges, fork action, and lifecycle/create/edit interactions.
- [x] T4 — Add tests and run lint, Jest, backend tests, and CodeGraph synchronization.

## Acceptance criteria

- Authenticated normal users can load base rubros and fork a selected rubro.
- Admin-only base writes remain admin-only.
- The catalog page renders loading, empty, error, and successful states.
- My Catalog exposes effective status/origin and does not assume filtered tree results always preserve every ancestor.
- Tests cover the new API/page behavior and all relevant verification commands are reported honestly.

## Route decision

Use `/catalogo` as the protected dashboard entry point, with Base Catalog and My Catalog as tabs/views. This keeps the first slice coherent without introducing unnecessary route fragmentation.

## Progress

- Route: implemented directly in the dashboard route with feature-local catalog API/components.
- T1 — complete: authenticated `GET /api/rubros`; base mutations remain inside `role:admin`.
- T2 — complete: typed API module and protected `/catalogo` Base Catalog/My Catalog page.
- T3 — complete: tree, effective status/origin badges, filters, fork, create/edit/status/delete actions.
- T4 — complete: focused API/tree Jest tests and backend read-access authorization tests added; verification results recorded in handoff.

## Next step

Slice 6 is implemented and verified. Next: manual UX review, then plan Slice 7 for the remaining catalog frontend flows.

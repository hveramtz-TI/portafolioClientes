# Base Catalog Read Access Specification

## Purpose

Defines the authorization contract for the base catalog read surface. Authenticated non-admin users MAY read the base rubro listing (defaulting to active rows) while every base mutation, status transition, and child-listing route MUST remain admin-only. This spec pins the Slice 6 widening of `GET /api/rubros` from admin-only to authenticated scope as an explicit contract, superseding the pre-Slice-6 status quo where the route file implicitly restricted reads to admins.

## Requirements

### Requirement: Authenticated Base Rubro Read

The system SHALL allow any authenticated user to retrieve the base rubro listing via `GET /api/rubros`. The response MUST be identical in shape for admin and non-admin users. Authorization MUST be granted by route placement (outside the `role:admin` middleware group), not by controller-level role checks.

#### Scenario: Normal user reads active base rubros

- GIVEN an authenticated user with role `user`
- AND the base rubros table contains one active rubro ("Visible") and one deactivated rubro ("Hidden")
- WHEN the user sends `GET /api/rubros` without query parameters
- THEN the response status MUST be 200
- AND the response body MUST contain exactly one rubro
- AND the returned rubro name MUST be "Visible"

#### Scenario: Normal user reads all base rubros with status filter

- GIVEN an authenticated user with role `user`
- AND the base rubros table contains active and deactivated rubros
- WHEN the user sends `GET /api/rubros?status=all`
- THEN the response status MUST be 200
- AND the response body MUST include both active and deactivated rubros

#### Scenario: Admin user reads base rubros

- GIVEN an authenticated user with role `admin`
- WHEN the admin sends `GET /api/rubros`
- THEN the response status MUST be 200
- AND the response shape MUST be identical to the normal-user response

#### Scenario: Unauthenticated user cannot read base rubros

- GIVEN no authenticated session
- WHEN a request sends `GET /api/rubros`
- THEN the response status MUST be 401

### Requirement: Admin-Only Base Mutations Preserved

The system MUST restrict all base catalog mutation routes to users with role `admin`. Non-admin authenticated users MUST receive a 403 Forbidden response for every mutation attempt. The restricted routes SHALL include: `POST /api/rubros` (store), `PUT /api/rubros/{id}` (update), `DELETE /api/rubros/{id}` (destroy), `PATCH /api/rubros/{id}/deactivate`, and `PATCH /api/rubros/{id}/reactivate`.

#### Scenario: Normal user cannot update a base rubro

- GIVEN an authenticated user with role `user`
- AND a base rubro exists with id `{rubroId}`
- WHEN the user sends `PUT /api/rubros/{rubroId}` with a modified name
- THEN the response status MUST be 403

#### Scenario: Normal user cannot create a base rubro

- GIVEN an authenticated user with role `user`
- WHEN the user sends `POST /api/rubros` with a valid payload
- THEN the response status MUST be 403

#### Scenario: Normal user cannot delete a base rubro

- GIVEN an authenticated user with role `user`
- AND a base rubro exists with id `{rubroId}`
- WHEN the user sends `DELETE /api/rubros/{rubroId}`
- THEN the response status MUST be 403

#### Scenario: Normal user cannot deactivate a base rubro

- GIVEN an authenticated user with role `user`
- AND a base rubro exists with id `{rubroId}`
- WHEN the user sends `PATCH /api/rubros/{rubroId}/deactivate`
- THEN the response status MUST be 403

#### Scenario: Normal user cannot reactivate a base rubro

- GIVEN an authenticated user with role `user`
- AND a deactivated base rubro exists with id `{rubroId}`
- WHEN the user sends `PATCH /api/rubros/{rubroId}/reactivate`
- THEN the response status MUST be 403

#### Scenario: Admin user can mutate base rubros

- GIVEN an authenticated user with role `admin`
- WHEN the admin sends `PUT /api/rubros/{rubroId}` with a valid payload
- THEN the response status MUST be 200

### Requirement: Admin-Only Base Child-Listing Routes

The system MUST restrict base catalog child-listing routes to users with role `admin`. Non-admin authenticated users MUST receive a 403 Forbidden response. The restricted routes SHALL include: `GET /api/rubros/{id}/categorias`, `GET /api/categorias/{id}/services`, and equivalent listing routes for the base categories and services resources.

#### Scenario: Normal user cannot list base categories of a rubro

- GIVEN an authenticated user with role `user`
- AND a base rubro exists with id `{rubroId}`
- WHEN the user sends `GET /api/rubros/{rubroId}/categorias`
- THEN the response status MUST be 403

#### Scenario: Admin user can list base categories of a rubro

- GIVEN an authenticated user with role `admin`
- AND a base rubro exists with id `{rubroId}`
- WHEN the admin sends `GET /api/rubros/{rubroId}/categorias`
- THEN the response status MUST be 200

### Requirement: Default Active Filter

The system SHALL default the `GET /api/rubros` response to active-only rubros when no `status` query parameter is provided. This behavior reuses the existing controller filter and is not a new filtering mechanism introduced by this change.

#### Scenario: Default request returns only active rubros

- GIVEN the base rubros table contains 3 active and 2 deactivated rubros
- WHEN an authenticated user sends `GET /api/rubros` (no query parameters)
- THEN the response status MUST be 200
- AND the response body MUST contain exactly 3 rubros
- AND every returned rubro MUST have status "activo"

#### Scenario: Explicit status=all returns all rubros

- GIVEN the base rubros table contains 3 active and 2 deactivated rubros
- WHEN an authenticated user sends `GET /api/rubros?status=all`
- THEN the response status MUST be 200
- AND the response body MUST contain exactly 5 rubros

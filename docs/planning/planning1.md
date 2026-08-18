# Planning — Autenticación + Roles

**Fecha:** 2026-08-18
**Objetivo del sprint:** Implementar autenticación de usuarios con Laravel Sanctum (SPA cookie-based) y roles simples (`admin` / `user`) en el portafolio de clientes.

## Contexto

- Stack: Next.js 16 (App Router) · Laravel 13 · PostgreSQL 16 · Redis 7 · MinIO.
- Estado actual: scaffold puro. El backend no tiene Sanctum instalado y `/api/user` con `auth:sanctum` hoy fallaría. `users` usa la migración default (bigint PK, sin rol). El frontend es `create-next-app` sin manejo de sesión.
- Flujo de cuentas: **solo login + seeder admin**. Sin registro público (decisión del usuario).

## Decisiones

| Decisión | Opción elegida | Alternativas | Impacto |
|----------|----------------|--------------|---------|
| Mecanismo de auth | **Sanctum cookie-based (SPA)** con CSRF | Bearer tokens en header | Cookies `SameSite` + XSRF, más seguro y es el patrón oficial de Sanctum para SPA. Sin migración `personal_access_tokens` (KISS) |
| Roles | **Enum simple `admin` / `user`** en `users.role` | Spatie Permission | Personal app → KISS/YAGNI. Middleware propio `EnsureRole` |
| Primary key | **UUID (v7)** en `users` | bigint autoincrement | Constraint del proyecto. `HasUuids` genera UUIDv7 (ordenable, mejor para índices). La tabla no tiene datos aún |
| Creación de cuentas | **Solo login + seeder admin** | Registro público | No existe `/register` ni página de registro |
| Protección de rutas frontend | **`proxy.ts`** (convención Next.js 16) | `middleware.ts` | `middleware.ts` está **deprecado en Next 16** (renombrado a `proxy.ts`). Usar la convención nueva |

## Detalles técnicos verificados (Context7)

### Backend

- `laravel/sanctum` (v4.x) compatible con Laravel 13. `vendor:publish` solo la config (no las migraciones).
- `config/sanctum.php`: `stateful` con `localhost:3000` (frontend) vía `SANCTUM_STATEFUL_DOMAINS` en `.env`.
- CORS (`config/cors.php`): permitir `http://localhost:3000` con `supports_credentials: true`.
- Sanctum inyecta `EnsureFrontendRequestsAreStateful` en las rutas `/api` cuando el dominio es stateful.
- Endpoint implícito `GET /sanctum/csrf-cookie` (lo expone Sanctum, no se crea a mano).
- `User` model: `use HasUuids`. Migración `users`: `$table->uuid('id')->primary()`, columna `role` (enum `admin`/`user`, default `user`).
- Rutas API: `POST /api/login`, `POST /api/logout`, `GET /api/user` (protegida), `GET /api/users` (admin).
- Middleware `EnsureRole` (`role:admin`).

### Frontend

- `lib/api.ts`: cliente fetch con `credentials: 'include'`; primero `GET /sanctum/csrf-cookie`, luego envía header `X-XSRF-TOKEN` (URL-decoded del cookie `XSRF-TOKEN`).
- `AuthContext` (React context) para estado de sesión.
- Rutas: `/login` (pública), `/dashboard` (protegida).
- `proxy.ts` con matcher para excluir assets estáticos (`/((?!api|_next/static|_next/image|.*\\.png$).*)`).

## Alcance

### Backend (Laravel 13)

1. Instalar `laravel/sanctum` y publicar config.
2. Migración `users`: UUID PK + columna `role` (soft deletes opcional).
3. Config: `SANCTUM_STATEFUL_DOMAINS`, CORS con credenciales, `statefulApi` / middleware de sesión.
4. Rutas: `login`, `logout`, `user`, `users` (admin).
5. Middleware `EnsureRole`.
6. Seeder: usuario admin inicial.
7. Tests Feature PHPUnit (login, logout, CSRF, admin).

### Frontend (Next.js 16)

8. `lib/api.ts` (cliente fetch + CSRF).
9. `AuthContext` + hook de sesión.
10. Páginas `/login` y `/dashboard`.
11. `proxy.ts` para redirección de rutas protegidas.
12. Tests Jest (login flow, protección de rutas).

### Docs

13. `docs/auth/README.md` + actualizar planning docs por pila.

## Fuera de alcance (YAGNI)

- Registro público / verificación de email / reset de password.
- Tokens de API (Personal Access Tokens).
- Permisos granulares (Spatie).
- PWA / offline.

## Verificación

```bash
# Frontend
cd frontend && npm run lint
cd frontend && npm test

# Backend (en Docker)
docker compose exec backend php artisan test

# CodeGraph
codegraph sync && codegraph status
```
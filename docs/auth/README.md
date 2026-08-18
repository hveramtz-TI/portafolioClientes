# Autenticación y Roles

## Stack

- **Backend:** Laravel 13 + Sanctum 4.x (cookie-based SPA)
- **Frontend:** Next.js 16 (App Router)
- **Base de datos:** PostgreSQL 16 con UUID v7

## Arquitectura

### Backend

- **Sanctum SPA**: Autenticación basada en cookies con CSRF
- **Roles**: Enum simple `admin` / `user` en tabla `users`
- **UUID v7**: Primary keys usando trait `HasUuids` (ordenable, mejor para índices)

### Frontend

- **AuthContext**: Estado global de autenticación con React Context
- **proxy.ts**: Protección de rutas (Next.js 16, reemplaza middleware.ts deprecado)
- **API Client**: `lib/api.ts` con manejo automático de CSRF

## Endpoints API

| Método | Ruta | Descripción | Auth | Rol |
|--------|------|-------------|------|-----|
| GET | `/sanctum/csrf-cookie` | Obtener cookie CSRF | No | - |
| POST | `/api/login` | Iniciar sesión | No | - |
| POST | `/api/logout` | Cerrar sesión | Sí | - |
| GET | `/api/user` | Usuario autenticado | Sí | - |
| GET | `/api/users` | Lista de usuarios | Sí | admin |

## Flujo de Autenticación

1. Frontend llama `GET /sanctum/csrf-cookie` → recibe cookies `XSRF-TOKEN` y `portafolioclientes-session`
2. Frontend hace `POST /api/login` con header `X-XSRF-TOKEN` (URL-decoded del cookie)
3. Backend valida credenciales y crea sesión
4. Requests subsecuentes incluyen cookies automáticamente (`credentials: 'include'`)
5. Backend valida sesión + CSRF token en cada request

## Configuración

### Backend (.env)

```env
SANCTUM_STATEFUL_DOMAINS=localhost:3010,localhost:8010
SESSION_DOMAIN=.localhost
FRONTEND_URL=http://localhost:3010
```

### Frontend (.env.local)

```env
NEXT_PUBLIC_API_URL=http://localhost:8010
```

## Usuarios por Defecto

Después de ejecutar `php artisan db:seed`:

- **Admin**: `admin@example.com` / `password` (rol: admin)
- **User**: `user@example.com` / `password` (rol: user)

## Rutas Frontend

| Ruta | Descripción | Protección |
|------|-------------|------------|
| `/` | Redirige a /login o /dashboard | - |
| `/login` | Página de login | Público |
| `/dashboard` | Dashboard con info de usuario | Requiere sesión |

## Testing

```bash
# Backend
docker compose exec backend php artisan test

# Frontend
cd frontend && npm test
```

## Estructura de Archivos

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/LoginController.php
│   │   │   └── UserController.php
│   │   └── Middleware/
│   │       ├── EnsureRole.php
│   │       └── VerifyCsrfToken.php
│   └── Models/User.php (HasUuids, HasApiTokens)
├── config/
│   ├── cors.php
│   └── sanctum.php
├── database/
│   ├── migrations/0001_01_01_000000_create_users_table.php (UUID, role)
│   └── seeders/DatabaseSeeder.php
└── routes/api.php

frontend/
├── src/
│   ├── app/
│   │   ├── login/page.tsx
│   │   ├── dashboard/page.tsx
│   │   └── page.tsx (redirección)
│   ├── context/AuthContext.tsx
│   ├── hooks/useAuth.ts
│   ├── lib/api.ts
│   └── proxy.ts (protección de rutas)
```

## Decisiones de Diseño

1. **Sanctum cookie-based vs Bearer tokens**: Elegimos cookie-based para SPA porque es más seguro (no expone tokens en JavaScript) y es el patrón oficial de Sanctum.

2. **Enum simple vs Spatie Permission**: Para una app personal, un enum simple es suficiente. Spatie sería sobre-ingeniería.

3. **UUID v7 vs UUID v4**: UUID v7 es ordenable por tiempo, mejor para índices de base de datos.

4. **proxy.ts vs middleware.ts**: Next.js 16 deprecó middleware.ts en favor de proxy.ts.

5. **Puertos no estándar (3010, 8010, etc.)**: Para evitar conflictos con otros proyectos en desarrollo.

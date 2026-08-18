# Backend

**Stack:** Laravel 13 · PHP 8.3 · PostgreSQL 16 · Redis 7 · MinIO (S3)

## Rutas API

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/health` | GET | Health check (DB, Redis, MinIO) |
| `/api/user` | GET | Usuario autenticado (auth:sanctum) |

## Testing

### SQLite (rápido, por defecto)

```bash
./test.sh
# o
docker compose exec backend php artisan test
```

### PostgreSQL (integración)

```bash
./test-pg.sh
# o
docker compose exec backend php artisan test --configuration=phpunit-pg.xml
```

## Estructura

```
backend/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/
├── database/migrations/
├── routes/
│   └── api.php
└── tests/
    ├── Feature/
    └── Unit/
```

## Comandos útiles

```bash
# Migraciones
docker compose exec backend php artisan migrate
docker compose exec backend php artisan migrate:fresh

# Crear migración
docker compose exec backend php artisan make:migration create_xxx_table

# Crear modelo
docker compose exec backend php artisan make:model Xxx
```

## Notas

- Dual testing: SQLite (rápido) + PostgreSQL (realista)
- MinIO configurado como S3-compatible (`filesystems.php` → disk `s3`)
- Redis para cache, colas y sesiones

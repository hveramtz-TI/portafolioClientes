# Database

**Stack:** PostgreSQL 16

## Gestión del esquema

El esquema se maneja exclusivamente via **migraciones de Laravel** en `backend/database/migrations/`.

### Inicialización

```bash
# Levantar servicios
docker compose up -d

# Ejecutar migraciones (primera vez)
docker compose exec backend php artisan migrate

# Reset completo (borra todo y re-migra)
docker compose exec backend php artisan migrate:fresh
```

### Crear nueva migración

```bash
docker compose exec backend php artisan make:migration create_xxx_table
```

### Conexión directa

```bash
docker exec -it portafolio-postgres psql -U portafolio -d portafolio
```

## Estructura

```
backend/database/
├── migrations/    # Migraciones PHP (source of truth)
├── seeders/       # Datos de prueba
├── factories/     # Factories para testing
└── database.sqlite  # SQLite local (tests rápidos)
```

## Notas

- PostgreSQL 16 (UUID como primary key)
- Timestamps automáticos (`created_at`, `updated_at`)
- Soft deletes opcionales (`deleted_at`)
- Dual testing: SQLite (rápido) + PostgreSQL (integración)

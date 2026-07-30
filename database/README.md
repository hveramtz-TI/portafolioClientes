# Database - Portafolio de Clientes

Esta carpeta contiene scripts SQL de inicialización para PostgreSQL.

## Estructura

```
database/
├── init/
│   ├── 01_schema.sql    # Esquema base (tablas principales)
│   └── 02_seed.sql      # Datos de prueba para desarrollo
├── migrations/          # Carpeta vacía (las migraciones reales están en backend/database/migrations/)
└── README.md
```

## Uso

### Inicialización manual (desarrollo)

```bash
# Conectar a PostgreSQL
docker exec -it portafolio-postgres psql -U portafolio -d portafolio

# Ejecutar esquema
\i /docker-entrypoint-initdb.d/01_schema.sql

# Ejecutar datos de prueba
\i /docker-entrypoint-initdb.d/02_seed.sql
```

### Inicialización automática (Docker)

Los scripts en `init/` se ejecutan automáticamente cuando el contenedor PostgreSQL se crea por primera vez si los montás en `/docker-entrypoint-initdb.d/`.

## Migraciones de Laravel

Las migraciones reales del proyecto están en:
```
backend/database/migrations/
```

Para ejecutarlas:
```bash
cd backend
./vendor/bin/sail artisan migrate
```

## Notas

- **PostgreSQL 16**: Se usa UUID como primary key para mejor distribución
- **Soft deletes**: Considerar agregar `deleted_at` si necesitás borrado lógico
- **Timestamps**: Todas las tablas tienen `created_at` y `updated_at`

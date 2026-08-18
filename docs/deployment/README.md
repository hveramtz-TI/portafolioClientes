# Deployment

**Stack:** Docker Compose · Coolify (target)

## Servicios

| Servicio | Imagen/Versión | Puerto | Healthcheck |
|----------|----------------|--------|-------------|
| frontend | Node 20 Alpine | 3000 | `wget http://localhost:3000` |
| backend | PHP 8.3-FPM Alpine | 8000 | `wget http://localhost:8000/api/health` |
| postgres | Postgres 16 Alpine | 5432 | `pg_isready` |
| redis | Redis 7 Alpine | 6379 | `redis-cli ping` |
| minio | MinIO latest | 9000, 9001 | `mc ready local` |

## Variables de entorno

Ver `.env.example` para todas las variables necesarias.

Principales:
- `APP_KEY` — Laravel app key
- `POSTGRES_PASSWORD` — Contraseña de PostgreSQL
- `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD` — Credenciales MinIO

## Comandos

```bash
# Levantar todo
docker compose up -d

# Ver logs
docker compose logs -f

# Ver estado
docker compose ps

# Borrar todo (incluye volúmenes)
docker compose down -v
```

## Volúmenes

- `postgres-data` — Datos de PostgreSQL
- `redis-data` — Datos de Redis
- `minio-data` — Objetos de MinIO
- `backend-storage` — Storage de Laravel
- `backend-logs` — Logs de Laravel

## Notas

- Deploy target: Coolify
- Red interna: `portafolio-network`

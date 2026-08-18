# Portafolio de Clientes

**Stack:** Next.js + Tailwind + TypeScript · Laravel (PHP) · PostgreSQL · MinIO (S3)

## Quickstart

```bash
cp .env.example .env
docker compose up -d
```

## Servicios

| Servicio | URL | Puerto |
|----------|-----|--------|
| Frontend | http://localhost:3000 | 3000 |
| Backend API | http://localhost:8000/api | 8000 |
| Health Check | http://localhost:8000/api/health | 8000 |
| PostgreSQL | localhost | 5432 |
| Redis | localhost | 6379 |
| MinIO API | http://localhost:9000 | 9000 |
| MinIO Console | http://localhost:9001 | 9001 |

## Documentación

- [Frontend](docs/frontend/README.md)
- [Backend](docs/backend/README.md)
- [Database](docs/database/README.md)
- [Deployment](docs/deployment/README.md)

## Estructura del proyecto

```
portafolioClientes/
├── frontend/          # Next.js 16 + Tailwind + TypeScript
├── backend/           # Laravel 13 (API REST)
└── docs/              # Documentación por pila
```

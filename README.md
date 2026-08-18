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
| Frontend | http://localhost:3010 | 3010 |
| Backend API | http://localhost:8010/api | 8010 |
| Health Check | http://localhost:8010/api/health | 8010 |
| PostgreSQL | localhost | 5433 |
| Redis | localhost | 6380 |
| MinIO API | http://localhost:9010 | 9010 |
| MinIO Console | http://localhost:9011 | 9011 |

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

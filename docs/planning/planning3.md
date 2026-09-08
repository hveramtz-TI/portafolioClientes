# Planning — Rubros, Categorías y Servicios

**Fecha:** 2026-08-28
**Estado:** 🔵 **En progreso (retomado 2026-09-08)** — Slices 1 y 2 (de 7) integrados y verificados; el detalle en *Registro de progreso* al final. Pendientes: Slices 3-7 (fork resolver/cascade, API de forks, seeders, frontend).
**Objetivo:** Implementar el catálogo personalizable de rubros, categorías y servicios sobre el modelo híbrido (catálogo base global + fork personal por usuario), incluyendo CRUD, lifecycle (desactivar/eliminar/reactivar), seeders del catálogo base y personalización sin alterar la base ni afectar a otros usuarios.

## Contexto

- Stack: Next.js 16 (App Router) · React 19 · Tailwind CSS 4 · TypeScript · Laravel 13 · PostgreSQL 16 · Redis 7 · MinIO. Componentes UI con **shadcn/ui**.
- **planning1 (Auth + Roles):** implementado. Sanctum cookie-based (SPA), roles `admin`/`user`, UUIDv7 PK en `users`, middleware `EnsureRole`, login + seeder admin (sin registro público).
- **planning2 (Clientes y empresas):** implementado. Modelos `Client` y `Company`, RUT único condicional, estados Activo/Desactivado, seeders ordenados (`CompanySeeder` antes de `ClientSeeder`), shadcn/ui en formularios.
- **Estado actual del catálogo:** no existe. No hay modelos `Rubro`/`Categoria`/`Service`/`UserCatalogItem` ni rutas de catálogo. Esta épica construye el modelo híbrido completo y sus APIs; el perfil público y las órdenes de trabajo (que consumen este catálogo) son épicas posteriores.
- Jerarquía: `Rubro → Categoría → Servicio`. Categorías y servicios en **lenguaje natural** orientado al cliente; términos técnicos (`frontend`, `backend`, `fullstack`, etc.) solo como **etiquetas internas opcionales**.
- Reglas de proyecto: KISS, YAGNI, feature-first, Clean Architecture, UUID como PK, Docker-first, migraciones como fuente de verdad del esquema.

## Decisiones confirmadas

| # | Decisión | Opción elegida | Fuente |
|---|----------|----------------|--------|
| D1 | Modelo de datos | **Híbrido**: tablas base globales (`rubros`, `categorias`, `services`) + `user_catalog_items` (polimórfico `item_type`: `rubro`\|`categoria`\|`service`) con `base_id` (referencia a la base, nullable) y `parent_fork_id` (jerarquía canónica del fork) | HU-025, flujo `catalogo-personalizacion.md` |
| D2 | Origen de los registros | El usuario crea/edita/elimina **solo items personales** (`user_catalog_items`, `base_id = null` o fork de base) vía `/api/user-catalog/...` (HU-013, HU-017, HU-021). El catálogo **base** se crea/modifica/desactiva solo mediante **seeders/admin** (HU-024) vía endpoints base | HU-013, HU-017, HU-021, HU-024 |
| D3 | Estado de cada item | Columna dedicada `status` (`activo` \| `desactivado`) en base y en `user_catalog_items`. **`overrides` nunca contiene status**. `SoftDeletes` para eliminación definitiva (`deleted_at`); desactivar NO usa `deleted_at` | HU-015, HU-019, HU-023, HU-025, flujo `lifecycle` |
| D4 | Tags técnicos | `tags` JSON array nullable en `services` (y override en fork). Lista permitida: `['frontend', 'backend', 'fullstack', 'devops', 'mobile']`. Validación en Request | HU-021, HU-024, flujo `servicio-crud.md` |
| D5 | Unicidad | **Base:** `name` único global (rubros), `rubro_id + name` (categorías), `categoria_id + title` (services). **Personal:** validación de aplicación — identidad única `user_id + item_type + base_id`; nombre/título visible único dentro de `parent_fork_id`. No se usan FKs ni índices únicos genéricos para lo polimórfico | HU-013/014/017/018/021/022/025, flujos CRUD |
| D6 | Estado efectivo | **Dinámico**: estado propio + estado de la base + estado de todos los ancestros fork. Cualquier `desactivado` en la cadena bloquea la visibilidad (AND lógico Rubro ∧ Categoría ∧ Servicio). Ningún override vence un ancestro desactivado | HU-015, HU-019, HU-023, flujo `lifecycle` |
| D7 | Reactivación | Establece `status = 'activo'` **solo en el item seleccionado**. No reactiva hijos desactivados; un ancestro desactivado mantiene oculto al item | HU-015, HU-019, HU-023, flujo `lifecycle` |
| D8 | Mover servicio | Editar categoría padre mueve el servicio: en **fork** cambia `parent_fork_id` al fork de categoría destino (la categoría no se guarda en `overrides`); en **base** cambia `categoria_id`. Validar unicidad de título en la categoría destino | HU-022, flujo `servicio-crud.md` |
| D9 | Eliminación | **Fork**: `DELETE /api/user-catalog/...` elimina recursivamente el fork y sus hijos (cascada), base intacta. **Base**: `forceDelete()` solo si 0 forks y 0 relaciones (categorías/servicios/órdenes); con relaciones → desactivar (HU-015/019) | HU-016, HU-020, HU-023, flujo `lifecycle` |
| D10 | Snapshot histórico | Al agregar un servicio a una orden, `order_services` guarda copia **inmutable** de `title` + `value` (más `service_id` y referencia al fork si corresponde). Cambios posteriores del servicio NO afectan órdenes existentes | HU-021, HU-022, HU-023, flujo `servicio-crud.md` |
| D11 | Set exacto de seeders | `RubroSeeder` (3 rubros: Informática, Diseño, Consultoría) → `CategoriaSeeder` (7 categorías: Informática 3, Diseño 2, Consultoría 2 — incluye `X` placeholder MVP sin servicios) → `ServiceSeeder` (12 servicios), todos `status='activo'`, valores CLP enteros, ordenados vía `DatabaseSeeder`. **Conteo: 22 registros base. Informática = 3 categorías y 6 servicios** | HU-024, flujo `seeders-catalogo.md` |
| D12 | Fork en cascada | Seleccionar un rubro base → `POST /api/user-catalog/rubros/{baseId}/fork` crea el item rubro + copia sus categorías y servicios en cascada como items del usuario (una sola operación atómica a nivel de negocio) | HU-025, flujo `catalogo-personalizacion.md` |
| D13 | Herencia dinámica | Fork sin override → ve el valor base actualizado automáticamente. Fork con override → ve su versión (badge "Override"). Override permite volver a base (p. ej. `value: null` elimina el override) | HU-025, flujo `catalogo-personalizacion.md` |

## Flujos UX/UI de referencia

| Flujo | Qué cubre |
|-------|-----------|
| `docs/flujos/rubro-crud.md` | CRUD completo de rubros: crear personal, fork de base, editar (base vs fork), desactivar/reactivar, eliminar según relaciones |
| `docs/flujos/categoria-crud.md` | CRUD de categorías dentro de un rubro (base o fork), fork en cascada de servicios, visibilidad compuesta con el rubro padre |
| `docs/flujos/servicio-crud.md` | CRUD de servicios: crear personal, editar título/descripción/valor/tags, mover de categoría, desactivar vs eliminar según historial, snapshot en `order_services` |
| `docs/flujos/rubro-categoria-servicio-lifecycle.md` | Ciclo de vida desactivar/eliminar/reactivar: cascada de visibilidad, matriz AND pública, reglas de hard delete y reactivación por item |
| `docs/flujos/catalogo-personalizacion.md` | Modelo híbrido y fork: estructura de `user_catalog_items`, override, creación personal (`base_id=null`), herencia dinámica, resolución de estado efectivo, endpoints `/api/user-catalog/...` |
| `docs/flujos/seeders-catalogo.md` | Carga inicial del catálogo base: set exacto (3/7/12 = 22 registros), orden de seeders, idempotencia (`firstOrCreate`), verificación de conteos |

## Alcance

### Backend (Laravel 13)

**Modelos:** `Rubro`, `Categoria`, `Service` (tablas base) y `UserCatalogItem` (fork polimórfico). Traits `HasUuids` (UUIDv7) y `SoftDeletes` donde aplique.

**Migraciones:** `rubros`, `categorias` (FK `rubro_id`), `services` (FK `categoria_id`, `value` integer CLP ≥ 0, `tags` JSON nullable) y `user_catalog_items` (FK `user_id`, `item_type`, `base_id` nullable, `parent_fork_id` nullable self-FK, `overrides` JSONB, `status`, `sort_order`). Índices de consulta; unicidad polimórfica a nivel de aplicación.

**Policies / autorización:** usuario propietario gestiona sus forks vía `/api/user-catalog/...`; mutaciones de base requieren rol `admin` (reutilizar middleware `EnsureRole`).

**Requests (Form Requests):** validación de unicidad por ámbito (D5), tags dentro de lista permitida (D4), `value` `integer|min:0`, `parent_fork_id` coherente con `item_type`.

**Controllers y rutas:**
- Base (admin/seeder): `GET/POST/PUT/DELETE /api/rubros`, `/api/categorias`, `/api/services` + `PATCH .../deactivate|reactivate`; listados de hijos (`GET /api/rubros/{id}/categorias`, `GET /api/categorias/{id}/services`).
- Fork (usuario): `POST /api/user-catalog/{type}/{baseId}/fork`, `POST/PUT/DELETE /api/user-catalog/{type}`, `PATCH /api/user-catalog/{type}/{forkId}/deactivate|reactivate`, `GET /api/user-catalog/tree` (con filtros de status/origen).

**Seeders:** `RubroSeeder` (3), `CategoriaSeeder` (7), `ServiceSeeder` (12) y orden en `DatabaseSeeder` (después de `UserSeeder`, antes de clientes/empresas). Idempotentes con `firstOrCreate`. Conteo verificado: 22 registros.

**Tests PHPUnit (Feature):**
- HU-024: conteos exactos (`assertDatabaseCount`) 3/7/12 y 22 total; valores CLP y tags del set aprobado.
- HU-013/017/021: creación de items personales con `base_id=null`; unicidad en fork.
- HU-014/018/022: edición base vs fork; propagación vs aislamiento; mover servicio validando unicidad en destino; snapshot inmutable en `order_services`.
- HU-015/019/023: desactivar conserva historial; oculta en público; estado efectivo dinámico.
- HU-016/020/023: eliminación recursiva de fork con hijos; base no eliminable con forks/relaciones; bloqueo y mensaje "debe desactivar".
- HU-025: fork en cascada (rubro → categorías → servicios), override, herencia dinámica, reactivación por item.

### Frontend (Next.js 16)

- Páginas de gestión de catálogo: **Catálogo Base** (rubros globales con badge "Base" y botón "Seleccionar") y **Mi Catálogo** (árbol fork rubro → categorías → servicios con columnas nombre, valor, tags, status, origen).
- Componentes shadcn/ui: dialog (modales de crear/editar y confirmación de eliminación), form + input/select (formularios de rubro/categoría/servicio), table, badge, switch/filtros de vista (activos/desactivados/solo personales/ocultar heredados).
- **Badges de origen:** "Base" (hereda), "Override" (personalizado por campo), "Personal" (`base_id=null`).
- Consumo de APIs desde `lib/api.ts` (cliente existente con CSRF) hacia `/api/...` y `/api/user-catalog/...`.
- Mensajes de confirmación diferenciados: con relaciones → "desactivará el elemento y sus hijos"; sin relaciones → "se eliminará permanentemente".
- Tests Jest por página/componente (render de árbol, badges, filtros, flujo de crear/editar/desactivar).

## Fuera de alcance (YAGNI)

- Permisos granulares (Spatie Permission) — se mantiene el par `admin`/`user` de planning1.
- Multi-idioma del catálogo.
- Catálogo colaborativo (edición compartida entre usuarios o empresas).
- Modificación del catálogo base por un usuario normal (solo admin/seeder).

## Verificación

```bash
# Frontend
cd frontend && npm run lint
cd frontend && npm test

# Backend (en Docker)
docker compose exec backend php artisan test

# Seeders del catálogo base
docker compose exec backend php artisan db:seed

# Verificación de conteos (3 rubros, 7 categorías, 12 servicios)
docker compose exec backend php artisan tinker --execute="
    echo 'Rubros: ' . App\Models\Rubro::where('status','activo')->count() . PHP_EOL;
    echo 'Categorías: ' . App\Models\Categoria::where('status','activo')->count() . PHP_EOL;
    echo 'Servicios: ' . App\Models\Service::where('status','activo')->count() . PHP_EOL;
"

# CodeGraph
codegraph sync && codegraph status
```

## Nota — Propuestas a definir en SDD-design (no decisiones)

Los siguientes puntos quedan **abiertos** y se resolverán en la fase de SDD-design, sin comprometer este planning:

- **Rate limiting** en endpoints públicos y de catálogo.
- **Auditoría de cambios de status** (registro de quién/cuándo desactivó o reactivó).
- **Cache warmup** del catálogo base en Redis (p. ej. claves `catalog:rubros|categorias|services`, TTL 24h) — marcado como opcional en el flujo de seeders.
- **Mecánica de transacciones** concretas (p. ej. atomicidad del fork en cascada y de los seeders) y estrategia exacta de persistencia del hard delete (`SoftDeletes` + `forceDelete()`), que no altera las reglas de autorización ni cascada definidas aquí.

---

## Registro de progreso

### 2026-09-08 — Slices 1 y 2 integrados en `main`

Se retomó el sprint pospuesto. Estado del chain de 7 slices:

| Slice | Alcance | PR | Estado |
|-------|---------|----|--------|
| 1/7 | Migraciones + models (esquema híbrido) | #6 | ✅ Merged al tracker |
| 2a | API base **rubros** (admin) | #8 | ✅ Merged al tracker |
| 2b | API base **categorías** (admin) | #9 | ✅ Merged al tracker |
| 2c | API base **servicios** (admin) + move + tags | #10 | ✅ Merged al tracker |
| 3-7 | Fork resolver/cascade, API de forks, seeders, frontend | — | ⬜ Pendiente |

**Integración a `main`:** merge tracker→main en `4922634` (PR #11). El tracker quedó sincronizado con `main` para que el Slice 3 nazca con todo.

**Verificación en verde (ambos motores):** suite completa **90 tests / 234 assertions** en SQLite **y** PostgreSQL, medida sobre `main` ya fusionado y corriendo por los bind mounts de desarrollo (sin rebuild).

**Review adversarial del stack 2a/2b/2c (antes de mergear):** detectó y se corrigieron 3 blockers con evidencia RED→GREEN:

- **B1/B2:** `RubroController::destroy` y `CategoriaController::destroy` no chequeaban forks de usuario (`UserCatalogItem.base_id`) → un admin podía `forceDelete` dejando `base_id` colgado. Añadido guard 409 (mismo patrón que `ServiceController`). Cubre **D9** (eliminación de base solo si 0 forks y 0 relaciones) y **HU-016/HU-020**.
- **B3:** `order` (`StoreCategoriaRequest`) y `categoria_id` (`UpdateServiceRequest`) usaban `nullable`, dejando pasar un `null` explícito contra columnas NOT NULL → 500 vía API. Cambiado a `sometimes` → 422 correcto. Cubre **D5**/reglas de request.
- **Criticals de cobertura:** +store tests de categorías, +test del guard de forks de servicios, +aserción de round-trip de `tags` (cast `array`). Pint normalizado en los 12 archivos del catálogo.

**Infraestructura de dev (necesaria para poder verificar):** se agregó bind-mount del código del backend al compose de desarrollo (`docker-compose.override.yml`) y se arregló `test-pg.sh`/`phpunit-pg.xml` (`<env>`→`<server>` + PHPUnit directo), porque antes la imagen buzoneaba el código y los tests del backend corrían contra código viejo.

**HUs afectadas:** **sin cambio de estado** — el backend de la API base está implementado y verificado, pero las HUs HU-013–HU-025 describen la funcionalidad de usuario **end-to-end** (requiere el modelo de fork — Slice 3 — y el frontend — Slices 5-7). Marcarlas *Implementada* ahora sería prematuro y falso; el catálogo aún no es operable por un usuario normal. Se actualizarán al cerrar el epic.

**Pendiente administrativo:** cerrar el token `sdd-attempt` de remediación (el ledger lo dejó `blocked` porque los merges de PR ocurrieron con el intento abierto; requiere un `reset` explícito de maintainer, que es decisión deliberada y no automática).
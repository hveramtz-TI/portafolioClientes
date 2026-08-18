# Project Agents — portafolioClientes

## Stack

- **Frontend:** Next.js 16 · React 19 · Tailwind CSS 4 · TypeScript · Jest 30
- **Backend:** Laravel 13 · PHP 8.3 · PostgreSQL 16 · Redis 7 · MinIO (S3)
- **Infraestructura:** Docker Compose con healthchecks

## Development Workflow

### Planning First

Siempre empezar cada nueva feature en **Planning mode**.

**Convención de archivos:**
- Los planning van en `docs/planning/` (centralizado).
- Nomenclatura: `planning1.md`, `planning2.md`, `planning3.md`, etc. (numeración secuencial).
- Cada planning es una fase/sprint separado.
- El planning actual es `docs/planning/planning1.md` (Auth + Roles).

Antes de ejecutar cualquier workflow de SDD:

1. Crear un `planning{N}.md` en `docs/planning/`.
2. Validar el planning con el usuario.
3. **No generar código durante la fase de planning.**
4. El documento de planning se convierte en la fuente de verdad para SDD.

### SDD Workflow

Una vez aprobado el `planning.md`:

```
planning.md ↓ sdd-spec ↓ sdd-design ↓ sdd-tasks ↓ sdd-apply ↓ sdd-verify
```

- Usar siempre el documento de planning como contexto primario.
- **Nunca inventar requisitos que no estén en el planning** salvo que se solicite explícitamente.
- Si falta información, **preguntar antes de continuar**.

## Coding

### Core Principles

- **KISS** → Priorizar soluciones simples y claras.
- **DRY** → Evitar duplicación innecesaria.
- **YAGNI** → No implementar funcionalidades que no se necesitan.
- **SOLID** → Aplicarlo cuando aporte claridad, no como dogma.
- **Separation of Concerns** → Cada componente debe tener una responsabilidad clara.
- **High Cohesion / Low Coupling** → Mantener módulos bien definidos y poco dependientes.
- **Fail Fast** → Detectar y comunicar errores lo antes posible.
- **Clean Code** → Código legible, nombres descriptivos y funciones pequeñas.
- **Composition over Inheritance** → Preferir composición cuando sea una mejor opción.
- **Convention over Configuration** → Seguir las convenciones del lenguaje o framework.
- **Program to Interfaces** → Depender de abstracciones cuando tenga sentido.
- **Encapsulation** → Ocultar detalles internos y exponer solo lo necesario.
- **Principle of Least Surprise** → El comportamiento del código debe ser predecible.
- **Boy Scout Rule** → Dejar el código mejor de como se encontró.
- **Testing mindset** → Escribir código fácil de probar, aunque no siempre se generen pruebas.

### Architecture & Structure

- Seguir **Clean Architecture**.
- Mantener archivos pequeños y enfocados.
- Escribir código mantenible.

### Project-Specific Constraints

- **Docker-first:** Todo corre en Docker Compose. Usar `docker compose exec` para comandos del backend.
- **Next.js 16 App Router:** Usar App Router, no Pages Router. Leer `frontend/node_modules/next/dist/docs/` antes de escribir código (APIs pueden diferir del training data).
- **Laravel 13:** Seguir convenciones de Laravel. Migraciones son la fuente de verdad del esquema (no SQL directo).
- **PostgreSQL 16:** UUID como primary key. Timestamps automáticos. Soft deletes opcionales.
- **MinIO (S3):** Configurado como `filesystems.php` disk `s3`. No usar storage local.
- **Personal App:** No sobre-ingenierizar. Si una solución simple funciona, usarla.
- **Feature-first structure:** El código de una feature vive junto, no disperso por capas.

### Available Skills

Este proyecto tiene skills específicas en `.opencode/command/` que deben usarse cuando apliquen:

- `verify-frontend` → Tests Jest del frontend.
- `verify-laravel` → Tests PHPUnit del backend en Docker.
- `verify-lint` → ESLint del frontend.
- `sync-codegraph` → Sincronizar índice de CodeGraph.

## Verification Commands

Antes de commitear, ejecutar las verificaciones relevantes:

```bash
# Frontend
cd frontend && npm run lint        # ESLint
cd frontend && npm test            # Jest tests

# Backend (en Docker)
docker compose exec backend php artisan test
./test-pg.sh                       # Tests contra PostgreSQL

# CodeGraph
codegraph sync && codegraph status
```

## Documentation Lookup (MANDATORY)

Cuando haya duda sobre una API, comportamiento de librería, configuración o feature del framework:

1. **Usar Context7 MCP primero**: llamar `context7_resolve-library-id` para obtener el library ID, luego `context7_query-docs` para el concepto específico. Esto da documentación actualizada y precisa por versión.
2. **Fallback**: usar `webfetch` para leer docs oficiales (nextjs.org, laravel.com, tailwindcss.com, etc.).

**NO adivinar APIs, props u opciones de configuración.** Verificar contra docs antes de usarlas.

<!-- CODEGRAPH_START -->
## CodeGraph

En repositorios indexados por CodeGraph (existe un directorio `.codegraph/` en la raíz), usarlo ANTES de grep/find o leer archivos cuando necesites entender o localizar código:

- **MCP tool** (cuando esté disponible): `codegraph_explore` responde la mayoría de las preguntas de código en una sola llamada — el source verbatim de los símbolos relevantes más los call paths entre ellos, incluyendo dynamic-dispatch hops que grep no puede seguir. Nombrar un símbolo o archivo en el query para leer su source actual con números de línea. Si está listado pero diferido, cargarlo por nombre vía tool search.
- **Shell** (siempre funciona): `codegraph explore "<symbol names or question>"` imprime el mismo output.

Si no existe un directorio `.codegraph/`, saltar CodeGraph completamente — el indexing es decisión del usuario.
<!-- CODEGRAPH_END -->

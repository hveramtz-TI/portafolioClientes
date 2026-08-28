# Flujo: Personalización Catálogo (Fork / Copia Personal)

**Épica:** Rubros, categorías y servicios
**HU principal:** HU-025
**HUs relacionadas:** HU-013, HU-014, HU-015, HU-016, HU-017, HU-018, HU-019, HU-020, HU-021, HU-022, HU-023
**Fecha:** 2026-08-28

---

## Diagrama Mermaid

```mermaid
flowchart TD
    %% Estilos
    classDef start fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px
    classDef process fill:#e3f2fd,stroke:#1565c0,stroke-width:2px
    classDef decision fill:#fff3e0,stroke:#ef6c00,stroke-width:2px
    classDef end fill:#fce4ec,stroke:#c2185b,stroke-width:2px
    classDef fork fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px
    classDef ui fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px,stroke-dasharray: 5 5

    %% Nodos
    Start([Inicio: Usuario ve catálogo base\n(Rubros globales)]):::start
    
    %% VISTA CATÁLOGO BASE
    ViewBase[UI: Lista rubros base\n→ Badge "Base" en cada uno\n→ Botón "Seleccionar" / "Personalizar"]:::ui
    Start --> ViewBase
    
    %% ACCIÓN: SELECCIONAR RUBRO BASE (CREAR FORK)
    ViewBase -->|Click "Seleccionar" rubro| ForkRubro[POST /api/user-catalog/rubros/{rubroId}/fork\n→ Transacción atómica:\n  1. CREATE user_catalog_items (rubro)\n     - user_id, item_type='rubro', base_id=rubroId\n     - overrides={}, status='activo', sort_order\n  2. FORK CATEGORÍAS en cascada\n     FOR cada categoria IN rubro.categorias:\n       CREATE user_catalog_items (categoria)\n       - base_id=categoria.id, rubro_fork_id=recien_creado\n  3. FORK SERVICIOS en cascada\n     FOR cada servicio IN categorias.servicios:\n       CREATE user_catalog_items (servicio)\n       - base_id=servicio.id, categoria_fork_id=recien_creado]:::fork
    
    ForkRubro --> ForkSuccess{¿Transacción\nOK?}:::decision
    ForkSuccess -->|Sí| ViewForkRubro[UI: Muestra rubro en "Mi Catálogo"\n→ Badge "Personalizado"\n→ Árbol completo: rubro → categorías → servicios\n→ Badges heredados: "Base" (sin cambios)]:::ui
    ForkSuccess -->|No| ForkError[Rollback completo\n→ Error al usuario\n→ Intenta de nuevo]:::end
    
    %% VISTA CATÁLOGO PERSONAL (MI CATÁLOGO)
    ViewForkRubro --> MyCatalog[UI: "Mi Catálogo" - Vista personal\n→ Tree: Rubros (forks) → Categorías → Servicios\n→ Columnas: Nombre | Valor | Tags | Status | Origen\n→ Origen: "Base" / "Override" / "Personal"]:::ui
    
    %% ACCIONES EN FORK EXISTENTE
    MyCatalog --> Action{¿Acción usuario?}:::decision
    
    %% OVERRIDE: EDITAR CAMPO
    Action -->|Edita campo (nombre, valor, tags, desc)| OverrideField[PUT /api/user-catalog/{type}/{forkId}\n→ Body: { field: "nuevo_valor" }\n→ Sistema: merge en overrides JSON\n→ overrides.{field} = nuevo_valor\n→ status='activo' (si estaba desactivado)]:::fork
    OverrideField --> MyCatalog
    
    %% OVERRIDE: DESACTIVAR ITEM
    Action -->|Click "Desactivar"| OverrideDeactivate[PATCH /api/user-catalog/{type}/{forkId}/deactivate\n→ status='desactivado' en fork\n→ NO toca base\n→ Cascada en fork: hijos heredan desactivado\n→ Badge UI: "Desactivado (personal)"]:::fork
    OverrideDeactivate --> MyCatalog
    
    %% OVERRIDE: REACTIVAR ITEM
    Action -->|Click "Reactivar"| OverrideReactivate[PATCH /api/user-catalog/{type}/{forkId}/reactivate\n→ status='activo' en fork\n→ Cascada: hijos heredan SI no tienen override desactivado]:::fork
    OverrideReactivate --> MyCatalog
    
    %% CREAR NUEVO 100% PERSONAL (base_id=null)
    Action -->|Click "Nuevo rubro/categoría/servicio"| CreatePersonal[POST /api/user-catalog/{type}\n→ base_id = null\n→ parent_fork_id = fork padre\n→ overrides = {todos los campos}\n→ Es 100% personal, sin base]:::fork
    CreatePersonal --> MyCatalog
    
    %% ELIMINAR FORK (solo quita copia usuario)
    Action -->|Click "Eliminar de mi catálogo"| DeleteFork{¿Tiene hijos\nfork?}:::decision
    DeleteFork -->|Sí| DeleteForkCascade[DELETE /api/user-catalog/{type}/{forkId}\n→ Cascada: elimina hijos fork recursivamente\n→ Base intacta, otros usuarios intactos]:::fork
    DeleteFork -->|No| DeleteForkSimple[DELETE /api/user-catalog/{type}/{forkId}\n→ Quita solo este item]:::fork
    DeleteForkCascade --> MyCatalog
    DeleteForkSimple --> MyCatalog
    
    %% HERENCIA DINÁMICA (BASE CAMBIA)
    MyCatalog -.->|Evento: Admin edita base| BaseChange[Admin: PUT /api/rubros,categorias,services/{id}\n→ Cambia name, value, etc en base]:::process
    BaseChange --> InheritCheck{¿Usuario tiene\noverride en ese campo?}:::decision
    InheritCheck -->|No (sin override)| AutoInherit[Usuario ve cambio automático\n→ UI refresca: valor base actualizado\n→ Badge sigue "Base"]:::ui
    InheritCheck -->|Sí (tiene override)| KeepOverride[Usuario sigue viendo SU valor\n→ Badge "Override" en campo\n→ Base cambio no afecta]:::fork
    AutoInherit --> MyCatalog
    KeepOverride --> MyCatalog
    
    %% SINCRONIZACIÓN VISIBILIDAD
    MyCatalog -.->|Filtro vista| VisibilityFilter[UI Filtros:\n☑ Mostrar solo activos\n☑ Mostrar desactivados\n☑ Mostrar solo personales\n☑ Ocultar heredados base]:::ui
    VisibilityFilter --> MyCatalog
    
    %% FIN
    MyCatalog -->|Salir| End([Fin: Catálogo personal listo\npara Perfil Público / Órdenes]):::end
```

---

## Tabla de Pasos (para Notion)

| Paso | Acción Usuario | Endpoint / Sistema | Qué Pasa en BD (`user_catalog_items`) | UI Resultado |
|------|----------------|-------------------|----------------------------------------|--------------|
| 1 | Ve catálogo base (rubros globales) | GET `/api/rubros` (base) | — | Lista rubros con badge "Base", botón "Seleccionar" |
| 2 | Click "Seleccionar" en rubro "Informática" | POST `/api/user-catalog/rubros/{id}/fork` | **Transacción atómica**:<br>1. Crea fork rubro<br>2. Fork categorías (7)<br>3. Fork servicios (11) | Rubro aparece en "Mi Catálogo" con árbol completo, badges "Base" |
| 3 | En "Mi Catálogo": edita nombre rubro | PUT `/api/user-catalog/rubros/{forkId}` body: `{name: "Desarrollo Web"}` | `overrides: {name: "Desarrollo Web"}` | Badge "Override" en nombre; base sigue "Informática" |
| 4 | Edita valor servicio "Actualizar portafolio web" | PUT `/api/user-catalog/services/{forkId}` body: `{value: 350000}` | `overrides: {value: 350000}` | Valor $350K en su catálogo; base sigue $300K |
| 5 | Agrega tag "frontend" a servicio | PUT `/api/user-catalog/services/{forkId}` body: `{tags: ["frontend", "fullstack"]}` | `overrides: {tags: [...]}` | Tags actualizados en su fork |
| 6 | Click "Desactivar" en categoría | PATCH `/api/user-catalog/categorias/{forkId}/deactivate` | `status='desactivado'` en fork + cascada a servicios fork | Categoría + servicios con badge "Desactivado (personal)" |
| 7 | Click "Nuevo servicio personal" | POST `/api/user-catalog/services` body: `{categoria_fork_id, title, value, tags}` | `base_id=null`, `overrides={todos campos}` | Servicio 100% personal, badge "Personal" |
| 8 | Click "Eliminar de mi catálogo" (rubro) | DELETE `/api/user-catalog/rubros/{forkId}` | Elimina fork rubro + TODOS sus hijos fork recursivamente | Desaparece de "Mi Catálogo"; base intacta |
| 9 | Admin cambia valor base servicio a $320K | PUT `/api/services/{id}` (admin) | Base actualizada | **Usuario SIN override**: ve $320K automático<br>**Usuario CON override**: ve su $350K (badge "Override") |
| 10 | Usuario quiere volver a valor base | PUT `/api/user-catalog/services/{forkId}` body: `{value: null}` | `overrides.value` eliminado (o unset) | Vuelve a heredar base ($320K); badge "Base" |

---

## Estructura `user_catalog_items` (Tabla Polimórfica)

```sql
CREATE TABLE user_catalog_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id),
    item_type VARCHAR(20) NOT NULL CHECK (item_type IN ('rubro','categoria','service')),
    base_id UUID NULL,                    -- FK a tabla base (rubros/categorias/services), NULL = 100% personal
    parent_fork_id UUID NULL REFERENCES user_catalog_items(id), -- Padre en fork (rubro→categoria→servicio)
    overrides JSONB DEFAULT '{}',         -- Solo campos que difieren de base
    status VARCHAR(20) DEFAULT 'activo' CHECK (status IN ('activo','desactivado')),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Índices y restricciones de unicidad
CREATE UNIQUE INDEX uq_user_catalog_fork_per_base ON user_catalog_items(user_id, base_id) WHERE base_id IS NOT NULL;
CREATE UNIQUE INDEX uq_user_catalog_name_fork ON user_catalog_items(user_id, parent_fork_id, (overrides->>'name'))
    WHERE item_type IN ('rubro','categoria') AND base_id IS NOT NULL AND overrides ? 'name';
CREATE UNIQUE INDEX uq_user_catalog_title_fork ON user_catalog_items(user_id, parent_fork_id, (overrides->>'title'))
    WHERE item_type = 'service' AND base_id IS NOT NULL AND overrides ? 'title';
CREATE UNIQUE INDEX uq_user_catalog_name_personal ON user_catalog_items(user_id, parent_fork_id, (overrides->>'name'))
    WHERE item_type IN ('rubro','categoria') AND base_id IS NULL AND overrides ? 'name';
CREATE UNIQUE INDEX uq_user_catalog_title_personal ON user_catalog_items(user_id, parent_fork_id, (overrides->>'title'))
    WHERE item_type = 'service' AND base_id IS NULL AND overrides ? 'title';

-- Índices de rendimiento
CREATE INDEX idx_user_catalog_user_type ON user_catalog_items(user_id, item_type);
CREATE INDEX idx_user_catalog_base ON user_catalog_items(base_id) WHERE base_id IS NOT NULL;
CREATE INDEX idx_user_catalog_parent ON user_catalog_items(parent_fork_id);
```

---

## Resolución de Valor en Tiempo de Ejecución (Runtime)

```php
// Service: resolve value for user catalog
function resolveUserCatalogValue(UserCatalogItem $fork): array
{
    $base = $fork->base_id ? $fork->base()->first() : null;
    $overrides = $fork->overrides ?? [];
    
    $resolvedName = $fork->item_type === 'service' 
        ? ($overrides['title'] ?? $base?->title)
        : ($overrides['name'] ?? $base?->name);
    
    return [
        'id' => $fork->id,
        'is_fork' => true,
        'base_id' => $base?->id,
        'name' => $resolvedName,
        'value' => $overrides['value'] ?? $base?->value,
        'description' => $overrides['description'] ?? $base?->description,
        'tags' => $overrides['tags'] ?? $base?->tags ?? [],
        'status' => $fork->status, // Fork status gana si es 'desactivado'
        'origin' => $overrides ? 'override' : 'base',
        'origin_field' => array_keys($overrides), // Qué campos son override
    ];
}

// Herencia de status: fork 'desactivado' SIEMPRE gana
function resolveEffectiveStatus(UserCatalogItem $fork): string
{
    if ($fork->status === 'desactivado') return 'desactivado';
    if ($fork->base_id && $fork->base->status === 'desactivado') return 'desactivado';
    if ($fork->parent_fork_id && $fork->parentFork->status === 'desactivado') return 'desactivado';
    return 'activo';
}
```

---

## UI: Representación Visual (Wireframe Lógico)

```
MI CATÁLOGO                                    CATÁLOGO BASE
┌─────────────────────────────────┐           ┌─────────────────────────────────┐
│ 🔷 Informática (Personalizado)  │           │ 🔷 Informática (Base)           │
│   ├─ 🔷 Sitios web (Override)   │           │   ├─ 🔷 Sitios web (Base)       │
│   │   ├─ 🔷 Actualizar portafolio│          │   │   ├─ Actualizar portafolio  │
│   │   │   Valor: $350K [Override]│          │   │   │   Valor: $300K [Base]     │
│   │   │   Tags: [frontend]      │           │   │   │   Tags: [frontend]       │
│   │   ├─ Landing page $450K     │           │   │   ├─ Landing page $450K      │
│   │   └─ E-commerce $800K       │           │   │   └─ E-commerce $800K        │
│   ├─ 🔷 Aplicaciones (Base)     │           │   ├─ Aplicaciones (Base)         │
│   └─ 🔴 Mantenimiento (Desact.) │           │   └─ Mantenimiento (Base)        │
│       └─ Retención $200K        │           │       └─ Retención $200K         │
├─────────────────────────────────┤           ├─────────────────────────────────┤
│ 🔷 Diseño (Personal)            │           │ 🔷 Diseño (Base)                 │
│   └─ (vacío - usuario no fork)  │           │   ├─ Identidad visual            │
├─────────────────────────────────┤           │   └─ UX/UI                       │
│ ➕ Nuevo rubro personal         │           │ ➕ (solo admin)                   │
└─────────────────────────────────┘           └─────────────────────────────────┘

Leyenda: 🔷 = Activo  |  🔴 = Desactivado  |  [Base] = Hereda  |  [Override] = Personalizado  |  [Personal] = base_id=null
```

---

## Reglas de Negocio Clave

| Regla | Descripción |
|-------|-------------|
| **Un fork por base por usuario** | `UNIQUE(user_id, base_id)` — no puede duplicar selección |
| **Override gana siempre** | Campo en `overrides` → usa ese valor; si no → hereda de base |
| **Status: desactivado bloquea herencia** | Fork `desactivado` → hijos efectivos `desactivado` (aunque tengan override `activo`); override `activo` NO vence a padre desactivado |
| **Base cambio → auto-sync** | Si usuario SIN override → ve cambio base automático |
| **Eliminar fork = solo usuario** | `DELETE user_catalog_items` → base y otros forks intactos |
| **Crear personal = base_id=null** | Item 100% propio, sin herencia, editable total |
| **Cascada fork creación** | Seleccionar rubro → fork rubro + categorías + servicios en una transacción |
| **Visibilidad pública** | AND(rubro, categoría, servicio) all `activo` (considerando forks) |

---

## Endpoints API Resumen

| Operación | Endpoint | Body / Params |
|-----------|----------|---------------|
| Fork rubro | `POST /api/user-catalog/rubros/{baseId}/fork` | — |
| Fork categoría | `POST /api/user-catalog/categorias/{baseId}/fork` | — |
| Fork servicio | `POST /api/user-catalog/services/{baseId}/fork` | — |
| Override campo | `PUT /api/user-catalog/{type}/{forkId}` | `{field: value}` (partial) |
| Override múltiple | `PUT /api/user-catalog/{type}/{forkId}` | `{name, value, tags, description, status}` |
| Desactivar fork | `PATCH /api/user-catalog/{type}/{forkId}/deactivate` | — |
| Reactivar fork | `PATCH /api/user-catalog/{type}/{forkId}/reactivate` | — |
| Crear personal | `POST /api/user-catalog/{type}` | `{parent_fork_id, base_id: null, overrides: {...}}` |
| Eliminar fork | `DELETE /api/user-catalog/{type}/{forkId}` | `?cascade=true` (default true) |
| Listar mi catálogo | `GET /api/user-catalog/tree` | `?type=rubro&status=all` |
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
    Start([Inicio: Usuario ve catálogo base<br/>(Rubros globales)]):::start
    
    %% VISTA CATÁLOGO BASE
    ViewBase[UI: Lista rubros base<br/>→ Badge "Base" en cada uno<br/>→ Botón "Seleccionar" / "Personalizar"]:::ui
    Start --> ViewBase
    
    %% ACCIÓN: SELECCIONAR RUBRO BASE (CREAR FORK)
    ViewBase -->|Click "Seleccionar" rubro| ForkRubro[POST /api/user-catalog/rubros/{rubroId}/fork<br/>→ Transacción atómica:<br/>  1. CREATE user_catalog_items (rubro)<br/>     - user_id, item_type='rubro', base_id=rubroId<br/>     - status='activo', sort_order<br/>  2. FORK CATEGORÍAS en cascada<br/>     FOR cada categoria IN rubro.categorias:<br/>       CREATE user_catalog_items (categoria)<br/>       - base_id=categoria.id, parent_fork_id=recien_creado<br/>  3. FORK SERVICIOS en cascada<br/>     FOR cada servicio IN categorias.servicios:<br/>       CREATE user_catalog_items (servicio)<br/>       - base_id=servicio.id, parent_fork_id=recien_creado]:::fork
    
    ForkRubro --> ForkSuccess{¿Transacción<br/>OK?}:::decision
    ForkSuccess -->|Sí| ViewForkRubro[UI: Muestra rubro en "Mi Catálogo"<br/>→ Badge "Personalizado"<br/>→ Árbol completo: rubro → categorías → servicios<br/>→ Badges heredados: "Base" (sin cambios)]:::ui
    ForkSuccess -->|No| ForkError[Rollback completo<br/>→ Error al usuario<br/>→ Intenta de nuevo]:::end
    
    %% VISTA CATÁLOGO PERSONAL (MI CATÁLOGO)
    ViewForkRubro --> MyCatalog[UI: "Mi Catálogo" - Vista personal<br/>→ Tree: Rubros (forks) → Categorías → Servicios<br/>→ Columnas: Nombre, Valor, Tags, Status, Origen<br/>→ Origen: "Base" / "Override" / "Personal"]:::ui
    
    %% ACCIONES EN FORK EXISTENTE
    MyCatalog --> Action{¿Acción usuario?}:::decision
    
    %% OVERRIDE: EDITAR CAMPO
    Action -->|Edita campo (nombre, valor, tags, desc)| OverrideField[PUT /api/user-catalog/{type}/{forkId}<br/>→ Body: { field: "nuevo_valor" }<br/>→ Sistema: merge en overrides JSON<br/>→ overrides.{field} = nuevo_valor<br/>→ status permanece en columna dedicada]:::fork
    OverrideField --> MyCatalog
    
    %% OVERRIDE: DESACTIVAR ITEM
    Action -->|Click "Desactivar"| OverrideDeactivate[PATCH /api/user-catalog/{type}/{forkId}/deactivate<br/>→ status='desactivado' en fork<br/>→ NO toca base<br/>→ Ancestro desactivado bloquea visibilidad descendiente<br/>→ Badge UI: "Desactivado (personal)"]:::fork
    OverrideDeactivate --> MyCatalog
    
    %% OVERRIDE: REACTIVAR ITEM
    Action -->|Click "Reactivar"| OverrideReactivate[PATCH /api/user-catalog/{type}/{forkId}/reactivate<br/>→ status='activo' en el fork seleccionado<br/>→ Hijos desactivados no se reactivan]:::fork
    OverrideReactivate --> MyCatalog
    
    %% CREAR NUEVO 100% PERSONAL (base_id=null)
    Action -->|Click "Nuevo rubro/categoría/servicio"| CreatePersonal[POST /api/user-catalog/{type}<br/>→ base_id = null<br/>→ parent_fork_id = fork padre<br/>→ overrides = {campos editables}<br/>→ Es 100% personal, sin base]:::fork
    CreatePersonal --> MyCatalog
    
    %% ELIMINAR FORK (solo quita copia usuario)
    Action -->|Click "Eliminar de mi catálogo"| DeleteFork{¿Tiene hijos<br/>fork?}:::decision
    DeleteFork -->|Sí| DeleteForkCascade[DELETE /api/user-catalog/{type}/{forkId}<br/>→ Cascada: elimina hijos fork recursivamente<br/>→ Base intacta, otros usuarios intactos]:::fork
    DeleteFork -->|No| DeleteForkSimple[DELETE /api/user-catalog/{type}/{forkId}<br/>→ Quita solo este item]:::fork
    DeleteForkCascade --> MyCatalog
    DeleteForkSimple --> MyCatalog
    
    %% HERENCIA DINÁMICA (BASE CAMBIA)
    MyCatalog -.->|Evento: Admin edita base| BaseChange[Admin: PUT /api/rubros/{id}, /api/categorias/{id} o /api/services/{id}<br/>→ Cambia campos en base]:::process
    BaseChange --> InheritCheck{¿Usuario tiene<br/>override en ese campo?}:::decision
    InheritCheck -->|No (sin override)| AutoInherit[Usuario ve cambio automático<br/>→ UI refresca: valor base actualizado<br/>→ Badge sigue "Base"]:::ui
    InheritCheck -->|Sí (tiene override)| KeepOverride[Usuario sigue viendo SU valor<br/>→ Badge "Override" en campo<br/>→ Base cambio no afecta]:::fork
    AutoInherit --> MyCatalog
    KeepOverride --> MyCatalog
    
    %% SINCRONIZACIÓN VISIBILIDAD
    MyCatalog -.->|Filtro vista| VisibilityFilter[UI Filtros:<br/>☑ Mostrar solo activos<br/>☑ Mostrar desactivados<br/>☑ Mostrar solo personales<br/>☑ Ocultar heredados base]:::ui
    VisibilityFilter --> MyCatalog
    
    %% FIN
    MyCatalog -->|Salir| End([Fin: Catálogo personal listo<br/>para Perfil Público / Órdenes]):::end
```

---

## Tabla de Pasos (para Notion)

| Paso | Acción Usuario | Endpoint / Sistema | Qué Pasa en BD (`user_catalog_items`) | UI Resultado |
|------|----------------|-------------------|----------------------------------------|--------------|
| 1 | Ve catálogo base (rubros globales) | GET `/api/rubros` (base) | — | Lista rubros con badge "Base", botón "Seleccionar" |
| 2 | Click "Seleccionar" en rubro "Informática" | POST `/api/user-catalog/rubros/{id}/fork` | **Transacción atómica**:<br>1. Crea fork rubro<br>2. Fork categorías (3)<br>3. Fork servicios (6) | Rubro aparece en "Mi Catálogo" con árbol completo, badges "Base" |
| 3 | En "Mi Catálogo": edita nombre rubro | PUT `/api/user-catalog/rubros/{forkId}` body: `{name: "Desarrollo Web"}` | `overrides: {name: "Desarrollo Web"}` | Badge "Override" en nombre; base sigue "Informática" |
| 4 | Edita valor servicio "Actualizar portafolio web" | PUT `/api/user-catalog/services/{forkId}` body: `{value: 350000}` | `overrides: {value: 350000}` | Valor $350K en su catálogo; base sigue $300K |
| 5 | Agrega tag "frontend" a servicio | PUT `/api/user-catalog/services/{forkId}` body: `{tags: ["frontend", "fullstack"]}` | `overrides: {tags: [...]}` | Tags actualizados en su fork |
| 6 | Click "Desactivar" en categoría fork | PATCH `/api/user-catalog/categorias/{forkId}/deactivate` | `status='desactivado'` en fork; visibilidad descendiente bloqueada | Categoría + servicios con badge "Desactivado (personal)" |
| 7 | Click "Nuevo servicio personal" | POST `/api/user-catalog/services` body: `{parent_fork_id, title, value, tags}` | `base_id=null`, `overrides={todos campos}` | Servicio 100% personal, badge "Personal" |
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
    base_id UUID NULL,                    -- Referencia conceptual al registro base según item_type; NULL = 100% personal
    parent_fork_id UUID NULL REFERENCES user_catalog_items(id), -- Padre en fork (rubro→categoria→servicio)
    overrides JSONB DEFAULT '{}',         -- Solo valores editables; nunca contiene status
    status VARCHAR(20) DEFAULT 'activo' CHECK (status IN ('activo','desactivado')),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Índices y restricciones de unicidad
-- Índices de consulta; la identidad polimórfica y la unicidad del nombre/título
-- se validan a nivel de aplicación, no con una FK o índice único genérico.
CREATE INDEX idx_user_catalog_identity ON user_catalog_items(user_id, item_type, base_id);
CREATE INDEX idx_user_catalog_display_parent ON user_catalog_items(user_id, parent_fork_id, item_type);

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
        'status' => resolveEffectiveStatus($fork),
        'origin' => $overrides ? 'override' : 'base',
        'origin_field' => array_keys($overrides), // Qué campos son override
    ];
}

// Herencia de status: fork 'desactivado' SIEMPRE gana
function resolveEffectiveStatus(UserCatalogItem $fork): string
{
    if ($fork->status === 'desactivado') return 'desactivado';
    if ($fork->base_id && $fork->base->status === 'desactivado') return 'desactivado';
    $parent = $fork->parentFork;
    while ($parent) {
        if ($parent->status === 'desactivado') return 'desactivado';
        $parent = $parent->parentFork;
    }
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
| **Un fork por base por usuario** | La aplicación evita duplicar la identidad `user_id + item_type + base_id` cuando `base_id` no es nulo |
| **Override gana siempre** | Campo en `overrides` → usa ese valor; si no → hereda de base |
| **Status propio y efectivo** | El status vive en su columna dedicada. Status propio, base o padre fork `desactivado` bloquea visibilidad; ningún override puede vencer un ancestro desactivado |
| **Base cambio → auto-sync** | Si usuario SIN override → ve cambio base automático |
| **Eliminar fork = solo usuario** | `DELETE /api/user-catalog/...` elimina recursivamente el fork y sus hijos; base y otros forks intactos |
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
| Override múltiple | `PUT /api/user-catalog/{type}/{forkId}` | `{name, value, tags, description}`; `status` se actualiza solo por su endpoint |
| Desactivar fork | `PATCH /api/user-catalog/{type}/{forkId}/deactivate` | — |
| Reactivar fork | `PATCH /api/user-catalog/{type}/{forkId}/reactivate` | — |
| Crear personal | `POST /api/user-catalog/{type}` | `{parent_fork_id, base_id: null, overrides: {...}}` |
| Eliminar fork | `DELETE /api/user-catalog/{type}/{forkId}` | `?cascade=true` (default true) |
| Listar mi catálogo | `GET /api/user-catalog/tree` | `?type=rubro&status=all` |

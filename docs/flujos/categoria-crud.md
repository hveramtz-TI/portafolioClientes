# Flujo: CRUD Categoría

**Épica:** Rubros, categorías y servicios
**HUs relacionadas:** HU-017, HU-018, HU-019, HU-020
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

    %% Nodos
    Start([Inicio: Usuario accede a categorías<br/>dentro de un rubro]):::start
    
    %% CONTEXTO: Rubro seleccionado (base o fork)
    Context{¿Rubro es base<br/>fork?}:::decision
    Start --> Context
    
    %% LISTAR CATEGORÍAS
    Context -->|Base| ListBase[GET /api/rubros/{rubroId}/categorias<br/>→ Muestra base + forks usuario<br/>→ Filtro: activos/desactivados]:::process
    Context -->|Fork| ListFork[GET /api/user-catalog/rubros/{forkId}/categorias<br/>→ Muestra: heredadas base + overrides + propias<br/>→ Badge: "Base" / "Override" / "Personal"]:::fork
    ListBase --> SelectCat{Selecciona categoría}:::decision
    ListFork --> SelectCat
    
    %% FORK CATEGORÍA (al seleccionar rubro base se copian en cascada)
    SelectCat -->|Categoría base sin fork| ForkCat[POST /api/user-catalog/categorias/{id}/fork<br/>→ Crea fork categoría en user_catalog_items<br/>→ Copia servicios en cascada<br/>→ Vincula a fork rubro padre]:::fork
    SelectCat -->|Categoría con fork| ViewForkCat[Ver fork categoría<br/>→ Override: name<br/>→ Status en columna dedicada<br/>→ Hereda: parent_fork_id y servicios base]:::fork
    ForkCat --> ViewForkCat
    
    %% CREAR
    ViewForkCat -->|Acción: Crear categoría personal| CreateCatFork[POST /api/user-catalog/categorias<br/>→ base_id=null<br/>→ parent_fork_id = fork rubro padre<br/>→ Usuario propietario]:::fork
    CreateCatFork --> ListFork
    
    %% EDITAR
    ViewForkCat -->|Acción: Editar| EditCatDecision{¿Edita base<br/>fork?}:::decision
    EditCatDecision -->|Base| EditCatBase[PUT /api/categorias/{id}<br/>→ name unique por rubro_id<br/>→ Propaga a forks sin override]:::process
    EditCatDecision -->|Fork| EditCatFork[PUT /api/user-catalog/categorias/{id}<br/>→ Solo overrides JSON<br/>→ Conserva parent_fork_id]:::fork
    EditCatBase --> ListBase
    EditCatFork --> ListFork
    
    %% DESACTIVAR
    ViewForkCat -->|Acción: Desactivar base| DeactivateCat[PATCH /api/categorias/{id}/deactivate<br/>→ status=desactivado<br/>→ Solo admin/seeder<br/>→ Oculta en perfil público]:::process
    ViewForkCat -->|Acción: Desactivar fork| DeactivateCatFork[PATCH /api/user-catalog/categorias/{forkId}/deactivate<br/>→ status propio=desactivado<br/>→ Usuario propietario]:::fork
    DeactivateCat --> ListFork
    
    %% REACTIVAR
    ViewForkCat -->|Acción: Reactivar base| ReactivateCat[PATCH /api/categorias/{id}/reactivate<br/>→ status=activo<br/>→ Solo admin/seeder<br/>→ Ancestro desactivado mantiene oculto]:::process
    ViewForkCat -->|Acción: Reactivar fork| ReactivateCatFork[PATCH /api/user-catalog/categorias/{forkId}/reactivate<br/>→ status propio=activo<br/>→ Hijos desactivados permanecen así]:::fork
    ReactivateCat --> ListFork
    
    %% ELIMINAR
    ViewForkCat -->|Acción: Eliminar| DeleteCatDecision{¿Tiene relaciones?<br/>servicios/órdenes/forks}:::decision
    DeleteCatDecision -->|Sí (base)| ForceDeactivateCat[Bloquea eliminación base<br/>→ Muestra: "Debe desactivar" (HU-019)]:::end
    DeleteCatDecision -->|No (base)| DeleteCatBase[DELETE /api/categorias/{id}<br/>→ forceDelete()<br/>→ Solo si 0 forks + 0 servicios]:::end
    DeleteCatDecision -->|Fork| DeleteCatFork[DELETE /api/user-catalog/categorias/{id}<br/>→ Elimina recursivamente el fork y sus hijos<br/>→ Base intacta]:::fork
    ForceDeactivateCat --> ListFork
    DeleteCatBase --> ListBase
    DeleteCatFork --> ListFork
    
    %% FIN
    ListBase -->|Salir| End([Fin]):::end
    ListFork -->|Salir| End
```

---

## Tabla de Pasos (para Notion)

| Paso | Acción Usuario | Endpoint / Acción Sistema | Validaciones | Resultado |
|------|----------------|---------------------------|--------------|-----------|
| 1 | Accede a rubro → ve categorías | GET `/api/rubros/{id}/categorias` o `/api/user-catalog/rubros/{forkId}/categorias` | Auth + rubro accesible | Lista con badges: "Base", "Override", "Personal" |
| 2 | Click "Seleccionar" categoría base | POST `/api/user-catalog/categorias/{id}/fork` | Usuario autenticado; rubro padre tiene fork | Crea fork categoría + copia servicios en cascada |
| 3 | Click "Nueva categoría personal" | POST `/api/user-catalog/categorias` | Usuario propietario; `base_id=null`; rubro fork padre | Categoría personal en el fork |
| 4 | Click "Nueva categoría personal" | POST `/api/user-catalog/categorias` | `rubro_id` = fork rubro padre; `name` unique en fork | Categoría 100% personal en fork |
| 5 | Edita nombre categoría base | PUT `/api/categorias/{id}` | Solo admin; `name` unique por `rubro_id` | Propaga a forks sin override |
| 6 | Edita nombre en fork | PUT `/api/user-catalog/categorias/{id}` | Solo overrides JSON; `rubro_id` inmutable | Cambio aisla al usuario |
| 7 | Click "Desactivar" base o fork | PATCH según origen | Usuario propietario para fork; admin/seeder para base | Status propio desactivado; ancestros bloquean visibilidad; oculta en público |
| 8 | Click "Reactivar" | PATCH `/api/categorias/{id}/reactivate` | Estaba desactivado; rubro padre activo | `status=activo`; visible si rubro padre activo |
| 9 | Click "Eliminar" (con relaciones) | — | Check `services.count() + order_services.count() + forks > 0` | Bloquea; muestra "Debe desactivar" |
| 10 | Click "Eliminar" base (sin relaciones) | DELETE `/api/categorias/{id}` | 0 forks + 0 servicios | `forceDelete()` hard delete |
| 11 | Click "Eliminar" fork | DELETE `/api/user-catalog/categorias/{id}` | Permitido con hijos | Elimina recursivamente el fork y sus hijos; base intacta |

---

## Notas de Implementación

- **Endpoints y autorización:** El usuario propietario administra forks únicamente mediante `/api/user-catalog/...`; las mutaciones de base requieren admin/seeder.
- **Rubro padre:** En un fork, `parent_fork_id` identifica la jerarquía canónica; crear/eliminar fork conserva esa relación. Las mutaciones de base son solo admin/seeder.
- **Herencia dinámica:** Fork categoría sin override → hereda `name` de base. Override → usa `overrides.name`. La identidad usa `item_type + base_id`; el nombre visible se valida aparte dentro de `parent_fork_id`.
- **Estado efectivo:** Estado propio, base y padre fork desactivado bloquean visibilidad. Reactivar establece solo el estado propio en `activo`; no reactiva hijos desactivados.
- **Eliminación:** Fork con hijos se elimina recursivamente. Base solo se elimina si no tiene forks ni relaciones.
- **Visibilidad compuesta:** Categoría visible en público SI: `categoria.status=activo` AND `rubro.status=activo`.
- **Unicidad:** La identidad de un fork se valida con `user_id + item_type + base_id`; el nombre visible se valida por separado dentro de `parent_fork_id`. Estas reglas polimórficas se validan a nivel de aplicación.
- **Seeders:** Categorías base creadas por `CategoriaSeeder` vinculadas a rubros base.

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
    SelectCat -->|Categoría con fork| ViewForkCat[Ver fork categoría<br/>→ Overrides: name, status<br/>→ Hereda: rubro_padre, servicios base]:::fork
    ForkCat --> ViewForkCat
    
    %% CREAR
    ViewForkCat -->|Acción: Crear categoría| CreateDecision{¿En base<br/>fork?}:::decision
    CreateDecision -->|Base (admin)| CreateCatBase[POST /api/categorias<br/>→ rubro_id obligatorio<br/>→ name unique por rubro_id<br/>→ status=activo]:::process
    CreateDecision -->|Fork (usuario)| CreateCatFork[POST /api/user-catalog/categorias<br/>→ base_id=null O fork de base<br/>→ rubro_id = fork rubro padre<br/>→ name unique en fork rubro]:::fork
    CreateCatBase --> ListBase
    CreateCatFork --> ListFork
    
    %% EDITAR
    ViewForkCat -->|Acción: Editar| EditCatDecision{¿Edita base<br/>fork?}:::decision
    EditCatDecision -->|Base| EditCatBase[PUT /api/categorias/{id}<br/>→ name unique por rubro_id<br/>→ Propaga a forks sin override]:::process
    EditCatDecision -->|Fork| EditCatFork[PUT /api/user-catalog/categorias/{id}<br/>→ Solo overrides JSON<br/>→ No cambia rubro_padre (inmutable)]:::fork
    EditCatBase --> ListBase
    EditCatFork --> ListFork
    
    %% DESACTIVAR
    ViewForkCat -->|Acción: Desactivar| DeactivateCat[PATCH /api/categorias/{id}/deactivate<br/>→ status=desactivado<br/>→ Cascada: servicios → desactivado<br/>→ Oculta en perfil público]:::process
    DeactivateCat --> ListFork
    
    %% REACTIVAR
    ViewForkCat -->|Acción: Reactivar| ReactivateCat[PATCH /api/categorias/{id}/reactivate<br/>→ status=activo<br/>→ Si rubro padre activo → visible<br/>→ Si rubro padre desactivado → sigue oculto]:::process
    ReactivateCat --> ListFork
    
    %% ELIMINAR
    ViewForkCat -->|Acción: Eliminar| DeleteCatDecision{¿Tiene relaciones?<br/>servicios/órdenes/forks}:::decision
    DeleteCatDecision -->|Sí| ForceDeactivateCat[Bloquea eliminación<br/>→ Muestra: "Debe desactivar" (HU-019)]:::end
    DeleteCatDecision -->|No (base)| DeleteCatBase[DELETE /api/categorias/{id}<br/>→ forceDelete()<br/>→ Solo si 0 forks + 0 servicios]:::end
    DeleteCatDecision -->|No (fork)| DeleteCatFork[DELETE /api/user-catalog/categorias/{id}<br/>→ Quita solo fork usuario<br/>→ Base intacta]:::fork
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
| 3 | Click "Nueva categoría" (base) | POST `/api/categorias` | Solo admin; `rubro_id` obligatorio; `name` unique por rubro | Categoría en base global |
| 4 | Click "Nueva categoría personal" | POST `/api/user-catalog/categorias` | `rubro_id` = fork rubro padre; `name` unique en fork | Categoría 100% personal en fork |
| 5 | Edita nombre categoría base | PUT `/api/categorias/{id}` | Solo admin; `name` unique por `rubro_id` | Propaga a forks sin override |
| 6 | Edita nombre en fork | PUT `/api/user-catalog/categorias/{id}` | Solo overrides JSON; `rubro_id` inmutable | Cambio aisla al usuario |
| 7 | Click "Desactivar" | PATCH `/api/categorias/{id}/deactivate` | Tiene servicios/órdenes | `status=desactivado`; cascada a servicios; oculta en público |
| 8 | Click "Reactivar" | PATCH `/api/categorias/{id}/reactivate` | Estaba desactivado; rubro padre activo | `status=activo`; visible si rubro padre activo |
| 9 | Click "Eliminar" (con relaciones) | — | Check `services.count() + order_services.count() + forks > 0` | Bloquea; muestra "Debe desactivar" |
| 10 | Click "Eliminar" base (sin relaciones) | DELETE `/api/categorias/{id}` | 0 forks + 0 servicios | `forceDelete()` hard delete |
| 11 | Click "Eliminar" fork | DELETE `/api/user-catalog/categorias/{id}` | Siempre permitido | Quita fork usuario; base intacta |

---

## Notas de Implementación

- **Rubro padre inmutable:** Al editar categoría (base o fork), `rubro_id` no se puede cambiar. Para mover → crear nueva en destino + eliminar original.
- **Herencia dinámica:** Fork categoría sin override → hereda `name` de base. Override → usa `overrides.name`.
- **Cascada desactivar:** Al desactivar categoría → `services` donde `categoria_id` en (base + forks) → `status=desactivado`.
- **Visibilidad compuesta:** Categoría visible en público SI: `categoria.status=activo` AND `rubro.status=activo`.
- **Unicidad:** Base = `rubro_id + name` unique. Fork = `user_id + base_categoria_id` unique (un fork por categoría base por usuario) + `rubro_fork_id + name` unique en fork.
- **Seeders:** Categorías base creadas por `CategoriaSeeder` vinculadas a rubros base.
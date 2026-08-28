# Flujo: CRUD Rubro

**Épica:** Rubros, categorías y servicios
**HUs relacionadas:** HU-013, HU-014, HU-015, HU-016
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
    Start([Inicio: Usuario accede a gestión de rubros]):::start
    
    %% LISTAR
    List[Listar rubros: GET /api/rubros<br/>→ Muestra base + forks usuario<br/>→ Filtro: activos/desactivados]:::process
    List -->|Selecciona rubro base| ForkDecision{¿Rubro tiene<br/>fork personal?}:::decision
    
    %% FORK
    ForkDecision -->|No| CreateFork[POST /api/user-catalog/rubros/{id}/fork<br/>→ Crea fork en user_catalog_items<br/>→ Copia categorías/servicios en cascada]:::fork
    ForkDecision -->|Sí| ViewFork[Ver fork personal<br/>→ Muestra overrides + herencia base]:::fork
    CreateFork --> ViewFork
    
    %% CREAR (solo admin/seeder en base)
    ViewFork -->|Acción: Crear rubro| CreateBase{¿Es admin<br/>creando en base?}:::decision
    CreateBase -->|Sí| CreateRubroBase[POST /api/rubros<br/>→ Valida name unique global<br/>→ status=activo<br/>→ Solo seeders/admin]:::process
    CreateBase -->|No (usuario)| CreateRubroFork[POST /api/user-catalog/rubros<br/>→ base_id=null<br/>→ Fork 100% personal]:::fork
    CreateRubroBase --> List
    CreateRubroFork --> List
    
    %% EDITAR
    ViewFork -->|Acción: Editar| EditDecision{¿Edita base<br/>fork?}:::decision
    EditDecision -->|Base| EditBase[PUT /api/rubros/{id}<br/>→ Valida name unique global<br/>→ Propaga a forks sin override]:::process
    EditDecision -->|Fork| EditFork[PUT /api/user-catalog/rubros/{id}<br/>→ Solo overrides JSON<br/>→ No afecta base ni otros]:::fork
    EditBase --> List
    EditFork --> List
    
    %% DESACTIVAR
    ViewFork -->|Acción: Desactivar| Deactivate[PATCH /api/rubros/{id}/deactivate<br/>→ status=desactivado<br/>→ Cascada: categorías/servicios<br/>→ Oculta en perfil público]:::process
    Deactivate --> List
    
    %% REACTIVAR
    ViewFork -->|Acción: Reactivar| Reactivate[PATCH /api/rubros/{id}/reactivate<br/>→ status=activo<br/>→ Restaura visibilidad]:::process
    Reactivate --> List
    
    %% ELIMINAR
    ViewFork -->|Acción: Eliminar| DeleteDecision{¿Tiene relaciones?<br/>categorías/servicios/órdenes/forks}:::decision
    DeleteDecision -->|Sí| ForceDeactivate[Bloquea eliminación<br/>→ Muestra: "Debe desactivar" (HU-015)]:::end
    DeleteDecision -->|No (base)| DeleteBase[DELETE /api/rubros/{id}<br/>→ forceDelete()<br/>→ Solo si 0 forks + 0 relaciones]:::end
    DeleteDecision -->|No (fork)| DeleteFork[DELETE /api/user-catalog/rubros/{id}<br/>→ Quita solo fork usuario<br/>→ Base intacta]:::fork
    ForceDeactivate --> List
    DeleteBase --> List
    DeleteFork --> List
    
    %% FIN
    List -->|Salir| End([Fin]):::end
```

---

## Tabla de Pasos (para Notion)

| Paso | Acción Usuario | Endpoint / Acción Sistema | Validaciones | Resultado |
|------|----------------|---------------------------|--------------|-----------|
| 1 | Accede a "Mis Rubros" | GET `/api/rubros` | Auth + role user | Lista: base (con badge "base") + forks usuario (badge "personalizado") |
| 2 | Click "Agregar rubro" (base) | POST `/api/rubros` | Solo admin/seeder; `name` unique global | Rubro creado en base global, `status=activo` |
| 3 | Click "Seleccionar" en rubro base | POST `/api/user-catalog/rubros/{id}/fork` | Usuario autenticado | Crea fork en `user_catalog_items` + copia categorías/servicios en cascada |
| 4 | Click "Nuevo rubro personal" | POST `/api/user-catalog/rubros` | `base_id=null`; `name` unique en forks usuario | Rubro 100% personal en fork |
| 5 | Edita nombre rubro base | PUT `/api/rubros/{id}` | Solo admin; `name` unique global | Cambio propaga a forks sin override |
| 6 | Edita nombre en fork | PUT `/api/user-catalog/rubros/{id}` | Solo overrides JSON | Cambio aisla al usuario |
| 7 | Click "Desactivar" | PATCH `/api/rubros/{id}/deactivate` | Tiene relaciones | `status=desactivado`; cascada a categorías/servicios; oculta en público |
| 8 | Click "Reactivar" | PATCH `/api/rubros/{id}/reactivate` | Estaba desactivado | `status=activo`; restaura visibilidad |
| 9 | Click "Eliminar" (con relaciones) | — | Check relaciones > 0 | Bloquea; muestra "Debe desactivar" |
| 10 | Click "Eliminar" base (sin relaciones) | DELETE `/api/rubros/{id}` | 0 forks + 0 relaciones | `forceDelete()` hard delete |
| 11 | Click "Eliminar" fork | DELETE `/api/user-catalog/rubros/{id}` | Siempre permitido | Quita fork usuario; base intacta |

---

## Notas de Implementación

- **Permisos:** Base = solo admin/seeder. Fork = usuario propietario.
- **Herencia:** Fork sin override → muestra valor base. Override → muestra valor usuario.
- **Cascada desactivar:** Al desactivar rubro → query `categorias`/`services` donde `rubro_id` en (base + forks) → `status=desactivado`.
- **Unicidad:** Base = `name` unique global. Fork = `user_id + base_rubro_id` unique (un fork por rubro base por usuario).
- **UI:** Badges visuales "Base" / "Personalizado" / "Override" en cada campo.
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
    ViewFork -->|Acción: Crear rubro personal| CreateRubroFork[POST /api/user-catalog/rubros<br/>→ base_id=null<br/>→ Crea item personal<br/>→ Usuario propietario]:::fork
    CreateRubroFork --> List
    
    %% EDITAR
    ViewFork -->|Acción: Editar| EditDecision{¿Edita base<br/>fork?}:::decision
    EditDecision -->|Base| EditBase[PUT /api/rubros/{id}<br/>→ Valida name unique global<br/>→ Propaga a forks sin override]:::process
    EditDecision -->|Fork| EditFork[PUT /api/user-catalog/rubros/{id}<br/>→ Solo overrides JSON<br/>→ No afecta base ni otros]:::fork
    EditBase --> List
    EditFork --> List
    
    %% DESACTIVAR
    ViewFork -->|Acción: Desactivar base| Deactivate[PATCH /api/rubros/{id}/deactivate<br/>→ status=desactivado<br/>→ Solo admin/seeder<br/>→ Oculta en perfil público]:::process
    ViewFork -->|Acción: Desactivar fork| DeactivateFork[PATCH /api/user-catalog/rubros/{forkId}/deactivate<br/>→ status propio=desactivado<br/>→ Usuario propietario]:::fork
    Deactivate --> List
    
    %% REACTIVAR
    ViewFork -->|Acción: Reactivar base| Reactivate[PATCH /api/rubros/{id}/reactivate<br/>→ status=activo<br/>→ Solo admin/seeder]:::process
    ViewFork -->|Acción: Reactivar fork| ReactivateFork[PATCH /api/user-catalog/rubros/{forkId}/reactivate<br/>→ status propio=activo<br/>→ Hijos desactivados permanecen así]:::fork
    Reactivate --> List
    
    %% ELIMINAR
    ViewFork -->|Acción: Eliminar| DeleteDecision{¿Tiene relaciones?<br/>categorías/servicios/órdenes/forks}:::decision
    DeleteDecision -->|Sí| ForceDeactivate[Bloquea eliminación<br/>→ Muestra: "Debe desactivar" (HU-015)]:::end
    DeleteDecision -->|No (base)| DeleteBase[DELETE /api/rubros/{id}<br/>→ forceDelete()<br/>→ Solo si 0 forks + 0 relaciones]:::end
    DeleteDecision -->|Fork| DeleteFork[DELETE /api/user-catalog/rubros/{id}<br/>→ Elimina recursivamente el fork y sus hijos<br/>→ Base intacta]:::fork
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
| 2 | Click "Nuevo rubro personal" | POST `/api/user-catalog/rubros` | Usuario propietario; `base_id=null`; nombre único en su catálogo | Rubro creado en su fork personal |
| 3 | Click "Seleccionar" en rubro base | POST `/api/user-catalog/rubros/{id}/fork` | Usuario autenticado | Crea fork en `user_catalog_items` + copia categorías/servicios en cascada |
| 4 | Click "Nuevo rubro personal" | POST `/api/user-catalog/rubros` | `base_id=null`; `name` unique en forks usuario | Rubro 100% personal en fork |
| 5 | Edita nombre rubro base | PUT `/api/rubros/{id}` | Solo admin; `name` unique global | Cambio propaga a forks sin override |
| 6 | Edita nombre en fork | PUT `/api/user-catalog/rubros/{id}` | Solo overrides JSON | Cambio aisla al usuario |
| 7 | Click "Desactivar" base o fork | PATCH según origen | Usuario propietario para fork; admin/seeder para base | Status propio desactivado; ancestros bloquean visibilidad; oculta en público |
| 8 | Click "Reactivar" | PATCH `/api/rubros/{id}/reactivate` | Estaba desactivado | `status=activo`; restaura visibilidad |
| 9 | Click "Eliminar" (con relaciones) | — | Check relaciones > 0 | Bloquea; muestra "Debe desactivar" |
| 10 | Click "Eliminar" base (sin relaciones) | DELETE `/api/rubros/{id}` | 0 forks + 0 relaciones | `forceDelete()` hard delete |
| 11 | Click "Eliminar" fork | DELETE `/api/user-catalog/rubros/{id}` | Permitido con hijos | Elimina recursivamente el fork y sus hijos; base intacta |

---

## Notas de Implementación

- **Permisos:** El usuario propietario administra su fork mediante `/api/user-catalog/...`. Las mutaciones de base corresponden exclusivamente a admin/seeder; no son creación ordinaria de usuario.
- **Endpoints por origen:** Toda acción sobre un fork usa `/api/user-catalog/...`; toda acción sobre base usa su endpoint base y requiere admin/seeder.
- **Herencia:** Fork sin override → muestra valor base. Override → muestra valor usuario.
- **Cascada desactivar:** El estado efectivo se resuelve dinámicamente: estado propio, base y padre fork desactivado bloquean visibilidad. Reactivar solo establece el estado propio en `activo`; no activa hijos desactivados.
- **Eliminación:** Fork con hijos se elimina recursivamente. Base solo se elimina si no tiene forks ni relaciones.
- **Unicidad:** La identidad de un fork se valida con `user_id + item_type + base_id`; el nombre visible se valida por separado dentro del padre fork. Estas reglas polimórficas se validan a nivel de aplicación.
- **UI:** Badges visuales "Base" / "Personalizado" / "Override" en cada campo.

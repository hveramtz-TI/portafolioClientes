# Flujo: CRUD Servicio

**Épica:** Rubros, categorías y servicios
**HUs relacionadas:** HU-021, HU-022, HU-023
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
    classDef special fill:#fff8e1,stroke:#f57f17,stroke-width:2px;

    %% Nodos
    Start([Inicio: Usuario accede a servicios<br/>dentro de una categoría]):::start
    
    %% CONTEXTO: Categoría seleccionada (base o fork)
    Context{¿Categoría es base<br/>fork?}:::decision
    Start --> Context
    
    %% LISTAR SERVICIOS
    Context -->|Base| ListBase[GET /api/categorias/{catId}/services<br/>→ Muestra base + forks usuario<br/>→ Filtro: activos/desactivados<br/>→ Tags visibles]:::process
    Context -->|Fork| ListFork[GET /api/user-catalog/categorias/{forkId}/services<br/>→ Heredados base + overrides + propios<br/>→ Badge: "Base" / "Override" / "Personal"<br/>→ Valor CLP + tags]:::fork
    ListBase --> SelectSvc{Selecciona servicio}:::decision
    ListFork --> SelectSvc
    
    %% FORK SERVICIO (al seleccionar categoría base se copian en cascada)
    SelectSvc -->|Servicio base sin fork| ForkSvc[POST /api/user-catalog/services/{id}/fork<br/>→ Crea fork servicio en user_catalog_items<br/>→ Vincula a fork categoría padre]:::fork
    SelectSvc -->|Servicio con fork| ViewForkSvc[Ver fork servicio<br/>→ Overrides: title, description, value, tags<br/>→ Status en columna dedicada<br/>→ Hereda: categoria padre]:::fork
    ForkSvc --> ViewForkSvc
    
    %% CREAR
    ViewForkSvc -->|Acción: Crear servicio personal| CreateSvcDecision{¿En base<br/>fork?}:::decision
    CreateSvcDecision -->|Fork (usuario)| CreateSvcFork[POST /api/user-catalog/services<br/>→ base_id=null<br/>→ parent_fork_id = fork categoría padre<br/>→ title unique en fork categoría<br/>→ value CLP + tags opcionales]:::fork
    CreateSvcFork --> ListFork
    
    %% EDITAR
    ViewForkSvc -->|Acción: Editar| EditSvcDecision{¿Edita base<br/>fork?}:::decision
    EditSvcDecision -->|Base| EditSvcBase[PUT /api/services/{id}<br/>→ title unique por categoria_id<br/>→ value CLP ≥0<br/>→ tags JSON validado<br/>→ Propaga a forks sin override]:::process
    EditSvcDecision -->|Fork| EditSvcFork[PUT /api/user-catalog/services/{id}<br/>→ Solo campos editables en overrides<br/>→ parent_fork_id identifica categoría padre<br/>→ Cambio value NO afecta órdenes históricas]:::fork
    EditSvcBase --> ListBase
    EditSvcFork --> ListFork
    
    %% ELIMINAR / DESACTIVAR (lógica combinada HU-023)
    ViewForkSvc -->|Acción: Eliminar| DeleteSvcCheck{¿Tiene historial<br/>en order_services?}:::decision
    DeleteSvcCheck -->|Sí (historial > 0)| DeactivateSvc[PATCH /api/services/{id}/deactivate<br/>→ status=desactivado<br/>→ Oculta en público y nuevas solicitudes<br/>→ Orden histórica conserva snapshot]:::special
    DeleteSvcCheck -->|No (historial = 0)| DeleteSvcDecision{¿Base<br/>fork?}:::decision
    DeleteSvcDecision -->|Base| DeleteSvcBase[DELETE /api/services/{id}<br/>→ forceDelete()<br/>→ Solo si 0 forks referenciándolo]:::end
    DeleteSvcDecision -->|Fork| DeleteSvcFork[DELETE /api/user-catalog/services/{id}<br/>→ Elimina el fork del usuario<br/>→ Base intacta]:::fork
    DeactivateSvc --> ListFork
    DeleteSvcBase --> ListBase
    DeleteSvcFork --> ListFork
    
    %% EDITAR CATEGORÍA PADRE (HU-022: permite mover servicio)
    ViewForkSvc -->|Acción: Cambiar categoría| MoveSvcDecision{¿En base<br/>fork?}:::decision
    MoveSvcDecision -->|Base| MoveSvcBase[PUT /api/services/{id}<br/>→ nuevo categoria_id<br/>→ Valida title unique en nueva categoría<br/>→ Propaga a forks sin override]:::process
    MoveSvcDecision -->|Fork| MoveSvcFork[PUT /api/user-catalog/services/{id}<br/>→ Mueve parent_fork_id a categoría destino<br/>→ La categoría no se guarda en overrides<br/>→ Valida title unique en fork categoría destino]:::fork
    MoveSvcBase --> ListBase
    MoveSvcFork --> ListFork
    
    %% REACTIVAR (si estaba desactivado)
    ViewForkSvc -->|Acción: Reactivar fork| ReactivateSvc[PATCH /api/user-catalog/services/{forkId}/reactivate<br/>→ status propio = activo<br/>→ Ancestro desactivado mantiene oculto]:::fork
    ReactivateSvc --> ListFork
    
    %% COPIA HISTÓRICA EN ÓRDENES (referencia)
    ViewForkSvc -.->|Al agregar a orden| HistoricalCopy[POST /api/orders/{id}/services<br/>→ Guarda en order_services:<br/>service_title, service_value<br/>service_id, fork item reference]:::special
    HistoricalCopy -.-> ViewForkSvc
    
    %% FIN
    ListBase -->|Salir| End([Fin]):::end
    ListFork -->|Salir| End
```

---

## Tabla de Pasos (para Notion)

| Paso | Acción Usuario | Endpoint / Acción Sistema | Validaciones | Resultado |
|------|----------------|---------------------------|--------------|-----------|
| 1 | Accede a categoría → ve servicios | GET `/api/categorias/{id}/services` o `/api/user-catalog/categorias/{forkId}/services` | Auth + categoría accesible | Lista con badges: "Base", "Override", "Personal"; muestra valor CLP + tags |
| 2 | Click "Seleccionar" servicio base | POST `/api/user-catalog/services/{id}/fork` | Usuario autenticado; categoría padre tiene fork | Crea fork servicio vinculado a fork categoría |
| 3 | Click "Nuevo servicio personal" | POST `/api/user-catalog/services` | Usuario propietario; `base_id=null`; categoría fork padre; `title` unique; `value` CLP ≥0 | Servicio personal en el fork |
| 4 | Click "Nuevo servicio personal" | POST `/api/user-catalog/services` | `parent_fork_id` = fork categoría padre; `title` unique en fork | Servicio 100% personal en fork |
| 5 | Edita servicio base | PUT `/api/services/{id}` | Admin; `title` unique por `categoria_id`; `value` CLP ≥0; `tags` válidos | Propaga a forks sin override |
| 6 | Edita servicio en fork | PUT `/api/user-catalog/services/{id}` | Solo campos editables en overrides; `parent_fork_id` conserva jerarquía | Cambio aisla al usuario; **value no afecta órdenes históricas** |
| 7 | Click "Eliminar" (con historial) | PATCH `/api/services/{id}/deactivate` | `order_services.count() > 0` | `status=desactivado`; oculta en público/nuevas solicitudes; snapshot en orden intacto |
| 8 | Click "Eliminar" base (sin historial) | DELETE `/api/services/{id}` | 0 `order_services` + 0 forks | `forceDelete()` hard delete |
| 9 | Click "Eliminar" fork | DELETE `/api/user-catalog/services/{id}` | Permitido | Elimina el fork del usuario; base intacta |
| 10 | Click "Reactivar" | PATCH `/api/services/{id}/reactivate` | Estaba desactivado; categoría y rubro activos | `status=activo`; visible en público |
| 11 | Agregar servicio a orden | POST `/api/orders/{id}/services` | Orden editable; servicio activo | **Snapshot histórico**: `title`, `value` copiados a `order_services` |

---

## Notas de Implementación

- **Endpoints y autorización:** El usuario propietario administra forks únicamente mediante `/api/user-catalog/...`; las mutaciones de base requieren admin/seeder.
- **Cambio de categoría (HU-022):** Se permite mover servicio a otra categoría validando unicidad de título en la categoría destino. En base: PUT `/api/services/{id}` con nuevo `categoria_id`. En fork: mover `parent_fork_id` al fork de categoría destino; la categoría no se guarda en `overrides`.
- **Tags técnicos:** Campo `tags` JSON array. Valores permitidos: `['frontend', 'backend', 'fullstack', 'devops', 'mobile']`. Validación en Request.
- **Valor CLP:** Entero (pesos chilenos). Ej: `300000` = $300.000 CLP. Validación `integer|min:0`.
- **Copia histórica (crítica):** Al agregar servicio a orden → `order_services` guarda `service_title`, `service_value`, `service_id` y, si corresponde, la referencia al fork. **Inmutable** — cambios posteriores al servicio no afectan órdenes ya creadas.
- **Desactivar vs Eliminar:** En base, intentar eliminar → check `order_services` y forks → si existe historial, desactivar; si no hay relaciones, `forceDelete()`. En fork, DELETE recursivo está permitido.
- **Visibilidad compuesta:** Servicio visible en público solo si su estado propio, el estado base y todos los padres fork aplicables están `activo`; ningún override puede vencer un ancestro desactivado.
- **Unicidad:** La identidad de un fork se valida con `user_id + item_type + base_id`; el título visible se valida por separado dentro de `parent_fork_id`. En base, la unicidad de título se valida junto con `categoria_id`. Las reglas polimórficas se validan a nivel de aplicación.
- **Seeders:** 12 servicios base creados por `ServiceSeeder` con valores CLP y tags según set aprobado (HU-024).

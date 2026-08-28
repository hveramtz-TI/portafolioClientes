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
    Start([Inicio: Usuario accede a servicios\ndentro de una categoría]):::start
    
    %% CONTEXTO: Categoría seleccionada (base o fork)
    Context{¿Categoría es base\nfork?}:::decision
    Start --> Context
    
    %% LISTAR SERVICIOS
    Context -->|Base| ListBase[GET /api/categorias/{catId}/services\n→ Muestra base + forks usuario\n→ Filtro: activos/desactivados\n→ Tags visibles]:::process
    Context -->|Fork| ListFork[GET /api/user-catalog/categorias/{forkId}/services\n→ Heredados base + overrides + propios\n→ Badge: "Base" / "Override" / "Personal"\n→ Valor CLP + tags]:::fork
    ListBase --> SelectSvc{Selecciona servicio}:::decision
    ListFork --> SelectSvc
    
    %% FORK SERVICIO (al seleccionar categoría base se copian en cascada)
    SelectSvc -->|Servicio base sin fork| ForkSvc[POST /api/user-catalog/services/{id}/fork\n→ Crea fork servicio en user_catalog_items\n→ Vincula a fork categoría padre]:::fork
    SelectSvc -->|Servicio con fork| ViewForkSvc[Ver fork servicio\n→ Overrides: title, description, value, tags, status\n→ Hereda: categoria_padre]:::fork
    ForkSvc --> ViewForkSvc
    
    %% CREAR
    ViewForkSvc -->|Acción: Crear servicio| CreateSvcDecision{¿En base\nfork?}:::decision
    CreateSvcDecision -->|Base (admin/seeder)| CreateSvcBase[POST /api/services\n→ categoria_id obligatorio\n→ title obligatorio\n→ value CLP entero ≥0\n→ tags JSON (lista permitida)\n→ title unique por categoria_id]:::process
    CreateSvcDecision -->|Fork (usuario)| CreateSvcFork[POST /api/user-catalog/services\n→ base_id=null O fork de base\n→ categoria_id = fork categoría padre\n→ title unique en fork categoría\n→ value CLP + tags opcionales]:::fork
    CreateSvcBase --> ListBase
    CreateSvcFork --> ListFork
    
    %% EDITAR
    ViewForkSvc -->|Acción: Editar| EditSvcDecision{¿Edita base\nfork?}:::decision
    EditSvcDecision -->|Base| EditSvcBase[PUT /api/services/{id}\n→ title unique por categoria_id\n→ value CLP ≥0\n→ tags JSON validado\n→ Propaga a forks sin override]:::process
    EditSvcDecision -->|Fork| EditSvcFork[PUT /api/user-catalog/services/{id}\n→ Solo overrides JSON\n→ categoria_id inmutable\n→ Cambio value NO afecta órdenes históricas]:::fork
    EditSvcBase --> ListBase
    EditSvcFork --> ListFork
    
    %% ELIMINAR / DESACTIVAR (lógica combinada HU-023)
    ViewForkSvc -->|Acción: Eliminar| DeleteSvcCheck{¿Tiene historial\nen order_services?}:::decision
    DeleteSvcCheck -->|Sí (historial > 0)| DeactivateSvc[PATCH /api/services/{id}/deactivate\n→ status=desactivado\n→ Oculta en público y nuevas solicitudes\n→ Orden histórica conserva snapshot]:::special
    DeleteSvcCheck -->|No (historial = 0)| DeleteSvcDecision{¿Base\nfork?}:::decision
    DeleteSvcDecision -->|Base| DeleteSvcBase[DELETE /api/services/{id}\n→ forceDelete()\n→ Solo si 0 forks referenciándolo]:::end
    DeleteSvcDecision -->|Fork| DeleteSvcFork[DELETE /api/user-catalog/services/{id}\n→ Quita fork usuario\n→ Base intacta]:::fork
    DeactivateSvc --> ListFork
    DeleteSvcBase --> ListBase
    DeleteSvcFork --> ListFork
    
    %% EDITAR CATEGORÍA PADRE (HU-022: permite mover servicio)
    ViewForkSvc -->|Acción: Cambiar categoría| MoveSvcDecision{¿En base\nfork?}:::decision
    MoveSvcDecision -->|Base| MoveSvcBase[PUT /api/services/{id}\n→ nuevo categoria_id\n→ Valida title unique en nueva categoría\n→ Propaga a forks sin override]:::process
    MoveSvcDecision -->|Fork| MoveSvcFork[PUT /api/user-catalog/services/{id}\n→ overrides.categoria_id = nuevo_fork_id\n→ Valida title unique en fork categoría destino]:::fork
    MoveSvcBase --> ListBase
    MoveSvcFork --> ListFork
    
    %% REACTIVAR (si estaba desactivado)
    ViewForkSvc -->|Acción: Reactivar| ReactivateSvc[PATCH /api/services/{id}/reactivate\n→ status=activo\n→ Visible si categoría y rubro activos]:::process
    ReactivateSvc --> ListFork
    
    %% COPIA HISTÓRICA EN ÓRDENES (referencia)
    ViewForkSvc -.->|Al agregar a orden| HistoricalCopy[POST /api/orders/{id}/services\n→ Guarda en order_services:\n  service_title (snapshot)\n  service_value (snapshot)\n  service_id (ref)\n  service_fork_id (ref si fork)]:::special
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
| 3 | Click "Nuevo servicio" (base) | POST `/api/services` | Admin/seeder; `categoria_id` obligatorio; `title` unique por categoría; `value` CLP ≥0; `tags` JSON válido | Servicio en base global |
| 4 | Click "Nuevo servicio personal" | POST `/api/user-catalog/services` | `categoria_id` = fork categoría padre; `title` unique en fork | Servicio 100% personal en fork |
| 5 | Edita servicio base | PUT `/api/services/{id}` | Admin; `title` unique por `categoria_id`; `value` CLP ≥0; `tags` válidos | Propaga a forks sin override |
| 6 | Edita servicio en fork | PUT `/api/user-catalog/services/{id}` | Solo overrides JSON; `categoria_id` inmutable | Cambio aisla al usuario; **value no afecta órdenes históricas** |
| 7 | Click "Eliminar" (con historial) | PATCH `/api/services/{id}/deactivate` | `order_services.count() > 0` | `status=desactivado`; oculta en público/nuevas solicitudes; snapshot en orden intacto |
| 8 | Click "Eliminar" base (sin historial) | DELETE `/api/services/{id}` | 0 `order_services` + 0 forks | `forceDelete()` hard delete |
| 9 | Click "Eliminar" fork | DELETE `/api/user-catalog/services/{id}` | Siempre permitido | Quita fork usuario; base intacta |
| 10 | Click "Reactivar" | PATCH `/api/services/{id}/reactivate` | Estaba desactivado; categoría y rubro activos | `status=activo`; visible en público |
| 11 | Agregar servicio a orden | POST `/api/orders/{id}/services` | Orden editable; servicio activo | **Snapshot histórico**: `title`, `value` copiados a `order_services` |

---

## Notas de Implementación

- **Cambio de categoría (HU-022):** Se permite mover servicio a otra categoría validando unicidad de título en la categoría destino. En base: PUT `/api/services/{id}` con nuevo `categoria_id`. En fork: override `categoria_id` en `user_catalog_items`.
- **Tags técnicos:** Campo `tags` JSON array. Valores permitidos: `['frontend', 'backend', 'fullstack', 'devops', 'mobile']`. Validación en Request.
- **Valor CLP:** Entero (pesos chilenos). Ej: `300000` = $300.000 CLP. Validación `integer|min:0`.
- **Copia histórica (crítica):** Al agregar servicio a orden → `order_services` guarda `service_title`, `service_value`, `service_id`, `service_fork_id`. **Inmutable** — cambios posteriores al servicio no afectan órdenes ya creadas.
- **Desactivar vs Eliminar:** Lógica automática: intentar eliminar → check `order_services` → si existe → desactivar; si no → `forceDelete()`.
- **Visibilidad compuesta:** Servicio visible en público SI: `service.status=activo` AND `categoria.status=activo` AND `rubro.status=activo`.
- **Unicidad:** Base = `categoria_id + title` unique. Fork = `user_id + base_service_id` unique + `categoria_fork_id + title` unique en fork.
- **Seeders:** 12 servicios base creados por `ServiceSeeder` con valores CLP y tags según set aprobado (HU-024).
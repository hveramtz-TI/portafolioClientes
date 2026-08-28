# Flujo: Lifecycle Desactivar/Eliminar (Rubro → Categoría → Servicio)

**Épica:** Rubros, categorías y servicios
**HUs relacionadas:** HU-015, HU-016, HU-019, HU-020, HU-023
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
    classDef warn fill:#ffebee,stroke:#c62828,stroke-width:2px,stroke-dasharray: 5 5
classDef special fill:#fff8e1,stroke:#f57f17,stroke-width:2px;

    %% Nodos
    Start([Inicio: Usuario intenta eliminar<br/>rubro/categoría/servicio]):::start
    
    %% DECISIÓN CENTRAL: TIPO ENTIDAD
    EntityType{¿Qué entidad?}:::decision
    Start --> EntityType
    
    %% ============ RUBRO ============
    EntityType -->|Rubro| RubroCheck{¿Tiene relaciones?<br/>categorías/servicios/órdenes/forks}:::decision
    
    RubroCheck -->|Base| RubroDeactivate[PATCH /api/rubros/{id}/deactivate<br/>→ status=desactivado<br/>→ Solo admin/seeder]:::process
    RubroDeactivate --> RubroCascade[CASCADA DE VISIBILIDAD:<br/>1. Rubro base desactivado bloquea el árbol<br/>2. Forks afectados quedan efectivamente ocultos<br/>3. Perfil público: oculta todo el árbol]:::warn
    RubroCascade --> RubroResult[Resultado: Rubro desactivado y árbol oculto<br/>→ Historial órdenes intacto<br/>→ Reactivable en cualquier momento]:::end
    
    RubroCheck -->|No (base)| RubroDeleteBase[DELETE /api/rubros/{id}<br/>→ forceDelete()<br/>→ Validación: 0 forks + 0 relaciones]:::end
    RubroCheck -->|Fork| RubroDeleteFork[DELETE /api/user-catalog/rubros/{id}<br/>→ Elimina recursivamente fork e hijos<br/>→ Base y otros forks intactos]:::fork
    
    %% REACTIVAR RUBRO
    RubroResult -->|Admin reactiva base| RubroReactivate[PATCH /api/rubros/{id}/reactivate<br/>→ status=activo]:::process
    RubroReactivate --> RubroReactivateCascade[REACTIVACIÓN:<br/>1. Solo el rubro seleccionado pasa a activo<br/>2. Hijos propios desactivados permanecen desactivados<br/>3. Ancestro desactivado mantiene ocultos sus descendientes]:::process
    RubroReactivateCascade --> EndRubro([Fin Rubro]):::end
    
    %% ============ CATEGORÍA ============
    EntityType -->|Categoría| CatCheck{¿Tiene relaciones?<br/>servicios/órdenes/forks}:::decision
    
    CatCheck -->|Base| CatDeactivate[PATCH /api/categorias/{id}/deactivate<br/>→ status=desactivado<br/>→ Solo admin/seeder]:::process
    CatDeactivate --> CatCascade[CASCADA DE VISIBILIDAD:<br/>1. Categoría base desactivada bloquea sus servicios<br/>2. Forks afectados quedan efectivamente ocultos<br/>3. Perfil público: oculta categoría + servicios]:::warn
    CatCascade --> CatResult[Resultado: Categoría + servicios desactivados<br/>→ Historial órdenes intacto<br/>→ Reactivable si rubro padre activo]:::end
    
    CatCheck -->|No (base)| CatDeleteBase[DELETE /api/categorias/{id}<br/>→ forceDelete()<br/>→ Validación: 0 forks + 0 servicios]:::end
    CatCheck -->|Fork| CatDeleteFork[DELETE /api/user-catalog/categorias/{id}<br/>→ Elimina recursivamente fork e hijos<br/>→ Base intacta]:::fork
    
    %% REACTIVAR CATEGORÍA
    CatResult -->|Admin reactiva base| CatReactivate[PATCH /api/categorias/{id}/reactivate<br/>→ status=activo]:::process
    CatReactivate --> CatReactivateLogic{¿Rubro padre<br/>activo?}:::decision
    CatReactivateLogic -->|Sí| CatVisible[Categoría + servicios (sin override) → visibles en público]:::process
    CatReactivateLogic -->|No| CatHidden[Categoría activa PERO oculta<br/>→ Rubro padre desactivado bloquea visibilidad]:::warn
    CatVisible --> EndCat([Fin Categoría]):::end
    CatHidden --> EndCat
    
    %% ============ SERVICIO ============
    EntityType -->|Servicio| SvcCheck{¿Tiene historial<br/>en order_services?}:::decision
    
    SvcCheck -->|Sí (historial > 0)| SvcDeactivate[PATCH /api/services/{id}/deactivate<br/>→ status=desactivado<br/>→ NO forceDelete()<br/>→ order_services conserva snapshot]:::special
    SvcDeactivate --> SvcResult[Resultado: Servicio desactivado<br/>→ Oculto en público y nuevas solicitudes<br/>→ Órdenes históricas: snapshot inmutable intacto<br/>→ Reactivable en cualquier momento]:::end
    
    SvcCheck -->|No (historial = 0)| SvcDeleteCheck{¿Base<br/>fork?}:::decision
    SvcDeleteCheck -->|Base| SvcDeleteBase[DELETE /api/services/{id}<br/>→ forceDelete()<br/>→ Validación: 0 forks]:::end
    SvcDeleteCheck -->|Fork| SvcDeleteFork[DELETE /api/user-catalog/services/{id}<br/>→ Elimina fork del usuario<br/>→ Base intacta]:::fork
    
    %% REACTIVAR SERVICIO
    SvcResult -->|Admin reactiva base| SvcReactivate[PATCH /api/services/{id}/reactivate<br/>→ status=activo]:::process
    SvcResult -->|Usuario reactiva fork| SvcReactivateFork[PATCH /api/user-catalog/services/{forkId}/reactivate<br/>→ status propio=activo<br/>→ Ancestro desactivado mantiene oculto]:::fork
    SvcReactivate --> SvcReactivateLogic{¿Categoría Y Rubro<br/>activos?}:::decision
    SvcReactivateLogic -->|Sí| SvcVisible[Servicio visible en público]:::process
    SvcReactivateLogic -->|No| SvcHidden[Servicio activo PERO oculto<br/>→ Padre desactivado bloquea]:::warn
    SvcVisible --> EndSvc([Fin Servicio]):::end
    SvcHidden --> EndSvc
    
%% ============ RESUMEN REGLAS ============
    subgraph ReglasClave [Reglas Clave de Lifecycle]
        R1[Desactivar = status propio desactivado<br/>Visibilidad resuelta dinámicamente]:::process
        R2[Base: eliminar sin forks ni relaciones<br/>Fork: eliminar recursivamente con hijos]:::end
        R3[Reactivar = status propio activo<br/>No activa hijos desactivados]:::process
        R4[Visibilidad pública = AND de toda la cadena<br/>(rubro ACTIVO ∧ categoría ACTIVO ∧ servicio ACTIVO)]:::warn
        R5[Estado efectivo: propio + base + padres fork<br/>Cualquier ancestro desactivado bloquea visibilidad]:::fork
        R6[Historial órdenes: NUNCA se toca<br/>snapshot en order_services inmutable]:::special
    end
    
    %% Conexiones a reglas
    RubroDeactivate -.-> R1
    RubroCheck -.-> R2
    RubroReactivate -.-> R3
    CatDeactivate -.-> R1
    CatCheck -.-> R2
    CatReactivate -.-> R3
    SvcDeactivate -.-> R1
    SvcCheck -.-> R2
    SvcReactivate -.-> R3
    RubroCascade -.-> R4
    CatCascade -.-> R4
    SvcDeactivate -.-> R4
    RubroCascade -.-> R5
    CatCascade -.-> R5
    SvcDeactivate -.-> R6
```

---

## Tabla de Decisiones (para Notion)

| Escenario | Entidad | ¿Tiene relaciones/historial? | Acción Sistema | Resultado | Reversible |
|-----------|---------|------------------------------|----------------|-----------|------------|
| Usuario click "Eliminar" | Rubro | Sí (categorías/servicios/órdenes/forks) | Bloquea eliminación → Fuerza desactivar | `status=desactivado` + cascada completa | ✅ Reactivable |
| Usuario click "Eliminar" | Rubro (base) | No | `forceDelete()` hard delete | Eliminado permanentemente BD | ❌ No |
| Usuario click "Eliminar" | Rubro (fork) | Cualquier cantidad de hijos | `DELETE /api/user-catalog/rubros/{id}` | Fork y descendientes eliminados recursivamente | ❌ No (pero base intacta) |
| Usuario click "Eliminar" | Categoría | Sí (servicios/órdenes/forks) | Bloquea → Fuerza desactivar | `status=desactivado` + cascada servicios | ✅ Reactivable |
| Usuario click "Eliminar" | Categoría (base) | No | `forceDelete()` | Eliminado permanentemente | ❌ No |
| Usuario click "Eliminar" | Categoría (fork) | Cualquier cantidad de hijos | `DELETE /api/user-catalog/categorias/{id}` | Fork y descendientes eliminados recursivamente | ❌ No |
| Usuario click "Eliminar" | Servicio | Sí (order_services > 0) | `status=desactivado` (NO elimina) | Oculto público; snapshot en órdenes intacto | ✅ Reactivable |
| Usuario click "Eliminar" | Servicio (base) | No (order_services = 0) | `forceDelete()` | Eliminado permanentemente | ❌ No |
| Usuario click "Eliminar" | Servicio (fork) | — | `DELETE /api/user-catalog/services/{id}` | Fork eliminado; base intacta | ❌ No |

---

## Matriz de Visibilidad Pública (AND Lógico)

| Rubro | Categoría | Servicio | ¿Visible en Perfil Público? |
|-------|-----------|----------|----------------------------|
| ✅ Activo | ✅ Activo | ✅ Activo | **SÍ** |
| ❌ Desactivado | ✅ Activo | ✅ Activo | **NO** (rubro oculta todo) |
| ✅ Activo | ❌ Desactivado | ✅ Activo | **NO** (categoría oculta servicios) |
| ✅ Activo | ✅ Activo | ❌ Desactivado | **NO** (servicio oculto) |
| ❌ Desactivado | ❌ Desactivado | ❌ Desactivado | **NO** |
| ✅ Activo | ✅ Activo | ✅ Activo (fork con override) | **SÍ** (con valores override) |

---

## Cascada Detallada por Operación

### **Desactivar Rubro (HU-015)**
```
1. Rubro.status = 'desactivado'
2. UPDATE categorias SET status='desactivado' WHERE rubro_id IN (rubro_base, forks_usuario)
3. UPDATE services SET status='desactivado' WHERE categoria_id IN (categorias_afectadas)
4. En forks, actualizar el `status` propio del item afectado; `base_id` es conceptual y no se usa para simular filas base.
5. Perfil público: query filtra WHERE rubro.status='activo' → árbol completo invisible
```

### **Desactivar Categoría (HU-019)**
```
1. Categoria.status = 'desactivado'
2. UPDATE services SET status='desactivado' WHERE categoria_id IN (cat_base, forks_usuario)
3. En forks, actualizar el `status` propio del item afectado; la jerarquía se sigue por `parent_fork_id`.
4. Perfil público: WHERE categoria.status='activo' AND rubro.status='activo'
```

### **Desactivar Servicio (HU-023)**
```
1. Service.status = 'desactivado'
2. En forks, actualizar el `status` propio del item afectado; la jerarquía se sigue por `parent_fork_id`.
3. Perfil público: WHERE service.status='activo' AND categoria.status='activo' AND rubro.status='activo'
4. order_services: SIN CAMBIOS (snapshot inmutable)
```

### **Reactivar (Cualquier entidad)**
```
1. El item seleccionado establece únicamente su status propio en `activo`.
2. No reactiva hijos cuyo status propio siga `desactivado`.
3. Visibilidad final exige estados propio, base y padres fork aplicables en `activo`.
```

---

## Notas de Implementación

- **Origen y autorización:** Base = endpoint base, solo admin/seeder. Fork = `/api/user-catalog/...`, solo el usuario propietario. Un fork con hijos puede eliminarse recursivamente.
- **Estado efectivo:** El status dedicado del item seleccionado, su base y todos sus padres fork deben estar activos para ser visible. `overrides` nunca contiene status; ningún override activo vence un ancestro desactivado.
- **Hard delete:** La estrategia concreta de persistencia (por ejemplo, `SoftDeletes` + `forceDelete()`) queda TBD para SDD; no cambia las reglas de autorización ni cascada descritas aquí.
- **Status enum**: `activo`, `desactivado` (NO `eliminado` — ese es `deleted_at` not null).
- **Scopes globales**: `Scope::whereStatus('activo')` en todos los queries públicos.
- **Validación prévia**: Antes de DELETE → `Model::checkDeletable()` retorna bool + razón.
- **UI**: Botón "Eliminar" muestra confirmación distinta según caso:
  - Con relaciones → "Esta acción desactivará el elemento y sus hijos. ¿Continuar?"
  - Sin relaciones → "Se eliminará permanentemente. ¿Confirmar?"

> **Nota:** Los detalles de implementación no definidos aquí quedan TBD para SDD.

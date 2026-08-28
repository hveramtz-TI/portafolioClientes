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
    Start([Inicio: Usuario intenta eliminar\nrubro/categoría/servicio]):::start
    
    %% DECISIÓN CENTRAL: TIPO ENTIDAD
    EntityType{¿Qué entidad?}:::decision
    Start --> EntityType
    
    %% ============ RUBRO ============
    EntityType -->|Rubro| RubroCheck{¿Tiene relaciones?\ncategorías/servicios/órdenes/forks}:::decision
    
    RubroCheck -->|Sí| RubroDeactivate[PATCH /api/rubros/{id}/deactivate\n→ status=desactivado]:::process
    RubroDeactivate --> RubroCascade[CASCADA AUTOMÁTICA:\n1. Categorías del rubro → desactivado\n2. Servicios de esas categorías → desactivado\n3. Forks usuario heredan status\n4. Perfil público: oculta todo el árbol]:::warn
    RubroCascade --> RubroResult[Resultado: Rubro + árbol desactivados\n→ Historial órdenes intacto\n→ Reactivable en cualquier momento]:::end
    
    RubroCheck -->|No (base)| RubroDeleteBase[DELETE /api/rubros/{id}\n→ forceDelete()\n→ Validación: 0 forks + 0 relaciones]:::end
    RubroCheck -->|No (fork)| RubroDeleteFork[DELETE /api/user-catalog/rubros/{id}\n→ Quita solo fork usuario\n→ Base y otros forks intactos]:::fork
    
    %% REACTIVAR RUBRO
    RubroResult -->|Usuario reactiva| RubroReactivate[PATCH /api/rubros/{id}/reactivate\n→ status=activo]:::process
    RubroReactivate --> RubroReactivateCascade[CASCADA REACTIVAR:\n1. Rubro → activo\n2. Categorías/servicios SOLO si no tienen override desactivado\n3. Forks usuario heredan si no tienen override\n4. Perfil público: muestra árbol activo]:::process
    RubroReactivateCascade --> EndRubro([Fin Rubro]):::end
    
    %% ============ CATEGORÍA ============
    EntityType -->|Categoría| CatCheck{¿Tiene relaciones?\nservicios/órdenes/forks}:::decision
    
    CatCheck -->|Sí| CatDeactivate[PATCH /api/categorias/{id}/deactivate\n→ status=desactivado]:::process
    CatDeactivate --> CatCascade[CASCADA AUTOMÁTICA:\n1. Servicios de la categoría → desactivado\n2. Forks usuario heredan status\n3. Perfil público: oculta categoría + servicios\n4. SI rubro padre desactivado → ya oculto]:::warn
    CatCascade --> CatResult[Resultado: Categoría + servicios desactivados\n→ Historial órdenes intacto\n→ Reactivable si rubro padre activo]:::end
    
    CatCheck -->|No (base)| CatDeleteBase[DELETE /api/categorias/{id}\n→ forceDelete()\n→ Validación: 0 forks + 0 servicios]:::end
    CatCheck -->|No (fork)| CatDeleteFork[DELETE /api/user-catalog/categorias/{id}\n→ Quita solo fork usuario]:::fork
    
    %% REACTIVAR CATEGORÍA
    CatResult -->|Usuario reactiva| CatReactivate[PATCH /api/categorias/{id}/reactivate\n→ status=activo]:::process
    CatReactivate --> CatReactivateLogic{¿Rubro padre\nactivo?}:::decision
    CatReactivateLogic -->|Sí| CatVisible[Categoría + servicios (sin override) → visibles en público]:::process
    CatReactivateLogic -->|No| CatHidden[Categoría activa PERO oculta\n→ Rubro padre desactivado bloquea visibilidad]:::warn
    CatVisible --> EndCat([Fin Categoría]):::end
    CatHidden --> EndCat
    
    %% ============ SERVICIO ============
    EntityType -->|Servicio| SvcCheck{¿Tiene historial\nen order_services?}:::decision
    
    SvcCheck -->|Sí (historial > 0)| SvcDeactivate[PATCH /api/services/{id}/deactivate\n→ status=desactivado\n→ NO forceDelete()\n→ order_services conserva snapshot]:::special
    SvcDeactivate --> SvcResult[Resultado: Servicio desactivado\n→ Oculto en público y nuevas solicitudes\n→ Órdenes históricas: snapshot inmutable intacto\n→ Reactivable en cualquier momento]:::end
    
    SvcCheck -->|No (historial = 0)| SvcDeleteCheck{¿Base\nfork?}:::decision
    SvcDeleteCheck -->|Base| SvcDeleteBase[DELETE /api/services/{id}\n→ forceDelete()\n→ Validación: 0 forks]:::end
    SvcDeleteCheck -->|Fork| SvcDeleteFork[DELETE /api/user-catalog/services/{id}\n→ Quita fork usuario]:::fork
    
    %% REACTIVAR SERVICIO
    SvcResult -->|Usuario reactiva| SvcReactivate[PATCH /api/services/{id}/reactivate\n→ status=activo]:::process
    SvcReactivate --> SvcReactivateLogic{¿Categoría Y Rubro\nactivos?}:::decision
    SvcReactivateLogic -->|Sí| SvcVisible[Servicio visible en público]:::process
    SvcReactivateLogic -->|No| SvcHidden[Servicio activo PERO oculto\n→ Padre desactivado bloquea]:::warn
    SvcVisible --> EndSvc([Fin Servicio]):::end
    SvcHidden --> EndSvc
    
%% ============ RESUMEN REGLAS ============
    subgraph ReglasClave [Reglas Clave de Lifecycle]
        R1[Desactivar = status=desactivado + cascada física en BD\nNO usa deleted_at]:::process
        R2[Eliminar = forceDelete() SOLO si 0 relaciones\n+ 0 forks referenciando]:::end
        R3[Reactiva = status=activo + herencia visible\nSI padres activos Y sin override desactivado]:::process
        R4[Visibilidad pública = AND de toda la cadena\n(rubro ACTIVO ∧ categoría ACTIVO ∧ servicio ACTIVO)]:::warn
        R5[Forks: heredan status base SI no tienen override\ndesactivado SIEMPRE bloquea override activo]:::fork
        R6[Historial órdenes: NUNCA se toca\nsnapshot en order_services inmutable]:::special
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
| Usuario click "Eliminar" | Rubro (fork) | No | `DELETE /user-catalog/rubros/{id}` | Solo fork usuario removido | ❌ No (pero base intacta) |
| Usuario click "Eliminar" | Categoría | Sí (servicios/órdenes/forks) | Bloquea → Fuerza desactivar | `status=desactivado` + cascada servicios | ✅ Reactivable |
| Usuario click "Eliminar" | Categoría (base) | No | `forceDelete()` | Eliminado permanentemente | ❌ No |
| Usuario click "Eliminar" | Categoría (fork) | No | `DELETE /user-catalog/categorias/{id}` | Solo fork removido | ❌ No |
| Usuario click "Eliminar" | Servicio | Sí (order_services > 0) | `status=desactivado` (NO elimina) | Oculto público; snapshot en órdenes intacto | ✅ Reactivable |
| Usuario click "Eliminar" | Servicio (base) | No (order_services = 0) | `forceDelete()` | Eliminado permanentemente | ❌ No |
| Usuario click "Eliminar" | Servicio (fork) | No | `DELETE /user-catalog/services/{id}` | Solo fork removido | ❌ No |

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
4. UPDATE user_catalog_items SET status='desactivado' WHERE base_id IN (SELECT id FROM user_catalog_items WHERE item_type='rubro' AND base_id = rubro_id)
5. Perfil público: query filtra WHERE rubro.status='activo' → árbol completo invisible
```

### **Desactivar Categoría (HU-019)**
```
1. Categoria.status = 'desactivado'
2. UPDATE services SET status='desactivado' WHERE categoria_id IN (cat_base, forks_usuario)
3. UPDATE user_catalog_items SET status='desactivado' WHERE base_id IN (SELECT id FROM user_catalog_items WHERE item_type='categoria' AND base_id = cat_id)
4. Perfil público: WHERE categoria.status='activo' AND rubro.status='activo'
```

### **Desactivar Servicio (HU-023)**
```
1. Service.status = 'desactivado'
2. UPDATE user_catalog_items SET status='desactivado' WHERE base_id IN (SELECT id FROM user_catalog_items WHERE item_type='service' AND base_id = service_id)
3. Perfil público: WHERE service.status='activo' AND categoria.status='activo' AND rubro.status='activo'
4. order_services: SIN CAMBIOS (snapshot inmutable)
```

### **Reactivar (Cualquier entidad)**
```
1. Entidad.status = 'activo'
2. SOLO propaga a hijos SI no tienen override 'desactivado' en fork usuario
3. Visibilidad final = AND(entidad, padre, abuelo) all 'activo'
```

---

## Notas de Implementación

- **SoftDeletes trait** en las 3 tablas base + `user_catalog_items` para `forceDelete()`.
- **Status enum**: `activo`, `desactivado` (NO `eliminado` — ese es `deleted_at` not null).
- **Scopes globales**: `Scope::whereStatus('activo')` en todos los queries públicos.
- **Validación prévia**: Antes de DELETE → `Model::checkDeletable()` retorna bool + razón.
- **UI**: Botón "Eliminar" muestra confirmación distinta según caso:
  - Con relaciones → "Esta acción desactivará el elemento y sus hijos. ¿Continuar?"
  - Sin relaciones → "Se eliminará permanentemente. ¿Confirmar?"

> **Nota:** Auditoría de cambios de status (`status_changes` table) es propuesta pendiente de aprobación en SDD-design.
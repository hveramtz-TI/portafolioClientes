# Historias de Usuario

Directorio central de las Historias de Usuario del proyecto **Portafolio de Clientes** (CRM personal).

## Índice de HUs

| Código | Título | Épica | Prioridad | Estado |
|--------|--------|-------|-----------|--------|
| HU-001 | PWA responsive base (instalable, escritorio + mobile) | Plataforma | Baja | Pendiente |
| HU-002 | Crear cliente | Clientes y empresas | Alta | Pendiente |
| HU-003 | Validar duplicado RUT + empresa con alerta | Clientes y empresas | Alta | Pendiente |
| HU-004 | Editar cliente | Clientes y empresas | Alta | Pendiente |
| HU-005 | Desactivar cliente (conserva datos) | Clientes y empresas | Alta | Pendiente |
| HU-006 | Eliminar definitivamente cliente con registros relacionados | Clientes y empresas | Alta | Pendiente |
| HU-007 | Reactivar cliente | Clientes y empresas | Media | Pendiente |
| HU-008 | Listado de clientes con filtro activos/desactivados | Clientes y empresas | Alta | Pendiente |
| HU-009 | Crear empresa | Clientes y empresas | Alta | Pendiente |
| HU-010 | Editar empresa | Clientes y empresas | Media | Pendiente |
| HU-011 | Eliminar empresa (bloqueo si tiene clientes asociados) | Clientes y empresas | Media | Pendiente |
| HU-012 | Asociar/desasociar cliente a empresa | Clientes y empresas | Alta | Pendiente |
| HU-013 | Crear rubro | Rubros, categorías y servicios | Alta | Pendiente |
| HU-014 | Editar rubro | Rubros, categorías y servicios | Media | Pendiente |
| HU-015 | Desactivar rubro (oculta servicios, conserva historial) | Rubros, categorías y servicios | Media | Pendiente |
| HU-016 | Eliminar definitivamente rubro sin relaciones | Rubros, categorías y servicios | Media | Pendiente |
| HU-017 | Crear categoría | Rubros, categorías y servicios | Alta | Pendiente |
| HU-018 | Editar categoría | Rubros, categorías y servicios | Media | Pendiente |
| HU-019 | Desactivar categoría (oculta servicios, conserva historial) | Rubros, categorías y servicios | Media | Pendiente |
| HU-020 | Eliminar definitivamente categoría sin relaciones | Rubros, categorías y servicios | Media | Pendiente |
| HU-021 | Crear servicio | Rubros, categorías y servicios | Alta | Pendiente |
| HU-022 | Editar servicio (título, descripción, valor) | Rubros, categorías y servicios | Alta | Pendiente |
| HU-023 | Eliminar o desactivar servicio (según historial de uso) | Rubros, categorías y servicios | Media | Pendiente |
| HU-024 | Seeders de rubros, categorías y servicios con valores ficticios | Rubros, categorías y servicios | Alta | Pendiente |
| HU-025 | Copia personalizable del catálogo por usuario | Rubros, categorías y servicios | Alta | Pendiente |
| HU-026 | Configurar contacto público explícito | Perfil público | Media | Pendiente |
| HU-027 | Activar/desactivar perfil con confirmación | Perfil público | Media | Pendiente |
| HU-028 | URL pública UUID inmutable + "Perfil temporalmente no disponible" | Perfil público | Media | Pendiente |
| HU-029 | Página pública estilo delivery (rubro→categoría→servicio) | Perfil público | Media | Pendiente |
| HU-030 | Carrito de servicios (multi-selección, una vez por servicio, quitar) | Perfil público | Media | Pendiente |
| HU-031 | Formulario público de solicitud | Solicitudes | Media | Pendiente |
| HU-032 | Total referencial del carrito | Solicitudes | Media | Pendiente |
| HU-033 | Confirmación al visitante con código SOL-XXXX | Solicitudes | Media | Pendiente |
| HU-034 | Email de confirmación al visitante (Resend) | Solicitudes | Media | Pendiente |
| HU-035 | Notificación por email al usuario (Resend) | Solicitudes | Media | Pendiente |
| HU-036 | Notificación push al usuario (PWA instalada y autorizada) | Solicitudes | Media | Pendiente |
| HU-037 | Sección de solicitudes con estados | Solicitudes | Media | Pendiente |
| HU-038 | Convertir solicitud en cliente (un clic) | Solicitudes | Media | Pendiente |
| HU-039 | Vincular solicitud a cliente existente (sin duplicar) | Solicitudes | Media | Pendiente |
| HU-040 | Crear orden desde solicitud con servicios modificables | Solicitudes | Media | Pendiente |
| HU-041 | Email al visitante al aceptar/rechazar | Solicitudes | Media | Pendiente |
| HU-042 | Crear orden para cliente (inicia Pendiente, vincula solicitud) | Órdenes de trabajo | Alta | Pendiente |
| HU-043 | Transicionar estado de orden | Órdenes de trabajo | Alta | Pendiente |
| HU-044 | Agregar servicio existente a la orden (guarda copia histórica) | Órdenes de trabajo | Alta | Pendiente |
| HU-045 | Editar/eliminar servicio dentro de la orden | Órdenes de trabajo | Alta | Pendiente |
| HU-046 | Crear servicio desde la orden | Órdenes de trabajo | Media | Pendiente |
| HU-047 | Fechas de inicio/término y notas de la orden | Órdenes de trabajo | Media | Pendiente |
| HU-048 | Total automático de la orden | Órdenes de trabajo | Alta | Pendiente |
| HU-049 | Solo lectura de órdenes Completada/Cancelada | Órdenes de trabajo | Alta | Pendiente |
| HU-050 | Tarjetas de clientes (activos, desactivados, nuevos este mes) | Dashboard y finanzas | Baja | Pendiente |
| HU-051 | Tarjetas de órdenes (total, pendientes, en progreso, completadas, canceladas) | Dashboard y finanzas | Baja | Pendiente |
| HU-052 | Tarjetas financieras (ingresos esperados/confirmados) | Dashboard y finanzas | Baja | Pendiente |
| HU-053 | Gráfico de órdenes | Dashboard y finanzas | Baja | Pendiente |
| HU-054 | Gráfico de clientes | Dashboard y finanzas | Baja | Pendiente |
| HU-055 | Gráfico de ingresos | Dashboard y finanzas | Baja | Pendiente |

## Estados

| Estado | Significado |
|--------|-------------|
| `Pendiente` | Capturada, sin revisar |
| `En Revisión` | En discusión con el usuario |
| `Aprobada` | Validada y lista para SDD |
| `Implementada` | Terminada y verificada |

## Template de HU

Copiar el siguiente bloque al crear `HU-XXX.md`:

```markdown
# HU-XXX — Título corto de la historia

**Épica:** Epic N — Nombre
**Prioridad:** Alta | Media | Baja
**Estado:** Pendiente
**Fecha:** YYYY-MM-DD

## Descripción

Como **[rol]**, quiero **[acción]** para **[beneficio]**.

## Criterios de Aceptación

- [ ] Criterio 1
- [ ] Criterio 2

## Notas / Dudas

- Preguntas abiertas, supuestos o decisiones pendientes.

## Flujos UX/UI relacionados

- [auth-flow](../flujos/auth-flow.md) — si aplica
```

## Instrucciones

1. Numeración secuencial global: la próxima HU libre es `HU-056`.
2. Una HU por archivo, nombre `HU-XXX.md`.
3. Actualizar la tabla índice al agregar/modificar una HU.
4. No inventar requisitos: si falta información, preguntar al usuario.
5. Cerrar una HU (`Aprobada`) solo tras validación explícita.
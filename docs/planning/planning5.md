# Planning — Documento de Portafolio (PDF)

**Fecha:** 2026-09-07
**Estado:** ⏳ **Pendiente de validación del usuario** — requisitos capturados de la conversación 2026-09-07; tras aprobación, pasa a SDD.
**Objetivo:** Capturar los requisitos de la épica **Documento de Portafolio (PDF)**: la generación de un PDF descargable, con plantilla TMC, construido automáticamente desde las órdenes del usuario, anonimizado y con montos degradados, para que el freelancer lo adjunte a su CV.

## Contexto

- Stack: Next.js 16 (App Router) · React 19 · Tailwind CSS 4 · TypeScript · Laravel 13 · PostgreSQL 16 · Redis 7 · MinIO. Componentes UI con **shadcn/ui**.
- Fuente de verdad de identidad: `docs/vision.md`. Este planning materializa la proyección "PDF (evidencia profesional)" de las dos proyecciones del dato.
- **TMC es multi-tenant** (decisión 2026-09-07): para el dueño del producto y otros freelancers. Esto valida el modelo híbrido de planning3 (pausado, PR #6) y exige que el PDF se genere por usuario autenticado, con sus propios datos.
- Datos de origen ya definidos por épicas previas: `order_services` guarda **snapshot inmutable** de `title` + `value` al agregar un servicio a la orden (planning3 D10 / HU-044). Órdenes con estados `Pendiente / En progreso / Completada / Cancelada` (planning2).
- El **HOW técnico** (librería de generación PDF, server-side vs cliente, almacenamiento) **NO se define en este planning**: se resuelve en SDD design. Este documento fija el QUÉ.

## Decisiones confirmadas (2026-09-07)

| # | Decisión | Valor elegido | Origen |
|---|----------|---------------|--------|
| D1 | Proyecciones del dato | **Dos separadas**: PDF (descargable, para CV) y perfil web público (captación de solicitudes). El PDF **no** es una vista de la web pública | Conversación 2026-09-07 |
| D2 | Moneda | **CLP only** en el PDF (el catálogo ya es CLP, planning3 D11) | Respuesta P1 |
| D3 | Redondeo de montos | **Centena superior**: `ceil(monto / 100) * 100` — siempre termina en 00, nunca baja del real (1453 → 1500; 853.400 → 853.500) | Respuesta P2 |
| D4 | Selección de proyectos | **Automática**: entran todas las órdenes elegibles del usuario, sin curado manual | Respuesta P3 |
| D5 | Branding del PDF | **Plantilla TMC** única; sin marca personal del freelancer ni editor de diseño | Respuesta P4 |

## Requisitos capturados

### Contenido y alcance

- R1. El usuario (logueado) puede **generar y descargar un PDF** desde su cuenta.
- R2. El contenido se arma **automáticamente** (D4): el sistema toma las órdenes del usuario sin que él elija cuáles.
- R3. Cada proyecto del PDF muestra **los servicios nominados** (título) tomados del **snapshot histórico** de la orden (`order_services`), no del catálogo actual: si el servicio se renombró después, el PDF usa el nombre con el que se ejecutó el trabajo.
- R4. Cada proyecto muestra su **estado general**: `Completada` o `En progreso`.

### Estados elegibles (filtro automático)

- R5. **Entran** solo órdenes en estado `Completada` o `En progreso`.
- R6. **Nunca aparecen**: órdenes `Pendiente`, órdenes `Cancelada`, solicitudes rechazadas/declinadas ni ningún registro descartado. El PDF no debe dejar rastro de trabajo no concretado.

### Anonimización (privacidad primero)

- R7. El PDF **nunca muestra datos sensibles**: ni nombre del cliente, ni nombre de la empresa, ni RUT, ni contacto de ninguna parte.
- R8. El proyecto se identifica de forma descriptiva y anónima, con el patrón validado por el usuario:
  - `Proyecto inmobiliaria de una empresa destacada en el rubro *****`
  - Es decir: **tipo de trabajo** (derivado de los servicios) + **rubro del cliente** (el rubro sí se revela; es la señal de industria), **sin nombre real**.
- R9. El rubro se toma del catálogo snapshot/asociado a los servicios de la orden.

### Montos degradados

- R10. Los valores se muestran en **CLP** (D2), **redondeados hacia arriba a la centena superior** (D3), para no transparentar el monto real del contrato.
- R11. El redondeo aplica a cada monto que se imprime en el PDF (por servicio y por total, si el diseño muestra totales).

### Plantilla

- R12. **Plantilla TMC** fija (D5): identidad visual del producto, sin configuración por usuario en esta épica.

## Fuera de alcance (YAGNI)

- Multi-moneda o conversión de divisas.
- Editor de contenido/diseño del PDF, selección manual de proyectos, ordenamiento o curado.
- Branding personal del freelancer (logo, colores propios) en la plantilla.
- Compartir el PDF por link público o URL permanente: el PDF es **descarga**.
- Exportación a formatos distintos de PDF (DOCX, etc.).
- Historial de versiones del PDF generado.
- Credenciales/verificación de terceros del trabajo (anti-fraude del portafolio).

## Riesgos y supuestos

| # | Riesgo / supuesto | Mitigación |
|---|-------------------|------------|
| RK1 | Una orden con un solo servicio muy genérico produce un proyecto anónimo poco informativo ("rubro *****" sin contexto). | Se acepta para MVP: la señal útil es rubro + servicio + estado. Ajustes de redacción van a SDD. |
| RK2 | Órdenes con servicios de **rubros cruzados**: el patrón "rubro X" asume uno. | **Abierto → P1** (abajo). |
| RK3 | El redondeo a la centena no oculta montos grandes (1.000.000 → 1.000.100 revela precisión). | **Abierto → P2**: evaluar magnitud proporcional en SDD. |
| RK4 | Depende de épicas previas: sin snapshot (`HU-044`) ni estados de orden (`HU-043`), el PDF no tiene fuente. | Orden de sprints: Órdenes → Perfil/Solicitudes → Portafolio PDF. Se documenta la dependencia. |

## Pendientes (para resolver antes o durante SDD)

| # | Pendiente | Impacto |
|---|-----------|---------|
| P1 | ¿Qué rubro mostrar cuando una orden mezcla servicios de varios rubros? (¿el del servicio de mayor valor? ¿enumerar varios?) | Redacción R8/R9 |
| P2 | ¿La centena es fija para todo monto o escalable por magnitud (centena / millar / decena de mil)? Ver RK3. | Refina D3 |
| P3 | ¿El proyecto `En progreso` muestra solo servicios ya agregados a la orden (posibles cambios futuros)? | Refina R4 |
| P4 | Fecha del proyecto en el PDF: ¿se muestra año de término/inicio, o nada (el planning2 define fechas de orden como opcionales)? | R4/contenido |

## HUs formalizadas (2026-09-07) — ver `docs/historias/`

| HU | Título | Prioridad | Estado |
|----|--------|-----------|--------|
| [HU-056](../historias/HU-056.md) | Generar y descargar PDF de portafolio (plantilla TMC) | Alta | Pendiente |
| [HU-057](../historias/HU-057.md) | Composición automática de proyectos (Completada + En progreso) | Alta | Pendiente |
| [HU-058](../historias/HU-058.md) | Anonimización del cliente con rubro visible | Alta | Pendiente |
| [HU-059](../historias/HU-059.md) | Redondeo CLP a centena superior | Media | Pendiente |
| [HU-060](../historias/HU-060.md) | Servicios del snapshot con estado general | Media | Pendiente |

## Flujo UX/UI de referencia

| Flujo | Qué cubre |
|-------|-----------|
| [portafolio-pdf.md](../flujos/portafolio-pdf.md) | Generación → anonimización → redondeo → descarga del PDF |

## Próximo paso

1. El usuario valida este planning (y resuelve P1–P4 o los delega a SDD).
2. `planning5` pasa a `✅ Aprobado` y alimenta SDD (`sdd-spec` → `sdd-design` → `sdd-tasks` → `sdd-apply`), respetando la dependencia RK4.
3. Espejo en Notion conforme a la regla "Git manda, Notion sigue".

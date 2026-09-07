# Visión e Identidad — TMC (That's My Client)

**Fecha:** 2026-09-07
**Estado:** ⏳ Pendiente de validación del usuario
**Rol del documento:** Fuente de verdad de la identidad del producto. Los plannings y HUs se derivan de esta visión; si un planning contradice este documento, se corrige el planning (o se actualiza aquí con decisión explícita).

## La idea central

**Tu cartera de clientes se convierte en tu portafolio profesional.**

Un CRM genérico te pide trabajo (cargar datos) y te devuelve orden. TMC te devuelve **evidencia**: con la información que ya administrás —clientes, empresas, servicios, órdenes— el sistema genera un **documento de portafolio en PDF** que podés descargar y adjuntar a tu CV. Cargar datos deja de ser una carga administrativa y pasa a ser construcción de tu reputación profesional.

## Target

- **Freelancers de servicios** (informática, diseño, consultoría y rubros personalizables).
- **Multi-tenant**: la plataforma se ofrece a múltiples usuarios. Esto justifica el modelo híbrido de catálogo (catálogo base global + fork personal con overrides, planning3) y la separación de datos por usuario.
- El repo interno sigue llamándose `portafolioClientes`; el nombre visible del producto es **TMC (That's My Client)** (planning4 D8).

## Las dos proyecciones del mismo dato

El mismo core de datos (clientes + catálogo + órdenes con snapshot) alimenta dos salidas con objetivos distintos:

| Proyección | Objetivo | Naturaleza | Estado |
|---|---|---|---|
| **Documento de Portafolio (PDF)** | Conseguir **trabajo como freelance**: evidencia profesional para adjuntar al CV | Descargable, anonimizada, montos degradados | Épica nueva → `planning5.md` |
| **Perfil público web** | Conseguir **clientes y solicitudes**: catálogo visible + carrito + formulario de solicitud | URL pública UUID, interactiva | Épica "Perfil público" (HU-026+, Pendiente) |

## Principios de producto

1. **Privacidad primero.** El portafolio nunca expone datos sensibles de clientes: nombres reales, RUT, contactos ni montos de contrato directos. La anonimización y el redondeo son reglas de identidad, no features opcionales.
2. **El dato se carga una vez, rinde dos veces.** Órdenes y servicios que hoy alimentan el dashboard, mañana alimentan el PDF y el perfil público sin trabajo extra.
3. **Honestidad degradada, no mentira.** El PDF muestra trabajo real (servicios nominados, estados reales, rubro real) con montos redondeados hacia arriba y clientes anónimos. No se inventan proyectos.
4. **KISS / YAGNI.** Plantilla única TMC, moneda única CLP, contenido automático sin editor. Las variantes se agregan solo con pedido explícito.

## Qué es TMC

- Un gestor de cartera de clientes y empresas para freelancers.
- Un catálogo personalizable de servicios con lenguaje natural orientado al cliente final.
- Una vía de captación: perfil público con solicitud tipo "app de delivery".
- Un generador de evidencia profesional: el portafolio PDF para tu CV.

## Qué NO es TMC

- No es una facturador ni cubre SII/boletas/contratos/transferencias (fuera de alcance desde planning2).
- No es un gestor de gastos (solo ingresos, planning2).
- No es un constructor de CVs: el PDF es un anexo *al* CV, generado desde datos reales de trabajo.
- No es un marketplace: las solicitudes llegan al perfil del usuario, TMC no interpone ventas.

## Impacto en el backlog

| Componente | Impacto |
|---|---|
| planning3 (Catálogo base+fork, pausado en slice 1/7, PR #6) | **Validado por multi-tenancy**: el modelo híbrido se justifica. Se retoma en un sprint posterior sin rediseñar. |
| Épica Perfil público (HU-026+) | Sin cambios: foco en captación de solicitudes. |
| Épica Órdenes (HU-042+) | Se convierte en la fuente de datos del portafolio: el snapshot inmutable (`order_services`) es crítico para el PDF. |
| Épica nueva: Documento de Portafolio (PDF) | Capturada en `planning5.md`, HUs HU-056–HU-060. |

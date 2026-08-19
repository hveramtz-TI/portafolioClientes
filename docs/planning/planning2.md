# Planning — Toma de Requisitos (Historias de Usuario)

**Fecha:** 2026-08-19
**Objetivo:** Capturar, organizar y validar las Historias de Usuario del CRM personal "Portafolio de Clientes", junto con sus flujos UX/UI, antes de pasar a la fase de SDD.

## Contexto

- Stack: Next.js 16 (App Router) · React 19 · Tailwind CSS 4 · TypeScript · Laravel 13 · PostgreSQL 16 · Redis 7 · MinIO.
- El planning anterior (`planning1.md`) define Auth + Roles (login + seeder admin, sin registro público).
- La captura actual reconstruye el backlog desde cero, de forma incremental y validada.
- Se utilizará **shadcn/ui** para acelerar la construcción del frontend mediante componentes reutilizables.
- Decisiones generales: KISS, YAGNI, feature-first, una HU por archivo y flujos UX/UI en directorio separado con Mermaid.

## Proceso

1. Definir épicas y alcance con el usuario.
2. Capturar HUs por épica, una HU por archivo en `docs/historias/`.
3. Revisar y validar cada HU antes de aprobarla.
4. Documentar flujos UX/UI en `docs/flujos/`.
5. Al cerrar una épica, pasar a SDD.

## Épicas activas

| Épica | Alcance | Estado |
|---|---|---|
| Plataforma | PWA responsive y componentes UI con shadcn/ui | En captura |
| Clientes y empresas | Registros, asociación, estados y validaciones | En captura |
| Rubros, categorías y servicios | Catálogo personalizable y lenguaje natural | En captura |
| Perfil público | URL pública, catálogo visible y contacto | En captura |
| Solicitudes | Formulario público, notificaciones y conversión | En captura |
| Órdenes de trabajo | Servicios, estados, fechas e historial | En captura |
| Dashboard y finanzas | Métricas, tarjetas e ingresos | En captura |

## Decisiones confirmadas

### Plataforma

- La aplicación será una PWA responsive para escritorio y dispositivos mobile.
- Se utilizará shadcn/ui para modales, formularios, tarjetas, tablas y otros componentes del frontend.

### Clientes y empresas

- La empresa es un registro independiente.
- Una empresa puede tener múltiples clientes.
- Cada cliente puede asociarse como máximo a una empresa; la relación es opcional.
- Campos obligatorios de empresa: nombre, RUT, email y teléfono.
- Campos opcionales de empresa: dirección y sitio web.
- Campos obligatorios de cliente: nombre, RUT, teléfono y email.
- Campos opcionales de cliente: apellido, dirección, empresa asociada, notas y sitio web.
- La combinación `RUT + empresa` debe ser única.
- Sin empresa asociada, el RUT también debe ser único.
- Si existe un duplicado, el guardado se cancela y se muestra una alerta.
- Los clientes tienen estado `Activo` o `Desactivado`.
- Los clientes desactivados siguen bloqueando duplicados, conservan historial y pueden reactivarse.
- El listado muestra clientes activos por defecto y ofrece un filtro para consultar desactivados.
- Eliminar un cliente requiere un modal con dos opciones: eliminar definitivamente todos sus registros relacionados o desactivarlo conservando los datos.
- Una empresa con clientes asociados no puede eliminarse. Primero se deben desvincular o reasignar sus clientes.
- Una empresa sin relaciones puede eliminarse definitivamente.

### Rubros, categorías y servicios

- La jerarquía será `Rubro → Categoría → Servicio`.
- Un usuario puede estar relacionado con uno o más rubros.
- Las categorías y servicios se expresarán prioritariamente en lenguaje natural para que sean comprensibles para los clientes.
- Ejemplo: `Informática → Sitios web y presencia digital → Actualizar portafolio web`.
- Los términos técnicos como `Frontend`, `Backend` o `Fullstack` podrán utilizarse como etiquetas internas opcionales.
- Los rubros, categorías y servicios comunes se precargarán mediante seeders con valores ficticios.
- El usuario podrá personalizar sus rubros, categorías y servicios sin modificar el catálogo base ni afectar a otros usuarios.
- El usuario podrá crear, editar y desactivar rubros.
- Un rubro sin relaciones puede eliminarse definitivamente; uno con categorías, servicios u órdenes relacionadas debe desactivarse.
- El usuario podrá crear, editar y eliminar categorías.
- Una categoría sin servicios ni historial puede eliminarse definitivamente; una categoría con relaciones debe desactivarse.
- Al desactivar un rubro o categoría, sus servicios dejan de mostrarse públicamente, pero se conserva el historial.
- El servicio tendrá título obligatorio, descripción opcional y valor aproximado simple en CLP por proyecto.
- El valor es referencial y el precio final queda sujeto a conversación según necesidades y requerimientos.
- Los servicios forman un catálogo global reutilizable.
- Al agregar un servicio a una orden, se guarda una copia del título y valor utilizados en ese momento.

### Perfil público

- Cada usuario tendrá una página pública independiente para mostrar sus servicios.
- La página pública estará desactivada inicialmente.
- Al activarla, los rubros, categorías y servicios existentes quedarán públicos automáticamente; el usuario es responsable de revisarlos antes de activar.
- Antes de activar se mostrará una confirmación indicando que el contenido será visible públicamente.
- La URL se generará automáticamente mediante un código único tipo UUID.
- La URL será inmutable y no podrá modificarse.
- Si el perfil está desactivado, la URL mostrará el mensaje `Perfil temporalmente no disponible`.
- El usuario podrá activar o desactivar el perfil, pero no cambiar la URL.
- El contacto público se configurará explícitamente; no se expondrán automáticamente los datos privados del usuario.
- La página tendrá formulario de solicitud y botones de contacto directo, como email o WhatsApp, según los datos configurados.
- La experiencia será similar a una app de delivery: el primer rubro y la primera categoría aparecerán seleccionados automáticamente.
- El visitante podrá navegar entre rubros, categorías y servicios.
- El visitante podrá seleccionar varios servicios, pero cada servicio solo se agrega una vez.
- Los servicios seleccionados funcionarán como un carrito.
- Se mostrará un `Total referencial`, calculado como la suma de los valores aproximados seleccionados.
- El total no será una cotización definitiva.
- La página pública no mostrará por ahora información sobre SII, boletas de honorarios, contratos o transferencias.

### Solicitudes públicas

- El formulario público solicitará nombre, RUT, teléfono y email como datos obligatorios.
- Empresa y mensaje o descripción de la necesidad serán opcionales.
- La persona externa no podrá crear clientes directamente.
- El envío generará una solicitud pendiente, no un cliente ni una orden automáticamente.
- La solicitud recibirá un código de referencia, por ejemplo `SOL-0001`.
- El código será solo informativo y no permitirá consultar el estado públicamente.
- El visitante recibirá un email de confirmación mediante Resend.
- El usuario recibirá una notificación por email mediante Resend.
- Si el usuario tiene la PWA instalada y autorizó notificaciones, recibirá también una notificación push.
- Las solicitudes tendrán estados `Pendiente`, `En revisión`, `Convertida` y `Rechazada`.
- El usuario tendrá una sección para consultar y gestionar solicitudes.
- Si la combinación `RUT + empresa` ya existe, no se creará un cliente duplicado: la solicitud se vinculará al cliente existente.
- Si no existe, el usuario podrá convertir la solicitud en cliente mediante una acción de un clic.
- El usuario podrá crear una orden de trabajo desde la solicitud.
- La orden se creará con los servicios solicitados y el usuario podrá modificarlos antes de continuar.
- El visitante recibirá un email cuando la solicitud sea aceptada o rechazada.

### Órdenes de trabajo

- Una orden pertenece a un cliente.
- Una orden creada desde una solicitud conservará el vínculo con la solicitud original y el cliente.
- Los estados serán `Pendiente`, `En progreso`, `Completada` y `Cancelada`.
- Las transiciones permitidas son `Pendiente → En progreso → Completada` o `Cancelada`.
- `Cancelada` y `Completada` son estados finales.
- Una orden `Pendiente` o `En progreso` puede editarse según las reglas de la orden.
- Una orden `Completada` o `Cancelada` será de solo lectura.
- La fecha de creación se registra automáticamente.
- La fecha de inicio y la fecha de término son opcionales y pueden registrarse automáticamente al cambiar de estado o manualmente.
- La orden tendrá un campo de notas o descripción.
- Permitirá agregar, editar y eliminar servicios mientras pueda editarse.
- Si no existe un servicio, podrá crearse desde la orden.
- El total se calculará automáticamente sumando los servicios de la orden.
- No habrá un valor manual independiente por orden.

### Dashboard y finanzas

- El dashboard mostrará gráficos de clientes, órdenes e ingresos.
- Tarjetas de clientes: total activos, desactivados y nuevos este mes.
- Tarjetas de órdenes: total, pendientes, en progreso, completadas y canceladas.
- Tarjetas financieras: ingresos confirmados, ingresos esperados y total de servicios.
- Ingresos esperados: suma de órdenes `En progreso`.
- Ingresos confirmados: suma de órdenes `Completadas`.
- Órdenes `Pendientes` y `Canceladas` no se contabilizan como ingresos.
- Por ahora solo se registrarán ingresos; los gastos quedan fuera de alcance.

## HUs pendientes de formalizar

Los requisitos anteriores todavía no son HUs aprobadas. Se dividirán, numerarán y documentarán individualmente en `docs/historias/HU-XXX.md` después de esta validación.

## Convenciones

- Formato de HU: `HU-XXX`, con numeración secuencial global.
- Una HU por archivo en `docs/historias/`.
- Estados: `Pendiente` → `En Revisión` → `Aprobada` → `Implementada`.
- Prioridades: `Alta` / `Media` / `Baja`.
- Flujos UX/UI en `docs/flujos/`, utilizando Mermaid.

## Estado actual

- HUs aprobadas: 0.
- Requisitos capturados: Plataforma, Clientes, Empresas, Rubros, Categorías, Servicios, Perfil Público, Solicitudes, Órdenes de Trabajo y Dashboard.
- Épicas cerradas: 0.
- Flujos documentados: 0.

## Fuera de alcance por ahora

- Información pública sobre SII, boletas de honorarios, contratos o transferencias.
- Registro de gastos.
- Cambiar la URL pública después de generada.
- Consulta pública del estado de una solicitud mediante su código.
- Detalles de implementación, que se definirán en SDD.

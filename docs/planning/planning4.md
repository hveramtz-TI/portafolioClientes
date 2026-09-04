# Planning — Landing Page del Producto

**Fecha:** 2026-09-04
**Estado:** ✅ **Aprobado (2026-09-04)** — P1–P4 resueltos por el usuario
**Objetivo:** Diseñar e implementar la landing page pública del producto en `/`, con estética editorial estilo `Desing/`, que presente la plataforma **TMC (That's My Client)** y convoque al login. Contenido estático: sin APIs, sin datos de usuarios, sin backend.

## Reordenamiento de sprint

- **Planning 3 (Rubros, Categorías y Servicios): pospuesto** para un sprint posterior. Su SDD change `catalog-rubros-categorias-servicios` queda pausada en Slice 1/7 (migraciones + modelos commiteados, **PR #6 open** hacia el tracker `feat/catalog-rubros-categorias-servicios`). Fases 2–7 sin iniciar. No se cierra ni archiva nada.
- Este planning es el sprint actual: **frontend únicamente**.

## Contexto

- Stack: Next.js 16 (App Router) · React 19 · Tailwind CSS 4 · TypeScript · **shadcn/ui**. Backend Laravel 13 no interviene.
- planning1 (Auth + Roles) y planning2 (Clientes y empresas): implementados. planning3: pausado (ver arriba).
- Hoy `/` redirige al login (protección vía `proxy.ts` de Next.js 16).
- Referencia de diseño: `Desing/` es un **snapshot auto-extraído de mastercard.com**, no una guía oficial.
  - `mastercard-DESIGN.md` (prosa): brief de estilo confiable y fuente de verdad del lenguaje visual.
  - `mastercard-tokens.json` / `mastercard-variables.css` / `mastercard-theme.css`: **corruptos por la extracción, NO se importan** (ver Correcciones obligatorias).
  - `Guidelines.md`: subconjunto duplicado de DESIGN.md, sin información extra.

## Guía de estilo curada (extraída de la prosa de DESIGN.md)

### Paleta

| Rol | Valor | Uso |
|-----|-------|-----|
| Canvas | `#F3F0EE` | fondo general crema-piedra, nunca blanco puro |
| Surface | `#FCFBFA` | tarjetas y superficies elevadas (elevación por tinte, **sin sombras**) |
| Ink | `#141413` | texto, footer oscuro, CTA primario |
| Accent | `#CF4500` | signal orange oscuro (acentos, órbitas decorativas) |
| Accent light | `#F37338` | acento claro |
| Accent mid | `#9A3A0A` | arcilla intermedia |
| Link | `#3860BE` | enlaces de texto |
| Neutros | `#F4F4F4` `#E8E2DA` `#D1CDC7` `#696969` `#555555` `#262627` | bordes, texto secundario, charcoal |

### Tipografía

- Familia única: **Inter** vía `next/font` (OFL, segura). Pesos: 400, **450** (variable), 500, 700.
- Escala: h1-hero 64px/64px w500 · h2-section 36px/44px w500 · h3-card 24px/1.5 w500 · h4-subhead 14px w700 · eyebrow 14px w700 **UPPERCASE con punto de acento** · body 16px/1.5 **w450** · nav/button 16px w500 · button-lg 20px w400.

### Forma y layout

- Radios extremados: píldoras `9999px` (cards, botones secundarios), CTA primario `20px`, marcos hero `40px`, imágenes en círculo.
- Espaciado: escala 4–128px; gap de sección 64px; padding de card 24px; gap de elementos 16px; ancho máximo 1200px.
- Sombras: ninguna definida. La elevación se logra con tintes de superficie.

### Componentes firma

1. **Nav flotante en píldora** ("rounded shoulders"), sticky bajo el borde superior del viewport.
2. **Eyebrow labels**: mayúsculas, bold, con punto de acento pequeño.
3. **Ghost watermark headlines**: texto crema-sobre-crema a escala de título, *detrás* de retratos/círculos (decorativo, `aria-hidden` + capa de texto real).
4. **Círculos con satellite CTAs**: imágenes recortadas en círculo con botones blancos solapados; **órbitas naranja** SVG como decoración.
5. **CTA primario negro** (`#141413`, radio 20px). Las píldoras naranjas se reservan para flujos de consentimiento — no las usamos como primario.
6. **Footer oscuro** (`#141413`): headline conversacional grande + grilla de 4 columnas de enlaces (14px/1.5 w450).

## Decisiones confirmadas

| # | Decisión | Opción elegida | Fuente |
|---|----------|----------------|--------|
| D1 | Alcance de la landing | **Landing del producto** en `/`: página pública de marketing que presenta la plataforma con CTA al login. El perfil público dinámico por usuario (épica Perfil público, HU-026+) queda para su épica y no se toca | Conversación 2026-09-04 |
| D2 | Contenido | Estática: sin consumo de APIs, sin datos de usuarios, sin formulario de contacto/solicitud (corresponde a épica Solicitudes) | Conversación 2026-09-04 |
| D3 | Fuente de estilo | Brief curado desde la **prosa de `DESIGN.md`**; los archivos de tokens son material en bruto y **no se importan** | Análisis `Desing/` |
| D4 | Tokens en el stack | Bloque `@theme` de Tailwind 4 **curado a mano** con nombres semánticos, mapeando variables de shadcn/ui: `background→#F3F0EE`, `foreground→#141413`, `card→#FCFBFA`, `primary→ink`, `accent→familia naranja` | Análisis `Desing/` |
| D5 | Fuente | Inter via `next/font` con pesos `400;450;500;700`. **No se busca la tipografía propietaria** del sitio extraído (la captura solo registró Inter como sustituta) | Análisis `Desing/` |
| D6 | Marca y trademark | Identidad **neutral**: no usar el nombre "Mastercard", ni los círculos entrelazados como logo, ni el par rojo/amarillo `#EB001B`/`#F79E1B` en elementos tipo marca. Se adopta solo el lenguaje visual (crema, píldoras, órbitas, editorial) | Análisis de riesgo |
| D7 | Solo light mode | La referencia es light-only; la landing no define tema oscuro | `DESIGN.md` |
| D8 | Nombre visible del producto | **TMC (That's My Client)**: wordmark "TMC" en el nav/footer con bajada "That's My Client". El nombre interno del repo (`portafolioClientes`) no cambia | Respuesta P1 (2026-09-04) |
| D9 | Assets visuales | **Slots placeholder reservados** para imágenes que se proveerán después: cajas con aspect-ratio fijo, fondo neutro del sistema (tinte `#E8E2DA`) y label de ubicación (p. ej. `hero-media`, `portrait-1..n`). Al llegar las imágenes, se reemplaza el placeholder por `next/image` **sin rediseñar la sección**. Sin fotografía externa provista ahora | Respuesta P2 (2026-09-04) |
| D10 | `/` con sesión iniciada | Anónimo: ve la landing. Usuario logueado: mantiene el acceso al dashboard como hoy | Respuesta P3 (2026-09-04) |
| D11 | SEO | Solo `metadata` estática básica de Next.js (title/description de TMC); sin OG dinámico ni analítica este sprint | Respuesta P4 (2026-09-04) |

## Correcciones obligatorias respecto de los tokens extraídos

Registrar para que SDD no los repita:

1. El token del "canvas crema" es un slug de ~700 caracteres cuyo valor es `#EB001B` (rojo): descartar; el canvas real es `#F3F0EE`.
2. `--font-family-2/3/4` no son fuentes, son descripciones: descartar.
3. Letter-spacings inválidos (`44px` en h2, `~20px` en footer-link, `12–14px` con en-dash): descartar o fijar en `0`.
4. La escala de radios de los tokens (`sm 0, md 4, lg 9, xl 6, pill 8`) **contradice la prosa**: usar los valores reales (20/40/9999px).
5. `--leading-*` / `--tracking-*` como variables sueltas y `--spacing-*` por paso no funcionan en Tailwind 4: codificar con `--text-*--line-height` y el multiplicador `--spacing` nativo.
6. Las secciones de componentes de los JSON/CSS son stubs genéricos: la especificación real es la sección "Componentes firma" de arriba.

## Secciones de la landing

1. **Nav flotante** en píldora: wordmark TMC + links de ancla + CTA "Ingresar".
2. **Hero**: eyebrow con punto, h1 64px, subtítulo body w450, CTA primario negro, marco con radio 40px conteniendo **placeholder de imagen** (`hero-media`).
3. **Qué hace la plataforma**: cards en píldora con value props (clientes y empresas, catálogo personalizable, perfil público, solicitudes, órdenes, dashboard) — descripciones de la *promesa del producto*, cuidando de no mostrar como "disponibles" módulos aún no implementados (redacción a validar en spec).
4. **Sección editorial**: ghost watermark headline detrás de composición circular con **placeholder de retrato** (`portrait-1`) y satellite CTA; órbitas naranja como decoración.
5. **CTA final banda oscura** (`#141413`): headline conversacional + botón.
6. **Footer oscuro** 4 columnas con wordmark TMC.

Convención de placeholders (D9): cada slot define aspect-ratio, nombre de ubicación y fondo del sistema; se documentan en la spec para que reemplazarlos por imágenes reales sea un cambio de contenido, no de layout.

## Pendientes resueltos (2026-09-04)

| # | Pendiente | Resolución |
|---|-----------|------------|
| P1 | Nombre visible del producto | **TMC (That's My Client)** → D8 |
| P2 | Assets visuales | Slots placeholder para imágenes futuras → D9 |
| P3 | `/` con sesión iniciada | Confirmado: anónimo landing / logueado dashboard → D10 |
| P4 | SEO | Confirmado: metadata estática básica → D11 |

## Fuera de alcance (YAGNI)

- Perfil público por usuario (HU-026+), solicitudes (HU-031+), i18n, dark mode, blog/CMS, analítica de marketing, librerías de animación, cualquier cambio de backend o de base de datos.
- **Las imágenes finales**: proveer el material fotográfico real queda fuera de este sprint (solo se reservan los slots, D9).

## Verificación

- Frontend: `cd frontend && npm run lint` · `npm test` (Jest por componente/sección) · `npm run build`.
- Visual manual: 1440px y 360px.
- Accesibilidad: contraste AA para texto (ojo: `#696969` sobre `#F3F0EE` es limítrofe — usar para texto grande o endurecer); ghost headlines `aria-hidden` con texto real disponible; placeholders con `alt`/label descriptivo.
- CodeGraph: `codegraph sync && codegraph status`.

## Propuestas a definir en SDD-design (no decisiones)

- Estructura del módulo: `frontend/src/modules/landing/` (feature-first) vs. componentes directos en `app/(public)/`.
- Ubicación del `@theme` curado: extender `globals.css` vs. archivo dedicado.
- Breakpoints concretos del nav flotante y la grilla editorial.
- Tratamiento de motion: transiciones CSS simples vs. respetar reduced-motion con animación cero.

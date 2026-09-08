# TMC (That's My Client) — Referencia de Estilo e Identidad Visual

**Fecha:** 2026-09-07
**Estado:** ✅ Curada — reemplaza la extracción cruda de `Desing/mastercard-DESIGN.md` como referencia de diseño del proyecto
**Fuentes:** `docs/vision.md` (identidad), `docs/planning/planning4.md` (guía curada de la landing, decisiones D3–D9), `Desing/mastercard-DESIGN.md` (referencia de lenguaje visual), `frontend/src/app/globals.css` (tokens reales implementados)
**Uso:** Toda interfaz o documento de TMC (landing, dashboard, PWA, **PDF de portafolio — planning5**) se diseña contra este documento.

---

## 1. Identidad: qué comunica el diseño

TMC es la plataforma donde **la cartera de clientes de un freelancer se convierte en su portafolio profesional** (visión 2026-09-07). El diseño debe hacer visible esa promesa:

| Principio de la visión | Traducción visual |
|---|---|
| **Privacidad primero** | Nada exhibicionista: el lenguaje es discreto y editorial, nunca "fintech ruidoso". En el PDF, lo sensible se omite por diseño, no por color. |
| **Honestidad degradada** | Tinta sólida, superficies mates, contraste franco. Sin gradientes decorativos ni sombras duras: la elevación se logra con **tintes**, igual que el trabajo real se muestra sin inflar. |
| **El dato rinde dos veces** | Un solo sistema visual sirve a la web (captación) y al PDF (evidencia): misma paleta, misma tipografía, mismas formas. |
| **KISS / YAGNI** | Una familia tipográfica, ~8 colores semánticos, dos radios firma. La plantilla PDF es **una** (planning5 D5). |

**Personalidad:** editorial cálida — la de un portafolio impreso de alto nivel, no la de un SaaS genérico. Header conversacionales, mucho aire, señal de acento quirúrgica (nunca en bloque).

**Voz de UI:** en español neutro profesional para producto; directa y en segunda persona ("Genera tu portafolio"). El nombre visible es **TMC** con bajada **That's My Client**; nunca `portafolioClientes` (nombre de repo).

## 2. Paleta (tokens reales en `globals.css`)

| Rol | Valor | Token | Uso |
|-----|-------|-------|-----|
| Canvas | `#F3F0EE` | `--color-tmc-canvas` | fondo general crema-piedra; **nunca blanco puro** |
| Surface | `#FCFBFA` | `--color-tmc-surface` | tarjetas y superficies elevadas (elevación por tinte, sin sombras) |
| Ink | `#141413` | `--color-tmc-ink` | texto, footer oscuro, CTA primario, banda oscura |
| Accent | `#CF4500` | `--color-tmc-accent` | signal orange oscuro: acentos, órbitas decorativas, punto del eyebrow |
| Accent light | `#F37338` | `--color-tmc-accent-light` | acento claro (hover/estados) |
| Accent mid | `#9A3A0A` | `--color-tmc-accent-mid` | arcilla intermedia |
| Link | `#3860BE` | `--color-tmc-link` | enlaces de texto |
| Neutral 1 | `#F4F4F4` | `--color-tmc-neutral-1` | superficies secundarias y estados suaves |
| Neutral 2 | `#E8E2DA` | `--color-tmc-neutral-2` | separación de superficies y accent semántico |
| Neutral 3 | `#D1CDC7` | `--color-tmc-neutral-3` | bordes, divisores e inputs |
| Neutral 4 | `#696969` | `--color-tmc-neutral-4` | texto secundario |
| Neutral 5 | `#555555` | `--color-tmc-neutral-5` | texto auxiliar semántico |
| Neutral 6 | `#262627` | `--color-tmc-neutral-6` | texto secundario fuerte |

**Prohibido (D6 planning4 — riesgo de trademark):** el nombre "Mastercard", los círculos entrelazados como logo, y el par rojo/amarillo `#EB001B` / `#F79E1B`. El orange de consentimientos de la referencia original **no se adopta** como color de marca.

**Tema:** solo light (D7 planning4).

### Dos capas de tokens

Los primitivos `tmc-*` son internos y expresan la identidad TMC. Los tokens semánticos de shadcn son el contrato público que consumen los componentes y traducen esa identidad a roles funcionales.

| Token semántico | Mapeo TMC light |
|---|---|
| `background` / `foreground` | `tmc-canvas` / `tmc-ink` |
| `card` / `popover` | `tmc-surface` |
| `card-foreground` / `popover-foreground` | `tmc-ink` |
| `primary` / `primary-foreground` | `tmc-ink` / `tmc-surface` |
| `secondary` / `muted` | `tmc-neutral-1` |
| `secondary-foreground` / `accent-foreground` | `tmc-ink` |
| `muted-foreground` | `tmc-neutral-5` |
| `accent` | `tmc-neutral-2` |
| `border` / `input` | `tmc-neutral-3` |
| `ring` | `tmc-accent` |

`destructive` y `destructive-foreground` son tokens de peligro independientes: no usan el accent TMC ni los colores rojo/amarillo de Mastercard.

## 3. Tipografía

La landing usa **Inter** vía `next/font` (pesos `400; 450; 500; 700`), la sustituta segura de la referencia. El dashboard y el layout raíz usan Geist hasta una migración global deliberada. No se busca la tipografía propietaria del sitio extraído (D5 planning4).

> **Implementación verificada:** el layout público expone `--font-inter` mediante `next/font` y aplica Inter explícitamente. El dashboard y el layout raíz mantienen Geist mediante el binding global de `--font-sans`; una migración global requiere una decisión separada.

### Escala

| Rol | Tamaño / Interlineado | Peso | Notas |
|---|---|---|---|
| h1-hero | 64px / 64px | 500 | |
| h2-section | 36px / 44px | 500 | letter-spacing `0` |
| h3-card | 24px / 1.5 | 500 | |
| h4-subhead | 14px / 1.5 | 700 | |
| eyebrow | 14px / 14px | 700 | UPPERCASE + punto de acento (`#CF4500`) |
| body | 16px / 1.5 | **450** | el peso firma |
| nav / button | 16px / 16px | 500 | |
| button-lg | 20px / 1.5 | 400 | |

Letter-spacing: `0` en todos los roles (los valores `44px` / `~20px` / `12–14px` de la extracción eran artefactos corruptos — ver §8).

## 4. Forma y layout

- **Radios firma:** `radius-tmc-pill` (`9999px`) para cards editoriales/marketing, nav flotante, botones secundarios y superficies compactas · `radius-tmc-cta` (`20px`) para el CTA primario · `radius-tmc-frame` (`40px`) para marcos hero · imágenes recortadas en **círculo**. Las tablas y formularios densos del dashboard usan el radio semántico moderado, no pill por defecto.
- **Sombras: ninguna.** Elevación solo por tinte de superficie.
- **Espaciado:** tokens reales `spacing-tmc-1/2/4/6/8/12/16/24/32` con valores 4/8/16/24/32/48/64/96/128px · gap de sección 64px · padding de card 24px · gap de elementos 16px · ancho máximo 1200px.

## 5. Componentes firma

1. **Nav flotante en píldora** ("rounded shoulders"), sticky bajo el borde superior del viewport.
2. **Eyebrow labels:** mayúsculas bold con punto de acento pequeño (extracto de sección).
3. **Ghost watermark headlines:** texto crema-sobre-crema a escala de título *detrás* de retratos/círculos; decorativo con `aria-hidden` + capa de texto real accesible.
4. **Círculos con satellite CTAs:** imágenes recortadas en círculo con botones blancos solapados; **órbitas naranja** SVG como decoración.
5. **CTA primario negro** (`#141413`, radio 20px). Las píldoras naranjas se reservan para señales, no como primario.
6. **Footer oscuro** (`#141413`): headline conversacional grande + grilla de 4 columnas de enlaces (14px/1.5 w450).

## 6. Aplicación: Plantilla TMC del Portafolio PDF (planning5)

La plantilla del documento de portafolio (HU-056, D5/R12 planning5) usa este mismo sistema, en formato A4:

- **Encabezado:** wordmark TMC + bajada "That's My Client" sobre canvas `#F3F0EE`; una órbita naranja como único elemento decorativo, discreta.
- **Proyectos:** tarjetas surface `#FCFBFA` en píldora, una por orden elegible. Línea de proyecto con el patrón anonimizado: *Proyecto {tipo} de una empresa destacada en el rubro {rubro}* (HU-058). **Nunca** nombre de cliente/empresa, RUT ni contacto.
- **Servicios:** listados nominados desde el snapshot (`order_services`), con su valor **CLP redondeado a la centena superior** (HU-059/HU-060).
- **Estado general por proyecto:** pill de estado — `Completada` en ink `#141413`, `En progreso` en accent `#CF4500`. Los estados no elegibles simplemente no existen en el documento.
- **Tipografía y forma:** misma escala Inter (§3) y radios (§4); sin sombras; jerarquía editorial tipo footer oscuro en la portada.
- Sin marca personal del freelancer ni variantes de plantilla (YAGNI planning5).

## 7. Do's & Don'ts

**Do**
- Anclar todas las superficies al canvas crema `#F3F0EE`; nada de blancos puros.
- Reservar el orange solo para señales (punto del eyebrow, órbitas, estado "En progreso"); el primario es ink.
- Lograr elevación con tintes de superficie.
- Mantener body en w450: es el peso que distingue el sistema.
- En el PDF: ante la duda sobre qué mostrar, la regla es la visión — **privacidad primero**.

**Don't**
- Introducir colores fuera del token set, en particular `#EB001B` / `#F79E1B` (marca de terceros).
- Usar sombras, esquinas rectas dominantes o gradientes decorativos.
- Renderizar texto pequeño en `#F37338` sobre `#F3F0EE` (contraste insuficiente).
- Mostrar datos de clientes reales, montos sin redondear, u órdenes Pendiente/Cancelada/rechazadas en el PDF.
- Tratar la extracción de `Desing/` como más reciente o más verdadera que este documento.

## 8. Correcciones aplicadas respecto de la extracción original

Heredadas de planning4 ("Correcciones obligatorias") — ya resueltas en este documento:

1. El token "canvas crema" corrupto (slug de ~700 caracteres con valor `#EB001B`): descartado; el canvas real es `#F3F0EE`.
2. `--font-family-2/3/4` no son fuentes, son descripciones de componentes: descartados.
3. Letter-spacings inválidos (`44px`, `~20px`, `12–14px`): fijados en `0`.
4. La escala de radios de los tokens (`sm 0, md 4, lg 9, xl 6, pill 8`) contradecía la prosa: usar los valores reales (20 / 40 / 9999px).
5. Variables sueltas `--leading-*` / `--tracking-*` / `--spacing-*` incompatibles con Tailwind 4: codificadas con `--text-*--line-height` y el multiplicador `--spacing` nativo.
6. Los stubs de componentes de los JSON/CSS no especifican nada: la especificación real es §5 y §6 de este documento.

7. Los nombres de neutros, el radio `radius-tmc-frame` y la escala real de spacing se documentan igual que la implementación de `globals.css`.

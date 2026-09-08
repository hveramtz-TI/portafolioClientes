# TMC (That's My Client) — Design System & Visual Identity

**Fecha:** 2026-09-07  
**Estado:** Propuesta consolidada — identidad TMC + lenguaje visual editorial inspirado en la referencia analizada de Mastercard, sin reproducir su identidad de marca.  
**Propósito:** Este documento define cómo debe verse, sentirse y comportarse visualmente TMC en la landing, dashboard, PWA y PDF de portafolio.

---

## 1. Esencia de la marca

### 1.1 Qué es TMC

TMC es la plataforma donde **la cartera de clientes de un freelancer se convierte en su portafolio profesional**.

La interfaz no debe comunicar simplemente "gestión de clientes". Debe comunicar la transformación:

> **Trabajo realizado → información organizada → evidencia profesional → portafolio.**

El diseño, por tanto, debe sentirse como una herramienta profesional que convierte información privada y dispersa en una presentación clara, elegante y confiable.

### 1.2 Misión visual

La misión visual de TMC es **hacer que el trabajo profesional del freelancer pueda presentarse con claridad, credibilidad y privacidad, sin exigirle construir manualmente un portafolio desde cero**.

Cada decisión visual debe ayudar a transformar datos internos en una experiencia profesional.

### 1.3 Visión

TMC aspira a que **la cartera de clientes deje de ser solamente un registro privado y se convierta en un activo profesional reutilizable**.

El sistema visual debe hacer visible esta idea:

- La información se organiza.
- El trabajo adquiere contexto.
- Los datos pueden convertirse en evidencia.
- La evidencia puede convertirse en portafolio.
- La privacidad permanece protegida.

### 1.4 Principios de producto

| Principio | Implicación de diseño |
|---|---|
| **Privacidad primero** | La interfaz debe ser discreta. Nunca utilizar información sensible como recurso decorativo. |
| **Honestidad** | El diseño debe mostrar el trabajo sin inflarlo. Evitar efectos visuales que intenten hacer parecer más importante algo de lo que es. |
| **El dato rinde dos veces** | La misma identidad visual debe funcionar para gestión interna y para presentación externa. |
| **KISS** | Pocos elementos visuales, jerarquía clara y composición sencilla. |
| **YAGNI** | No añadir efectos, componentes o variantes que no aporten al flujo principal. |
| **Profesional sin ser corporativo** | TMC debe sentirse profesional, pero cercano y humano. |
| **Editorial antes que SaaS genérico** | La experiencia debe recordar a un portafolio profesional cuidadosamente diseñado, no a un panel administrativo estándar. |

---

# 2. Personalidad de TMC

La identidad de TMC se define como:

## Editorial · Cálida · Profesional · Discreta · Humana

### Editorial

Mucho espacio negativo, titulares grandes, composición asimétrica controlada y superficies limpias.

### Cálida

El fondo no es blanco puro. La base crema-piedra evita la sensación clínica de una aplicación empresarial.

### Profesional

La tipografía, el ritmo y la jerarquía deben transmitir precisión.

### Discreta

La plataforma trabaja con información de clientes. La interfaz nunca debe sentirse exhibicionista.

### Humana

La comunicación debe hablar directamente al freelancer y evitar lenguaje corporativo innecesario.

---

# 3. Idea visual central

## "Un portafolio construido a partir de tu trabajo real."

La identidad se construye alrededor de cuatro gestos visuales:

1. **Superficies suaves**
2. **Formas redondeadas**
3. **Tipografía editorial de gran escala**
4. **Señales naranjas precisas**

La referencia visual analizada utiliza una estética basada en un canvas crema, radios extremadamente grandes, círculos, órbitas y CTAs oscuros. TMC adopta ese **lenguaje geométrico y editorial**, pero lo convierte en una identidad propia centrada en clientes, proyectos y evidencia profesional.

La inspiración no debe convertirse en imitación.

**TMC no utiliza:**

- el nombre Mastercard;
- su logotipo;
- círculos entrelazados;
- el par rojo/amarillo asociado a Mastercard;
- composiciones que puedan confundirse con su identidad.

---

# 4. Lenguaje visual

## 4.1 Canvas

El canvas principal es:

`#F3F0EE`

Debe sentirse como papel cálido o piedra clara.

**Regla:** nunca utilizar blanco puro como fondo principal.

El blanco puede aparecer únicamente cuando tenga una función específica dentro de una superficie o componente.

---

## 4.2 Superficies

Superficie elevada:

`#FCFBFA`

Las superficies se diferencian del canvas principalmente mediante **tinte**, no mediante sombras.

Esto refuerza el principio de honestidad:

> La jerarquía visual aparece por composición y contraste, no por efectos artificiales.

---

## 4.3 Tinta

Color principal:

`#141413`

Uso:

- texto principal;
- titulares;
- CTA primario;
- footer;
- bloques de alto contraste;
- estados importantes.

El negro de TMC debe sentirse como **tinta**, no como negro digital agresivo.

---

## 4.4 Acento

Color de señal:

`#CF4500`

El naranja no debe dominar la interfaz.

Debe funcionar como una **señal visual quirúrgica**:

- punto de eyebrow;
- indicadores;
- órbitas;
- estados;
- pequeños elementos de navegación;
- detalles decorativos;
- llamadas visuales.

### Regla fundamental

**El naranja señala; la tinta decide.**

El CTA principal no debe ser naranja. El CTA principal utiliza `#141413`.

---

# 5. Paleta oficial

| Rol | Valor | Uso |
|---|---|---|
| Canvas | `#F3F0EE` | Fondo principal |
| Surface | `#FCFBFA` | Cards y superficies |
| Ink | `#141413` | Texto, CTA principal, footer |
| Accent | `#CF4500` | Señales y decoración |
| Accent Light | `#F37338` | Hover y estados donde exista suficiente contraste |
| Accent Mid | `#9A3A0A` | Variación tonal |
| Link | `#3860BE` | Enlaces de texto |
| Neutral 1 | `#F4F4F4` | Superficies secundarias y estados suaves |
| Neutral 2 | `#E8E2DA` | Separación de superficies y accent semántico |
| Neutral 3 | `#D1CDC7` | Bordes, divisores e inputs |
| Neutral 4 | `#696969` | Texto secundario |
| Neutral 5 | `#555555` | Texto auxiliar semántico |
| Neutral 6 | `#262627` | Texto secundario fuerte |

### Colores prohibidos como identidad de TMC

`#EB001B` y `#F79E1B` no forman parte de la identidad de TMC.

No utilizar el rojo/amarillo característico de Mastercard como combinación de marca.

### 5.1 Dos capas de tokens

Los tokens primitivos `tmc-*` son la capa interna de identidad: exponen valores de color, radios, spacing y escala tipográfica para construir la experiencia TMC. Los tokens semánticos de shadcn (`background`, `primary`, `border`, etc.) son el contrato público que consumen los componentes. Los componentes deben depender de la capa semántica; solo una decisión explícita de identidad debe usar un primitivo TMC directamente.

| Token semántico | Mapeo TMC light |
|---|---|
| `background` | `tmc-canvas` |
| `foreground` | `tmc-ink` |
| `card`, `popover` | `tmc-surface` |
| `card-foreground`, `popover-foreground` | `tmc-ink` |
| `primary` | `tmc-ink` |
| `primary-foreground` | `tmc-surface` |
| `secondary`, `muted` | `tmc-neutral-1` |
| `secondary-foreground`, `accent-foreground` | `tmc-ink` |
| `muted-foreground` | `tmc-neutral-5` |
| `accent` | `tmc-neutral-2` |
| `border`, `input` | `tmc-neutral-3` |
| `ring` | `tmc-accent` |

`destructive` y `destructive-foreground` son tokens semánticos de peligro independientes. No se mapean al accent TMC ni a los colores rojo/amarillo de Mastercard.

---

# 6. Tipografía

## Familia

**Inter**

Uso mediante `next/font` en el layout público de la landing. El dashboard y el layout raíz continúan usando Geist; una migración global de fuente requiere una decisión separada.

Pesos utilizados:

- 400 — regular;
- 450 — body;
- 500 — títulos y navegación;
- 700 — labels y énfasis.

En la landing, la familia es única para mantener consistencia y cumplir KISS. El dashboard conserva Geist hasta que exista una decisión de migración global.

---

## Escala tipográfica

| Rol | Tamaño | Line height | Peso |
|---|---:|---:|---:|
| Hero H1 | 64px | 64px | 500 |
| Section H2 | 36px | 44px | 500 |
| Card H3 | 24px | 1.5 | 500 |
| Subhead H4 | 14px | 1.5 | 700 |
| Eyebrow | 14px | 14px | 700 |
| Body | 16px | 1.5 | 450 |
| Navigation | 16px | 16px | 500 |
| Button Large | 20px | 1.5 | 400 |

### Letter spacing

Usar `0` como regla general.

Los valores extremos encontrados en la extracción original se consideran artefactos y no deben convertirse en tokens de diseño.

---

# 7. Geometría de TMC

La geometría es una de las principales firmas de la identidad.

## 7.1 Radios

### Pill

`9999px`

Uso:

- cards editoriales o de marketing;
- navegación flotante;
- botones secundarios;
- chips y superficies compactas.

En tablas y formularios densos del dashboard se utiliza el radio semántico moderado. El radio `9999px` no es el valor predeterminado para toda la interfaz.

### CTA primario

`20px`

Uso:

- botones principales;
- acciones de conversión.

### Hero frame

`40px`

Uso:

- marcos de hero;
- bloques visuales destacados;
- contenedores principales.

### Imagen circular

`50%`

Uso:

- fotografías;
- avatares;
- elementos visuales de proyectos cuando corresponda.

---

## 7.2 Regla de forma

TMC debe favorecer:

**píldoras → estadios → círculos**

sobre:

**rectángulos → cuadrados → esquinas rígidas**

Las esquinas rectas no están prohibidas funcionalmente, pero no deben dominar la identidad.

---

# 8. Elevación

## Sin sombras como lenguaje principal

TMC no utiliza sombras decorativas fuertes.

La jerarquía se obtiene mediante:

1. color;
2. superficie;
3. espacio;
4. tamaño;
5. contraste;
6. forma.

Ejemplo:

```text
Canvas #F3F0EE
    ↓
Surface #FCFBFA
    ↓
Ink #141413
```

La diferencia entre canvas y surface debe ser suficiente para separar elementos sin recurrir a sombras.

---

# 9. Sistema de espaciado

Escala implementada:

`4 / 8 / 16 / 24 / 32 / 48 / 64 / 96 / 128`

### Tokens principales

| Token | Valor |
|---|---:|
| `spacing-tmc-1` | 4px |
| `spacing-tmc-2` | 8px |
| `spacing-tmc-4` | 16px |
| `spacing-tmc-6` | 24px |
| `spacing-tmc-8` | 32px |
| `spacing-tmc-12` | 48px |
| `spacing-tmc-16` | 64px |
| `spacing-tmc-24` | 96px |
| `spacing-tmc-32` | 128px |

### Ritmo

- Gap entre elementos: **16px**
- Padding de card: **24px**
- Gap entre secciones: **64px**
- Contenido máximo: **1200px**

El espacio negativo es parte de la identidad, no espacio desperdiciado.

---

# 10. Componentes de identidad

## 10.1 Navigation

La navegación debe utilizar una **píldora flotante**.

Características:

- forma pill;
- fondo claro;
- separación visual del canvas;
- navegación compacta;
- posición sticky;
- radios grandes;
- no utilizar sombras duras.

La navegación debe sentirse como una pieza editorial flotando sobre la página.

---

## 10.2 Eyebrow

Formato:

```text
• CLIENTES
```

Características:

- uppercase;
- 14px;
- peso 700;
- punto pequeño naranja;
- letter-spacing 0;
- función de categoría.

El eyebrow funciona como una etiqueta editorial, no como un título.

---

## 10.3 Hero

El Hero es el lugar donde TMC debe comunicar la transformación.

Estructura recomendada:

```text
EYEBROW

Titular grande
que explique la
transformación.

Texto breve de apoyo.

[ CTA principal ]

        ○
    elemento visual
```

El titular debe ser grande, pero la composición debe conservar aire.

### Regla

El Hero debe responder rápidamente:

**¿Qué hace TMC por mí?**

No debe comenzar explicando funcionalidades técnicas.

---

# 11. Ghost Headlines

Los titulares fantasma son un recurso decorativo.

Características:

- escala grande;
- color muy cercano al canvas;
- detrás de imágenes o círculos;
- baja prominencia visual;
- nunca deben competir con el contenido principal.

Cuando exista contenido accesible equivalente, el texto decorativo debe marcarse como `aria-hidden`.

---

# 12. Sistema orbital

Las órbitas son uno de los recursos gráficos distintivos.

Una órbita representa:

> **Información que conecta trabajo, cliente, proyecto y evidencia.**

Debe utilizarse como SVG fino y discreto.

### Reglas

- utilizar `#CF4500`;
- evitar saturar la pantalla;
- no convertir la órbita en un logo;
- no reproducir círculos entrelazados de marcas externas;
- usarla como elemento de movimiento/composición;
- preferir curvas simples.

---

# 13. Imágenes y círculos

Cuando se utilicen imágenes:

- priorizar recortes circulares;
- utilizar composiciones con mucho espacio alrededor;
- evitar grids fotográficos genéricos;
- utilizar botones o pequeñas acciones superpuestas solo cuando aporten funcionalidad.

La imagen debe sentirse como parte de una composición editorial, no como una tarjeta de stock.

---

# 14. Buttons

## Primary

```text
Background: #141413
Text: #FFFFFF
Radius: 20px
```

Uso:

- acciones principales;
- crear;
- generar;
- exportar;
- continuar.

Debe existir un solo CTA dominante por sección cuando sea posible.

---

## Secondary

```text
Background: #FCFBFA
Text: #141413
Radius: 9999px for editorial/marketing and compact surfaces; semantic moderate radius for dense dashboard controls
```

Uso:

- acciones alternativas;
- navegación secundaria;
- filtros;
- acciones menos importantes.

---

## Accent

El naranja no debe convertirse en el botón principal de toda la aplicación.

Puede utilizarse para:

- estados;
- indicadores;
- pequeñas acciones contextuales;
- elementos seleccionados.

---

# 15. Cards

Las cards representan información que el usuario puede organizar, revisar o convertir en evidencia.

### Forma

`border-radius: 9999px` for editorial/marketing cards and compact surfaces. Dense dashboard tables and forms use the semantic moderate radius instead of the pill radius by default.

### Superficie

`#FCFBFA`

### Padding

`24px`

### Sombras

Ninguna como regla.

### Contenido

Una card debe tener:

1. categoría o eyebrow;
2. título;
3. información esencial;
4. acción contextual.

Evitar cards con demasiados elementos.

---

# 16. Dashboard / PWA

El dashboard debe conservar la identidad editorial sin perder eficiencia operativa.

La interfaz interna debe sentirse como:

**una herramienta profesional con lenguaje de portafolio.**

No debe convertirse en una landing decorativa.

### Prioridades

1. información;
2. acciones;
3. estado;
4. navegación;
5. decoración.

En el dashboard, la funcionalidad siempre gana a la decoración.

---

# 17. Clientes

El módulo de clientes debe transmitir orden y confianza.

Una entrada de cliente debe priorizar:

- identificación;
- relación profesional;
- proyectos;
- servicios;
- estado;
- acciones.

Los datos sensibles no deben utilizarse como elementos visuales destacados.

---

# 18. Proyectos

Los proyectos son el puente entre la cartera y el portafolio.

Visualmente deben ser más importantes que los registros administrativos.

Un proyecto debe permitir entender:

```text
Cliente
   ↓
Proyecto
   ↓
Servicios realizados
   ↓
Resultado / evidencia
   ↓
Portafolio
```

La interfaz debe hacer visible esta transformación.

---

# 19. Portafolio PDF

El PDF es la expresión externa de TMC.

Debe compartir exactamente la misma identidad:

- canvas crema;
- Inter;
- tinta;
- orange de señal;
- radios grandes;
- lenguaje editorial;
- ausencia de sombras.

### Encabezado

Debe incluir:

**TMC**  
*That's My Client*

con una órbita naranja discreta.

### Proyectos

Utilizar tarjetas de superficie.

Los proyectos se presentan de manera anonimizada:

> **Proyecto {tipo} de una empresa destacada en el rubro {rubro}**

Nunca incluir:

- nombre real del cliente;
- nombre de empresa;
- RUT;
- información de contacto.

### Servicios

Utilizar los servicios registrados en el snapshot.

Los valores deben mostrarse en CLP y redondeados a la centena superior.

### Estados

`Completada`

- tinta `#141413`

`En progreso`

- accent `#CF4500`

Los estados no elegibles no aparecen.

### Regla principal del PDF

**La privacidad es una decisión de diseño.**

No se debe depender únicamente del color para ocultar información sensible.

---

# 20. Footer

El footer utiliza:

`#141413`

Debe funcionar como cierre editorial de la experiencia.

Características:

- headline conversacional grande;
- texto claro;
- estructura de enlaces organizada;
- cuatro columnas cuando el espacio lo permita;
- enlaces en tamaño 14px;
- alto contraste.

El footer debe sentirse como el cierre de una publicación profesional.

---

# 21. Responsive

La identidad debe conservarse en todos los tamaños.

## Desktop

- Hero de gran escala;
- navegación flotante;
- composiciones circulares;
- espacios generosos;
- máximo 1200px.

## Tablet

- reducir escala tipográfica;
- conservar radios;
- reducir elementos decorativos;
- mantener jerarquía.

## Mobile

- Hero compacto;
- navegación simplificada;
- cards completamente adaptables;
- órbitas reducidas;
- menor cantidad de decoración;
- CTA fácilmente accesible.

### Regla

**Responsive no significa eliminar la identidad.**

Debe mantenerse:

- canvas crema;
- tinta;
- orange de señal;
- radios grandes;
- Inter;
- aire.

---

# 22. Accesibilidad visual

La identidad debe mantener contraste suficiente.

Especialmente:

- no utilizar `#F37338` como texto pequeño sobre `#F3F0EE`;
- no depender exclusivamente del color para estados;
- los elementos decorativos deben ser distinguibles del contenido real;
- ghost headlines deben ser decorativos;
- el contenido principal debe conservar contraste claro.

---

# 23. Do's

### Sí

- usar canvas `#F3F0EE`;
- usar surfaces `#FCFBFA`;
- usar tinta `#141413`;
- reservar naranja para señales;
- utilizar radios grandes;
- utilizar círculos como recurso compositivo;
- usar Inter;
- utilizar mucho espacio negativo;
- diseñar con jerarquía editorial;
- mantener privacidad como principio visual;
- usar una única identidad entre web, PWA y PDF;
- preferir composición sobre decoración.

---

# 24. Don'ts

### No

- copiar la identidad visual de Mastercard;
- utilizar su logo;
- utilizar círculos entrelazados como marca;
- utilizar rojo `#EB001B` + amarillo `#F79E1B` como identidad;
- utilizar gradientes decorativos;
- llenar la interfaz de naranja;
- utilizar sombras fuertes;
- utilizar esquinas cuadradas como lenguaje dominante;
- convertir el dashboard en una landing;
- mostrar información sensible de clientes;
- crear múltiples variantes visuales sin necesidad;
- agregar animaciones solo por decoración;
- utilizar tipografías adicionales sin una razón de producto.

---

# 25. Jerarquía de decisión

Cuando exista una duda de diseño, aplicar este orden:

```text
1. Privacidad
      ↓
2. Claridad
      ↓
3. Funcionalidad
      ↓
4. Jerarquía
      ↓
5. Identidad
      ↓
6. Decoración
```

La decoración nunca debe ganar sobre la claridad.

---

# 26. Design Tokens

```css
:root {
  --color-tmc-canvas: #F3F0EE;
  --color-tmc-surface: #FCFBFA;

  --color-tmc-ink: #141413;

  --color-tmc-accent: #CF4500;
  --color-tmc-accent-light: #F37338;
  --color-tmc-accent-mid: #9A3A0A;

  --color-tmc-link: #3860BE;

  --color-tmc-neutral-1: #F4F4F4;
  --color-tmc-neutral-2: #E8E2DA;
  --color-tmc-neutral-3: #D1CDC7;
  --color-tmc-neutral-4: #696969;
  --color-tmc-neutral-5: #555555;
  --color-tmc-neutral-6: #262627;

  --radius-tmc-cta: 20px;
  --radius-tmc-frame: 40px;
  --radius-tmc-pill: 9999px;

  --spacing-tmc-1: 4px;
  --spacing-tmc-2: 8px;
  --spacing-tmc-4: 16px;
  --spacing-tmc-6: 24px;
  --spacing-tmc-8: 32px;
  --spacing-tmc-12: 48px;
  --spacing-tmc-16: 64px;
  --spacing-tmc-24: 96px;
  --spacing-tmc-32: 128px;
}
```

---

# 27. Tailwind 4 / implementación

TMC utiliza Tailwind 4.

La familia Inter debe cargarse mediante `next/font` en el layout público. El dashboard y el layout raíz continúan usando Geist hasta una migración global deliberada; no se agrega una fuente nueva en este cambio.

En Tailwind 4, `@theme inline` conserva el binding global de Geist:

```css
--font-sans: var(--font-geist-sans);
```

para que `font-sans` sea seguro en el dashboard y en la aplicación raíz. La landing aplica Inter explícitamente mediante `var(--font-inter)` en su layout público, donde esa variable sí está disponible.

La capa semántica se mantiene como contrato público para los componentes. Los primitivos TMC se consumen directamente solo en piezas de identidad que lo requieran.

Cuando sea necesario garantizar Inter fuera de ese alcance, utilizar directamente:

```css
font-family: var(--font-inter);
```

en el punto de aplicación correspondiente.

Los tokens de spacing deben utilizar el sistema compatible con Tailwind 4 y su multiplicador `--spacing`.

---

# 28. Arquitectura de identidad

La identidad TMC puede resumirse en esta fórmula:

```text
                 TMC
                  │
          That's My Client
                  │
        ┌─────────┴─────────┐
        │                   │
    CARTE​RA             PORTAFOLIO
        │                   │
        └─────── DATOS ─────┘
                  │
              EVIDENCIA
                  │
             PROFESIONAL
```

Visualmente:

```text
CREMA
  +
TINTA
  +
ORANGE DE SEÑAL
  +
INTER
  +
RADIOS GRANDES
  +
CÍRCULOS
  +
ÓRBITAS
  +
ESPACIO NEGATIVO
  =
IDENTIDAD TMC
```

---

# 29. Principio final

TMC no debe intentar parecer una gran corporación financiera.

Debe parecer **el portafolio profesional que un freelancer siempre quiso tener, construido automáticamente a partir del trabajo que ya realizó**.

La referencia visual aporta el lenguaje:

**calidez + editorial + geometría + formas suaves + contraste + aire.**

La misión y visión de TMC aportan el significado:

**privacidad + honestidad + transformación de datos + evidencia profesional.**

El resultado debe ser una identidad propia:

> **TMC convierte tu trabajo real en una presentación profesional, sin exagerarlo y sin exponer lo que debe permanecer privado.**

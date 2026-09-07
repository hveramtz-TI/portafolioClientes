# Flujo: Generación del Portafolio PDF

**Épica:** Documento de Portafolio (PDF)
**HU principal:** HU-056
**HUs relacionadas:** HU-057, HU-058, HU-059, HU-060
**Fecha:** 2026-09-07

---

## Propósito

Capturar el flujo de extremo a extremo: el usuario autenticado genera su portafolio PDF con plantilla TMC, construido automáticamente desde sus órdenes, con anonimización de clientes y montos degradados, y lo descarga para adjuntarlo a su CV.

## Diagrama Mermaid

```mermaid
flowchart TD
    classDef start fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px
    classDef process fill:#e3f2fd,stroke:#1565c0,stroke-width:2px
    classDef decision fill:#fff3e0,stroke:#ef6c00,stroke-width:2px
    classDef ui fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px,stroke-dasharray: 5 5
    classDef privacy fill:#fce4ec,stroke:#c2185b,stroke-width:2px
    classDef endn fill:#e0f2f1,stroke:#00695c,stroke-width:2px

    Start([Usuario autenticado<br/>Quiere su portafolio PDF]):::start

    Start --> Click[UI: Acción "Generar mi portafolio"<br/>→ POST solicitud de generación<br/>(HOW server/client se define en SDD)]:::ui

    Click --> Query[Query: Órdenes del usuario autenticado<br/>WHERE estado IN ('completada','en_progreso')<br/>→ Excluye pendiente, cancelada<br/>→ Excluye solicitudes rechazadas/declinadas]:::process

    Query --> Empty{¿Tiene órdenes<br/>elegibles?}:::decision
    Empty -->|No| Warn[UI: Aviso: "Aún no tienes<br/>trabajos para mostrar"<br/>→ No genera PDF]:::decision
    Empty -->|Sí| Loop[Para cada orden elegible:<br/>→ Cargar order_services (snapshot)<br/>  title + value históricos]:::process

    Loop --> Anon[{ANONIMIZACIÓN}<br/>→ Eliminar nombre cliente/empresa<br/>→ Eliminar RUT, contacto, dirección<br/>→ Mantener solo EL RUBRO<br/>→ Redactar: "Proyecto X de una<br/>empresa destacada en el rubro Y"]:::privacy

    Anon --> Round[{MONTO DEGRADADO}<br/>→ CLP únicamente<br/>→ ceil(valor_real / 100) * 100<br/>→ 1453 → 1500 / 853.400 → 853.500<br/>→ Nunca inferior al real]:::privacy

    Round --> Render[Render plantilla TMC fija:<br/>→ Identidad visual del producto<br/>→ Por proyecto: rubro anónimo,<br/>  servicios nominados (snapshot),<br/>  estado general, montos redondeados]:::process

    Render --> Pdf[Generar PDF]:::process

    Pdf --> Download[UI: Descarga del PDF<br/>→ Sin link público permanente<br/>→ Usuario lo adjunta a su CV]:::endn

    Warn --> End([Fin sin PDF]):::endn
    Download --> End2([Fin con PDF descargado]):::endn
```

## Reglas del flujo (resumen normativo)

1. **Automático** (D4/HU-057): todas las órdenes `Completada` + `En progreso` del usuario; el usuario no elige proyectos. Nada de `Pendiente`/`Cancelada`/solicitudes rechazadas.
2. **Anonimización** (R7/R8/HU-058): nunca nombre de cliente/empresa, RUT ni contacto; sí rubro visible. Patrón: "Proyecto {tipo} de una empresa destacada en el rubro {rubro}".
3. **Snapshot** (R3/HU-060): títulos y valores salen de `order_services`, no del catálogo actual.
4. **Redondeo** (D3/HU-059): CLP, centena superior, `ceil(v/100)*100`, nunca por debajo del real.
5. **Plantilla TMC** (D5/HU-056): fija, sin marca personal, sin editor.

## Fuera de este flujo

- Distribución del PDF: el PDF es descarga; no se hostea públicamente ni se comparte por URL.
- Generación desde la web pública: el perfil público tiene su propio flujo de solicitudes y no se toca acá.

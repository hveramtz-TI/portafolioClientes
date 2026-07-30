# Portafolio de Clientes (Expo PWA)

## Visión

CRM Personal orientado a personas y profesionales, con soporte
offline-first, sincronización en la nube, grafo de relaciones
comerciales, finanzas, recordatorios, documentos y exportación de un CV
enriquecido con un resumen anonimizado del portafolio.

## Requisitos principales

-   Expo App + PWA
-   Offline + sincronización cuando exista conexión
-   Grafo interactivo de relaciones comerciales
-   Exportación a Excel y PDF
-   Exportación de CV con resumen por áreas y rangos (sin datos
    sensibles)

# Historias de Usuario

## Epic 1 - Gestión de Perfil Personal

### HU-001 - Crear perfil personal

**Como** usuario **quiero** crear mi perfil profesional **para**
personalizar mi espacio de trabajo.

**Criterios** - Nombre - Fotografía - Cargo - Empresa - Redes sociales -
CV PDF - Biografía - Tecnologías - Habilidades - Todo editable

### HU-002 - Gestionar CV

Subir distintas versiones del CV, con versionado, historial y una
versión activa.

## Epic 2 - Clientes

### HU-003 - Crear Cliente

Registrar persona, empresa, email, teléfono, LinkedIn, notas, etiquetas
y estado.

### HU-004 - Historial del cliente

Registrar reuniones, llamadas, correos, documentos y tareas en orden
cronológico.

### HU-005 - Clasificación

Estados: - Prospecto - Activo - En negociación - Pausado - Finalizado

Filtros por búsqueda y etiquetas.

## Epic 3 - Relaciones Comerciales

### HU-006 - Crear relaciones

Relacionar Cliente, Empresa, Proveedor, Partner, Mentor o Contacto.

### HU-007 - Grafo interactivo

-   Zoom
-   Pan
-   Drag
-   Buscar
-   Expandir/Colapsar
-   Selección de nodos

### HU-008 - Métricas

-   Cantidad de conexiones
-   Nodos aislados
-   Nodo más influyente
-   Clusters

## Epic 4 - Finanzas

### HU-009 - Registrar ingresos

Fecha, cliente, concepto, categoría, estado y monto.

### HU-010 - Registrar gastos

### HU-011 - Dashboard financiero

Ingresos, gastos, balance, gráficos y estadísticas.

## Epic 5 - Recordatorios

### HU-012 - Crear recordatorio

Fecha, hora, prioridad y cliente asociado.

### HU-013 - Notificaciones

Locales, push y repetición.

## Epic 6 - Documentos

### HU-014 - Adjuntar documentos

PDF, Excel, imágenes, contratos y presentaciones.

### HU-015 - Organizador

Carpetas, etiquetas y vista previa.

## Epic 7 - Exportaciones

### HU-016 - Exportar Excel

Clientes, finanzas, tareas, relaciones y recordatorios.

### HU-017 - Exportar PDF Ejecutivo

Resumen anonimizado sin nombres, montos ni datos sensibles.

### HU-018 - Exportar CV Inteligente

CV + resumen del portafolio mostrando: - Áreas comerciales -
Industrias - Tecnologías - Rangos de proyectos - Nivel de experiencia

## Epic 8 - Offline First

### HU-019

Funcionamiento completo sin Internet.

### HU-020

Cola de sincronización.

### HU-021

Resolución de conflictos.

## Epic 9 - Backup

### HU-022

Backup automático.

### HU-023

Restauración de versiones.

## Epic 10 - Dashboard

### HU-024

Widgets, actividad reciente y métricas.

## Epic 11 - Analytics

### HU-025

Estadísticas por industria, región, tecnologías y relaciones.

## Epic 12 - Seguridad

### HU-026

PIN, biometría y autenticación.

### HU-027

Cifrado local y de respaldos.

## Epic 13 - IA

### HU-028

Resumen inteligente.

### HU-029

Recomendaciones.

### HU-030

Generación automática de CV ATS.

## Epic 14 - Portfolio Profesional

### HU-031

Generación automática de un portafolio profesional anonimizado.

## Epic 15 - Sincronización de Contactos

### HU-032 - Sincronizar contactos nativos

Como usuario quiero sincronizar contactos de mi dispositivo para actualizar datos de mis clientes existentes.

**Criterios**:

- Pedir permiso de contactos (expo-contacts)
- Normalizar teléfonos (quitar +, espacios, guiones)
- Matchear contactos con clientes existentes por teléfono
- Actualizar datos del cliente con información del contacto (nombre, email, etc.)
- Mostrar resumen: X contactos sincronizados, Y sin match
- No crear clientes nuevos

### HU-033 - Pantalla de Ajustes con sincronización

Como usuario quiero acceder a ajustes para sincronizar contactos.

**Criterios**:

- Tab "Contactos" en Ajustes
- Botón "Sincronizar" que inicia el proceso
- Mostrar progreso y resultado (sincronizados/sin match)
- Opción para re-sincronizar

### HU-034 - Utilidad de normalización de teléfonos

Utilidad para normalizar teléfonos (quitar caracteres especiales, espacios, códigos de país).

**Criterios**:

- Función `normalizePhone(phone: string): string`
- Quitar todo excepto dígitos
- Manejar formatos internacionales, locales, con extensiones
- Tests comprehensivos

# Arquitectura

``` text
Presentation
├── Dashboard
├── Clientes
├── Grafo
├── Finanzas
├── Recordatorios
├── Documentos
├── Exportaciones
├── Perfil
└── Configuración

Application
├── Use Cases
├── Services
├── Sync Engine
└── Export Engine

Domain
├── Cliente
├── Empresa
├── Relación
├── Proyecto
├── Documento
├── Finanza
├── Recordatorio
├── Perfil
└── Analytics

Infrastructure
├── SQLite
├── IndexedDB
├── API REST
├── Auth
├── Storage
└── IA
```

# Roadmap MVP

| Sprint | Objetivo | Historias |
|--------|----------|-----------|
| 1 | Base | HU-001, HU-003, HU-005, HU-024 |
| 2 | CRM + Contactos | HU-032, HU-033, HU-034 (Sincronización de contactos nativos) |
| 3 | Offline | HU-019, HU-020, HU-021 |
| 4 | Grafo | HU-006, HU-007, HU-008 |
| 5 | Finanzas | HU-009, HU-010, HU-011, HU-025 |
| 6 | Exportación | HU-016, HU-017, HU-018 |
| 7 | IA y Portfolio | HU-028, HU-029, HU-030, HU-031 |

# Testing en Laravel

## Ejecutar tests



## Estructura de tests



## Configuración

- **Base de datos**: Usa SQLite en memoria para tests rápidos (configurado en phpunit.xml)
- **Cache/Session**: Array driver (no persiste)
- **Queue**: Sync (ejecuta jobs inmediatamente)
- **Mail**: Array (no envía emails reales)

## Buenas prácticas

1. **Feature tests**: Prueban endpoints completos, autenticación, validaciones
2. **Unit tests**: Prueban lógica de negocio aislada (models, services, helpers)
3. **Usar factories**: Para crear datos de prueba
4. **RefreshDatabase**: Para tests que necesitan base de datos limpia
5. **Mocking**: Para servicios externos (MinIO, APIs third-party)

## Ejemplos

### Test con base de datos


### Test con mocking


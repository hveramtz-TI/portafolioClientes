# Testing en Laravel

## Ejecutar tests

### Tests rápidos (SQLite en memoria) - Por defecto

```bash
# Desde el directorio raíz del proyecto
./test.sh

# O directamente con docker compose
docker compose exec backend php artisan test

# Ejecutar un archivo específico
docker compose exec backend php artisan test tests/Feature/HealthCheckTest.php

# Con cobertura (requiere Xdebug)
docker compose exec backend php artisan test --coverage
```

### Tests realistas (PostgreSQL) - Para integración

```bash
# Ejecutar todos los tests con PostgreSQL
./test-pg.sh

# O directamente
docker compose exec backend php artisan test --configuration=phpunit-pg.xml

# Ejecutar un archivo específico
docker compose exec backend php artisan test --configuration=phpunit-pg.xml tests/Feature/HealthCheckTest.php
```

## Configuración

### SQLite (por defecto) - Rápido

- **Base de datos**: SQLite en memoria (`:memory:`)
- **Velocidad**: ~2-5 segundos para suite completa
- **Uso**: Desarrollo diario, TDD, CI/CD
- **Archivo**: `phpunit.xml`

### PostgreSQL - Realista

- **Base de datos**: PostgreSQL (`portafolio_test`)
- **Velocidad**: ~15-30 segundos para suite completa
- **Uso**: Tests de integración, features específicas de PostgreSQL
- **Archivo**: `phpunit-pg.xml`

## ¿Cuándo usar cada uno?

### Usá SQLite (rápido) para:

✅ Tests unitarios
✅ Tests de validación
✅ Tests de lógica de negocio
✅ TDD (feedback inmediato)
✅ CI/CD (más rápido)
✅ Desarrollo diario

### Usá PostgreSQL (realista) para:

✅ Tests que usan UUIDs como primary keys
✅ Tests con queries JSONB complejas
✅ Tests de full-text search
✅ Tests de integración crítica
✅ Antes de deploy a producción
✅ Cuando sospechas diferencias de comportamiento

## Estructura de tests

```
tests/
├── Feature/          # Tests de integración (endpoints, flujos completos)
│   ├── ExampleTest.php
│   ├── HealthCheckTest.php
│   └── ApiRoutesTest.php
├── Unit/             # Tests unitarios (funciones, clases aisladas)
│   └── ExampleTest.php
└── TestCase.php      # Clase base para todos los tests
```

## Configuración técnica

### SQLite (`phpunit.xml`)

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

**Ventajas**:
- ⚡ 5-10x más rápido
- No requiere PostgreSQL corriendo
- Ideal para CI/CD
- Perfecto para TDD

**Limitaciones**:
- ⚠️ No soporta features específicas de PostgreSQL
- ⚠️ Comportamiento ligeramente diferente en algunos casos

### PostgreSQL (`phpunit-pg.xml`)

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_HOST" value="postgres"/>
<env name="DB_DATABASE" value="portafolio_test"/>
<env name="DB_USERNAME" value="portafolio"/>
<env name="DB_PASSWORD" value="secret"/>
```

**Ventajas**:
- ✅ Idéntico a producción
- ✅ Soporta todas las features de PostgreSQL
- ✅ Validación estricta de tipos

**Desventajas**:
- 🐢 5-10x más lento
- Requiere PostgreSQL corriendo
- Más overhead en CI/CD

## Preparar base de datos para tests con PostgreSQL

Antes de ejecutar tests con PostgreSQL, necesitás crear la base de datos de testing:

```bash
# Crear base de datos portafolio_test
docker compose exec postgres psql -U portafolio -c "CREATE DATABASE portafolio_test;"

# O si ya existe, recrearla
docker compose exec postgres psql -U portafolio -c "DROP DATABASE IF EXISTS portafolio_test; CREATE DATABASE portafolio_test;"
```

## Buenas prácticas

1. **Desarrollo diario**: Usá SQLite (`./test.sh`) para feedback rápido
2. **Antes de commit**: Corré SQLite para verificar lógica
3. **Antes de PR**: Corré PostgreSQL (`./test-pg.sh`) para verificar integración
4. **CI/CD**: Usá SQLite para velocidad, PostgreSQL para tests críticos
5. **TDD**: SQLite es ideal por la velocidad de feedback
6. **Tests de integración**: PostgreSQL para comportamiento real

## Ejemplos

### Test con base de datos
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

public function test_user_can_login(): void
{
    $user = User::factory()->create();
    
    $response = $this->post('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $response->assertStatus(200);
}
```

### Test con mocking (funciona con ambos)
```php
public function test_file_upload(): void
{
    Storage::fake('s3');
    
    $response = $this->post('/api/upload', [
        'file' => UploadedFile::fake()->image('avatar.jpg'),
    ]);
    
    Storage::disk('s3')->assertExists('avatars/avatar.jpg');
}
```

### Test específico de PostgreSQL
```php
public function test_uuid_primary_key(): void
{
    // Este test solo tiene sentido con PostgreSQL
    $client = Client::factory()->create();
    
    // Verificar que el ID es un UUID válido
    $this->assertMatchesRegularExpression(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
        $client->id
    );
}
```

## Workflow recomendado

```bash
# 1. Desarrollo diario (rápido)
./test.sh

# 2. Antes de commit (rápido)
./test.sh

# 3. Antes de PR (realista)
docker compose exec postgres psql -U portafolio -c "CREATE DATABASE portafolio_test;" 2>/dev/null || true
./test-pg.sh

# 4. CI/CD (rápido)
./test.sh --coverage
```

## Troubleshooting

### PostgreSQL: "Database does not exist"

```bash
docker compose exec postgres psql -U portafolio -c "CREATE DATABASE portafolio_test;"
```

### PostgreSQL: Tests muy lentos

- Verificar que PostgreSQL está corriendo: `docker compose ps`
- Usar `RefreshDatabase` en vez de migraciones manuales
- Considerar usar `DatabaseTransactions` cuando sea posible

### SQLite: Tests pasan pero fallan en producción

- Probá con PostgreSQL para verificar comportamiento real
- Revisar queries que usan features específicas de PostgreSQL

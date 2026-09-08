#!/bin/bash

# Script para ejecutar los tests de Laravel contra PostgreSQL real (mismo
# engine que produccion), con la config de backend/phpunit-pg.xml.
#
# Uso: ./test-pg.sh [ruta-de-test-...]   (ej: ./test-pg.sh tests/Feature)
#
# Se invoca vendor/bin/phpunit directo y NO `php artisan test --configuration=...`
# porque el comando test de Collision siempre agrega su propio --configuration
# (TestCommand::commonArguments) y PHPUnit recibe la opcion duplicada:
# "Option --configuration cannot be used more than once" -> WARN + exit 1
# incluso con toda la suite en verde.

# Credenciales duras a proposito: son las mismas de docker-compose.yml y
# phpunit-pg.xml (valores de dev, no secretos).
POSTGRES_USER="${POSTGRES_USER:-portafolio}"
TEST_DB="portafolio_test"

# Asegurar la DB de tests: sin ella, RefreshDatabase (migrate:fresh sobre
# portafolio_test) falla de conexion en un stack recien levantado.
# Idempotente: solo la crea la primera vez.
if ! docker compose exec -T postgres psql -U "$POSTGRES_USER" -d postgres -tAc \
     "SELECT 1 FROM pg_database WHERE datname='$TEST_DB'" | grep -q '^1$'; then
    echo "==> Creando base de datos de tests '$TEST_DB'..."
    docker compose exec -T postgres psql -U "$POSTGRES_USER" -d postgres -qc \
        "CREATE DATABASE $TEST_DB OWNER $POSTGRES_USER" || exit 1
fi

docker compose exec -T backend vendor/bin/phpunit -c phpunit-pg.xml "$@"

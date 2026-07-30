#!/bin/bash

# Script para ejecutar tests de Laravel con PostgreSQL
# Uso: ./test-pg.sh [test-path]

if [ -z "$1" ]; then
    docker compose exec backend php artisan test --configuration=phpunit-pg.xml
else
    docker compose exec backend php artisan test --configuration=phpunit-pg.xml "$1"
fi

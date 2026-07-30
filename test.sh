#!/bin/bash

# Script para ejecutar tests de Laravel en Docker
# Uso: ./test.sh [test-path]

if [ -z "$1" ]; then
    docker compose exec backend php artisan test
else
    docker compose exec backend php artisan test "$1"
fi

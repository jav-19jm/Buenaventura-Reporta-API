#!/bin/sh
# Arranque del contenedor backend: prepara la base de datos y las cachés de
# Laravel y luego ejecuta el comando principal (supervisord).
set -e

cd /var/www/html

if [ -z "$APP_KEY" ] || [ -z "$JWT_SECRET" ]; then
    echo "ERROR: faltan APP_KEY o JWT_SECRET. Revisa el archivo .env del repositorio de infraestructura." >&2
    exit 1
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "> Aplicando migraciones..."
    php artisan migrate --force
fi

# Solo en una base vacía: catálogo inicial y administrador (ADMIN_EMAIL / ADMIN_PASSWORD).
# No se ejecuta en cada arranque para no pisar los cambios hechos desde el panel.
if [ "${SEED_ON_EMPTY_DB:-true}" = "true" ]; then
    php artisan app:inicializar-datos
fi

echo "> Optimizando configuración, rutas y eventos..."
php artisan optimize

exec "$@"

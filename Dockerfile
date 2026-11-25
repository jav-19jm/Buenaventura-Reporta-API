# syntax=docker/dockerfile:1
#
# API de Buenaventura Reporta (Laravel 12)
#
# Etapas:
#   base    PHP-FPM 8.2 + extensiones + Nginx + Supervisor (común a todas)
#   deps    dependencias de Composer de producción, instaladas con el MISMO PHP y
#           extensiones de la imagen final (si el composer.lock no es compatible, falla aquí)
#   test    base + dependencias de desarrollo; ejecuta la suite (la usa el pipeline)
#   prod    imagen final: PHP-FPM, Nginx, Reverb (WebSockets) y worker de colas
#
#   docker build --target test -t buenaventura/api:test .  &&  docker run --rm buenaventura/api:test
#   docker build --target prod -t buenaventura/api .
#
# PHP 8.2: es la versión del entorno de desarrollo del equipo y la que admite el
# composer.lock actual (lcobucci/clock 2.x, dependencia de tymon/jwt-auth, no admite 8.3).

# ---------------------------------------------------------------------------
FROM php:8.2-fpm-alpine AS base

# Extensiones: PostgreSQL, Redis, imágenes (gd/exif), intl, zip, bcmath,
# pcntl (señales para Reverb y queue:work) y OPcache
RUN apk add --no-cache nginx supervisor curl libpq icu-libs libzip libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
        postgresql-dev icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql gd intl zip bcmath pcntl opcache exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-buenaventura.ini
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint

# Nginx y Supervisor corren sin root: sus directorios de trabajo pasan a www-data
RUN mkdir -p /run/nginx /var/lib/nginx/tmp /var/log/nginx \
    && chown -R www-data:www-data /run/nginx /var/lib/nginx /var/log/nginx

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
FROM base AS deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------------------------------------------------------------------------
FROM base AS test

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

COPY . .
# .env vacío: `php artisan test` lo lee para pasar variables al proceso de PHPUnit.
# Debe estar vacío para que no pise los valores de phpunit.xml.
RUN composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && : > .env

# phpunit.xml ya define las variables de prueba (SQLite en memoria, claves de test)
CMD ["php", "artisan", "test"]

# ---------------------------------------------------------------------------
FROM base AS prod

COPY --from=deps /var/www/html/vendor ./vendor
COPY . .

RUN php artisan package:discover --ansi \
    && ln -sfn ../storage/app/public public/storage \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

# 8000: API (Nginx → PHP-FPM) · 8080: WebSockets (Reverb)
EXPOSE 8000 8080

HEALTHCHECK --interval=15s --timeout=5s --start-period=40s --retries=5 \
    CMD curl -fsS http://127.0.0.1:8000/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]

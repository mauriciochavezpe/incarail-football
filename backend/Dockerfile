# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Base: PHP 8.5 FPM sobre Alpine Linux + extensiones que necesita la API.
# (sodium, mbstring, dom, fileinfo, pdo_sqlite y OPcache ya vienen en la imagen.)
# ---------------------------------------------------------------------------
FROM php:8.5-fpm-alpine AS base

RUN apk add --no-cache libpq \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
 && apk del .build-deps

# ---------------------------------------------------------------------------
# Dependencias de Composer (sin las de desarrollo).
# ---------------------------------------------------------------------------
FROM base AS vendor

RUN apk add --no-cache git unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction

# ---------------------------------------------------------------------------
# Imagen final: solo el código y vendor, sin Composer ni herramientas de build.
# ---------------------------------------------------------------------------
FROM base AS app

WORKDIR /var/www/html

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
COPY --from=vendor --chown=www-data:www-data /app /var/www/html

RUN chmod +x /usr/local/bin/entrypoint

HEALTHCHECK --interval=5s --timeout=3s --start-period=10s --retries=20 \
  CMD test -f /var/www/html/storage/app/.ready || exit 1

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

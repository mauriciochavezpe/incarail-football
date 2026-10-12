#!/bin/sh
# Prepara la API al arrancar el contenedor. Es idempotente: se puede reiniciar
# las veces que sea sin perder datos ni duplicar seeders.
set -e
cd /var/www/html

rm -f storage/app/.ready

# 1. APP_KEY: si no viene por entorno, se genera una vez y se guarda en el volumen.
if [ -z "${APP_KEY}" ]; then
  if [ ! -s storage/app/.app_key ]; then
    php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/.app_key
  fi
  APP_KEY="$(cat storage/app/.app_key)"
  export APP_KEY
fi

# 2. Llaves RSA de Passport (persisten en el volumen de storage).
if [ ! -f storage/oauth-private.key ]; then
  php artisan passport:keys --no-interaction
fi
chmod 600 storage/oauth-private.key
chmod 600 storage/oauth-public.key  # league/oauth2-server exige 600 o 660

# 3. Migraciones (con reintentos mientras la base de datos termina de arrancar).
tries=0
until php artisan migrate --force --no-interaction; do
  tries=$((tries + 1))
  if [ "$tries" -ge 20 ]; then
    echo "La base de datos no respondió a tiempo." >&2
    exit 1
  fi
  echo "Esperando a la base de datos ($tries)..."
  sleep 3
done

# 4. Seeders solo la primera vez (no pisa cambios hechos desde la app).
if [ ! -f storage/app/.seeded ]; then
  php artisan db:seed --force --no-interaction
  touch storage/app/.seeded
fi

# 5. Cachés de configuración, rutas y eventos para producción
#    (no se usa "optimize" porque la API no tiene vistas Blade que cachear).
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan event:cache --no-interaction

chown -R www-data:www-data storage bootstrap/cache
touch storage/app/.ready

exec "$@"

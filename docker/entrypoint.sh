#!/bin/sh
set -e

echo "[entrypoint] Starting Laravel setup..."

# Ensure storage directories exist
mkdir -p storage/framework/{cache,sessions,views,testing}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Wait for DB to be ready (max 30s)
if [ -n "$DB_HOST" ]; then
    echo "[entrypoint] Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    for i in $(seq 1 30); do
        php -r "
            try {
                new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');
                exit(0);
            } catch (Exception \$e) { exit(1); }
        " && break
        echo "[entrypoint] DB not ready yet ($i/30)..."
        sleep 2
    done
fi

# Laravel optimisation (only for app container, not queue/scheduler)
if [ "$1" = "php-fpm" ]; then
    echo "[entrypoint] Running Laravel optimisation..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    echo "[entrypoint] Optimisation complete."
fi

exec "$@"

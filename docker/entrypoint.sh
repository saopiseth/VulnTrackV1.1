#!/bin/sh
set -e

echo "[entrypoint] Starting VulnTrack setup..."

# ── Ensure writable directories exist ────────────────────────
mkdir -p storage/framework/{cache/data,sessions,views,testing} \
         storage/logs \
         bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── Wait for MySQL (max 60 s) ─────────────────────────────────
if [ -n "$DB_HOST" ]; then
    echo "[entrypoint] Waiting for database ($DB_HOST:${DB_PORT:-3306})..."
    i=0
    until php -r "
        try {
            new PDO(
                'mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}',
                '${DB_USERNAME}', '${DB_PASSWORD}'
            );
            exit(0);
        } catch (Exception \$e) { exit(1); }
    " 2>/dev/null; do
        i=$((i+1))
        if [ $i -ge 30 ]; then
            echo "[entrypoint] ERROR: database not reachable after 60 s" >&2
            exit 1
        fi
        echo "[entrypoint] DB not ready yet ($i/30)..."
        sleep 2
    done
    echo "[entrypoint] Database is ready."
fi

# ── Run migrations automatically ─────────────────────────────
php artisan migrate --force --no-interaction

# ── Laravel optimisation (app container only) ─────────────────
if [ "$1" = "php-fpm" ] || [ "$1" = "supervisord" ]; then
    echo "[entrypoint] Building caches..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    echo "[entrypoint] Cache warm-up complete."
fi

echo "[entrypoint] Booting: $@"
exec "$@"

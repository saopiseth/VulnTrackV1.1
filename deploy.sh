#!/bin/bash
set -e

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$APP_DIR"

echo "========================================"
echo "  VulnTrack — Deploy Script"
echo "========================================"

# ── 1. Pull latest code ───────────────────────────────────────
echo ""
echo "[1/6] Pulling latest code from GitHub..."
git pull origin main

# ── 2. Rebuild app image ──────────────────────────────────────
echo ""
echo "[2/6] Building Docker image..."
docker compose build app

# ── 3. Restart containers ─────────────────────────────────────
echo ""
echo "[3/6] Restarting containers..."
docker compose up -d --remove-orphans

# ── 4. Ensure storage directories exist ──────────────────────
echo ""
echo "[4/6] Ensuring storage directories..."
docker compose exec app mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/private/scan-uploads \
    bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache

# ── 5. Clear and rebuild Laravel caches ──────────────────────
echo ""
echo "[5/6] Rebuilding Laravel caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan event:cache

# ── 6. Health check ───────────────────────────────────────────
echo ""
echo "[6/6] Checking container status..."
docker compose ps

echo ""
echo "========================================"
echo "  Deploy complete!"
echo "========================================"

# Deployment Guide — Security Assessment Platform

A step-by-step guide to deploying this Laravel application with Docker in a production environment.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Server Setup](#2-server-setup)
3. [Clone the Repository](#3-clone-the-repository)
4. [Configure Environment](#4-configure-environment)
5. [Build & Start Containers](#5-build--start-containers)
6. [Database Setup](#6-database-setup)
7. [Verify Deployment](#7-verify-deployment)
8. [SSL / HTTPS Setup](#8-ssl--https-setup)
9. [Updating the Application](#9-updating-the-application)
10. [Useful Commands](#10-useful-commands)
11. [Troubleshooting](#11-troubleshooting)
12. [Architecture Overview](#12-architecture-overview)

---

## 1. Prerequisites

Install the following on your server before proceeding.

| Tool | Minimum Version | Install |
|---|---|---|
| Docker | 24.x | `curl -fsSL https://get.docker.com | sh` |
| Docker Compose | 2.x (v2 plugin) | included with Docker Desktop / `apt install docker-compose-plugin` |
| Git | any | `apt install git` |

Verify installations:

```bash
docker --version
docker compose version
git --version
```

> **Recommended OS:** Ubuntu 22.04 LTS or Debian 12.

---

## 2. Server Setup

### Create a dedicated user (optional but recommended)

```bash
adduser deploy
usermod -aG docker deploy
su - deploy
```

### Open firewall ports

```bash
# Allow HTTP and HTTPS
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

> MySQL port `3306` is bound to `127.0.0.1` only — it is **not** exposed to the internet.

---

## 3. Clone the Repository

```bash
git clone https://github.com/your-org/your-repo.git /var/www/security-assessment
cd /var/www/security-assessment
```

---

## 4. Configure Environment

### 4a. Copy the production environment file

```bash
cp .env.production .env.production.local
```

Edit `.env.production.local` with your real values:

```bash
nano .env.production.local
```

**Required fields to fill in:**

```env
APP_KEY=          # See step 4b
APP_URL=https://yourdomain.com

DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=          # Choose a strong password
DB_ROOT_PASSWORD=     # Choose a strong root password

REDIS_PASSWORD=       # Choose a strong password

MAIL_HOST=smtp.yourprovider.com
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your-mail-password
```

### 4b. Generate the application key

```bash
docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Copy the output and paste it as the value for `APP_KEY` in your `.env.production.local`.

### 4c. Point Docker Compose to your env file

```bash
# Symlink so docker-compose picks it up
ln -sf .env.production.local .env
```

---

## 5. Build & Start Containers

### Build images

```bash
docker compose build --no-cache
```

> First build takes 3–5 minutes while it downloads base images and compiles PHP extensions.

### Start all services in the background

```bash
docker compose up -d
```

### Check all containers are running

```bash
docker compose ps
```

Expected output:

```
NAME                STATUS          PORTS
laravel_app         running         9000/tcp
laravel_nginx       running         0.0.0.0:80->80/tcp
laravel_db          running (healthy)
laravel_redis       running (healthy)
laravel_queue       running
laravel_scheduler   running
```

> All services should show `running`. The `db` and `redis` services should show `(healthy)` within ~30 seconds.

---

## 6. Database Setup

### Run migrations

```bash
docker compose exec app php artisan migrate --force
```

### Seed initial data (if applicable)

```bash
docker compose exec app php artisan db:seed --force
```

### Create the first admin user

```bash
docker compose exec app php artisan tinker
```

Inside tinker:

```php
\App\Models\User::create([
    'name'        => 'Admin',
    'email'       => 'admin@yourdomain.com',
    'password'    => bcrypt('YourStrongPassword'),
    'role'        => 'administrator',
    'mfa_enabled' => true,
]);
exit
```

---

## 7. Verify Deployment

### Check the application is responding

```bash
curl -I http://localhost/up
# Expected: HTTP/1.1 200 OK
```

### Check application logs

```bash
docker compose logs app --tail=50
```

### Check Nginx logs

```bash
docker compose logs webserver --tail=50
```

### Check queue worker is processing jobs

```bash
docker compose logs queue --tail=20
```

---

## 8. SSL / HTTPS Setup

### Option A — Let's Encrypt with Certbot (recommended)

```bash
# Install Certbot on the host
apt install certbot

# Stop Nginx temporarily
docker compose stop webserver

# Obtain certificate
certbot certonly --standalone -d yourdomain.com

# Certificates are saved to:
# /etc/letsencrypt/live/yourdomain.com/fullchain.pem
# /etc/letsencrypt/live/yourdomain.com/privkey.pem
```

Copy certs into the project:

```bash
mkdir -p docker/nginx/ssl
cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem docker/nginx/ssl/
cp /etc/letsencrypt/live/yourdomain.com/privkey.pem   docker/nginx/ssl/
```

Enable HTTPS in `docker/nginx/app.conf`:
- Uncomment the `return 301 https://...` line in the port 80 block
- Uncomment the entire `server { listen 443 ssl ... }` block
- Update `server_name yourdomain.com`

Restart Nginx:

```bash
docker compose up -d webserver
```

### Auto-renew certificates (cron on host)

```bash
crontab -e
```

Add:

```
0 3 * * * certbot renew --quiet && cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem /var/www/security-assessment/docker/nginx/ssl/ && cp /etc/letsencrypt/live/yourdomain.com/privkey.pem /var/www/security-assessment/docker/nginx/ssl/ && docker compose -f /var/www/security-assessment/docker-compose.yml exec webserver nginx -s reload
```

### Option B — Reverse Proxy (Cloudflare / Load Balancer)

If you use Cloudflare or an external load balancer for SSL termination, no changes are needed — leave Nginx serving HTTP on port 80 and let your proxy handle HTTPS.

---

## 9. Updating the Application

```bash
# Pull latest code
git pull origin main

# Rebuild app image
docker compose build app queue scheduler

# Restart with zero-downtime (app stays up during build)
docker compose up -d --no-deps app queue scheduler

# Run any new migrations
docker compose exec app php artisan migrate --force

# Clear old caches (entrypoint re-caches on start automatically)
docker compose exec app php artisan optimize:clear
```

---

## 10. Useful Commands

### Container management

```bash
# View all container statuses
docker compose ps

# Restart a single service
docker compose restart app

# Stop everything
docker compose down

# Stop and remove volumes (DESTRUCTIVE — deletes database)
docker compose down -v
```

### Laravel Artisan

```bash
# Run any artisan command inside the app container
docker compose exec app php artisan <command>

# Open tinker REPL
docker compose exec app php artisan tinker

# Clear all caches
docker compose exec app php artisan optimize:clear

# Re-cache everything (done automatically on restart)
docker compose exec app php artisan optimize
```

### Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f app
docker compose logs -f webserver
docker compose logs -f queue

# Laravel application log
docker compose exec app tail -f storage/logs/laravel.log
```

### Database

```bash
# Open MySQL shell
docker compose exec db mysql -u laravel -p laravel

# Backup database
docker compose exec db mysqldump -u laravel -p laravel > backup_$(date +%Y%m%d).sql

# Restore database
cat backup.sql | docker compose exec -T db mysql -u laravel -p laravel
```

### Redis

```bash
# Open Redis CLI
docker compose exec redis redis-cli -a $REDIS_PASSWORD

# Flush all cache keys
docker compose exec redis redis-cli -a $REDIS_PASSWORD FLUSHDB
```

---

## 11. Troubleshooting

### Container exits immediately

```bash
# Check exit logs
docker compose logs app
```

Common causes:
- `APP_KEY` is missing or malformed in `.env`
- Database credentials wrong — DB healthcheck still failing
- Permission error on `storage/` or `bootstrap/cache/`

Fix permissions manually:

```bash
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### 502 Bad Gateway from Nginx

The `app` container (PHP-FPM) is not ready or has crashed.

```bash
docker compose logs app --tail=30
docker compose restart app
```

### Database connection refused

The DB container may still be initialising. Wait 30 seconds and retry. Check health:

```bash
docker compose ps db
docker compose logs db --tail=20
```

### Queue jobs not processing

```bash
docker compose logs queue --tail=30
docker compose restart queue
```

### View cached config / route is stale

```bash
docker compose exec app php artisan optimize:clear
docker compose restart app   # entrypoint re-caches on boot
```

---

## 12. Architecture Overview

```
Internet
   │
   ▼
┌──────────────────────────────────────────────────────┐
│  Docker network: laravel                             │
│                                                      │
│  ┌─────────────┐    ┌──────────────────────────┐    │
│  │   Nginx     │───▶│  PHP-FPM (app)           │    │
│  │  :80 / :443 │    │  Laravel 12 / PHP 8.2    │    │
│  └─────────────┘    └────────────┬─────────────┘    │
│                                  │                   │
│              ┌───────────────────┼──────────┐        │
│              ▼                   ▼          ▼        │
│       ┌────────────┐   ┌───────────────┐            │
│       │  MySQL 8   │   │  Redis 7      │            │
│       │  (db)      │   │  Cache/Queue  │            │
│       └────────────┘   └──────┬────────┘            │
│                               │                      │
│              ┌────────────────┼──────────────┐       │
│              ▼                               ▼       │
│       ┌─────────────┐              ┌──────────────┐  │
│       │ Queue Worker│              │  Scheduler   │  │
│       │  (queue)    │              │  (scheduler) │  │
│       └─────────────┘              └──────────────┘  │
└──────────────────────────────────────────────────────┘
```

| Service | Image | Role |
|---|---|---|
| `webserver` | nginx:1.25-alpine | Serves static files, proxies PHP to app:9000 |
| `app` | Custom PHP 8.2-FPM | Runs Laravel, warms caches on startup |
| `db` | mysql:8.0 | Persistent relational data |
| `redis` | redis:7-alpine | Cache, sessions, queue backend |
| `queue` | Custom PHP 8.2-FPM | Processes background jobs |
| `scheduler` | Custom PHP 8.2-FPM | Runs `schedule:run` every 60 seconds |

---

> For issues or questions open a ticket in the project repository.

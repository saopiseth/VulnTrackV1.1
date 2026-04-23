# Security Assessment Platform

A web-based vulnerability assessment and management platform built with Laravel 12. Designed for security teams to track vulnerabilities across assessment scopes, manage remediation workflows, assign work to user groups, and monitor progress over time.

---

## Features

### Vulnerability Management
- Upload Nessus scan results and automatically track findings across multiple scans
- Baseline vs. subsequent scan comparison with automatic status transitions (New → Open → Unresolved → Resolved → Reopened)
- Per-finding severity classification: Critical, High, Medium, Low
- OS family detection and vulnerability categorisation
- Plugin output viewer per finding

### Assessment Scope
- Define assessment scope groups with IP addresses, hostnames, system names, criticality levels, environments, and locations
- Link scope entries to assessments for system-name resolution on findings

### Remediation Workflow
- Per-finding remediation records: status, assigned-to, due date, comments, evidence
- Bulk assign findings to user groups from the findings table
- SLA policy enforcement with due-date tracking
- Remediation status breakdown: Open / In Progress / Resolved / Accepted Risk

### User & Group Management
- Role-based access: Administrator and Assessor
- User groups for team-based remediation assignment
- Per-group member management
- Optional MFA (email OTP) per user

### Progress Tracking
- Per-assessment progress page with Chart.js visualisations:
  - Severity trend line chart across scan uploads
  - Remediation status doughnut chart
  - Current severity breakdown bar chart
- Scan history timeline with finding and host counts

### Reporting
- Export assessment reports to PDF, Word, and Excel
- Customisable cover page, header, footer, disclaimer, and accent color per report

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| PHP | 8.2 |
| Database | MySQL 8 |
| Cache / Queue / Session | Redis 7 |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Chart.js 4 |
| PDF generation | barryvdh/laravel-dompdf |
| Web server | Nginx 1.25 (Alpine) |
| Containerisation | Docker + Docker Compose |

---

## Getting Started (Docker)

### Requirements
- [Docker Engine 24+](https://docs.docker.com/get-docker/)
- [Docker Compose v2](https://docs.docker.com/compose/install/)

### 1 — Clone the repository

```bash
git clone https://github.com/saopiseth/VulnTrackV1.0.git
cd VulnTrackV1.0
```

### 2 — Configure environment

```bash
cp .env.docker .env.docker
```

Open `.env.docker` and set `APP_KEY`. Generate one with:

```bash
docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Paste the output into `APP_KEY=` in `.env.docker`. All other defaults work out of the box for local use.

### 3 — Build and start

```bash
docker compose up -d --build
```

This starts four containers — **app** (PHP-FPM + queue + scheduler via Supervisor), **nginx**, **db** (MySQL 8), and **redis**. Migrations run automatically on first boot.

### 4 — Create the first admin user

```bash
docker compose exec app php artisan tinker --execute="
\App\Models\User::create([
    'name'        => 'Admin',
    'email'       => 'admin@localhost',
    'password'    => bcrypt('Admin1234!'),
    'role'        => 'administrator',
    'mfa_enabled' => false,
]);"
```

### 5 — Open the app

Visit **http://localhost** and sign in with the credentials above.

Open **http://localhost** — done.

> Migrations run automatically on container start via `docker/entrypoint.sh`.

---

## Internal Server Deployment (Ubuntu + Docker)

### Architecture

Four containers on an isolated `vulntrack` bridge network. Only Nginx (port 80) is exposed to the host — MySQL and Redis are internal only.

| Container | Image | Exposed | Purpose |
|---|---|---|---|
| `vulntrack_app` | Custom PHP 8.2-FPM | No | Laravel app + queue worker + scheduler (Supervisor) |
| `vulntrack_nginx` | nginx:1.25-alpine | **Port 80** | Reverse proxy → PHP-FPM |
| `vulntrack_mysql` | mysql:8.0 | No | Database (internal only) |
| `vulntrack_redis` | redis:7-alpine | No | Cache, sessions, queues (internal only) |

---

### Prerequisites

- Docker Engine 24+ and Docker Compose v2
- A server with at least 2 GB RAM and 20 GB disk
- A domain name pointed at the server (for HTTPS)

Verify installation:

```bash
docker --version
docker compose version
```

---

### Step 1 — Clone the Repository

```bash
git clone https://github.com/saopiseth/VulnTrackV1.0.git
cd security-assessment
```

---

### Step 2 — Configure Environment

Copy the production environment template:

```bash
cp .env.production .env
```

Open `.env` and fill in every required value:

```env
APP_NAME="Security Assessment"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com        # <-- your actual domain

# Generate with: php artisan key:generate --show
APP_KEY=base64:REPLACE_WITH_GENERATED_KEY

# Database
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=STRONG_DB_PASSWORD        # <-- change this
DB_ROOT_PASSWORD=STRONG_ROOT_PASSWORD # <-- change this

# Redis
REDIS_PASSWORD=STRONG_REDIS_PASSWORD  # <-- change this

# Mail (required for MFA email OTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Security Assessment"
```

Generate `APP_KEY` without starting containers:

```bash
php artisan key:generate --show
# Copy the output (base64:...) into APP_KEY in .env
```

---

### Step 3 — (Optional) Configure SSL / HTTPS

Place your SSL certificate files in `docker/nginx/ssl/`:

```
docker/nginx/ssl/
├── fullchain.pem
└── privkey.pem
```

Then open `docker/nginx/app.conf` and:

1. Uncomment the `return 301 https://...` redirect in the HTTP block
2. Uncomment the entire `# ── HTTPS Server Block` at the bottom
3. Replace `yourdomain.com` with your actual domain

To obtain a free certificate with Certbot (run on the host before starting Docker):

```bash
# Install certbot on Ubuntu/Debian
sudo apt install certbot

# Obtain certificate (port 80 must be free)
sudo certbot certonly --standalone -d yourdomain.com

# Copy certs into the project
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem   docker/nginx/ssl/
```

---

### Step 4 — Build and Start Containers

```bash
# Build images (first time or after code changes)
docker compose build --no-cache

# Start all services in the background
docker compose up -d
```

Check all containers are running:

```bash
docker compose ps
```

Expected output — all containers should show `running` or `Up`:

```
NAME                STATUS
laravel_app         Up
laravel_nginx       Up
laravel_db          Up (healthy)
laravel_redis       Up (healthy)
laravel_queue       Up
laravel_scheduler   Up
```

---

### Step 5 — Run Migrations

Wait for the database to be healthy, then run migrations:

```bash
docker compose exec app php artisan migrate --force
```

Create the storage symlink (once):

```bash
docker compose exec app php artisan storage:link
```

---

### Step 6 — Create the First Admin User

```bash
docker compose exec app php artisan tinker
```

```php
\App\Models\User::create([
    'name'        => 'Admin',
    'email'       => 'admin@yourdomain.com',
    'password'    => bcrypt('StrongPassword123!'),
    'role'        => 'administrator',
    'mfa_enabled' => false,
]);
exit
```

---

### Step 7 — Verify the Deployment

Visit `http://yourdomain.com` (or `https://` if SSL is configured). You should see the login page.

Check application health:

```bash
curl -s http://localhost/up
# Should return: {"status":"UP"}
```

---

### Updating the Application

```bash
# 1. Pull latest code
git pull origin main

# 2. Rebuild the app image
docker compose build --no-cache app queue scheduler

# 3. Restart with zero-downtime (rolling)
docker compose up -d --no-deps app queue scheduler

# 4. Run any new migrations
docker compose exec app php artisan migrate --force

# 5. Clear and rebuild caches
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
```

---

### Database Backup and Restore

**Backup:**

```bash
docker compose exec db mysqldump \
  -u root -p"${DB_ROOT_PASSWORD}" \
  --single-transaction --quick \
  "${DB_DATABASE}" > backup_$(date +%Y%m%d_%H%M%S).sql
```

**Restore:**

```bash
docker compose exec -T db mysql \
  -u root -p"${DB_ROOT_PASSWORD}" \
  "${DB_DATABASE}" < backup_20260101_120000.sql
```

---

### Useful Commands

```bash
# View logs (all services)
docker compose logs -f

# View logs for a specific service
docker compose logs -f app
docker compose logs -f webserver

# Open a shell inside the app container
docker compose exec app bash

# Clear all Laravel caches
docker compose exec app php artisan optimize:clear

# Rebuild config/route/view caches
docker compose exec app php artisan optimize

# Restart a single service without downtime
docker compose restart queue

# Stop everything
docker compose down

# Stop and delete all volumes (WARNING: destroys database)
docker compose down -v
```

---

### Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Container exits immediately | Missing or invalid `.env` values | Check `docker compose logs app` |
| 502 Bad Gateway | PHP-FPM not ready or crashed | `docker compose logs app` — check for PHP errors |
| Database connection refused | DB not healthy yet | Wait 30 s; entrypoint retries for 60 s |
| Migrations fail | `APP_KEY` not set | Set `APP_KEY` in `.env` and rebuild |
| Emails not sending | MAIL_* not configured | Verify SMTP settings in `.env` |
| Storage files not served | Missing storage symlink | `docker compose exec app php artisan storage:link` |
| Permission denied on storage | Wrong ownership | `docker compose exec app chown -R www-data:www-data storage bootstrap/cache` |

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/        # VulnAssessmentController, UserController, etc.
│   ├── Middleware/
│   └── Requests/
├── Models/                 # VulnAssessment, VulnTracked, VulnFinding, UserGroup, etc.
└── Services/               # VulnTrackingService, OsDetector, VulnClassifier

resources/views/
├── layouts/app.blade.php   # Sidebar layout
├── vuln_assessments/       # Findings, progress, show, create, reports
├── users/                  # User management
└── user-groups/            # Group management

docker/
├── nginx/app.conf          # Nginx site config (HTTP + HTTPS blocks)
├── nginx/ssl/              # SSL certificate files (not committed)
├── php/php.ini             # PHP production settings
├── php/opcache.ini         # OPcache tuning
├── mysql/my.cnf            # MySQL tuning
└── entrypoint.sh           # Container startup script (waits for DB, caches config)

Dockerfile                  # Multi-stage build: Composer deps → PHP-FPM image
docker-compose.yml          # Six-service production stack
.env.production             # Environment template for production
```

---

## User Roles

| Role | Permissions |
|---|---|
| **Administrator** | Full access — manage users, groups, assessments, findings, settings |
| **Assessor** | View, create, edit assessments and findings — no delete, no user management |

---

## License

Private — all rights reserved.

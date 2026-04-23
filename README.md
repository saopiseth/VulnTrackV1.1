# VulnTrack

A web-based vulnerability assessment and management platform built with Laravel 12. Designed for security teams to upload scan results, track findings across assessments, manage remediation workflows, and export professional reports.

---

## Features

| Area | Capabilities |
|---|---|
| **Scan Import** | Upload Nessus `.nessus` files; auto-track findings across multiple scans |
| **Finding Lifecycle** | Automatic status transitions: New → Open → Unresolved → Resolved → Reopened |
| **Severity** | Critical / High / Medium / Low per finding with CVSS score |
| **Assessment Scope** | IP/hostname groups linked to assessments for system-name resolution |
| **Remediation** | Per-finding status, assignee, due date, evidence, comments |
| **SLA Policies** | Configurable SLA per severity; due-date auto-calculation |
| **User Groups** | Bulk-assign findings to teams; per-group member management |
| **MFA** | Optional email OTP per user |
| **Dashboard** | Customisable per-user widget layout (drag, hide, reorder) |
| **Progress** | Severity trend charts, remediation doughnut, scan timeline |
| **Reports** | Export to PDF, Word, Excel with customisable branding |
| **Settings** | Theme color, logo, company name, report header/footer — all from the UI |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 / PHP 8.2 |
| Database | MySQL 8 |
| Cache · Queue · Session | Redis 7 |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Chart.js 4, SortableJS |
| PDF Export | barryvdh/laravel-dompdf |
| Web server | Nginx 1.25 (Alpine) |
| Process manager | Supervisor (php-fpm + queue workers + scheduler) |
| Containerisation | Docker + Docker Compose v2 |

---

## Architecture

Four containers on an isolated `vulntrack` bridge network. Only Nginx is exposed to the host.

```
┌─────────────────────────────────────────────────┐
│  Host (port 80)                                 │
│                                                 │
│  ┌──────────────┐      vulntrack network        │
│  │ vulntrack_   │  ┌─────────────────────────┐  │
│  │ nginx        │──▶ vulntrack_app            │  │
│  │ nginx:1.25   │  │ PHP 8.2-FPM             │  │
│  └──────────────┘  │ + Queue workers (x2)    │  │
│                    │ + Scheduler             │  │
│                    │ via Supervisor          │  │
│                    └────────┬────────────────┘  │
│                             │                   │
│                    ┌────────┴────────────────┐  │
│                    │ vulntrack_mysql          │  │
│                    │ mysql:8.0  (no host port)│  │
│                    └─────────────────────────┘  │
│                    ┌─────────────────────────┐  │
│                    │ vulntrack_redis          │  │
│                    │ redis:7-alpine (no port) │  │
│                    └─────────────────────────┘  │
└─────────────────────────────────────────────────┘
```

---

## Repository Structure

```
docker/
├── Dockerfile                  # Multi-stage: Composer deps → PHP 8.2-FPM + Supervisor
├── entrypoint.sh               # Waits for MySQL, runs migrate, warms cache, starts app
├── nginx/default.conf          # Nginx FastCGI config (app:9000)
├── supervisor/supervisord.conf # php-fpm + 2× queue workers + scheduler
├── php/php.ini                 # Upload limits, execution time, memory
├── php/opcache.ini             # OPcache tuning
└── mysql/my.cnf                # utf8mb4, InnoDB settings

app/
├── Http/Controllers/           # Feature controllers
├── Models/                     # Eloquent models
├── Services/                   # VulnTrackingService, OsDetector, VulnClassifier
└── Policies/                   # Gate policies

resources/views/
├── layouts/app.blade.php       # Sidebar shell (theme vars injected server-side)
├── vuln_assessments/           # Assessment pages + PDF/Word/Excel report templates
├── account/                    # Profile, settings (theme, logo, report branding)
└── dashboard.blade.php         # Customisable widget dashboard

docker-compose.yml              # 4-service stack
.env.docker                     # Local dev template  (git-ignored)
.env.internal                   # Internal server template (git-ignored)
.env.example                    # Committed reference with safe defaults
```

---

## Local Development (Docker)

### Requirements
- Docker Engine 24+ and Docker Compose v2

### Steps

```bash
# 1. Clone
git clone https://github.com/saopiseth/VulnTrackV1.0.git
cd VulnTrackV1.0

# 2. Generate APP_KEY (no PHP needed on host)
docker run --rm php:8.2-cli php -r \
  "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# 3. Set APP_KEY in .env.docker
nano .env.docker   # paste key, leave everything else as-is

# 4. Build and start (migrations run automatically)
ENV_FILE=.env.docker docker compose up -d --build

# 5. Create first admin user
docker compose exec app php artisan tinker --execute="
\App\Models\User::create([
    'name'        => 'Admin',
    'email'       => 'admin@localhost',
    'password'    => bcrypt('Admin1234!'),
    'role'        => 'administrator',
    'mfa_enabled' => false,
]);"
```

Open **http://localhost**

---

## Internal Server Deployment (Ubuntu 22.04)

### 1 — Install Docker on the server

```bash
sudo apt update && sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
  | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update && sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
sudo usermod -aG docker $USER && newgrp docker
```

Verify: `docker --version && docker compose version`

### 2 — Clone the repository

```bash
git clone https://github.com/saopiseth/VulnTrackV1.0.git
cd VulnTrackV1.0
```

### 3 — Configure environment

```bash
nano .env.internal
```

Minimum required changes:

```env
APP_URL=http://192.168.1.100        # your server's internal IP
APP_KEY=                            # generate below

DB_PASSWORD=YourStrongDbPass!
DB_ROOT_PASSWORD=YourStrongRootPass!
REDIS_PASSWORD=YourStrongRedisPass!

MAIL_HOST=192.168.1.10              # internal SMTP relay, or set MAIL_MAILER=log
```

Generate `APP_KEY`:

```bash
docker run --rm php:8.2-cli php -r \
  "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

### 4 — Build and start

```bash
ENV_FILE=.env.internal docker compose up -d --build
```

Watch startup logs until you see `[entrypoint] Booting: supervisord`:

```bash
docker compose logs -f app
```

### 5 — Verify

```bash
docker compose ps
```

```
NAME                STATUS
vulntrack_app       Up
vulntrack_nginx     Up (healthy)
vulntrack_mysql     Up (healthy)
vulntrack_redis     Up (healthy)
```

```bash
curl -s http://localhost/up    # → {"status":"UP"}
```

### 6 — Create first admin user

```bash
docker compose exec app php artisan tinker --execute="
\App\Models\User::create([
    'name'        => 'Admin',
    'email'       => 'admin@internal.local',
    'password'    => bcrypt('StrongPassword123!'),
    'role'        => 'administrator',
    'mfa_enabled' => false,
]);"
```

Access from any machine on the network: **http://\<server-ip\>**

---

## Operations

### Update

```bash
git pull origin main
ENV_FILE=.env.internal docker compose up -d --build --no-deps app
docker compose exec app php artisan migrate --force
```

### Database Backup

```bash
source .env.internal
docker compose exec mysql mysqldump \
  -uroot -p"${DB_ROOT_PASSWORD}" --single-transaction --quick \
  "${DB_DATABASE}" > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Database Restore

```bash
source .env.internal
docker compose exec -T mysql mysql \
  -uroot -p"${DB_ROOT_PASSWORD}" "${DB_DATABASE}" \
  < backup_20260101_120000.sql
```

### Common Commands

```bash
docker compose logs -f                              # all logs
docker compose logs -f app                          # app only
docker compose exec app bash                        # shell into app
docker compose exec app supervisorctl status        # queue/scheduler status
docker compose exec app supervisorctl restart laravel-queue:*   # restart workers
docker compose exec app php artisan optimize:clear  # clear all caches
docker compose exec app php artisan optimize        # rebuild caches
docker compose down                                 # stop (volumes kept)
docker compose down -v                              # stop + delete all data
```

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Container exits immediately | Invalid or missing env value | `docker compose logs app` |
| 502 Bad Gateway | PHP-FPM not running | `docker compose logs app` — check for PHP fatal errors |
| `SQLSTATE[HY000]` connection error | MySQL still initialising | Entrypoint retries 30× — wait 60 s or check `docker compose logs mysql` |
| Wrong URL in emails / redirects | `APP_URL` incorrect | Update `.env.internal` and rebuild app |
| Emails not delivered | No SMTP relay | Set `MAIL_MAILER=log` for log-based delivery |
| Uploaded files not accessible | Storage permissions | `docker compose exec app chown -R www-data:www-data storage` |
| `No application encryption key` | `APP_KEY` not set | Generate key, update env file, rebuild |

---

## User Roles

| Role | Access |
|---|---|
| **Administrator** | Full access — users, groups, assessments, findings, all settings |
| **Assessor** | Create and manage assessments and findings — no user management, no delete |

---

## License

Private — all rights reserved.

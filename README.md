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

#
git clone https://github.com/saopiseth/VulnTrackV1.0.git
#
cd VulnTrackV1.0
#
KEY=$(docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));")
sed -i "s|APP_KEY=|APP_KEY=${KEY}|" .env.docker
#
echo "Generated: $KEY"
#
ENV_FILE=.env.docker docker compose up -d --build

# 5. Create first admin user
docker compose exec app php artisan tinker --execute="
\App\Models\User::create([
    'name'        => 'Admin',
    'email'       => 'admin@localhost',
    'password'    => bcrypt(''),
    'role'        => 'administrator',
    'mfa_enabled' => false,
]);"

Open **http://localhost**

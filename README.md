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

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| PHP | 8.2 |
| Database | SQLite (local) / MySQL 8 (production) |
| Cache / Queue / Session | Redis (production) |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Chart.js 4 |
| PDF generation | barryvdh/laravel-dompdf |
| Web server | Nginx (Docker) |
| Containerisation | Docker + Docker Compose |

---

## Local Development Setup

### Requirements

- PHP 8.2+
- Composer 2
- SQLite (bundled with PHP)
- Node.js (optional, for Vite asset compilation)

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/saopiseth/VulnTrackV1.0.git
cd security-assessment

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Run migrations
php artisan migrate

# 6. Create the first admin user
php artisan tinker
```

Inside tinker:

```php
\App\Models\User::create([
    'name'        => 'Admin',
    'email'       => 'admin@example.com',
    'password'    => bcrypt('password'),
    'role'        => 'administrator',
    'mfa_enabled' => false,
]);
exit
```

```bash
# 7. Start the development server
php artisan serve
```

Visit `http://localhost:8000`.

---

## Production Deployment (Docker)

A full Docker-based production setup is included. See **[DEPLOYMENT.md](DEPLOYMENT.md)** for the complete step-by-step guide covering:

- Docker Compose services (app, webserver, db, redis, queue, scheduler)
- Environment configuration
- SSL / HTTPS with Let's Encrypt
- Database backup and restore
- Zero-downtime updates
- Troubleshooting

**Quick start:**

```bash
cp .env.production .env
# Fill in APP_KEY, DB_PASSWORD, REDIS_PASSWORD, APP_URL

docker compose build --no-cache
docker compose up -d
docker compose exec app php artisan migrate --force
```

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/        # VulnAssessmentController, UserController, etc.
│   ├── Middleware/
│   └── Requests/
├── Models/                 # VulnAssessment, VulnTracked, VulnFinding, UserGroup, etc.
├── Policies/               # Gate policies per model
└── Services/               # VulnTrackingService, OsDetector, VulnClassifier

resources/views/
├── layouts/app.blade.php   # Sidebar layout
├── vuln_assessments/       # Findings, progress, show, create
├── users/                  # User management
└── user-groups/            # Group management

docker/
├── nginx/app.conf          # Nginx site config
├── php/php.ini             # PHP production settings
├── php/opcache.ini         # OPcache config
├── mysql/my.cnf            # MySQL tuning
└── entrypoint.sh           # Container startup script
```

---

## User Roles

| Role | Permissions |
|---|---|
| **Administrator** | Full access — manage users, groups, assessments, findings, settings |
| **Assessor** | View, create, edit assessments and findings — no delete, no user management |

---

## Key Artisan Commands

```bash
# Clear and rebuild all caches
php artisan optimize:clear
php artisan optimize

# Run queue worker
php artisan queue:work --tries=3

# Run scheduled tasks
php artisan schedule:run
```

---

## License

Private — all rights reserved.

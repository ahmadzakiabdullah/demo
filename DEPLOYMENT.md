# Deployment Guide

# SportOS

Guide for deploying SportOS from local development (Laragon) to staging and production environments.

> Local dev still uses `demo.test` and database `demo`. Production uses SportOS branding and `sportos` database name.

---

## 1. Environments

| Environment | URL (example) | DB name | Purpose |
|-------------|---------------|---------|---------|
| Local | https://demo.test | `demo` | Development (Laragon) |
| Staging | https://staging.sportos.app | `sportos` | Pre-production testing |
| Production | https://sportos.app | `sportos` | Live platform |

---

## 2. System Requirements

### Application Server

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 8.3 | 8.4 |
| Extensions | mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, bcmath, redis | + imagick (PDF/QR) |
| Web server | Nginx or Apache | Nginx |
| Node.js | 20 LTS | 22 LTS (build only) |
| Composer | 2.x | Latest |

### Data Layer

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| MySQL | 8.0 | 8.0 managed (RDS, DigitalOcean) |
| Redis | 6.x | 7.x (cache, queue, sessions) |

### Workers

| Process | Command | Purpose |
|---------|---------|---------|
| Queue worker | `php artisan queue:work redis` | Async jobs (email, PDF, reports) |
| Scheduler | `php artisan schedule:run` (cron) | Scheduled tasks |
| Reverb (Phase 3+) | `php artisan reverb:start` | Live results WebSocket |

---

## 3. Local Development (Laragon)

Already configured. See [README.md](README.md).

**Critical:** DocumentRoot must be `D:/www/demo/public`.

```powershell
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev          # Terminal 1
php artisan serve    # Or use Laragon Apache
# Or: composer run dev  # All-in-one
```

---

## 4. Production Deployment Steps

### 4.1 Server Provisioning

Options: Laravel Forge, Ploi, DigitalOcean App Platform, AWS EC2, VPS.

Minimum: 2 vCPU, 4 GB RAM (scale horizontally for live events).

### 4.2 Clone & Install

```bash
git clone https://github.com/ahmadzakiabdullah/demo.git /var/www/sportos
cd /var/www/sportos
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 4.3 Environment Configuration

```env
APP_NAME=SportOS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sportos.app

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=sportos
DB_USERNAME=sportos
DB_PASSWORD=strong-password

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis-password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host

FILESYSTEM_DISK=s3   # or local for small deployments
AWS_BUCKET=sportos-media
```

### 4.4 Laravel Optimization

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 4.5 Nginx Configuration (Example)

```nginx
server {
    listen 443 ssl http2;
    server_name sportos.app;
    root /var/www/sportos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4.6 SSL

Use Let's Encrypt via Forge/Certbot. Force HTTPS redirect.

### 4.7 Supervisor (Queue Workers)

```ini
[program:sportos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sportos/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
```

### 4.8 Cron (Scheduler)

```cron
* * * * * cd /var/www/sportos && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. CI/CD Pipeline (Planned)

GitHub Actions workflow:

```yaml
# .github/workflows/ci.yml (planned)
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.4' }
      - run: composer install
      - run: npm ci && npm run build
      - run: php artisan test
```

Deploy to staging on `develop` merge; production on tagged release.

---

## 6. Database Backup Strategy

| Method | Frequency | Retention |
|--------|-----------|-----------|
| Automated mysqldump | Daily 02:00 UTC | 30 days |
| Point-in-time recovery | Continuous (managed DB) | 7 days |
| Pre-migration backup | Before every deploy | Until verified |

```bash
# Manual backup
mysqldump -u sportos -p sportos > backup_$(date +%Y%m%d).sql
```

---

## 7. Monitoring & Logging

| Tool | Purpose | Phase |
|------|---------|-------|
| Laravel logs | `storage/logs/laravel.log` | Now |
| Sentry | Error tracking | Phase 4 |
| Uptime monitor | `/up` health check | Phase 4 |
| Redis monitoring | Queue depth alerts | Phase 4 |

Health endpoint: `GET /up` returns `{"status":"ok"}`.

---

## 8. Scaling for Live Events

| Strategy | When |
|----------|------|
| Horizontal app servers | > 500 concurrent users |
| Redis cache for public results | Live event days |
| Read replica for MySQL | Heavy read load on public portal |
| CDN for static assets | Production |
| Dedicated queue workers | PDF/report generation spikes |

---

## 9. Rollback Procedure

1. Revert to previous Git tag: `git checkout v0.x.x`
2. `composer install --no-dev`
3. `php artisan migrate:rollback` (if schema changed)
4. `php artisan config:cache && php artisan route:cache`
5. Verify `/up` and smoke test login

---

## 10. Pre-Deploy Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` (never reuse from local)
- [ ] Database credentials secured
- [ ] Redis password set
- [ ] `npm run build` completed
- [ ] `php artisan test` passes
- [ ] Migrations tested on staging
- [ ] SSL certificate valid
- [ ] Backup verified
- [ ] Queue workers running
- [ ] Cron configured

---

## 11. Related Documents

| Document | Link |
|----------|------|
| README (local setup) | [README.md](README.md) |
| Architecture | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Security | [SECURITY.md](SECURITY.md) |
| Roadmap | [ROADMAP.md](ROADMAP.md) |
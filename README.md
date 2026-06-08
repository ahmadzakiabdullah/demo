# SportOS

**The Operating System for Sports Management**

Enterprise-grade, multi-tenant sports management platform built with Laravel 13, React, Inertia.js, and shadcn/ui.

| Item | Details |
|------|---------|
| Product | SportOS |
| Local URL | https://demo.test |
| Framework | Laravel 13.14 |
| PHP | 8.4 (Laragon) |
| Database | MySQL 8.0.30 |
| Frontend | React 18 + Inertia.js |
| UI | shadcn/ui + Tailwind CSS 4 |
| Repository | [github.com/ahmadzakiabdullah/demo](https://github.com/ahmadzakiabdullah/demo) |
| Pilot target | University sports carnival (Malaysia) |

> **Note:** Product is **SportOS**; local folder/URL/DB still use `demo` until Phase 1 rebrand. See [DOCUMENTATION.md](DOCUMENTATION.md).

## Documentation

| File | Contents |
|------|----------|
| [DOCUMENTATION.md](DOCUMENTATION.md) | **Start here** — master index & maintenance |
| [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) | Project overview & status |
| [PRD.md](PRD.md) | Product Requirement Document |
| [BRD.md](BRD.md) | Business Requirement Document |
| [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) | Functional specification |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Technical architecture |
| [DATABASE.md](DATABASE.md) | Database design + ERD |
| [API.md](API.md) | API specification |
| [UI_UX.md](UI_UX.md) | UI/UX guidelines |
| [SECURITY.md](SECURITY.md) | Security guidelines |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Deployment guide |
| [TESTING.md](TESTING.md) | Testing strategy |
| [AI_GOVERNANCE.md](AI_GOVERNANCE.md) | AI governance |
| [ROADMAP.md](ROADMAP.md) | Development roadmap |
| [MODULES.md](MODULES.md) | Modules & components |
| [AGENTS.md](AGENTS.md) | Instructions for AI agents |
| [CHANGELOG.md](CHANGELOG.md) | Change history |

## Requirements

- [Laragon](https://laragon.org/) (Full version recommended)
- PHP 8.3 / 8.4
- Composer
- Node.js 20+
- Git
- MySQL 8

## Local Setup (Laragon)

### 1. Clone

```powershell
git clone https://github.com/ahmadzakiabdullah/demo.git D:\www\demo
cd D:\www\demo
```

### 2. Install Dependencies

```powershell
composer install
npm install
```

### 3. Environment

```env
APP_URL=https://demo.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demo
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database & Migrations

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan key:generate
php artisan migrate
```

### 5. Laragon vhost (IMPORTANT)

DocumentRoot **must** be `D:/www/demo/public` — not the project root.

1. Laragon → right-click `demo.test` → Open vhost file
2. Set `DocumentRoot "D:/www/demo/public"`
3. Restart Laragon

### 6. Run

```powershell
npm run dev          # Terminal 1 — Vite HMR
# Browse: https://demo.test

# Or all-in-one:
composer run dev
```

## Useful Commands

```powershell
php artisan migrate
php artisan test
php artisan optimize:clear
npm run dev
npm run build
npx shadcn@latest add <component>
composer run dev
```

## Folder Structure

```
demo/
├── app/
│   ├── Http/Controllers/    # Web + Admin controllers
│   ├── Models/
│   ├── Policies/
│   └── Services/            # (planned) Bracket, Scheduling, etc.
├── database/migrations/
├── resources/js/
│   ├── Pages/               # Inertia React pages
│   ├── Layouts/
│   └── Components/ui/     # shadcn/ui (import: @/components/ui/)
├── routes/
│   ├── web.php
│   └── auth.php
└── tests/Feature/
```

## Current Features

- Full authentication (login, register, profile, password reset, email verification)
- Admin user management (CRUD, search, role filter) — admin only
- shadcn/ui across all pages
- 35 passing tests

## Roadmap

See [ROADMAP.md](ROADMAP.md). Current phase: **Phase 1 — Foundation** (organizations, RBAC, events, API v1).

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for staging and production guide.

## Troubleshooting

1. Ensure Laragon MySQL is running
2. DocumentRoot must point to `/public`
3. Run `php artisan optimize:clear`
4. Check `storage/logs/laravel.log`
5. Run `npm run build` before tests that render Inertia pages

---

**SportOS** — Prepared for local development using Laragon
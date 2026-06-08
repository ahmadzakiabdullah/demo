# Architecture

System architecture of the **Demo** project — Laravel 13 monolith with Inertia.js + React + shadcn/ui.

## Overview

```
┌─────────────────────────────────────────────────────────┐
│                     Browser / Client                     │
└─────────────────────────┬───────────────────────────────┘
                          │ HTTPS
                          ▼
┌─────────────────────────────────────────────────────────┐
│              Laragon (Apache/Nginx + PHP 8.4)            │
│                   DocumentRoot: /public                  │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│                   Laravel Application                    │
│  ┌──────────┐  ┌────────────┐  ┌─────────────────────┐  │
│  │  Routes  │→ │ Middleware │→ │    Controllers      │  │
│  └──────────┘  └────────────┘  └──────────┬──────────┘  │
│                                           │              │
│                              ┌────────────▼──────────┐  │
│                              │   Inertia Responses   │  │
│                              └────────────┬──────────┘  │
└───────────────────────────────────────────┼─────────────┘
                                            │ JSON + page name
                                            ▼
┌─────────────────────────────────────────────────────────┐
│              React SPA (client-side via Inertia)         │
│  ┌──────────┐  ┌────────────┐  ┌─────────────────────┐  │
│  │  Pages   │→ │  Layouts   │→ │  shadcn/ui Components│  │
│  └──────────┘  └────────────┘  └─────────────────────┘  │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│                    MySQL 8.0 (demo)                      │
│   users · sessions · cache · jobs · password_reset_*    │
└─────────────────────────────────────────────────────────┘
```

## Architecture Pattern

| Aspect | Choice | Notes |
|--------|--------|-------|
| Style | Monolith MVC + SPA hybrid | Laravel backend, React frontend via Inertia |
| Frontend | React 18 + Inertia.js | No separate API for page rendering |
| UI library | shadcn/ui + Base UI | Copy-paste components, Tailwind 4 |
| API | Not implemented | Sanctum installed for future REST API |
| Auth | Laravel Breeze | Session-based, full auth flow |
| Queue | Database driver | Tables `jobs`, `failed_jobs` |
| Cache | Database driver | Tables `cache`, `cache_locks` |
| Session | Database driver | Table `sessions` |

## Request Lifecycle

1. Request enters `public/index.php`.
2. Bootstrap via `bootstrap/app.php`.
3. Router matches route from `routes/web.php`.
4. Middleware pipeline: `HandleInertiaRequests`, auth, verified, etc.
5. Controller returns `Inertia::render('PageName', $props)`.
6. `app.blade.php` loads Vite assets and mounts React via `app.jsx`.
7. Inertia resolves and renders the matching React page component.

## Application Layers

### Presentation (React)

- **Pages**: `resources/js/Pages/` — Inertia page components
- **Layouts**: `resources/js/Layouts/` — Guest and authenticated layouts
- **UI components**: `resources/js/components/ui/` — shadcn/ui
- **Legacy components**: `resources/js/Components/` — Breeze defaults (being migrated)
- **Styles**: `resources/css/app.css` — Tailwind 4 + shadcn theme
- **Entry**: `resources/js/app.jsx` — Inertia bootstrap

### Application (Laravel)

- **Routes**: `routes/web.php`, `routes/auth.php`
- **Controllers**: `app/Http/Controllers/Auth/`, `ProfileController`
- **Middleware**: `HandleInertiaRequests` shares auth data to React
- **Form requests**: `LoginRequest`, `ProfileUpdateRequest`

### Domain / Data

- **Models**: `app/Models/User.php`
- **Migrations**: `database/migrations/`
- **Factories & Seeders**: `database/factories/`, `database/seeders/`

### Infrastructure

| Component | Driver | Config location |
|-----------|--------|-----------------|
| Database | MySQL | `config/database.php`, `.env` |
| Cache | Database | `CACHE_STORE=database` |
| Session | Database | `SESSION_DRIVER=database` |
| Queue | Database | `QUEUE_CONNECTION=database` |
| Mail | Log | `MAIL_MAILER=log` |
| Filesystem | Local | `FILESYSTEM_DISK=local` |

## Routing

| Route | Method | Handler | Auth |
|-------|--------|---------|------|
| `/` | GET | `Welcome` (Inertia) | No |
| `/dashboard` | GET | `Dashboard` (Inertia) | Yes + verified |
| `/profile` | GET/PATCH/DELETE | `ProfileController` | Yes |
| `/login`, `/register`, etc. | — | Auth controllers | Mixed |
| `/up` | GET | Health check | No |

Auth routes are in `routes/auth.php`.

## Frontend Build Pipeline

```
resources/js/app.jsx ──────┐
resources/js/Pages/**/*.jsx ┼──► Vite + React plugin ──► public/build/
resources/css/app.css ─────┘         │
                                     ├── @tailwindcss/vite
                                     ├── @vitejs/plugin-react
                                     └── laravel-vite-plugin
```

Development: `npm run dev` (HMR)  
Production: `npm run build`

Path alias: `@/` → `resources/js/` (configured in `vite.config.js` and `jsconfig.json`).

## Security

| Control | Status |
|---------|--------|
| CSRF protection | Active |
| Session auth (Breeze) | Active |
| Password hashing | `bcrypt` via Eloquent cast |
| Email verification | Available (Breeze) |
| Local HTTPS | Laragon SSL (optional) |
| Sanctum (API tokens) | Installed, not configured |
| Rate limiting | Not yet |

## Key Files

| File | Role |
|------|------|
| `bootstrap/app.php` | App bootstrap, routing, middleware |
| `routes/web.php` | Web routes |
| `routes/auth.php` | Authentication routes |
| `resources/views/app.blade.php` | Inertia root template |
| `resources/js/app.jsx` | React/Inertia entry |
| `components.json` | shadcn/ui CLI config |
| `vite.config.js` | Vite + React + Tailwind + aliases |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared Inertia props |
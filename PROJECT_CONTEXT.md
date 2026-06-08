# Project Context

A brief overview of the **Demo** project for developers and AI agents.

## Summary

| Item | Value |
|------|-------|
| Name | Demo |
| Type | Web application (Laravel monolith + React SPA via Inertia) |
| Status | Phase 1 complete — full auth + shadcn/ui |
| Local URL | https://demo.test |
| Location | `D:\www\demo` |
| Git | Not initialized |

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.4, Laravel 13.14 |
| Frontend | React 18, Inertia.js 2 |
| UI | shadcn/ui 4 (Base UI + Tailwind CSS 4) |
| Database | MySQL 8.0.30 (local Laragon) |
| Build | Vite 8 |
| Auth | Laravel Breeze (session-based) |
| API (future) | Laravel Sanctum (installed) |
| Testing | PHPUnit 12 (25 tests) |

## Current State

### What exists

- Laravel 13 + Breeze with full authentication (login, register, profile, password reset, email verification).
- Inertia.js + React frontend with 12+ page components.
- shadcn/ui initialized with Button, Input, Label, Card, Checkbox.
- All pages migrated to shadcn/ui (auth, dashboard, profile, layouts).
- 25 passing feature/unit tests.

### What does not exist yet

- REST API endpoints.
- Admin user management.
- Production deployment.

## Important Configuration

### Environment (`.env`)

```env
APP_URL=https://demo.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demo
DB_USERNAME=root
DB_PASSWORD=
```

### Laragon vhost

DocumentRoot **must** point to `D:/www/demo/public`.

### Frontend dev

```powershell
npm run dev        # Vite HMR
composer run dev   # Full stack (server + queue + logs + vite)
```

## Related Documentation

| File | Contents |
|------|----------|
| [README.md](README.md) | Setup & daily commands |
| [UI_UX.md](UI_UX.md) | shadcn/ui guidelines |
| [ARCHITECTURE.md](ARCHITECTURE.md) | System architecture |
| [MODULES.md](MODULES.md) | Modules & components |
| [ROADMAP.md](ROADMAP.md) | Development plan |
| [AGENTS.md](AGENTS.md) | Instructions for AI agents |

## Conventions

- Code language: **English**.
- Documentation: **English**.
- New UI: **shadcn/ui** components (`npx shadcn@latest add <name>`).
- Legacy Breeze components in `Components/` — migrate, don't extend.
- Path alias: `@/` → `resources/js/`.
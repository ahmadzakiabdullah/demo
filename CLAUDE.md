# CLAUDE.md

Quick instructions for Claude (and similar AI agents) working on the **Demo** project.

> For full instructions, see [AGENTS.md](AGENTS.md). This file is a quick reference.

## Project

- **Demo** — Laravel 13 web app, local at `https://demo.test` via Laragon
- **Status:** Breeze auth + Inertia/React + shadcn/ui (Phase 1 in progress)
- **DB:** MySQL `demo` (root, no password)
- **Path:** `D:\www\demo`, web root `public/`

## Before Coding

1. Read [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md)
2. Check [ROADMAP.md](ROADMAP.md) for priorities
3. Check [DATABASE.md](DATABASE.md) for existing schema

## Key Rules

- Use `php artisan make:*` to scaffold
- New UI: shadcn/ui (`npx shadcn@latest add <component>`)
- Pages are React/Inertia in `resources/js/Pages/`
- Do not extend Breeze legacy `Components/` — use shadcn instead
- Migrations for all DB changes
- Do not edit `vendor/` or `public/build/`
- Code and documentation in **English**
- Run `php artisan test` after changes
- Update `.md` docs if architecture/API/DB/UI changes

## Laragon

Vhost DocumentRoot **must** be `D:/www/demo/public` — not the project root.

## Quick Commands

```powershell
php artisan migrate
php artisan test
php artisan optimize:clear
npm run dev
composer run dev
```

## Reference Files

| Topic | File |
|-------|------|
| Setup | [README.md](README.md) |
| Architecture | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Modules | [MODULES.md](MODULES.md) |
| Database | [DATABASE.md](DATABASE.md) |
| API | [API.md](API.md) |
| UI | [UI_UX.md](UI_UX.md) |
| Plan | [ROADMAP.md](ROADMAP.md) |
| Changelog | [CHANGELOG.md](CHANGELOG.md) |
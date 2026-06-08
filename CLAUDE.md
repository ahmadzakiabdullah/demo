# CLAUDE.md

Quick reference for AI agents working on **SportOS**.

> Full instructions: [AGENTS.md](AGENTS.md)

## Project

- **SportOS** — *The Operating System for Sports Management*
- Laravel 13 + React + Inertia + shadcn/ui at `https://demo.test`
- **Phase:** 1 — Foundation (partial; auth + admin users done)
- **DB:** MySQL `demo` · **Path:** `D:\www\demo`

## Before Coding

1. [DOCUMENTATION.md](DOCUMENTATION.md) — naming, spec vs code
2. [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md)
3. [ROADMAP.md](ROADMAP.md) — check current phase
4. [DATABASE.md](DATABASE.md) — schema
5. [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) — requirements

## Key Rules

- **shadcn/ui only** for UI — no other component libraries
- Multi-tenant: all domain tables need `organization_id` (when built)
- API-first: build API resources alongside Inertia pages
- Migrations for all DB changes
- Do not delete owner: `ahmadzaki@utem.edu.my`
- Run `php artisan test` + `npm run build` before finishing
- Update `.md` docs when architecture/API/DB/UI changes

## Quick Commands

```powershell
php artisan migrate
php artisan test
npm run build
npm run dev
composer run dev
```

## Documentation

| Topic | File |
|-------|------|
| Doc index | [DOCUMENTATION.md](DOCUMENTATION.md) |
| Vision & status | [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) |
| Product requirements | [PRD.md](PRD.md) |
| Business requirements | [BRD.md](BRD.md) |
| Functional spec | [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) |
| Architecture | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Database + ERD | [DATABASE.md](DATABASE.md) |
| API spec | [API.md](API.md) |
| UI/UX | [UI_UX.md](UI_UX.md) |
| Security | [SECURITY.md](SECURITY.md) |
| Deployment | [DEPLOYMENT.md](DEPLOYMENT.md) |
| Testing | [TESTING.md](TESTING.md) |
| AI governance | [AI_GOVERNANCE.md](AI_GOVERNANCE.md) |
| Roadmap | [ROADMAP.md](ROADMAP.md) |
| Modules | [MODULES.md](MODULES.md) |
| Setup | [README.md](README.md) |
| Changelog | [CHANGELOG.md](CHANGELOG.md) |
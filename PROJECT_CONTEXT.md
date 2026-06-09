# Project Context

A brief overview of **SportOS** for developers and AI agents.

## Summary

| Item | Value |
|------|-------|
| Name | **SportOS** |
| Tagline | *The Operating System for Sports Management* |
| Type | Enterprise multi-tenant sports management SaaS |
| Status | Phase 1 — Foundation (partial) |
| Local URL | https://demo.test |
| Location | `D:\www\demo` |
| Git | [github.com/ahmadzakiabdullah/demo](https://github.com/ahmadzakiabdullah/demo) |
| Docs | [DOCUMENTATION.md](DOCUMENTATION.md) — master index |

## Naming Note

- **Product name:** SportOS (all documentation and future UI)
- **Codebase paths:** still `demo` (folder, URL, database) until Phase 1 rebrand
- See [DOCUMENTATION.md](DOCUMENTATION.md) for full naming table

## Vision

SportOS is a comprehensive multi-tenant platform for sports organizations at all levels — from school sports day to international multi-sport games. It manages organizations, events, sports, competitions, athletes, teams, officials, venues, scheduling, results, rankings, medals, accreditation, and analytics from a single unified system.

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.4, Laravel 13.14 |
| Frontend | React 18, Inertia.js 2 |
| UI | shadcn/ui only + Tailwind CSS 4 |
| Database | MySQL 8.0.30 (local Laragon) |
| Cache / Queue | Redis (planned; database driver now) |
| Build | Vite 8 |
| Auth (web) | Laravel Breeze (session-based) |
| Auth (API) | Laravel Sanctum (planned `/api/v1/`) |
| Testing | PHPUnit 12 (35 tests passing) |

## Design Principles

Modular · Scalable · Multi-Tenant · API First · Mobile Ready · Cloud Ready · AI Ready · Secure by Design · Audit Compliant · Accessibility Compliant

## Pilot Target

First production use case: **university sports carnival** (e.g. UTeM inter-faculty games), then school/state (MSSM-style), then national/international scale.

Defaults: timezone `Asia/Kuala_Lumpur`, locale `en`.

## Unified Competition Flow

**Same workflow for SAF, SUKMA, and SEA Games** — only participant labels change:

| Step | Action |
|------|--------|
| 1 | Pilih / cipta **Event** |
| 2 | Pilih / cipta **Sukan/Acara** |
| 3 | Daftar **Participant** (fakulti / negeri / negara) |
| 4 | Participant **pilih sukan** yang disertai |
| 5 | Daftar **atlet & pasukan** |
| 6 | **Jadual** → pertandingan → keputusan → pingat |

- **Organization** = SaaS tenant (UTeM, MSN) — not shown as competing units in org switcher
- **Event participant** = competing unit — fakulti, negeri, or negara

Spec: [FUNCTIONAL_SPEC.md §0](FUNCTIONAL_SPEC.md#0-unified-competition-lifecycle-event-first)

## Development Layers

| Layer | Status |
|-------|--------|
| **Bootstrap** (Laravel, Breeze, shadcn, admin users) | Complete |
| **SportOS Phase 1** (orgs, RBAC, events, API v1) | In progress |
| **SportOS Phases 2–6** | Not started |

**MVP** = Phases 1–3 (foundation + sports registration + competition engine).

## Current State

### Implemented

- Laravel 13 + Breeze with full authentication (login, register, profile, password reset, email verification)
- Inertia.js + React frontend with shadcn/ui (all pages migrated)
- **RBAC** — `roles`, `permissions`, `role_permission`, `role_user`; org-scoped roles via `organization_user.role_id`
- Admin user CRUD with system role assignment (`system_owner` / member)
- **Organizations module** — CRUD, branches, `organization_user` pivot, UTeM pilot seeder
- **Audit logs** — append-only `audit_logs`, `Auditable` trait, admin activity UI
- **Events module** — CRUD, lifecycle, dashboard, team assignments (`event_user`)
- Permission-based policies, admin middleware, 66 passing PHPUnit tests total
- Git repository connected to GitHub

### Not Yet Built

- Sports, competitions, athletes, teams
- REST API `/api/v1/`
- Redis, public portal, AI layer

## Multi-Tenancy Model (Target)

```
Organization (tenant)
└── Event
    ├── Sports / Acara
    ├── Event Participants (fakulti · negeri · negara)
    │   └── Sport Entries
    ├── Athletes & Teams
    └── Competitions → Results → Medals
```

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

## Protected Account

Do not modify or delete: **Ahmad Zaki Abdullah** (`ahmadzaki@utem.edu.my`, `admin`).

## Documentation Index

> Full guide: [DOCUMENTATION.md](DOCUMENTATION.md)

| File | Contents |
|------|----------|
| [DOCUMENTATION.md](DOCUMENTATION.md) | Master index, naming, maintenance |
| [README.md](README.md) | Setup & daily commands |
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
| [CLAUDE.md](CLAUDE.md) | Quick reference |
| [CHANGELOG.md](CHANGELOG.md) | Change history |

## Conventions

- Code language: **English**
- Documentation: **English**
- New UI: **shadcn/ui only** — no Bootstrap, MUI, Ant Design, Chakra
- Path alias: `@/` → `resources/js/`
- All schema changes via migrations
- Do not modify or delete the project owner account (`ahmadzaki@utem.edu.my`)
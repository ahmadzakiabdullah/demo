# Technical Architecture

# SportOS

System architecture for the SportOS enterprise sports management platform.

---

## 1. Executive Summary

SportOS is a Laravel monolith with a React SPA frontend (via Inertia.js), designed as a multi-tenant, API-first, cloud-ready sports management platform. The architecture supports modular growth from a single-organization deployment to a global SaaS serving thousands of concurrent events.

---

## 2. System Overview

```
┌──────────────────────────────────────────────────────────────────────┐
│                         Clients                                       │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌────────────┐ │
│  │ Web Admin   │  │ Public      │  │ Mobile App  │  │ External   │ │
│  │ (Inertia)   │  │ Portal      │  │ (API v1)    │  │ Systems    │ │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └─────┬──────┘ │
└─────────┼────────────────┼────────────────┼───────────────┼─────────┘
          │                │                │               │
          ▼                ▼                ▼               ▼
┌──────────────────────────────────────────────────────────────────────┐
│                    Load Balancer / CDN (production)                   │
└──────────────────────────────┬───────────────────────────────────────┘
                               │ HTTPS
                               ▼
┌──────────────────────────────────────────────────────────────────────┐
│                   Laravel Application (PHP 8.4)                       │
│  ┌──────────┐  ┌────────────┐  ┌────────────┐  ┌─────────────────┐ │
│  │ Routes   │→ │ Middleware │→ │ Controllers│→ │ Services/Actions│ │
│  │web+api   │  │ auth,tenant│  │ Admin,Api  │  │ Bracket,Ranking │ │
│  └──────────┘  └────────────┘  └─────┬──────┘  └────────┬────────┘ │
│                                      │                    │          │
│                         ┌────────────▼────────────────────▼──────┐  │
│                         │         Eloquent Models + Policies      │  │
│                         └────────────┬────────────────────────────┘  │
└──────────────────────────────────────┼──────────────────────────────┘
          │                              │
          ▼                              ▼
┌──────────────────┐          ┌──────────────────┐
│  MySQL 8         │          │  Redis            │
│  Primary data    │          │  Cache, queue,    │
│                  │          │  sessions (prod)  │
└──────────────────┘          └──────────────────┘
```

---

## 3. Architecture Pattern

| Aspect | Choice | Notes |
|--------|--------|-------|
| Style | Modular monolith | Single deployable unit; modules as bounded contexts |
| Frontend (admin) | React 18 + Inertia.js | Server-driven routing; no separate SPA build for admin |
| Frontend (public) | React + Inertia (Phase 5) | Public portal pages |
| UI library | shadcn/ui only | No third-party component kits |
| API | REST `/api/v1/` (planned) | Sanctum token auth; mobile-ready |
| Auth (web) | Laravel Breeze | Session-based — active |
| Auth (API) | Laravel Sanctum | Bearer tokens — planned |
| Multi-tenancy | Organization-scoped | `organization_id` on all domain tables |
| Queue | Redis (prod) / Database (local) | Async: email, PDF, reports, AI |
| Real-time | Laravel Reverb (Phase 3) | Live results WebSocket |

---

## 4. Multi-Tenancy Architecture

```
System Owner (global, no org scope)
    │
    ├── Organization A (tenant)
    │   ├── Users (via organization_user pivot)
    │   ├── Events
    │   │   ├── Sports → Competitions → Matches → Results
    │   │   ├── Athletes, Teams, Officials
    │   │   └── Venues, Accreditations
    │   └── Audit Logs (org-scoped)
    │
    └── Organization B (tenant)
        └── ... (isolated data)
```

**Enforcement:**
- `OrganizationScope` global Eloquent scope (planned)
- `organization_id` foreign key on all domain tables
- Middleware validates user's org membership per request
- Policies check org + event + role permissions

---

## 5. Request Lifecycle

### 5.1 Web (Inertia)

1. Request → `public/index.php`
2. Bootstrap → `bootstrap/app.php`
3. Router → `routes/web.php` or `routes/auth.php`
4. Middleware: `HandleInertiaRequests`, `auth`, `admin` (or future `permission`)
5. Controller → `Inertia::render('Page', $props)`
6. `app.blade.php` → Vite assets → React page

### 5.2 API (Planned)

1. Request → `routes/api.php` prefix `/api/v1/`
2. Middleware: `auth:sanctum`, `throttle:api`, org scope
3. Controller → `JsonResource` response
4. Error format: Laravel JSON errors

---

## 6. Application Layers

### 6.1 Presentation

| Layer | Location | Technology |
|-------|----------|------------|
| Admin pages | `resources/js/Pages/Admin/` | Inertia + React + shadcn |
| Auth pages | `resources/js/Pages/Auth/` | Inertia + React + shadcn |
| Public pages | `resources/js/Pages/Public/` (planned) | Inertia + React + shadcn |
| Layouts | `resources/js/Layouts/` | Guest, Authenticated, Admin (planned) |
| UI components | `resources/js/Components/ui/` | shadcn/ui only; import `@/components/ui/` |
| Styles | `resources/css/app.css` | Tailwind 4 + CSS variables |

### 6.2 Application

| Layer | Location |
|-------|----------|
| Web controllers | `app/Http/Controllers/Admin/` |
| API controllers | `app/Http/Controllers/Api/V1/` (planned) |
| Form requests | `app/Http/Requests/` |
| Middleware | `app/Http/Middleware/` |
| Policies | `app/Policies/` |
| API resources | `app/Http/Resources/` (planned) |

### 6.3 Domain / Services

| Layer | Location |
|-------|----------|
| Models | `app/Models/` |
| Enums | `app/Enums/` (planned) |
| Actions | `app/Actions/` (planned) |
| Services | `app/Services/Bracket/`, `Scheduling/`, `Ranking/` (planned) |
| Observers | `app/Observers/` (planned — audit) |

### 6.4 Infrastructure

| Component | Local | Production |
|-----------|-------|------------|
| Database | MySQL `demo` | Managed MySQL |
| Cache | Database | Redis |
| Session | Database | Redis |
| Queue | Database | Redis |
| Files | Local disk | S3 / compatible |
| Mail | Log | SMTP / SES |

---

## 7. Module Architecture

```
┌─────────────────────────────────────────────────────────┐
│                      Core Module                         │
│  Organizations · Branches · Users · RBAC · Audit        │
└────────────────────────┬────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        ▼                ▼                ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Event Module │ │ Sports Module│ │ Venue Module │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                │
       ▼                ▼                ▼
┌─────────────────────────────────────────────────────────┐
│              Competition Engine (Phase 3)                  │
│  Fixtures · Matches · Results · Rankings · Medals        │
└────────────────────────┬────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        ▼                ▼                ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Operations   │ │ Public Portal│ │ AI Layer     │
│ Accred, Cert │ │ Live Results │ │ Scheduling   │
│ Reports      │ │ Rankings     │ │ Analytics    │
└──────────────┘ └──────────────┘ └──────────────┘
```

---

## 8. API Architecture

| Aspect | Standard |
|--------|----------|
| Base URL | `/api/v1/` |
| Auth | `Authorization: Bearer {sanctum_token}` |
| Format | JSON |
| Versioning | URL prefix; breaking changes → v2 |
| Pagination | `?page=1&per_page=25` |
| Filtering | Query params per resource |
| Errors | `{ "message": "...", "errors": {} }` |
| Future | GraphQL-ready service layer separation |

See [API.md](API.md) for endpoint specification.

---

## 9. Security Architecture

| Layer | Control |
|-------|---------|
| Transport | HTTPS (production) |
| Authentication | Session (web) + Sanctum (API) |
| Authorization | RBAC policies + org scope |
| Input | Form requests + validation rules |
| Output | API Resources exclude sensitive fields |
| Audit | Append-only audit_logs |
| Rate limiting | Login, API endpoints |

See [SECURITY.md](SECURITY.md).

---

## 10. Repository Structure (Target)

```
app/
├── Actions/
├── Enums/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Api/V1/
│   │   └── Public/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
│   ├── Concerns/       # BelongsToOrganization, Auditable
│   └── Scopes/         # OrganizationScope
├── Policies/
├── Services/
│   ├── Bracket/
│   ├── Scheduling/
│   ├── Ranking/
│   └── Accreditation/
└── Observers/

resources/js/
├── Pages/
│   ├── Admin/
│   ├── Auth/
│   ├── Public/
│   └── Profile/
├── Layouts/
└── Components/ui/            # import as @/components/ui/

routes/
├── web.php
├── api.php         (planned)
├── auth.php
└── public.php      (planned)

database/
├── migrations/
├── seeders/
└── factories/

tests/
├── Unit/
├── Feature/
│   ├── Admin/
│   └── Api/V1/
└── Browser/        (planned)
```

---

## 11. Current Implementation Status

| Component | Status |
|-----------|--------|
| Laravel 13 monolith | Active |
| Inertia + React + shadcn | Active |
| Session auth (Breeze) | Active |
| Binary admin/user roles | Active (to be upgraded) |
| Admin user CRUD | Active |
| Multi-tenancy | Not started |
| API v1 | Not started |
| Redis | Not started |
| Competition engine | Not started |

---

## 12. Key Files (Current)

| File | Role |
|------|------|
| `bootstrap/app.php` | Routing, middleware aliases |
| `routes/web.php` | Web + admin routes |
| `routes/auth.php` | Authentication routes |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared Inertia props |
| `app/Http/Middleware/EnsureUserIsAdmin.php` | Admin gate |
| `resources/views/app.blade.php` | Inertia root template |
| `resources/js/app.jsx` | React entry |
| `components.json` | shadcn CLI config |

---

## 13. Related Documents

| Document | Link |
|----------|------|
| Database design | [DATABASE.md](DATABASE.md) |
| API spec | [API.md](API.md) |
| UI architecture | [UI_UX.md](UI_UX.md) |
| Security | [SECURITY.md](SECURITY.md) |
| Deployment | [DEPLOYMENT.md](DEPLOYMENT.md) |
| Roadmap | [ROADMAP.md](ROADMAP.md) |
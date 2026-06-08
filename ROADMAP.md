# SportOS — Development Roadmap

> **The Operating System for Sports Management**

Enterprise-grade, multi-tenant sports management platform — from school sports day to international multi-sport games.

| Item | Value |
|------|-------|
| Product | **SportOS** |
| Repository | [github.com/ahmadzakiabdullah/demo](https://github.com/ahmadzakiabdullah/demo) |
| Local URL | https://demo.test |
| Stack | Laravel 13 · MySQL · Redis (planned) · React · Inertia · shadcn/ui |
| Current Phase | **Phase 4 — Operations** (next) |
| Documentation | **Specification complete** — see [DOCUMENTATION.md](DOCUMENTATION.md) |
| MVP | Phases 1–3 (foundation + registration + competition engine) |
| Pilot | University sports carnival first (e.g. UTeM) |

---

## Executive Summary

SportOS is a modular, scalable, multi-tenant SaaS platform for managing sports organizations, events, competitions, athletes, officials, venues, results, rankings, medals, accreditation, and analytics from a single unified system.

The platform targets organizations at every level — international federations, national associations, universities, schools, clubs, and corporate sports — and must scale from a local tournament to concurrent global events.

**Design principles:** Modular · Scalable · Multi-Tenant · API First · Mobile Ready · Cloud Ready · AI Ready · Secure by Design · Audit Compliant · Accessibility Compliant.

**UI policy:** [shadcn/ui](https://github.com/shadcn-ui/ui) only. No Bootstrap, Material UI, Ant Design, Chakra UI, or Tailwind UI component kits.

---

## System Overview

### Multi-Tenancy Hierarchy

```
Organization (tenant)
└── Event
    └── Sport
        └── Competition
```

Each organization has isolated data. Users, roles, and permissions are scoped per organization (and optionally per event).

### Target Users

| Role | Scope | Primary Responsibilities |
|------|-------|--------------------------|
| System Owner | Global | Platform administration, tenant provisioning |
| Organization Administrator | Organization | Association / federation management |
| Event Organizer | Event | Competition lifecycle, scheduling |
| Sports Manager | Sport | Discipline rules, categories, divisions |
| Team Manager | Team | Registration, lineup, transfers |
| Athlete | Self | Profile, registration, participation history |
| Official | Event | Referee / judge assignments, score entry |
| Volunteer | Event | Operations support |
| Media | Event | Galleries, press access |
| Public User | Public | Results, rankings, event pages |

### Functional Modules (Full Product)

| Module | Description | Target Phase |
|--------|-------------|--------------|
| **Core** | Organizations, branches, users, roles, permissions, audit logs | Phase 1 |
| **Event** | Event creation, categories, types, lifecycle, status | Phase 1 |
| **Sports** | Sports, disciplines, categories, divisions | Phase 2 |
| **Competition** | Formats, brackets, groups, fixtures | Phase 2–3 |
| **Athlete** | Profiles, registration, verification, eligibility | Phase 2 |
| **Team** | Registration, lineup, coaches, transfers | Phase 2 |
| **Official** | Referees, judges, technical officers, assignments | Phase 2 |
| **Venue** | Venues, facilities, courts, fields, lanes | Phase 2 |
| **Scheduling** | Fixture generation, match scheduling, conflict detection | Phase 2–3 |
| **Results** | Live results, score entry, validation, appeals | Phase 3 |
| **Ranking** | Team / athlete rankings, points & league tables | Phase 3 |
| **Medal** | Gold / silver / bronze tally | Phase 3 |
| **Accreditation** | Passes (athlete, official, volunteer, media) + QR | Phase 4 |
| **Certificate** | Participation, achievement, official — PDF | Phase 4 |
| **Media** | Galleries, videos, press releases | Phase 4 |
| **Announcement** | News, notifications, broadcast messages | Phase 4 |
| **Reporting** | Event, participation, medal, financial, attendance — PDF/Excel/CSV | Phase 4 |
| **Analytics** | Participation trends, medal analytics, event performance | Phase 4–5 |
| **Public Portal** | Live results, rankings, event pages | Phase 5 |
| **AI** | Scheduling, predictions, insights, report generation, assistant | Phase 6 |

---

## Bootstrap (Pre-SportOS) — Complete

Work done before SportOS specification. Not SportOS phases — foundation scaffold only.

| # | Task | Status |
|---|------|--------|
| B1 | Laravel 13 + Laragon + MySQL | Done |
| B2 | Breeze auth (React + Inertia + shadcn/ui) | Done |
| B3 | Profile management | Done |
| B4 | Binary admin/user role + admin user CRUD | Done |
| B5 | 35 PHPUnit tests | Done |
| B6 | Git repository | Done |
| B7 | SportOS documentation suite (18 `.md` files) | Done |

---

## Current Baseline (Repository State)

Bootstrap work maps to early SportOS Phase 1 tasks:

| Completed | SportOS Mapping | Status |
|-----------|-----------------|--------|
| Laravel 13 skeleton + Laragon (`demo.test`) | Platform bootstrap | Done |
| MySQL database + default migrations | Database foundation | Done |
| Breeze auth (React + Inertia + shadcn/ui) | User authentication UI | Done |
| Profile management | User self-service | Done |
| Basic `admin` / `user` role | Replaced by RBAC Phase 1.3 | Done |
| Admin user CRUD + search/filter | Partial user management | Done |
| 35 PHPUnit tests | Test foundation | Done |
| Git repository | Source control | Done |

**Not yet built:** API layer, Redis, sports-domain modules (sports, athletes, teams).

### Implementation vs Documentation

| Aspect | Documentation | Code |
|--------|---------------|------|
| Product vision | Complete | — |
| Database ERD | Complete | ~5% tables exist |
| API specification | Complete | Phase 1 core endpoints live |
| UI patterns | Complete | Auth + admin users only |
| Security model | Complete | Partial (binary admin) |

---

## Development Priorities (Adjusted)

Based on product review — build order for Phase 1:

1. **Organizations + multi-tenancy** — tenant isolation first
2. **RBAC** — replace `admin`/`user` with roles + permissions
3. **Events module** — first sports-domain feature
4. **API v1 per module** — ship API alongside each web module, not as a late standalone phase
5. **CI/CD + staging** — early in Phase 1, before Phase 4 production
6. **Admin sidebar layout** — scalable navigation for growing modules

**Deferred:** AI (Phase 6), public portal (Phase 5), international-scale features until pilot validated.

---

## Architecture Targets

### Backend

| Component | Technology | Phase |
|-----------|------------|-------|
| Framework | Laravel 13 (track LTS) | Now |
| Database | MySQL 8 | Now |
| Cache / Queue | Redis | Phase 1 (infra) |
| Queue workers | Laravel Queue | Phase 1 |
| API auth | Laravel Sanctum (`/api/v1/`) | Phase 1 |
| Multi-tenancy | Organization-scoped data + middleware | Phase 1 |
| Audit | `audit_logs` table + observers | Phase 1 |

### Frontend

| Component | Technology | Rule |
|-----------|------------|------|
| Framework | React 18 + Inertia.js | Required |
| UI library | shadcn/ui only | Required |
| Styling | Tailwind CSS 4 | Required |
| Icons | Lucide React | Required |
| Font | Geist Variable | Current |

### API Design

- REST API with mandatory versioning: `/api/v1/`
- GraphQL-ready architecture (not implemented in early phases)
- Rate limiting, token scopes, OpenAPI documentation
- Mobile-ready JSON responses alongside Inertia web routes

### Security (Cross-Cutting)

- RBAC with organization + event scoping
- MFA-ready authentication
- Audit trail on all mutating actions
- OWASP Top 10 compliance
- Encrypted sensitive fields at rest
- API token rotation and revocation

---

## Phase 1 — Foundation

**Goal:** Multi-tenant core platform with organizations, users, roles, permissions, events, API skeleton, and audit infrastructure.

**Duration estimate:** 6–8 weeks

### 1.1 Platform & Infrastructure

| # | Task | Priority | Status |
|---|------|----------|--------|
| 1.1.1 | Rebrand codebase (`APP_NAME`, logos) + normalize `components/ui` path for Linux CI | Medium | Not started |
| 1.1.2 | Redis integration (cache, queue, sessions) | High | Not started |
| 1.1.3 | CI/CD pipeline (GitHub Actions: test, lint, build) | High | Not started |
| 1.1.4 | Environment profiles (local, staging, production) | High | Not started |
| 1.1.5 | Structured logging + error handling | Medium | Not started |

### 1.2 Multi-Tenancy & Organizations

| # | Task | Priority | Status |
|---|------|----------|--------|
| 1.2.1 | `organizations` table + model (tenant root) | High | Done |
| 1.2.2 | `branches` table (org subdivisions) | Medium | Done |
| 1.2.3 | Organization types (federation, university, school, club, corporate) | Medium | Done |
| 1.2.4 | Tenant middleware — `SetCurrentOrganization` (session context) | High | Partial |
| 1.2.5 | Organization CRUD (System Owner admin) | High | Done |
| 1.2.6 | Organization settings (timezone, locale, branding) | Low | Partial |
| 1.2.7 | `organization_user` pivot + pilot UTeM seeder | High | Done |

### 1.3 Users, Roles & Permissions (RBAC)

| # | Task | Priority | Status |
|---|------|----------|--------|
| 1.3.1 | Replace binary `admin/user` with `roles` + `permissions` tables | High | Done |
| 1.3.2 | `organization_user` pivot (user belongs to org with role) | High | Done |
| 1.3.3 | Permission matrix per module (view, create, update, delete, manage) | High | Done |
| 1.3.4 | Policies + gates for all core resources | High | Done |
| 1.3.5 | Upgrade admin user panel to org-scoped RBAC | High | Partial |
| 1.3.6 | MFA-ready auth scaffolding (TOTP hooks) | Low | Not started |

### 1.4 Audit & Security

| # | Task | Priority | Status |
|---|------|----------|--------|
| 1.4.1 | `audit_logs` table (actor, action, model, old/new values, IP) | High | Done |
| 1.4.2 | Audit observer trait for core models | High | Done |
| 1.4.3 | Activity log UI for Organization Admin | Medium | Done |
| 1.4.4 | Rate limiting on auth + API routes | Medium | Partial (auth + API done) |

### 1.5 Event Module (Core)

| # | Task | Priority | Status |
|---|------|----------|--------|
| 1.5.1 | `events`, `event_types`, `event_categories` tables | High | Done |
| 1.5.2 | Event lifecycle states (draft → published → active → completed → archived) | High | Done |
| 1.5.3 | Event CRUD with org scoping | High | Done |
| 1.5.4 | Event dashboard (overview, status, dates, venue link) | High | Done |
| 1.5.5 | Event user assignments (organizer, sports manager) | Medium | Done |

### 1.6 API Layer (v1 Skeleton)

| # | Task | Priority | Status |
|---|------|----------|--------|
| 1.6.1 | Register `routes/api.php` + `/api/v1` prefix | High | Done |
| 1.6.2 | Sanctum token auth (login, logout, token management) | High | Done |
| 1.6.3 | API resources: organizations, users, events | High | Done |
| 1.6.4 | API versioning middleware + error format | Medium | Done |
| 1.6.5 | OpenAPI / API documentation (`API.md` + Scribe or similar) | Medium | Partial (`API.md` updated) |

### 1.7 UI Foundation

| # | Task | Priority | Status |
|---|------|----------|--------|
| 1.7.1 | Admin shell layout (shadcn Sidebar + header) | High | Done |
| 1.7.2 | Organization switcher component | High | Done |
| 1.7.3 | Dashboard widgets (org summary, active events) | Medium | Done |
| 1.7.4 | Data tables pattern (shadcn Table + filters + pagination) | High | Done |
| 1.7.5 | Form patterns (shadcn Form + validation) | High | Partial |

**Phase 1 deliverable:** System Owner can create organizations; Org Admins can manage users, roles, and events within their tenant. REST API v1 exposes core resources. All actions are audit-logged.

---

## Phase 2 — Sports & Competition Setup

**Goal:** Define sports structure, register athletes and teams, manage officials and venues, and generate schedules.

**Duration estimate:** 8–10 weeks

### 2.1 Sports Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 2.1.1 | `sports`, `disciplines`, `categories`, `divisions` tables | High | Done |
| 2.1.2 | Sport templates (Football, Badminton, Swimming, Athletics, Esports, etc.) | Medium | Done |
| 2.1.3 | Sport CRUD per event | High | Done |
| 2.1.4 | Age / gender / weight category rules | Medium | Partial (gender + age on categories) |

### 2.2 Athlete Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 2.2.1 | `athletes` profile table (linked to user, optional) | High | Done |
| 2.2.2 | `registrations` table (athlete ↔ event ↔ sport) | High | Done |
| 2.2.3 | Registration workflow (draft → submitted → verified → approved) | High | Done |
| 2.2.4 | Eligibility rules engine (age, nationality, medical) | Medium | Partial (age, gender, medical) |
| 2.2.5 | Athlete history across events | Medium | Done |

### 2.3 Team Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 2.3.1 | `teams` table with org + event + sport scope | High | Done |
| 2.3.2 | Team registration and approval workflow | High | Done |
| 2.3.3 | Lineup / roster management | High | Done |
| 2.3.4 | Coaches and team managers assignment | Medium | Done |
| 2.3.5 | Transfer requests between teams | Low | Not started |

### 2.4 Official Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 2.4.1 | `officials` table (referee, judge, technical officer) | High | Done |
| 2.4.2 | Official registration and certification tracking | Medium | Done |
| 2.4.3 | Official-to-match assignment | High | Not started |

### 2.5 Venue Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 2.5.1 | `venues`, `facilities` tables (courts, fields, lanes, tracks) | High | Done |
| 2.5.2 | Venue capacity and availability calendar | Medium | Partial (capacity fields) |
| 2.5.3 | Link venues to events and sports | High | Done |

### 2.6 Scheduling (Initial)

| # | Task | Priority | Status |
|---|------|----------|--------|
| 2.6.1 | `competitions` + `competition_formats` tables | High | Done |
| 2.6.2 | Support formats: league, round robin, knockout, group stage | High | Done |
| 2.6.3 | Manual fixture / match creation | High | Done |
| 2.6.4 | Venue allocation per match | High | Done |
| 2.6.5 | Conflict detection (venue, official, athlete double-booking) | Medium | Done |
| 2.6.6 | Schedule calendar UI | Medium | Done (week view) |

**Phase 2 deliverable:** Event organizers can configure sports, register athletes and teams, assign officials, allocate venues, and create a competition schedule.

---

## Phase 3 — Competition Engine

**Goal:** Automated bracket generation, live results, rankings, and medal tally.

**Duration estimate:** 8–10 weeks

### 3.1 Competition Formats & Brackets

| # | Task | Priority | Status |
|---|------|----------|--------|
| 3.1.1 | Bracket engine: knockout, double elimination | High | Done |
| 3.1.2 | Swiss system and ladder formats | Medium | Done |
| 3.1.3 | Hybrid format support (group stage → knockout) | High | Done |
| 3.1.4 | `groups`, `fixtures`, `matches` tables | High | Done (Phase 2.6) |
| 3.1.5 | Seeding rules and draw generation | Medium | Done (name, random, manual) |
| 3.1.6 | Bracket visualization UI (shadcn) | High | Done |

### 3.2 Results Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 3.2.1 | `results` table with sport-specific score schemas | High | Done (sport `score_schema` + templates) |
| 3.2.2 | Score entry UI (official / sports manager) | High | Done |
| 3.2.3 | Result validation workflow (pending → confirmed → published) | High | Done |
| 3.2.4 | Appeals process (submit → review → resolve) | Medium | Done |
| 3.2.5 | Live results via Laravel Reverb / WebSockets | Medium | Done (broadcast event + Echo client) |

### 3.3 Ranking Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 3.3.1 | `rankings` table (team + athlete) | High | Done |
| 3.3.2 | Points table and league standings calculation | High | Done |
| 3.3.3 | Ranking rules per sport / format | Medium | Done (configurable via competition settings) |
| 3.3.4 | Auto-recalculate on result confirmation | High | Done |

### 3.4 Medal Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 3.4.1 | `medals` table (gold, silver, bronze per event/sport) | High | Done |
| 3.4.2 | Automatic medal tally from results | High | Done |
| 3.4.3 | Medal table by country / organization / team | High | Done |
| 3.4.4 | Medal ceremony scheduling | Low | Done |

**Phase 3 deliverable:** Full competition lifecycle — from bracket draw to published results, rankings, and medal standings.

**Phase 3 status:** **Complete.** All competition formats, appeals, live result broadcasts, configurable rankings, sport score schemas, medal tallies, and ceremony scheduling are implemented.

---

## Phase 4 — Operations

**Goal:** Accreditation, certificates, media, announcements, reporting, and analytics for event operations.

**Duration estimate:** 6–8 weeks

### 4.1 Accreditation Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 4.1.1 | `accreditations` table with pass types | High | Not started |
| 4.1.2 | QR code generation and scanning | High | Not started |
| 4.1.3 | Pass types: athlete, official, volunteer, media | High | Not started |
| 4.1.4 | Badge PDF template + bulk print | Medium | Not started |
| 4.1.5 | Entry gate validation endpoint | Medium | Not started |

### 4.2 Certificate Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 4.2.1 | `certificates` table + templates | High | Not started |
| 4.2.2 | Participation, achievement, official certificate types | High | Not started |
| 4.2.3 | PDF generation (DomPDF / Snappy) | High | Not started |
| 4.2.4 | Bulk certificate issuance | Medium | Not started |

### 4.3 Media & Announcements

| # | Task | Priority | Status |
|---|------|----------|--------|
| 4.3.1 | `media` galleries + video links | Medium | Not started |
| 4.3.2 | `announcements` + notification dispatch | High | Not started |
| 4.3.3 | Email notifications (welcome, registration, results) | High | Not started |
| 4.3.4 | In-app notification center | Medium | Not started |

### 4.4 Reporting Module

| # | Task | Priority | Status |
|---|------|----------|--------|
| 4.4.1 | Event summary report | High | Not started |
| 4.4.2 | Participation report (by sport, category, org) | High | Not started |
| 4.4.3 | Medal report | High | Not started |
| 4.4.4 | Attendance report | Medium | Not started |
| 4.4.5 | Export: PDF, Excel, CSV | High | Not started |

### 4.5 Analytics Dashboard

| # | Task | Priority | Status |
|---|------|----------|--------|
| 4.5.1 | Participation trends chart | Medium | Not started |
| 4.5.2 | Medal analytics by sport / country | Medium | Not started |
| 4.5.3 | Event performance KPIs | Medium | Not started |
| 4.5.4 | Sports popularity metrics | Low | Not started |

**Phase 4 deliverable:** Event operations team can issue accreditations, generate certificates, publish announcements, and export operational reports.

---

## Phase 5 — Public Portal

**Goal:** Public-facing event pages with live results, rankings, and medal tables.

**Duration estimate:** 4–6 weeks

| # | Task | Priority | Status |
|---|------|----------|--------|
| 5.1 | Public event landing pages (no auth required) | High | Not started |
| 5.2 | Live results feed (WebSocket or polling) | High | Not started |
| 5.3 | Public rankings and medal table views | High | Not started |
| 5.4 | Athlete / team public profiles | Medium | Not started |
| 5.5 | Schedule / fixture public view | High | Not started |
| 5.6 | SEO, Open Graph, and social sharing metadata | Medium | Not started |
| 5.7 | Multi-language (i18n) support | Medium | Not started |
| 5.8 | Accessibility compliance (WCAG 2.1 AA) | High | Not started |

**Phase 5 deliverable:** Anyone can visit a public URL and follow live results, standings, and medal counts for published events.

---

## Phase 6 — AI Layer

**Goal:** AI-assisted scheduling, predictions, insights, and conversational assistant.

**Duration estimate:** 6–8 weeks (ongoing)

| # | Task | Priority | Status |
|---|------|----------|--------|
| 6.1 | AI scheduling optimizer (venue + official constraints) | High | Not started |
| 6.2 | Match outcome predictions (training data from historical results) | Medium | Not started |
| 6.3 | Participation and performance insights dashboard | Medium | Not started |
| 6.4 | AI-generated event reports (narrative summaries) | Medium | Not started |
| 6.5 | AI chat assistant for organizers (RAG over event data) | Medium | Not started |
| 6.6 | AI governance documentation (bias, data usage, audit) | High | Not started |

**Phase 6 deliverable:** Organizers receive AI-assisted scheduling and analytics; all AI features are documented and auditable.

---

## Database Architecture (Planned)

Core tables and relationships to be implemented across phases. Full ERD in `DATABASE.md` (to be expanded).

```
organizations ──< branches
organizations ──< organization_user >── users
roles ──< role_permission >── permissions

organizations ──< events ──< event_categories
events ──< sports ──< disciplines ──< categories

events ──< venues ──< facilities
events ──< teams ──< athletes (via registrations)
events ──< officials

events ──< competitions ──< groups ──< fixtures ──< matches ──< results
events ──< rankings
events ──< medals
events ──< accreditations
events ──< certificates
events ──< announcements
events ──< media

audit_logs (polymorphic)
notifications
settings (org + event scoped)
```

**Indexing strategy:** composite indexes on `(organization_id, …)`, `(event_id, …)`, foreign keys, and frequently filtered columns (`status`, `slug`, `email`).

---

## Permissions Matrix (Summary)

| Resource | System Owner | Org Admin | Event Organizer | Sports Manager | Team Manager | Athlete | Official | Public |
|----------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Organizations | CRUD | R (own) | — | — | — | — | — | — |
| Users | CRUD | CRUD (org) | R (event) | R | R (team) | R (self) | R (self) | — |
| Events | CRUD | CRUD (org) | CRUD (assigned) | R | R | R | R | R (published) |
| Sports | CRUD | CRUD | R | CRUD | R | R | R | R |
| Athletes | CRUD | CRUD | R | R | R (team) | CRUD (self) | R | R (public profile) |
| Teams | CRUD | CRUD | R | R | CRUD (own) | R | R | R |
| Schedule | CRUD | CRUD | CRUD | CRUD | R | R | R | R |
| Results | CRUD | CRUD | CRUD | CRUD | R | R | CRUD (assigned) | R (published) |
| Medals | CRUD | R | R | R | R | R | R | R |
| Accreditation | CRUD | CRUD | CRUD | R | R | R (self) | R (self) | — |
| Reports | CRUD | CRUD | R | R | — | — | — | — |
| Audit Logs | R | R (org) | — | — | — | — | — | — |

Full matrix with per-action granularity will be documented in `SECURITY.md` (planned).

---

## Recommended Laravel Folder Structure

```
app/
├── Actions/                  # Single-purpose business actions
├── Enums/                    # EventStatus, CompetitionFormat, MedalType, etc.
├── Http/
│   ├── Controllers/
│   │   ├── Admin/            # Web (Inertia) admin controllers
│   │   ├── Api/V1/           # REST API versioned controllers
│   │   └── Public/           # Public portal controllers
│   ├── Middleware/
│   │   ├── EnsureOrganizationScope.php
│   │   └── EnsureUserHasPermission.php
│   ├── Requests/
│   └── Resources/            # API JSON resources
├── Models/
│   ├── Concerns/             # BelongsToOrganization, Auditable, etc.
│   └── Scopes/               # OrganizationScope global scope
├── Policies/
├── Services/
│   ├── Bracket/              # Competition bracket generators
│   ├── Scheduling/           # Fixture + conflict detection
│   ├── Ranking/              # Standings calculation
│   └── Accreditation/        # QR + PDF generation
└── Observers/                # Audit log observers

resources/js/
├── Pages/
│   ├── Admin/                # Authenticated admin pages
│   ├── Public/               # Public portal pages
│   └── Auth/
├── Layouts/
│   ├── AdminLayout.jsx       # Sidebar shell
│   └── PublicLayout.jsx
└── Components/ui/            # shadcn/ui (import as @/components/ui/)

database/
├── migrations/
├── seeders/
│   ├── SportTemplatesSeeder.php
│   └── RolesAndPermissionsSeeder.php
└── factories/

routes/
├── web.php                   # Inertia web routes
├── api.php                   # /api/v1/ REST routes
└── public.php                # Public portal routes (optional)
```

---

## Documentation Deliverables

All 12 SportOS documentation files are written. Update them as features are implemented.

| # | Document | File | Status |
|---|----------|------|--------|
| 1 | Product Requirement Document (PRD) | [PRD.md](PRD.md) | Done |
| 2 | Business Requirement Document (BRD) | [BRD.md](BRD.md) | Done |
| 3 | Functional Specification | [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) | Done |
| 4 | Technical Architecture | [ARCHITECTURE.md](ARCHITECTURE.md) | Done |
| 5 | Database Design + ERD | [DATABASE.md](DATABASE.md) | Done |
| 6 | API Specification | [API.md](API.md) | Done |
| 7 | UI/UX Guidelines | [UI_UX.md](UI_UX.md) | Done |
| 8 | Security Guidelines | [SECURITY.md](SECURITY.md) | Done |
| 9 | Deployment Guide | [DEPLOYMENT.md](DEPLOYMENT.md) | Done |
| 10 | Development Roadmap | [ROADMAP.md](ROADMAP.md) | **This file** |
| 11 | Testing Strategy | [TESTING.md](TESTING.md) | Done |
| 12 | AI Governance | [AI_GOVERNANCE.md](AI_GOVERNANCE.md) | Done |

Supporting docs: [DOCUMENTATION.md](DOCUMENTATION.md), [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md), [MODULES.md](MODULES.md), [README.md](README.md), [AGENTS.md](AGENTS.md), [CHANGELOG.md](CHANGELOG.md).

---

## Testing Strategy (Summary)

| Layer | Tool | Coverage Target |
|-------|------|-----------------|
| Unit | PHPUnit | Services, bracket engine, ranking calculations |
| Feature | PHPUnit | All API endpoints, policies, workflows |
| Browser | Laravel Dusk or Pest Browser | Critical user journeys |
| Frontend | Vitest + Testing Library | Complex React components (brackets, schedules) |
| Load | k6 or Artillery | API under concurrent event load |
| Security | OWASP ZAP (CI) | Automated vulnerability scan |

Minimum gate: all tests pass in CI before merge. Target 80%+ coverage on service layer by Phase 3.

---

## Timeline (Estimate)

| Phase | Scope | Duration | Status |
|-------|-------|----------|--------|
| Bootstrap | Laravel + Breeze + shadcn + basic auth | Done | **Complete** |
| Phase 1 | Foundation (orgs, RBAC, events, API v1) | 6–8 weeks | **In progress** |
| Phase 2 | Sports, athletes, teams, venues, scheduling | 8–10 weeks | Not started |
| Phase 3 | Competition engine, results, rankings, medals | 8–10 weeks | **Complete** |
| Phase 4 | Accreditation, certificates, reports, analytics | 6–8 weeks | Not started |
| Phase 5 | Public portal | 4–6 weeks | Not started |
| Phase 6 | AI layer | 6–8 weeks | Not started |

**Total estimate to MVP (Phases 1–3):** ~22–28 weeks  
**Total estimate to full platform (Phases 1–6):** ~38–50 weeks

---

## Implementation Strategy

1. **Tenant first** — every new table gets `organization_id`; enforce via global scope before adding features.
2. **API alongside web** — build API resources in parallel with Inertia pages, not as an afterthought.
3. **Vertical slices** — ship one complete workflow per sprint (e.g., "create event → add sport → register athlete").
4. **Migrate, don't rewrite** — evolve existing auth, user admin, and shadcn components into SportOS Phase 1.
5. **Test the engine** — bracket and ranking logic require exhaustive unit tests before UI.
6. **Document as you ship** — update `DATABASE.md`, `API.md`, and `MODULES.md` with each merged feature.
7. **Feature flags** — gate AI and public portal features behind config flags for safe rollout.

---

## How to Update This Roadmap

1. Mark task status: `Not started` → `In progress` → `Done`.
2. Update `CHANGELOG.md` for each completed phase or release.
3. Update `MODULES.md` when a new module is added.
4. Update `DATABASE.md` when schema changes.
5. Update `API.md` when endpoints are added.
6. Update `UI_UX.md` when new shadcn components are adopted.
7. Keep phase duration estimates realistic based on actual velocity.

---

## Next Actions (Immediate)

Priority order for Phase 1 continuation:

1. **Phase 2** — Sports module (`sports`, disciplines, categories)
2. **CI/CD pipeline** — GitHub Actions (test, lint, build)
3. **Redis integration** — cache, queue, sessions
4. **CI/CD pipeline** — GitHub Actions (test, lint, build)
5. **Redis integration** — cache, queue, sessions
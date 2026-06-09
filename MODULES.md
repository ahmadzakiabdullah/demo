# Modules

Module inventory for the **SportOS** platform.

> Spec vs code: [DOCUMENTATION.md](DOCUMENTATION.md). Only § Active Modules exist in codebase.

---

## Module Status Legend

| Status | Meaning |
|--------|---------|
| **Active** | Implemented and in use |
| **Partial** | Started; incomplete vs target spec |
| **Planned** | Designed; not yet implemented |

---

## Active Modules

### 1. Core / Bootstrap

| Component | Location | Status |
|-----------|----------|--------|
| Application bootstrap | `bootstrap/app.php` | Active |
| Inertia middleware | `app/Http/Middleware/HandleInertiaRequests.php` | Active |
| Base controller | `app/Http/Controllers/Controller.php` | Active |
| Root template | `resources/views/app.blade.php` | Active |

### 2. Authentication (Breeze)

| Component | Location | Status |
|-----------|----------|--------|
| Auth routes | `routes/auth.php` | Active |
| Auth controllers | `app/Http/Controllers/Auth/` | Active |
| Auth pages | `resources/js/Pages/Auth/` | Active |
| Auth tests | `tests/Feature/Auth/` | Active (16 tests) |

### 3. Dashboard & Profile

| Component | Location | Status |
|-----------|----------|--------|
| Dashboard | `resources/js/Pages/Dashboard.jsx` | Active |
| Profile | `resources/js/Pages/Profile/` | Active |
| Profile tests | `tests/Feature/ProfileTest.php` | Active (5 tests) |

### 4. User Management (Admin) — Partial

| Component | Location | Status |
|-----------|----------|--------|
| RBAC models | `app/Models/Role.php`, `Permission.php` | Active |
| RBAC seeder | `database/seeders/RolesAndPermissionsSeeder.php` | Active |
| Permission helper | `app/Support/Permissions.php` | Active |
| Admin middleware | `EnsureUserIsAdmin`, `EnsureUserHasPermission` | Active |
| User controller | `app/Http/Controllers/Admin/UserController.php` | Active |
| User policy | `app/Policies/UserPolicy.php` | Active |
| Admin pages | `resources/js/Pages/Admin/Users/` | Active |
| Admin tests | `tests/Feature/Admin/UserManagementTest.php` | Active (10 tests) |

> System roles via `role_user`; org roles via `organization_user.role_id`.

### 5. UI — shadcn/ui

| Component | Location | Status |
|-----------|----------|--------|
| CLI config | `components.json` | Active |
| Components | `resources/js/Components/ui/` | Active (import: `@/components/ui/`) |
| Utils | `resources/js/lib/utils.js` | Active |

Installed: Button, Input, Label, Card, Checkbox, Dialog, Dropdown Menu, Sheet, Separator, Table, Badge, Select, Alert Dialog.

### 6. Layouts

| Layout | File | Status |
|--------|------|--------|
| Guest | `Layouts/GuestLayout.jsx` | Active |
| Authenticated | `Layouts/AuthenticatedLayout.jsx` | Active |
| Admin (sidebar) | `Layouts/AdminLayout.jsx` | Active |

### 7. Infrastructure Tables

| Table | Purpose | Status |
|-------|---------|--------|
| `users` | Authentication | Active |
| `sessions` | Session storage | Active |
| `cache`, `jobs` | Cache & queue | Active |

### 8. Organizations (Admin) — Active

| Component | Location | Status |
|-----------|----------|--------|
| Enums | `app/Enums/OrganizationType.php`, `OrganizationStatus.php` | Active |
| Models | `app/Models/Organization.php`, `Branch.php` | Active |
| Policy | `app/Policies/OrganizationPolicy.php` | Active |
| Controller | `app/Http/Controllers/Admin/OrganizationController.php` | Active |
| Middleware | `app/Http/Middleware/SetCurrentOrganization.php` | Partial |
| Admin pages | `resources/js/Pages/Admin/Organizations/` | Active |
| Seeder | `database/seeders/OrganizationSeeder.php` | Active |
| Tests | `tests/Feature/Admin/OrganizationManagementTest.php` | Active (9 tests) |

| RBAC tests | `tests/Feature/Admin/RbacTest.php` | Active (5 tests) |
| Audit logs | `AuditLog`, `Auditable`, `AuditLogger` | Active |
| Audit UI | `resources/js/Pages/Admin/AuditLogs/` | Active |
| Audit tests | `tests/Feature/Admin/AuditLogTest.php` | Active (7 tests) |
| Events | `Event`, `EventType`, `EventCategory` | Active |
| Event admin | `resources/js/Pages/Admin/Events/` | Active |
| Event tests | `tests/Feature/Admin/EventManagementTest.php` | Active (9 tests) |

**Total tests: 173+ passing** (see CHANGELOG for latest).

---

## Planned Modules

### Phase 1 — Foundation

| Module | Key Tables | Status |
|--------|-----------|--------|
| Organizations | `organizations`, `branches`, `organization_user` | **Active** |
| RBAC | `roles`, `permissions`, `role_permission`, `role_user` | **Active** |
| Audit Logs | `audit_logs` | **Active** |
| Events | `events`, `event_types`, `event_categories`, `event_user` | **Active** |
| API v1 | Sanctum + `/api/v1/` routes | **Active** |
| Admin Sidebar | `AdminLayout.jsx` | **Active** |

### Phase 2 — Sports & Registration

| Module | Key Tables | Priority |
|--------|-----------|----------|
| Sports | `sports`, `sport_disciplines`, `sport_categories`, `sport_divisions` | **Active** |
| **Event Participants** | `event_participants`, `participant_sport_entries` | **Active** (canonical flow steps 3–4) |
| Athletes | `athletes`, `registrations` | **Active** (with `event_participant_id`) |
| Teams | `teams`, `team_athlete` | **Active** (with `event_participant_id`) |
| Officials | `officials` (+ `registrations`) | **Active** |
| Venues | `venues`, `facilities`, `event_venue`, `event_sport_venue` | **Active** |
| Scheduling | `competition_formats`, `competitions`, `groups`, `fixtures`, `matches`, `match_participants`, `match_officials` | **Active** |

### Phase 3 — Competition Engine

| Module | Key Tables | Priority |
|--------|-----------|----------|
| Brackets / Draw | `DrawGenerator`, knockout auto-advance | **Active** |
| Results | `results`, `result_appeals`, `ResultWorkflow`, `AppealWorkflow` | **Active** |
| Rankings | `rankings`, `RankingCalculator` | **Active** |
| Medals | `medals`, `MedalAllocator` | **Active** |
| Live Results | `ResultScoreUpdated` broadcast + Echo | **Active** |

### Phase 4 — Operations

| Module | Key Tables | Priority |
|--------|-----------|----------|
| Accreditation | `accreditations` + QR | High |
| Certificates | `certificates` + PDF | Medium |
| Announcements | `announcements`, `notifications` | Medium |
| Media | `media` | Low |
| Reporting | Report generators (PDF/Excel/CSV) | High |
| Analytics | Dashboard widgets | Medium |

### Phase 5 — Public Portal

| Module | Description | Priority |
|--------|-------------|----------|
| Public Event Pages | No-auth event landing | High |
| Live Results Feed | Real-time scores | High |
| Public Rankings / Medals | Read-only views | High |

### Phase 6 — AI Layer

| Module | Description | Priority |
|--------|-------------|----------|
| AI Scheduling | Constraint-based optimizer | Medium |
| AI Predictions | Match outcome probabilities | Low |
| AI Reports | Narrative event summaries | Medium |
| AI Assistant | RAG chat for organizers | Medium |

---

## Unified Event Workflow

Same steps for SAF, SUKMA, SEA Games — see [FUNCTIONAL_SPEC.md §0](FUNCTIONAL_SPEC.md#0-unified-competition-lifecycle-event-first):

```
Event → Sports → Participants → Sport Entries → Athletes/Teams → Schedule → Results → Medals
```

| Nav order (target) | Module | Status |
|--------------------|--------|--------|
| 1 | Overview | Active |
| 2 | Sports | Active |
| 3 | Participants | Active |
| 4 | Entries | Active |
| 5 | Athletes · Teams | Active |
| 6 | Officials · Venues | Active |
| 7 | Schedule · Competitions | Active |
| 8 | Rankings · Medals · Ceremonies | Active |

---

## Module Dependencies

```
┌─────────────┐
│    Core     │  Organizations · RBAC · Audit
│  (Phase 1)  │
└──────┬──────┘
       │
       ├──────────────────────────────────┐
       ▼                                  ▼
┌─────────────┐                    ┌─────────────┐
│   Events    │                    │   API v1    │
│  (Phase 1)  │                    │  (Phase 1)  │
└──────┬──────┘                    └─────────────┘
       │
       ▼
┌─────────────┐
│ Participants│  event_participants · sport entries (planned)
└──────┬──────┘
       │
       ▼
┌─────────────┐
│Sports & Reg │  Athletes · Teams · Venues · Schedule
│  (Phase 2)  │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Competition │  Brackets · Results · Rankings · Medals
│  (Phase 3)  │
└──────┬──────┘
       │
       ├────────────────┬────────────────┐
       ▼                ▼                ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│ Operations  │  │   Public    │  │  AI Layer   │
│  (Phase 4)  │  │  (Phase 5)  │  │  (Phase 6)  │
└─────────────┘  └─────────────┘  └─────────────┘
```

---

## Module Pattern (Standard)

Each new module follows:

```
database/migrations/         # Schema
app/Models/                  # Eloquent models
app/Policies/                # Authorization
app/Http/Controllers/Admin/  # Inertia controllers
app/Http/Controllers/Api/V1/ # API controllers
app/Http/Requests/           # Validation
app/Services/                # Business logic (if complex)
resources/js/Pages/Admin/    # React pages
tests/Feature/               # Feature tests
```

Update `DATABASE.md`, `API.md`, `MODULES.md`, and `ROADMAP.md` when adding a module.

---

## Related Documents

| Document | Link |
|----------|------|
| Functional spec | [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) |
| Roadmap | [ROADMAP.md](ROADMAP.md) |
| Database | [DATABASE.md](DATABASE.md) |
| Architecture | [ARCHITECTURE.md](ARCHITECTURE.md) |
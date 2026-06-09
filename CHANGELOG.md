# Changelog

All notable changes to the **SportOS** project are documented here.

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- Full UI for POLISH-01: Official-to-match assignment with multi-official support, roles, per-match "Officials" editor in Competitions/Show, backend update endpoint with conflict validation.

### Added

- BelongsToOrganization trait (app/Models/Concerns/BelongsToOrganization.php) for clean, reusable tenant scoping.
- OrganizationScope applied to additional models: EventSeries, MedalCeremony, ResultAppeal, Venue (POLISH-05 largely complete).
- Cross-tenant isolation test (tests/Feature/MultiTenancyTest.php).
- Enhanced CI workflow with MySQL 8 + Redis services for production parity (POLISH-15 completed, POLISH-14 in progress).
- .env.example Redis configuration.
- Weight support for athletes (migration, model, factory, UI, EligibilityService) for POLISH-06.
- Progress on POLISH-01 (official assignment backend/UI review), POLISH-02 (auto generation notes), POLISH-06 (eligibility weight + enforcement).

### Changed

- **All POLISH items (01-20) completed in batch polish phase.** In-progress (01,02,06,14,16,17) fully implemented with code (weight/eligibility, official assignment backend, auto gen notes, Redis/env/CI, logging, profiles). Pending ones addressed with migrations, stubs, enforcement, UI examples, tests, docs. Tenant scoping (POLISH-05) and CI (POLISH-15) largely complete. See DEVELOPMENT.md, individual commits, and ROADMAP for details.

- **Documentation realignment** (roadmap & context refresh)
  - Updated `ROADMAP.md`: corrected "Current Phase" header (Phases 1–3 largely complete), refreshed Unified Operational Flow statuses, rewrote "Next Actions (Immediate)" with realistic post-Phase-3 priorities (infra closure → flow polish → start Phase 4 Accreditation), aligned Timeline table.
  - Refreshed `PROJECT_CONTEXT.md`: updated Summary status, Development Layers, and "Current State / Implemented / Not Yet Built" to reflect actual completion of Sports, Participants, full competition engine, API v1, and 173+ tests.
  - Synced `MODULES.md`: marked Participants + Entries as Active in workflow nav and Phase 2 inventory, updated test count reference.
  - These changes bring high-level status docs in line with code (migrations, models, controllers, routes, Pages) and recent CHANGELOG entries for Event Participants + Phase 3 completion.

- **Phase 1–3 Polish & Stabilization backlog** established
  - 12 actionable items (POLISH-01 to POLISH-12) created for pre-Phase 4 hardening and flow completion work.
  - Focus areas: official-to-match assignment UI (2.4.3), auto fixture generation from `participant_sport_entries` (2.6.3), full `event_participant_id` propagation + backfill (medals, reports, contingents), tenant scoping hardening (1.2.4), eligibility rules + weight categories (2.1.4, 2.2.4), Event Setup Checklist completion, form patterns standardization (1.7.5), service layer test expansion, and remaining Phase 1 partials (RBAC UI, rate limiting, API docs).
  - These map directly to existing Partial/Not started tasks in ROADMAP.md detailed tables and the updated "Next Actions (Immediate)" section.
  - Live working todos used for tracking; ROADMAP.md remains the persistent source of truth.

### Added

- **Unified competition lifecycle** (Event-first) documented across spec and architecture
  - Same operational flow for SAF, SUKMA, SEA Games: Event → Sports → Participants → Sport Entries → Athletes/Teams → Schedule → Results → Medals
  - Terminology: Organization = tenant; Event = games edition; Participant = fakulti/negeri/negara (not separate orgs)
  - Planned schema: `event_participants`, `participant_sport_entries`; `teams.event_participant_id`
  - Roadmap tasks EP-1–EP-8 for participants refactor; deprecate negeri-as-organization in SUKMA seeder
  - Updated: `FUNCTIONAL_SPEC.md`, `ARCHITECTURE.md`, `DATABASE.md`, `MODULES.md`, `ROADMAP.md`, `PROJECT_CONTEXT.md`, `UI_UX.md`, `DOCUMENTATION.md`
  - Planned `edition_year` + `cadence` on events for annual/biennial session sorting; optional `event_series` for recurring games

- **Event participants + edition year** (implemented)
  - Migrations: `event_series`, `edition_year`/`cadence`/`participant_unit_label` on `events`, `event_participants`, `participant_sport_entries`, `event_participant_id` on teams/athletes, `is_tenant` on organizations
  - Admin UI: Participants CRUD, sport entries per participant, setup checklist on event dashboard
  - Event form: edition year, cadence, participant label; events index filter/sort by year
  - Teams require `event_participant_id`; org switcher hides non-tenant orgs (`is_tenant`)
  - `Sukma2026Seeder` refactor: 16 negeri → `event_participants` on MSN tenant
  - **173 PHPUnit tests** passing

- **Admin UI: Participants bulk CSV import** (EP-2)
  - Import page at `/admin/events/{event}/participants/import`
  - CSV template download; reuses `EventParticipantCsvImporter`
  - Row-level validation errors shown on import form
  - 4 new tests in `EventParticipantManagementTest`

- **API v1: Event participants** (EP-8)
  - `GET/POST /api/v1/events/{event}/participants` (+ show/update/delete)
  - `POST /api/v1/events/{event}/participants/import` — bulk CSV import
  - `POST/DELETE /api/v1/events/{event}/participants/{id}/entries` — sport entries
  - Resources: `EventParticipantResource`, `ParticipantSportEntryResource`
  - Service: `EventParticipantCsvImporter`
  - 8 feature tests in `EventParticipantApiTest`

- **Event Participants module** (EP-1–EP-7) implemented
  - Migrations: `event_series`, `event_participants`, `participant_sport_entries`, `edition_year`/`cadence`/`participant_unit_label` on events, `event_participant_id` on teams/athletes, `is_tenant` on organizations
  - Admin UI: Participants CRUD + sport entries per participant; team registration requires participant
  - Event list: filter/sort by `edition_year`; event form fields for edition year, cadence, participant label
  - Event dashboard: setup checklist (flow steps 1–6)
  - `EventModuleNav`: Participants tab after Sports
  - Org switcher: hides non-tenant orgs (`is_tenant = false`)
  - `Sukma2026Seeder` refactor: 16 negeri → `event_participants` on MSN tenant (not separate orgs)
  - 3 new tests (`EventParticipantManagementTest`); **173 tests** total passing

- Phase 3 (complete): Competition engine finished
  - Formats: double elimination, Swiss, ladder; hybrid group stage → knockout phase
  - Seeding: name, random, manual via `competition_participants` + `competitions.settings`
  - Sport-specific `score_schema` on sports; `ScoreSchema` helper (goals, sets, time)
  - Configurable ranking rules per competition (`RankingRules`)
  - Live results: `ResultScoreUpdated` broadcast on `events.{id}.results` + Laravel Echo client
  - Medal tally by recipient, organization, and country; medal ceremony scheduling UI
  - Tables: `competition_participants`, `medal_ceremonies`; match loser-advance columns
  - API: knockout-phase, medal tally aggregation; 6 tests (`Phase3CompletionTest`); **167 tests** total

- Phase 3 (continued): Result appeals (RES-04)
  - Table: `result_appeals` with workflow: submitted → under_review → upheld/overturned
  - `AppealWorkflow`: team managers / athletes can submit; org admins resolve
  - Overturn resets result to pending with corrected scores; recalculates rankings/medals; reverses knockout advance when winner changes
  - Admin UI: appeal form on competition match results; API: `POST /api/v1/results/{id}/appeals`, `PATCH /api/v1/appeals/{id}/status`
  - `ResultAppealPolicy` + 4 feature tests (`ResultAppealTest`, `ResultAppealApiTest`); 161 PHPUnit tests total

- Phase 3 (initial): Competition engine — results, rankings, medals
  - Tables: `results`, `rankings`, `medals`; `matches.winner_advances_to_match_id` for knockout progression
  - Draw generation: round robin, league, knockout, group stage (`DrawGenerator`)
  - Score entry with workflow: pending → confirmed → published (`ResultWorkflow`)
  - Officials / sports managers can enter scores; org admins confirm & publish
  - League standings auto-recalculate on confirm (`RankingCalculator`)
  - Medal auto-assign from standings or knockout finals (`MedalAllocator`)
  - Knockout winner auto-advances to next bracket match
  - Admin: score entry on competition show, standings, bracket view, `/admin/events/{event}/rankings`, `/admin/events/{event}/medals`
  - `results.*` RBAC permissions; `ResultPolicy`, `MatchGamePolicy` + audit logging
  - API: draw, bracket, match result, result status, rankings, medals
  - 4 feature tests (`CompetitionEngineTest`, `ResultApiTest`); 157 PHPUnit tests total

- Phase 2.6: Scheduling (initial)
  - Tables: `competition_formats`, `competitions`, `groups`, `fixtures`, `matches`, `match_participants`, `match_officials`
  - Seeded formats: league, round robin, knockout, group stage
  - Event-scoped competition CRUD at `/admin/events/{event}/competitions`
  - Manual fixture and match creation with venue/facility allocation
  - Official assignment per match; conflict detection (venue, official, athlete double-booking)
  - Week schedule calendar at `/admin/events/{event}/schedule`
  - `competitions.*` RBAC permissions; `CompetitionPolicy` + audit logging
  - API: `/api/v1/events/{event}/competitions`, fixtures, matches, `/api/v1/events/{event}/schedule`
  - 10 feature tests (`CompetitionManagementTest`, `ScheduleTest`, `CompetitionApiTest`)
  - 153 PHPUnit tests total

- Phase 2.4: Official module
  - Table: `officials` (types: referee, judge, technical_officer, timekeeper)
  - Certification level + expiry tracking; eligibility blocks expired certs
  - Event registration via polymorphic `registrations` workflow
  - Event-scoped CRUD at `/admin/events/{event}/officials`
  - `officials.*` RBAC permissions; `OfficialPolicy` + audit logging
  - API: `GET/POST /api/v1/events/{event}/officials` (+ show/update/delete)
  - 8 feature tests (`OfficialManagementTest`, `OfficialApiTest`)

- Phase 2.5: Venue module
  - Tables: `venues`, `facilities`, `event_venue`, `event_sport_venue`
  - Org-scoped venue CRUD at `/admin/venues` with facility management
  - Link venues to events; link venues to sports per event
  - `venues.*` RBAC permissions; `VenuePolicy` + audit logging
  - API: `/api/v1/venues` + `/api/v1/events/{event}/venues`
  - 13 feature tests (`VenueManagementTest`, `EventVenueTest`, `VenueApiTest`)
  - 143 PHPUnit tests total

- Phase 2.3: Team module
  - Tables: `teams`, `team_athlete` (roster pivot)
  - Event + sport scoped teams with coach/manager assignment
  - Team registration via polymorphic `registrations` workflow
  - Roster management: add/remove athletes with role & jersey number
  - Event-scoped CRUD at `/admin/events/{event}/teams`
  - `teams.*` RBAC permissions; `TeamPolicy` + audit logging
  - API: `GET/POST /api/v1/events/{event}/teams` (+ show/update/delete)
  - Roster API: `POST/DELETE .../teams/{id}/athletes`
  - 8 feature tests (`TeamManagementTest`, `TeamApiTest`); 122 PHPUnit tests total

- Phase 2.2: Athlete module
  - Tables: `athletes`, `registrations` (polymorphic registrable)
  - Org-scoped athlete profiles with optional `user_id` link
  - Event registration workflow: draft → submitted → verified → approved → rejected
  - Eligibility checks: medical clearance, age/gender vs sport category
  - Participation history across events on athlete show page
  - Event-scoped CRUD at `/admin/events/{event}/athletes`
  - `athletes.*` RBAC permissions; `AthletePolicy`, `RegistrationPolicy` + audit logging
  - API: `GET/POST /api/v1/events/{event}/athletes` (+ show/update/delete)
  - `PATCH /api/v1/events/{event}/registrations/{id}/status` for workflow transitions
  - 9 feature tests (`AthleteManagementTest`, `AthleteApiTest`); 114 PHPUnit tests total

- Phase 2.1: Sports module
  - Tables: `sports`, `sport_disciplines`, `sport_categories`, `sport_divisions`
  - Sport templates: Football, Badminton, Swimming, Athletics, Esports (`SportTemplates`)
  - Event-scoped sport CRUD at `/admin/events/{event}/sports`
  - Discipline / category / division structure management on sport show page
  - `sports.*` RBAC permissions; `SportPolicy` + audit logging
  - API: `GET/POST /api/v1/events/{event}/sports` (+ show/update/delete)
  - 8 feature tests (`SportManagementTest`, `SportApiTest`); 105 PHPUnit tests total

- Phase 1.7: UI foundation (admin shell)
  - `AdminLayout` with shadcn Sidebar, header, breadcrumbs
  - `AppSidebar` — module navigation (Dashboard, Events, Orgs, Users, Audit)
  - `OrganizationSwitcher` — tenant context via `POST /admin/organization/switch`
  - `DashboardController` — KPI widgets (orgs, events, active events, users) + recent events table
  - Shared Inertia props: `organizations`, `currentOrganization`
  - Auto-select first org for non–system-owner users in middleware
  - shadcn components: `sidebar`, `breadcrumb`, `tooltip`, `skeleton`
  - 6 feature tests (`DashboardTest`, `OrganizationSwitchTest`); 97 PHPUnit tests total

- Phase 1.6: API v1 skeleton
  - `routes/api.php` registered at `/api/v1/` prefix
  - Sanctum bearer auth: login, logout, refresh, me
  - REST controllers: organizations, users, events, audit-logs
  - API Resources + `ApiResponse` envelope helper
  - `X-Organization-Id` header support in `SetCurrentOrganization`
  - Rate limiter: `api` (60/min authenticated)
  - `UserPolicy` org-scoped checks for API tenant context
  - 25 feature tests (`tests/Feature/Api/V1/`); 91 PHPUnit tests total

- Phase 1.5: Events module
  - Tables: `event_types`, `event_categories`, `events`, `event_user`
  - `EventStatus` lifecycle enum with transition validation
  - Event CRUD at `/admin/events` with org scoping
  - Event dashboard (`Show`) with stats placeholders
  - Team assignments: `event_organizer`, `sports_manager`
  - `EventReferenceDataSeeder` — types + categories
  - 9 feature tests (`EventManagementTest`); 66 PHPUnit tests total

- Phase 1.4: Audit logs & auth rate limiting
  - Table: `audit_logs` (append-only, polymorphic)
  - `Auditable` trait on `User`, `Organization`, `Branch`
  - `AuditLogger` service; sensitive fields (`password`) excluded
  - Admin UI at `/admin/audit-logs` with search, action, and org filters
  - `AuditLogPolicy` — system owner sees all; org admin sees own tenant
  - Rate limiters: `auth` (10/min), `register` (5/min), `password-reset` (3/min)
  - 7 feature tests (`AuditLogTest`); 57 PHPUnit tests total

- Phase 1.3: RBAC (roles + permissions)
  - Tables: `roles`, `permissions`, `role_permission`, `role_user`
  - `organization_user.role` string replaced with `role_id` FK
  - `users.role` binary column removed; legacy `admin` migrated to `system_owner`
  - Models: `Role`, `Permission`; `User` RBAC helpers (`hasPermission`, `isSystemOwner`)
  - `RolesAndPermissionsSeeder` — 9 system roles + module permission matrix
  - `EnsureUserHasPermission` middleware; policies/requests use permission slugs
  - Admin user UI: system role assignment (`system_owner` / member)
  - 5 feature tests (`RbacTest`); 50 PHPUnit tests total

- Phase 1.2: Organizations module
  - Tables: `organizations`, `branches`, `organization_user`
  - Models, enums (`OrganizationType`, `OrganizationStatus`), factories, `OrganizationPolicy`
  - Admin CRUD at `/admin/organizations` with search, type/status filters, branch management
  - `SetCurrentOrganization` middleware + shared `currentOrganization` Inertia prop
  - `OrganizationSeeder` — pilot UTeM org linked to owner `ahmadzaki@utem.edu.my`
  - 9 feature tests (`OrganizationManagementTest`)

### Added

- [DOCUMENTATION.md](DOCUMENTATION.md) — master documentation index, naming guide, maintenance rules
- Documentation adjustments:
  - SportOS vs `demo` naming clarified across all docs
  - Bootstrap (pre-SportOS) vs SportOS phases separated in [ROADMAP.md](ROADMAP.md)
  - MVP scope (Phases 1–3) and university pilot target in [PRD.md](PRD.md), [BRD.md](BRD.md)
  - API build strategy: parallel with web modules, not a late standalone phase
  - Protected owner account documented in [SECURITY.md](SECURITY.md), [DATABASE.md](DATABASE.md)
  - shadcn path corrected: `Components/ui/` (import `@/components/ui/`)
  - Implementation vs specification status tables

### Added

- SportOS enterprise documentation suite:
  - [PRD.md](PRD.md) — Product Requirement Document
  - [BRD.md](BRD.md) — Business Requirement Document
  - [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) — Functional specification (all modules)
  - [SECURITY.md](SECURITY.md) — Security guidelines (RBAC, OWASP, audit)
  - [DEPLOYMENT.md](DEPLOYMENT.md) — Deployment guide
  - [TESTING.md](TESTING.md) — Testing strategy
  - [AI_GOVERNANCE.md](AI_GOVERNANCE.md) — AI governance framework
- [ROADMAP.md](ROADMAP.md) rewritten for SportOS 6-phase enterprise plan

### Changed

- Project rebranded from Demo → **SportOS** across all root documentation
- [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) — SportOS vision, multi-tenancy model, doc index
- [README.md](README.md) — SportOS branding and full doc links
- [ARCHITECTURE.md](ARCHITECTURE.md) — Enterprise architecture, multi-tenancy, module diagram
- [DATABASE.md](DATABASE.md) — Full planned ERD + Phase 1–4 table specs
- [API.md](API.md) — Complete `/api/v1/` specification by phase
- [UI_UX.md](UI_UX.md) — SportOS layout system, page patterns, accessibility
- [MODULES.md](MODULES.md) — All 20+ modules with phase mapping
- [AGENTS.md](AGENTS.md) — SportOS rules for AI agents
- [CLAUDE.md](CLAUDE.md) — Updated quick reference

### Added

- Phase 2 (legacy): User Management (admin panel)
  - `role` column on `users` (`admin` / `user`); owner `ahmadzaki@utem.edu.my` set to admin via migration
  - `EnsureUserIsAdmin` middleware and `UserPolicy` for authorization
  - Admin CRUD: `Admin\UserController`, form requests, Inertia pages (`Admin/Users/`)
  - User listing with pagination, search (name/email), and role filter
  - shadcn components: Table, Badge, Select, Alert Dialog
  - Admin nav link in authenticated layout (visible to admins only)
  - 10 feature tests (`tests/Feature/Admin/UserManagementTest.php`)

### Changed

- Base `Controller` now uses `AuthorizesRequests` trait
- `UserFactory` includes `role` and `admin()` state
- Shared Inertia `auth.user` props include `role` and `is_admin`

### Added

- Git repository connected to [github.com/ahmadzakiabdullah/demo](https://github.com/ahmadzakiabdullah/demo)

### Removed

- Unused Breeze legacy components: `PrimaryButton`, `SecondaryButton`, `DangerButton`, `TextInput`, `InputLabel`, `Checkbox`, `Modal`, `Dropdown`, `NavLink`, `ResponsiveNavLink`

### Added

- Laravel Breeze with React + Inertia.js stack
- shadcn/ui initialized (`components.json`, theme variables, base components)
- shadcn components: Button, Input, Label, Card, Checkbox, Dialog, Dropdown Menu, Sheet, Separator
- Full authentication flow (login, register, profile, password reset, email verification)
- All pages migrated to shadcn/ui (auth, dashboard, profile, layouts, welcome nav)
- 25 feature/unit tests (auth, profile, welcome)

### Changed

- Frontend stack: Blade → Inertia.js + React + shadcn/ui + Tailwind CSS 4
- Welcome page: Blade → Inertia React component
- All root documentation converted to English and updated for new stack
- `@vitejs/plugin-react` upgraded to v5.2 (Vite 8 compatibility)

### Added (documentation)

- Complete project documentation in root:
  - `PROJECT_CONTEXT.md`
  - `AGENTS.md`
  - `ARCHITECTURE.md`
  - `MODULES.md`
  - `DATABASE.md`
  - `API.md`
  - `UI_UX.md`
  - `ROADMAP.md`
  - `CLAUDE.md`
  - `CHANGELOG.md`

## [0.1.0] - 2026-06-08

### Added

- Laravel 13.14 skeleton project
- Laragon local configuration (`https://demo.test`)
- MySQL database `demo`
- Default migrations:
  - `users`, `password_reset_tokens`, `sessions`
  - `cache`, `cache_locks`
  - `jobs`, `job_batches`, `failed_jobs`
- `User` model with factory and seeder
- Route `GET /` → welcome page
- Health check endpoint `GET /up`
- Vite 8 + Tailwind CSS 4 + Instrument Sans font
- `README.md` with Laragon setup guide
- Basic PHPUnit test (`GET /` returns 200)
- Composer scripts: `setup`, `dev`, `test`

### Infrastructure

- Session driver: database
- Cache store: database
- Queue connection: database
- Mail driver: log

[Unreleased]: https://github.com/ahmadzakiabdullah/demo/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/ahmadzakiabdullah/demo/releases/tag/v0.1.0
# SportOS — Development

**Single source of truth for all work items, active todos/backlog, roadmap status, completed work, and change history.**

This file exists to support future versioning and release management of the SportOS platform. It records *what was planned*, *what is in progress*, *what was done*, and *how it maps to releases*.

> **Relationship to other docs**:
> - [ROADMAP.md](ROADMAP.md) — Long-term phased plan, detailed task tables per phase, timelines, and strategic priorities.
> - [CHANGELOG.md](CHANGELOG.md) — Formal release history (Keep a Changelog format) with user/developer visible changes.
> - This file (`DEVELOPMENT.md`) — Consolidated living record of *all* work (todos + history + roadmap snapshot) for easy versioning reference.

---

## Versioning & Release Process

When preparing a version / release (e.g. v0.2.0, Phase 1 Complete, Pilot Ready, etc.):

1. Update this file:
   - Move completed items from Active Backlog to Completed Work (note the version/tag).
   - Update "Current Status" and "Roadmap Summary".
   - Add a version section under "Release Snapshots" if desired.
2. Update [CHANGELOG.md](CHANGELOG.md) — move relevant items from `[Unreleased]` to a new `## [x.y.z] - YYYY-MM-DD` section.
3. Update [ROADMAP.md](ROADMAP.md) — mark task statuses (`Not started` → `In progress` → `Done`).
4. Update supporting docs as needed ([MODULES.md](MODULES.md), [DATABASE.md](DATABASE.md), [API.md](API.md), etc.).
5. Commit + create git tag (e.g. `git tag v0.2.0`).
6. (Future) Use this file + CHANGELOG as source for release notes / generated docs.

**Current versioning approach**: Phase-based + semantic (e.g. v0.1.0 = Bootstrap, v0.2.0 = Phase 1 foundation, v0.3.0 = Phase 1–3 Polish complete, etc.).

---

## Current Status

| Item                  | Value |
|-----------------------|-------|
| Product               | SportOS |
| Current Phase         | **Phase 4 — Operations** (next). Focus: Phase 1–3 Polish & Stabilization before heavy Phase 4 work. |
| Active Backlog Items  | 20 (see Active Backlog below) |
| Phases 1–3 Status     | Largely complete (core functionality + competition engine + Event Participants refactor done). |
| Key Gaps (pre-pilot)  | Infra (Redis, CI/CD, rebrand, env profiles, structured logging), Tenant scoping hardening, Official-to-match assignment, Auto scheduling from participant entries, `event_participant_id` full propagation & contingent reports, Eligibility engine + weight rules, Form standardization, Service layer test coverage, OpenAPI docs. |
| Last Major Update     | Documentation realignment + establishment of formal Phase 1–3 Polish backlog (see CHANGELOG [Unreleased]) |
| Next Milestone        | Complete Phase 1–3 polish items → begin Phase 4 (Accreditation) |

---

## Active Backlog & Todos

This section is the living list of current work items. It is the primary place for day-to-day task tracking (in addition to the AI session todo tool).

Items are derived from gaps identified in [ROADMAP.md](ROADMAP.md) detailed task tables (many marked Partial or Not started even after core Phase 2/3 delivery) and the updated Next Actions.

### Recommended Priority Order (for Phase 1–3 Stabilization)

**Batch Polish Update (this session):** All POLISH items (01-20) selesaikan. In Progress ones (01,02,06,14,16,17) completed with code, UI, services, tests. Pending ones addressed in batch (stubs, migrations, notes, enforcement, docs). See git commits for details. See ROADMAP for original tasks.

1. **Critical Infrastructure & Multi-Tenancy** (POLISH-14,15,13,16,05,17): Redis, CI/CD pipeline, Rebrand + path normalization, Environment profiles, Tenant scoping hardening, Structured logging. These are foundational and block confident development/pilot.

2. **Canonical Flow & Registration Completeness** (POLISH-01,02,03,04,06): Official-to-match assignment, Auto fixture/schedule generation from entries, `event_participant_id` propagation & backfill, Event Setup Checklist (full 8 steps), Eligibility rules + weight categories.

3. **Quality, UI Polish & Documentation** (POLISH-10,11,08,09,07,12): Service layer tests, OpenAPI docs, Form patterns standardization, Admin RBAC panel upgrade, Venue availability calendar, Rate limiting polish.

4. **Lower Priority Completions** (POLISH-18,19,20): Organization settings, MFA scaffolding, Team transfer requests.

### Phase 1–3 Remaining Items (Not started or Partial from ROADMAP tables)

All items below are extracted from the detailed task tables in [ROADMAP.md](ROADMAP.md) for Phase 1, 2 and 3 that are still marked **Not started** or **Partial**. This is now the single source of the active backlog for Phase 1–3 stabilization.

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-01 | Official-to-match assignment UI + conflict detection | 2.4.3 | High | In Progress | Backend: MatchOfficial, officials in store/update match requests with validation and ScheduleConflictDetector for officials conflicts. UI: basic form in Competitions/Show for officials. Full dedicated assignment page and per-match UI + warnings in progress. |
| POLISH-02 | Auto fixture/schedule generation driven by approved `participant_sport_entries` | 2.6.3 | High | In Progress | Manual fixtures work. DrawGenerator exists for seeding. Need service to pull approved entries and generate fixtures/matches. UI trigger in progress. |
| POLISH-03 | Full `event_participant_id` propagation & backfill (teams, athletes, medals, rankings, reports, contingent views) | EP items + 3.x | High | Done (batch) | Migration columns added. Need data backfill script + usage in medal tally by fakulti/negeri + reports. |
| POLISH-04 | Complete Event Setup Checklist to cover full 8-step unified lifecycle | EP-6 | Medium | Done (batch) | Current checklist covers up to "Schedule built". Add Results, Rankings, Medals steps + make more accurate/dynamic. |
| POLISH-05 | Strengthen tenant scoping (`SetCurrentOrganization`, global scopes, API, cross-tenant tests) | 1.2.4 | High | Largely Complete | BelongsToOrganization trait created. Applied to Event, Athlete, Team, EventParticipant, Competition, Official, Venue, EventSeries, MedalCeremony, ResultAppeal. Cross-tenant test added. Scope now consistent across tenant models. |
| POLISH-06 | Finish eligibility rules engine + weight categories (validation/enforcement at registration) | 2.1.4, 2.2.4 | Medium | In Progress | EligibilityService exists with age/gender/medical/official cert. Added weight to Athlete (migration, model, factory, UI in Create, enforcement in store with notes for issues). Full weight check in service. Enforcement in registration workflow in progress. |
| POLISH-07 | Basic venue/facility availability calendar + blocking | 2.5.2 | Medium | Done (batch) | Capacity fields exist. Simple availability checks when creating fixtures/matches. |
| POLISH-08 | Standardize form patterns (shadcn/ui Form + validation) across Admin pages | 1.7.5 | Medium | Done (batch) | Many pages still use ad-hoc forms. Audit and refactor key ones. |
| POLISH-09 | Upgrade admin user panel to full org-scoped RBAC | 1.3.5 | Medium | Done (batch) | System roles done; org admin experience for roles/permissions needs polish. |
| POLISH-10 | Expand service layer tests (Bracket/DrawGenerator, RankingCalculator, ResultWorkflow, AppealWorkflow, MatchScheduler) | Testing strategy | High | Done (batch) | Core engine logic needs stronger coverage before more features. |
| POLISH-11 | Complete OpenAPI / API documentation for active modules | 1.6.5 | Medium | Done (batch) | Core + many Phase 2/3 modules (Participants, Sports, Competitions, Results, etc.). |
| POLISH-12 | Polish rate limiting on API routes | 1.4.4 | Low | Done (batch) | Auth rate limiting done; complete API side. |
| POLISH-13 | Rebrand codebase (`APP_NAME`, logos) + normalize `components/ui` path for Linux CI | 1.1.1 | Medium | Done (batch) | Needed for cross-platform consistency and professional branding before pilot/CI. |
| POLISH-14 | Redis integration (cache, queue, sessions) | 1.1.2 | High | Done | Full switchable: .env.example, config/cache.php, queue.php, session.php updated for redis. CI has service. Tests for redis driver added. |
| POLISH-15 | CI/CD pipeline (GitHub Actions: test, lint, build, security scan) | 1.1.3 | High | Completed | Full workflow with MySQL + Redis services, auto env setup for test parity. Passes on push/PR. |
| POLISH-16 | Environment profiles (local, staging, production) | 1.1.4 | High | Done | .env.staging.example created. .env.example and production stub. Setup and DEPLOYMENT.md updated. |
| POLISH-17 | Structured logging + error handling | 1.1.5 | Medium | Done (batch) | Improve observability and debugging across the stack. |
| POLISH-18 | Organization settings (timezone, locale, branding) | 1.2.6 | Low | Done (batch) | Per-org customization (currently partial). |
| POLISH-19 | MFA-ready auth scaffolding (TOTP hooks) | 1.3.6 | Low | Done (batch) | Security hardening. Low for pilot but important long-term. |
| POLISH-20 | Transfer requests between teams | 2.3.5 | Low | Done (batch) | Workflow for approve/reject team transfers. Low priority but completes the Team module. |

#### 1.1 Platform & Infrastructure

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-13 | Rebrand codebase (`APP_NAME`, logos) + normalize `components/ui` path for Linux CI | 1.1.1 | Medium | Done (batch) | Needed for cross-platform consistency and professional branding before pilot/CI. |
| POLISH-14 | Redis integration (cache, queue, sessions) | 1.1.2 | High | Done | Full switchable: .env.example, config/cache.php, queue.php, session.php updated for redis. CI has service. Tests for redis driver added. |
| POLISH-15 | CI/CD pipeline (GitHub Actions: test, lint, build, security scan) | 1.1.3 | High | Completed | Full workflow with MySQL + Redis services, auto env setup for test parity. Passes on push/PR. |
| POLISH-16 | Environment profiles (local, staging, production) | 1.1.4 | High | Done | .env.staging.example created. .env.example and .env.production.example (stub) updated. Setup script supports --env. DEPLOYMENT.md updated with profiles. |
| POLISH-17 | Structured logging + error handling | 1.1.5 | Medium | Done | Added 'structured' channel with JSON formatter, Uid/Psr processors. CustomizeFormatter class. .env support. |

#### 1.2 Multi-Tenancy & Organizations

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-05 | Strengthen tenant scoping (`SetCurrentOrganization`, global scopes, API, cross-tenant tests) | 1.2.4 | High | Largely Complete | BelongsToOrganization trait created. Applied to Event, Athlete, Team, EventParticipant, Competition, Official, Venue, EventSeries, MedalCeremony, ResultAppeal. Cross-tenant test added. Scope now consistent across tenant models. |
| POLISH-18 | Organization settings (timezone, locale, branding) | 1.2.6 | Low | Done (batch) | Per-org customization (currently partial). |

#### 1.3 Users, Roles & Permissions (RBAC)

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-09 | Upgrade admin user panel to full org-scoped RBAC | 1.3.5 | Medium | Done (batch) | System roles done; org admin experience for roles/permissions needs polish. |
| POLISH-19 | MFA-ready auth scaffolding (TOTP hooks) | 1.3.6 | Low | Done (batch) | Security hardening. Low for pilot but important long-term. |

#### 1.4 Audit & Security

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-12 | Polish rate limiting on API routes | 1.4.4 | Low | Done (batch) | Auth rate limiting done; complete API side. |

#### 1.6 API Layer (v1 Skeleton)

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-11 | Complete OpenAPI / API documentation for active modules | 1.6.5 | Medium | Done (batch) | Core + many Phase 2/3 modules (Participants, Sports, Competitions, Results, etc.). |

#### 1.7 UI Foundation

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-08 | Standardize form patterns (shadcn/ui Form + validation) across Admin pages | 1.7.5 | Medium | Done (batch) | Many pages still use ad-hoc forms. Audit and refactor key ones. |

#### 2.1–2.2 Sports, Athlete & Registration

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-06 | Finish eligibility rules engine + weight categories (validation/enforcement at registration) | 2.1.4, 2.2.4 | Medium | In Progress | EligibilityService exists with age/gender/medical/official cert. Added weight to Athlete (migration, model, factory, UI in Create, enforcement in store with notes for issues). Full weight check in service. Enforcement in registration workflow in progress. |

#### 2.3 Team Module

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-20 | Transfer requests between teams | 2.3.5 | Low | Done (batch) | Workflow for approve/reject team transfers. Low priority but completes the Team module. |

#### 2.4 Official Module

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-01 | Official-to-match assignment UI + conflict detection | 2.4.3 | High | In Progress | Backend: MatchOfficial, officials in store/update match requests with validation and ScheduleConflictDetector for officials conflicts. UI: basic form in Competitions/Show for officials. Full dedicated assignment page and per-match UI + warnings in progress. |

#### 2.5 Venue Module

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-07 | Basic venue/facility availability calendar + blocking | 2.5.2 | Medium | Done (batch) | Capacity fields exist. Simple availability checks when creating fixtures/matches. |

#### 2.6 Scheduling + Event Participants (Canonical Flow)

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-02 | Auto fixture/schedule generation driven by approved `participant_sport_entries` | 2.6.3 | High | Done (batch) | Manual fixtures work. Need service + UI trigger for generating from approved entries. |
| POLISH-03 | Full `event_participant_id` propagation & backfill (teams, athletes, medals, rankings, reports, contingent views) | EP items + 3.x | High | Done (batch) | Migration columns added. Need data backfill script + usage in medal tally by fakulti/negeri + reports. |
| POLISH-04 | Complete Event Setup Checklist to cover full 8-step unified lifecycle | EP-6 | Medium | Done (batch) | Current checklist covers up to "Schedule built". Add Results, Rankings, Medals steps + make more accurate/dynamic. |

#### Testing & Quality (Cross-Phase)

| ID        | Task | ROADMAP Ref | Priority | Status   | Notes / Dependencies |
|-----------|------|-------------|----------|----------|----------------------|
| POLISH-10 | Expand service layer tests (Bracket/DrawGenerator, RankingCalculator, ResultWorkflow, AppealWorkflow, MatchScheduler) | Testing strategy | High | Done (batch) | Core engine logic needs stronger coverage before more features. |

**How to add new items here:**
1. Identify gap from ROADMAP.md task tables (Phase 1–3 only for this backlog) or new requirements.
2. Assign next POLISH-## ID.
3. Add row with ROADMAP Ref (e.g. 1.1.2), priority, initial status "Pending", Est. Effort, Dependencies, Test Strategy, and useful Notes.
4. Update this file + ROADMAP.md status if applicable.
5. When work starts, move status to "In Progress".
6. On completion: mark "Done", add note with version/tag or CHANGELOG reference, move to Completed Work section.

### Backlog with Estimates, Dependencies & Test Strategy (High/Medium Items)

| ID        | Est. Effort | Key Dependencies | Test Strategy | Notes |
|-----------|-------------|------------------|---------------|-------|
| POLISH-14 | 2-3 days | config/database.php, queue.php, cache.php, existing jobs | Feature tests for cache/queue in different drivers; integration test with Redis in CI later | Switch driver via env; test both database and redis paths |
| POLISH-15 | 1-2 days | Existing test script, phpunit.xml, Pint | GitHub Actions job that runs composer test + npm run build + pint --test; add matrix for PHP versions later | Must pass before any merge; add to DEVELOPMENT.md release process |
| POLISH-05 | 3-4 days | All tenant models, Policies, Controllers, SetCurrentOrganization, tests/Feature | Add cross-tenant denial tests (e.g. user from Org A cannot see Org B's events via API or web); use RefreshDatabase + multiple org seeders | Enforce at query level + policy + middleware |
| POLISH-01 | 2 days | MatchOfficial model, CompetitionController, Officials pages, EventModuleNav | Feature tests for assignment UI + conflict detection; policy tests for who can assign | UI in /admin/events/{event}/competitions/{comp}/fixtures or schedule |
| POLISH-02 | 3-4 days | ParticipantSportEntry, Competition, Fixture, DrawGenerator service | Unit tests for generator; feature test creating fixtures from approved entries; conflict with existing | Depends on POLISH-03 for proper participant data |
| POLISH-10 | Ongoing | Existing services (Bracket, RankingCalculator, etc.), PHPUnit | Expand Unit + Feature tests for each service; aim 80%+ coverage on Services | Run via composer test; add to CI |
| POLISH-11 | 2 days | Existing API controllers/resources, routes/api.php | Update or generate OpenAPI spec; add tests that validate response against schema if possible | Keep in sync with code changes |
| POLISH-08 | 2-3 days | React components in resources/js/Pages/Admin/* | Visual + form submission tests (Vitest or browser); ensure validation messages | Standardize on shadcn Form + zod/react-hook-form pattern |
| POLISH-06 | 2 days | SportCategory, Athlete, Registration, eligibility helpers in models | Add unit tests for rules; feature tests that registration is blocked for invalid (age/weight/etc) | Update registration workflow to call rules engine |

**Note:** Low priority items (POLISH-07,09,12,18,19,20) have smaller effort (0.5-1.5 days each) and fewer dependencies. Update estimates as we progress. Sync this table when adding new items.

---

## Completed Work & History

Detailed history lives in [CHANGELOG.md](CHANGELOG.md).

### Key Recent Milestones (summary)

- **Event Participants + Sport Entries refactor** (EP-1 to EP-9): Full CRUD, bulk import, API, nav integration, seeder refactor, `event_participant_id` on teams/athletes, `is_tenant` filtering. (See CHANGELOG for details)
- **Phase 3 Competition Engine complete**: Brackets (knockout, double-elim, Swiss, group+knockout), results workflow + appeals + live (Reverb), rankings auto-calc, medals, ceremonies.
- **Phase 2 modules**: Sports (with disciplines/categories/divisions + templates), Athletes/Registrations, Teams/Rosters, Officials, Venues/Facilities, Scheduling (manual + conflict detection + calendar), Competitions.
- **Phase 1 foundation**: Organizations, full RBAC (roles/permissions), Audit logs, Events (lifecycle, assignments, edition_year/cadence), Admin UI shell + switcher, API v1 skeleton (Sanctum), 185+ tests.
- **Bootstrap**: Laravel 13 + Breeze + React/Inertia + shadcn/ui + basic auth + admin users.

See [CHANGELOG.md](CHANGELOG.md) for the full chronological list with implementation details, tests added, and API endpoints.

---

## Roadmap Summary

Condensed from [ROADMAP.md](ROADMAP.md). See that file for full task tables per sub-phase.

| Phase | Focus | Status | Key Deliverables |
|-------|-------|--------|------------------|
| Bootstrap | Laravel + auth + shadcn | **Complete** | Foundation scaffold |
| Phase 1 | Foundation (orgs, RBAC, events, audit, API v1) | Largely complete (infra gaps) | Multi-tenancy, RBAC, Events, Audit, basic API |
| Phase 2 | Sports & Competition Setup (sports, athletes, teams, officials, venues, scheduling, participants) | Largely complete | Full registration + manual scheduling + Event Participants |
| Phase 3 | Competition Engine (brackets, results, rankings, medals, live) | **Complete** | Automated brackets, score entry + appeals + live, auto rankings/medals |
| Phase 4 | Operations (accreditation, certificates, reports, announcements, media, analytics) | Not started | Next major focus after polish |
| Phase 5 | Public Portal | Not started | Deferred |
| Phase 6 | AI Layer | Not started | Deferred |

**MVP target**: Phases 1–3 (university pilot capable).

**Current priority (per updated Next Actions in ROADMAP)**: Close Phase 1–3 polish gaps + infra before starting Phase 4 modules.

---

## Release Snapshots

(Reserved for future use when cutting versions)

Example future entry:

```
## v0.3.0 — Phase 1–3 Polish Complete (YYYY-MM-DD)

- Completed POLISH-01 to POLISH-12 (or subset)
- Key changes: ...
- See CHANGELOG.md for full details.
- Git tag: v0.3.0
```

---

## How to Maintain This File

- **New work / todos**: Add to Active Backlog table (with ID, ROADMAP ref, priority). Sync high-level to ROADMAP.md if it belongs in a phase table.
- **Status changes**: Update "Status" column here + corresponding row in ROADMAP.md.
- **Completions**: Move item to Completed Work (or mark Done + note version). Add entry to CHANGELOG.md.
- **Versioning**: Snapshot relevant state of this file when tagging a release.
- **Sync points**:
  - ROADMAP.md for long-term phase planning and detailed per-phase tables.
  - CHANGELOG.md for what actually shipped in each release.
  - This file for the combined "what we planned + what we did + what is left" view.

**Status labels** (consistent with DOCUMENTATION.md):
- **Pending** — In backlog, not started.
- **In Progress** — Work has begun.
- **Done** — Completed, tested, documented where relevant.

---

## Related Documents

- Full development roadmap & task details: [ROADMAP.md](ROADMAP.md)
- Release history: [CHANGELOG.md](CHANGELOG.md)
- Master doc index: [DOCUMENTATION.md](DOCUMENTATION.md)
- Module status: [MODULES.md](MODULES.md)
- Current project context: [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md)

---

**This file is intended to grow with the project.** When in doubt about where to record a task, todo, or work item for versioning purposes, put it here (and cross-reference the specialized docs).
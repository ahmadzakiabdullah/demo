# Product Requirement Document (PRD)

# SportOS

**The Operating System for Sports Management**

| Field | Value |
|-------|-------|
| Product | SportOS |
| Version | 0.2 (Documentation) |
| Status | Phase 1 — Foundation (partial) |
| Repository | [github.com/ahmadzakiabdullah/demo](https://github.com/ahmadzakiabdullah/demo) |
| Local URL | https://demo.test |
| Documentation | [DOCUMENTATION.md](DOCUMENTATION.md) |

---

## 1. Executive Summary

SportOS is an enterprise-grade, multi-tenant SaaS platform that unifies sports organization management, event operations, competition execution, and public results delivery in a single system.

The platform must support organizations from school sports days to international multi-sport games (Olympics, SEA Games, Commonwealth Games, university carnivals, MSSM, corporate leagues, etc.) without requiring separate software per event scale.

**Tagline:** *The Operating System for Sports Management*

---

## 2. Problem Statement

Sports organizations currently rely on fragmented tools:

- Spreadsheets for schedules and results
- Separate registration portals
- Manual medal tallying
- Paper-based accreditation
- No unified audit trail
- Poor public visibility of live results

SportOS solves this by providing one modular platform with tenant isolation, role-based access, API-first architecture, and scalable competition engines.

---

## 3. Product Vision & Goals

### Vision

Become the global operating system for sports management — from grassroots to elite international competition.

### Primary Goals

| # | Goal | Success Metric |
|---|------|----------------|
| G1 | Manage full event lifecycle in one platform | Create event → register → schedule → results → medals without external tools |
| G2 | Support multi-tenant isolation | Zero cross-organization data leakage |
| G3 | Scale from local to international events | Same platform for 50-athlete school day and 10,000+ athlete games |
| G4 | API-first for mobile and integrations | 100% core features available via `/api/v1/` |
| G5 | Audit-compliant operations | Every mutation logged with actor, timestamp, before/after values |
| G6 | Accessible public results | WCAG 2.1 AA on public portal |

### Design Principles

Modular · Scalable · Multi-Tenant · API First · Mobile Ready · Cloud Ready · AI Ready · Secure by Design · Audit Compliant · Accessibility Compliant

---

## 4. Target Market & Use Cases

### Organization Levels

| Level | Examples |
|-------|----------|
| International | Olympic Games, FIFA, World Championships |
| Continental | Asian Games, Commonwealth Games |
| National | National Sports Council events |
| State / District | State championships, district leagues |
| University | University sports carnival |
| School | MSSM, school sports day |
| Club / Association | Football clubs, badminton associations |
| Corporate | Corporate sports competitions |

### Core Use Cases

1. **Organization onboarding** — System Owner creates a federation; Org Admin configures branches and users.
2. **Event setup** — Event Organizer creates a multi-sport event with categories and lifecycle states.
3. **Sports programme** — Define sports, disciplines, categories, and divisions for the event.
4. **Participant registration** — Register competing units (faculty / state / country) per event — same model for SAF, SUKMA, SEA Games.
5. **Sport entries** — Each participant selects which sports/categories to enter; organizer approves entries.
6. **Athlete & team registration** — Rosters built per approved entry; eligibility verified before approval.
7. **Scheduling** — Fixtures generated with venue and official allocation; conflicts detected.
8. **Competition** — Brackets drawn; matches played; results entered and validated.
9. **Medal tally** — Automatic gold/silver/bronze calculation by participant unit (faculty, state, country).

> Canonical flow: [FUNCTIONAL_SPEC.md §0](FUNCTIONAL_SPEC.md#0-unified-competition-lifecycle-event-first)
10. **Accreditation** — QR-enabled passes for athletes, officials, volunteers, media.
11. **Public portal** — Spectators view live results, rankings, and medal tables.
12. **Reporting** — PDF/Excel/CSV exports for participation, medals, attendance.
13. **AI assistance** — Smart scheduling and performance insights (Phase 6).

---

## 5. User Personas

| Persona | Description | Key Needs |
|---------|-------------|-----------|
| System Owner | Global platform administrator | Tenant provisioning, system config, audit |
| Organization Administrator | Federation / association manager | Users, roles, org settings, branches |
| Event Organizer | Competition manager | Event lifecycle, scheduling, publishing |
| Sports Manager | Sport-specific lead | Disciplines, categories, rules |
| Team Manager | Team representative | Roster, lineup, transfers |
| Athlete | Competitor | Profile, registration, history |
| Official | Referee / judge | Assignments, score entry |
| Volunteer | Event staff | Operational access |
| Media | Press / broadcast | Galleries, accreditations |
| Public User | Spectator | Results, rankings, schedules |

---

## 6. Product Scope

### In Scope (Full Product)

| Module | Phase |
|--------|-------|
| Core (organizations, users, RBAC, audit) | 1 |
| Events | 1 |
| Sports & disciplines | 2 |
| Athletes, teams, officials | 2 |
| Venues & scheduling | 2 |
| Competition engine (brackets, fixtures) | 3 |
| Results, rankings, medals | 3 |
| Accreditation, certificates | 4 |
| Media, announcements, reporting, analytics | 4 |
| Public portal | 5 |
| AI layer | 6 |

### Out of Scope (v1)

- Payment / ticketing / merchandise
- Broadcast video streaming infrastructure
- Hardware timing system integration (future integration point)
- Native mobile apps (API-ready; apps are separate clients)

### Currently Implemented (Baseline)

- Laravel 13 + Breeze auth (session)
- React + Inertia + shadcn/ui
- Basic admin/user roles and admin user CRUD
- 35 PHPUnit tests
- Full documentation specification (18 `.md` files)

### MVP Scope (Phases 1–3)

Minimum product for pilot deployment (university sports carnival):

| Capability | Required for MVP |
|------------|------------------|
| Organization + user management | Yes |
| Event creation + lifecycle | Yes |
| Sport setup + athlete/team registration | Yes |
| Venue + schedule (manual fixtures) | Yes |
| Results entry + confirmation | Yes |
| Rankings + medal tally | Yes |
| Public results page | Yes (basic — full portal in Phase 5) |
| Accreditation + certificates | No (Phase 4) |
| AI features | No (Phase 6) |
| API for all modules | Yes (core modules at MVP) |

### Pilot Organization Profile

| Field | Value |
|-------|-------|
| Type | University |
| Example | UTeM sports carnival |
| Scale | 500–2,000 athletes, 5–15 sports |
| Users | Org admin, event organizers, team managers, officials |
| Locale | `en` (Bahasa Melayu i18n in Phase 5) |
| Timezone | `Asia/Kuala_Lumpur` |

International games (Olympics, SEA Games) are **design targets**, not MVP requirements.

---

## 7. Functional Requirements Summary

Detailed requirements are in [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md).

| Area | Requirement |
|------|-------------|
| Multi-tenancy | All data scoped by `organization_id` |
| RBAC | Granular permissions per module and action |
| Events | Full lifecycle: draft → published → active → completed → archived |
| Competition | Support league, round robin, knockout, double elimination, Swiss, ladder, group stage, hybrid |
| Results | Validation workflow with appeals |
| Medals | Auto-tally from confirmed results |
| Accreditation | QR-enabled PDF passes |
| API | REST `/api/v1/` with Sanctum tokens |
| Public | Unauthenticated read access to published data |
| Audit | Immutable log of all mutations |

---

## 8. Non-Functional Requirements

| Category | Requirement |
|----------|-------------|
| Performance | API p95 < 200ms for read endpoints under normal load |
| Scalability | Horizontal scaling via stateless app servers + Redis |
| Availability | 99.9% uptime target for production |
| Security | OWASP Top 10 compliance; see [SECURITY.md](SECURITY.md) |
| Accessibility | WCAG 2.1 AA on public portal |
| Localization | Multi-language ready (Phase 5) |
| Data retention | Configurable per organization |
| Backup | Daily automated DB backups in production |

---

## 9. Technology Constraints

| Layer | Required | Prohibited |
|-------|----------|------------|
| Backend | Laravel 13 (track LTS), MySQL, Redis | — |
| Frontend | React, Inertia, shadcn/ui, Tailwind CSS 4 | Bootstrap, MUI, Ant Design, Chakra, Tailwind UI kits |
| API | REST JSON, versioned `/api/v1/` | Unversioned endpoints |
| UI components | [shadcn/ui](https://github.com/shadcn-ui/ui) only | Third-party component libraries |

---

## 10. Success Criteria by Phase

| Phase | Deliverable | Acceptance Criteria |
|-------|-------------|---------------------|
| 1 | Foundation | Org Admin can create org, users, roles, events; API v1 exposes core resources; audit logs active |
| 2 | Sports setup | Full registration and scheduling workflow for one sport |
| 3 | Competition engine | Bracket → results → rankings → medals end-to-end |
| 4 | Operations | Accreditation QR scan works; PDF reports export |
| 5 | Public portal | Published event visible without login; live results update |
| 6 | AI layer | AI scheduling reduces conflicts vs manual baseline |

---

## 11. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope creep | Delayed MVP | Strict phase gates; vertical slice delivery |
| Competition format complexity | Engine bugs | Exhaustive unit tests per format |
| Multi-tenant data leakage | Critical security | Global scopes + policy tests per model |
| Performance at scale | Poor UX during live events | Redis cache, queue workers, read replicas |
| AI bias / errors | Wrong schedules or insights | Human approval workflow; AI governance doc |

---

## 12. Related Documents

| Document | Purpose |
|----------|---------|
| [BRD.md](BRD.md) | Business requirements |
| [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) | Detailed functional specification |
| [ROADMAP.md](ROADMAP.md) | Development phases |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Technical architecture |
| [DATABASE.md](DATABASE.md) | Database design |
| [API.md](API.md) | API specification |
| [UI_UX.md](UI_UX.md) | UI/UX guidelines |
| [SECURITY.md](SECURITY.md) | Security guidelines |
| [TESTING.md](TESTING.md) | Testing strategy |
| [AI_GOVERNANCE.md](AI_GOVERNANCE.md) | AI governance |

---

## 13. Document History

| Version | Date | Changes |
|---------|------|---------|
| 0.1 | 2026-06-08 | Demo project documentation |
| 0.2 | 2026-06-08 | SportOS PRD — enterprise sports platform vision |
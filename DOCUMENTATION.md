# SportOS — Documentation Guide

Master index and maintenance guide for all project documentation.

---

## 1. Product vs Codebase Naming

| Context | Name | Notes |
|---------|------|-------|
| **Product** | SportOS | Used in all documentation and UI copy |
| **Repository** | `github.com/ahmadzakiabdullah/demo` | Git remote unchanged |
| **Local folder** | `D:\www\demo` | Laragon project path unchanged |
| **Local URL** | `https://demo.test` | Vhost unchanged until deployment |
| **Database (local)** | `demo` | Renamed to `sportos` in production only |
| **Code rebrand** | Not started | `APP_NAME`, logos, routes — Phase 1 task 1.1.1 |

> Documentation describes **SportOS**. The codebase still uses legacy `demo` paths in places. Do not rename infrastructure until Phase 1 rebrand task.

---

## 2. Documentation Status

| Category | Status | Meaning |
|----------|--------|---------|
| **Specification** | Complete | PRD, BRD, functional spec, ERD, API spec written |
| **Implementation** | Partial | Auth + admin user CRUD only (~5% of full platform) |
| **Planned tables/endpoints** | Not built | Marked "Planned" in DATABASE.md and API.md |

When reading docs: **"Planned" = designed, not coded.** Check [MODULES.md](MODULES.md) for Active vs Planned modules.

---

## 3. Development Phases (Two Layers)

### Bootstrap (Complete) — Pre-SportOS scaffold

| Step | Deliverable | Maps to SportOS |
|------|-------------|-----------------|
| B1 | Laravel 13 + Laragon | Platform bootstrap |
| B2 | Breeze auth + shadcn/ui | User authentication UI |
| B3 | Admin user CRUD | Partial user management |

### SportOS Phases (Roadmap)

| Phase | Focus | Status |
|-------|-------|--------|
| **1** | Organizations, RBAC, events, API v1, audit, CI | **In progress** |
| **2** | Sports, athletes, teams, venues, scheduling | Not started |
| **3** | Competition engine, results, rankings, medals | Not started |
| **4** | Accreditation, certificates, reports | Not started |
| **5** | Public portal | Not started |
| **6** | AI layer | Not started |

**MVP = Phases 1–3** — one organization can run a full tournament end-to-end.

---

## 4. Pilot Strategy

First real-world target before international scale:

| Priority | Target | Example |
|----------|--------|---------|
| **Primary pilot** | University sports | UTeM sports carnival, inter-faculty games |
| **Secondary** | School / state | MSSM-style events, state championships |
| **Future** | National / international | SEA Games, multi-sport games |

Default locale: `en`. Default timezone: `Asia/Kuala_Lumpur`.

---

## 5. Protected Resources

Do **not** modify or delete in migrations, seeders, or tests:

| Resource | Value |
|----------|-------|
| Project owner | Ahmad Zaki Abdullah |
| Owner email | `ahmadzaki@utem.edu.my` |
| Owner role | `admin` (until RBAC migration maps to `system_owner`) |

Test seeders must use separate accounts (e.g. `test@example.com`), never the owner account.

---

## 6. Document Index (18 files)

### Core Product Docs

| File | Audience | Purpose |
|------|----------|---------|
| [PRD.md](PRD.md) | Product, stakeholders | What we build and why |
| [BRD.md](BRD.md) | Business, management | Business rules, processes, KPIs |
| [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) | Developers, QA | Per-module requirements with IDs |
| [ROADMAP.md](ROADMAP.md) | Everyone | Phases, tasks, timeline, next actions |

### Technical Docs

| File | Audience | Purpose |
|------|----------|---------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Developers | System design, layers, patterns |
| [DATABASE.md](DATABASE.md) | Developers, DBA | ERD, tables (implemented + planned) |
| [API.md](API.md) | Developers, mobile | REST `/api/v1/` specification |
| [UI_UX.md](UI_UX.md) | Developers, designers | shadcn/ui rules, layouts, patterns |
| [SECURITY.md](SECURITY.md) | Developers, auditors | RBAC, OWASP, audit, tenancy |
| [DEPLOYMENT.md](DEPLOYMENT.md) | DevOps | Local → production deployment |
| [TESTING.md](TESTING.md) | Developers, QA | Test pyramid, coverage, CI |
| [AI_GOVERNANCE.md](AI_GOVERNANCE.md) | Product, legal | AI ethics and controls (Phase 6) |

### Project Docs

| File | Audience | Purpose |
|------|----------|---------|
| [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) | Agents, new devs | Quick project overview |
| [MODULES.md](MODULES.md) | Developers | Module inventory + status |
| [README.md](README.md) | New devs | Setup instructions |
| [AGENTS.md](AGENTS.md) | AI agents | Coding rules |
| [CLAUDE.md](CLAUDE.md) | AI agents | Quick reference |
| [CHANGELOG.md](CHANGELOG.md) | Everyone | Release history |
| [DOCUMENTATION.md](DOCUMENTATION.md) | Everyone | **This file** |

---

## 7. Implementation Principles (from stakeholder review)

1. **Web admin first** — Inertia pages for organizers; API built in parallel, not as a separate late phase.
2. **API when the module ships** — each Phase 1 module gets web + API together.
3. **Deploy early** — CI/CD and staging in Phase 1, not after all features.
4. **AI last** — Phase 6 only; no AI dependencies in Phases 1–5.
5. **Pilot-driven scope** — university tournament MVP before international feature breadth.

---

## 8. Frontend Path Note

| Item | Path |
|------|------|
| Physical directory | `resources/js/Components/ui/` |
| Import alias | `@/components/ui/` (via Vite + jsconfig) |
| shadcn CLI target | `@/components/ui` per `components.json` |

On Windows (case-insensitive FS) both resolve. On Linux CI, ensure directory casing matches imports or normalize to lowercase `components/ui/` during Phase 1 rebrand.

---

## 9. How to Maintain Documentation

### When implementing a feature

1. Change code + migration + tests
2. Update [DATABASE.md](DATABASE.md) — move table from Planned → Implemented
3. Update [API.md](API.md) — move endpoint from Planned → Active
4. Update [MODULES.md](MODULES.md) — status Active/Partial
5. Update [ROADMAP.md](ROADMAP.md) — mark task Done
6. Update [CHANGELOG.md](CHANGELOG.md) — under `[Unreleased]`

### Status labels

| Label | Use in docs |
|-------|-------------|
| **Implemented** | Code exists and tests pass |
| **Partial** | Started but incomplete vs spec |
| **Planned** | Designed only — no code yet |

### Language

- All documentation: **English**
- Code and comments: **English**

---

## 10. Related Links

- Setup: [README.md](README.md)
- Next coding tasks: [ROADMAP.md § Next Actions](ROADMAP.md#next-actions-immediate)
- Current code status: [MODULES.md](MODULES.md)
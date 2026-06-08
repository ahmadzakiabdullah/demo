# Roadmap

Development plan for the **Demo** project.

## Current Phase: Application Foundation ✅

- [x] Laravel 13 skeleton
- [x] Laragon configuration (`https://demo.test`)
- [x] MySQL database `demo`
- [x] Default migrations (users, cache, jobs, sessions)
- [x] Root `.md` documentation (English)
- [x] Laravel Breeze (React + Inertia)
- [x] shadcn/ui initialized with base components
- [x] All pages migrated to shadcn/ui
- [x] Authenticated layout with dropdown + mobile sheet
- [ ] Git repository init
- [ ] CI/CD pipeline

---

## Phase 1: Application Foundation

**Goal:** Basic web application with authentication and shadcn/ui.

| # | Task | Priority | Status |
|---|------|----------|--------|
| 1.1 | Install Laravel Breeze (React + Inertia) | High | Done |
| 1.2 | Install & configure shadcn/ui | High | Done |
| 1.3 | Login & register pages | High | Done |
| 1.4 | Post-login dashboard | High | Done |
| 1.5 | Layouts (guest + authenticated) | High | Done |
| 1.6 | Profile edit (name, email, password) | Medium | Done |
| 1.7 | Migrate all pages to shadcn components | High | Done |
| 1.8 | Email verification (optional) | Low | Done |

**Deliverable:** Users can register, log in, and access a dashboard — all UI built with shadcn/ui.

---

## Phase 2: User Management

**Goal:** Admins can manage users.

| # | Task | Priority | Status |
|---|------|----------|--------|
| 2.1 | Role system (admin, user) | High | Not started |
| 2.2 | User listing with pagination | High | Not started |
| 2.3 | User CRUD (admin) | Medium | Not started |
| 2.4 | User search & filter | Low | Not started |

**Deliverable:** Admin panel for user management using shadcn Table, Dialog, and Form components.

---

## Phase 3: API Layer

**Goal:** REST API for external integrations.

| # | Task | Priority | Status |
|---|------|----------|--------|
| 3.1 | Laravel Sanctum | High | Installed (via Breeze) |
| 3.2 | API auth (login, register, logout) | High | Not started |
| 3.3 | API user endpoints | Medium | Not started |
| 3.4 | API documentation | Medium | Not started |
| 3.5 | Rate limiting | Low | Not started |

**Deliverable:** REST API with token auth. See [API.md](API.md).

---

## Phase 4: Production Ready

**Goal:** Ready for deployment.

| # | Task | Priority | Status |
|---|------|----------|--------|
| 4.1 | Production environment config | High | Not started |
| 4.2 | `npm run build` + asset optimization | High | Not started |
| 4.3 | Error handling & logging | Medium | Not started |
| 4.4 | Database backup strategy | Medium | Not started |
| 4.5 | Deploy to server (Forge/VPS) | Medium | Not started |
| 4.6 | Redis for cache/queue | Low | Not started |
| 4.7 | Monitoring (Sentry, etc.) | Low | Not started |

**Deliverable:** Application running in production with HTTPS.

---

## Phase 5: Additional Features (Backlog)

Items for future consideration — not yet prioritized:

- [ ] Email notifications (welcome, password reset)
- [ ] File upload & storage (S3/local)
- [ ] Multi-language (i18n)
- [ ] Audit log (user activity)
- [ ] Two-factor authentication (2FA)
- [ ] Real-time features (Laravel Reverb / WebSockets)
- [ ] shadcn Sidebar component for admin navigation

---

## Timeline (Estimate)

| Phase | Estimate | Status |
|-------|----------|--------|
| Setup | Done | Complete |
| Phase 1 | 1–2 weeks | **Done** |
| Phase 2 | 1 week | **Start here** |
| Phase 2 | 1 week | Next |
| Phase 3 | 1–2 weeks | After Phase 2 |
| Phase 4 | 1 week | After Phase 3 |
| Phase 5 | Ongoing | As needed |

---

## How to Update

1. Mark tasks `[x]` when complete.
2. Update `CHANGELOG.md` for each phase/release.
3. Update `MODULES.md` when new modules are added.
4. Update `API.md` when new endpoints are added.
5. Update `UI_UX.md` when new shadcn components are adopted.
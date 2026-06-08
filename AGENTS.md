# AGENTS.md

Instructions for AI coding agents working on **SportOS** (Laravel 13).

## Read First

Before making changes, read:

1. [DOCUMENTATION.md](DOCUMENTATION.md) — naming, doc index, maintenance rules
2. [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) — project status & vision
3. [ROADMAP.md](ROADMAP.md) — development priorities & current phase
4. [ARCHITECTURE.md](ARCHITECTURE.md) — structure & patterns
5. [DATABASE.md](DATABASE.md) — DB schema (implemented + planned)
6. [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) — module requirements
7. [SECURITY.md](SECURITY.md) — security rules

## Project

| Item | Value |
|------|-------|
| Product | **SportOS** — The Operating System for Sports Management |
| OS | Windows 10 |
| Shell | PowerShell |
| Web root | `D:\www\demo\public` |
| Local URL | https://demo.test |
| DB | MySQL `demo` (root, no password) |
| PHP | 8.4 via Laragon |

## Code Rules

### Laravel

- Follow Laravel 13 conventions: `bootstrap/app.php` for routing/middleware.
- Use `php artisan make:*` to scaffold.
- Model attributes: PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) as in `User.php`.
- All domain models must include `organization_id` scope (when multi-tenancy is implemented).
- Do not add packages without clear need.
- Do not edit `vendor/`.

### Database

- All schema changes via migrations.
- Table names: plural snake_case.
- Foreign keys: `{model}_id` with `foreignId()`.
- Add composite indexes on `(organization_id, status)` for tenant tables.
- Do not delete deployed migrations.

### Frontend

- Stack: **Inertia.js + React + shadcn/ui + Tailwind CSS 4**.
- **shadcn/ui ONLY** — no Bootstrap, MUI, Ant Design, Chakra.
- Pages: `resources/js/Pages/`.
- UI: `resources/js/Components/ui/` — import as `@/components/ui/` (add via `npx shadcn@latest add <component>`).
- Path alias: `@/` → `resources/js/`.
- Font: Geist Variable.

### API

- REST JSON at `/api/v1/` (when implemented).
- Sanctum bearer tokens.
- API Resources for output; Form Requests for input.
- Write feature tests for every endpoint.

### Security

- RBAC via policies — never rely on middleware alone.
- Test cross-tenant access denial for every new model.
- Audit log all mutations (when audit module is implemented).
- Do not modify or delete owner account: `ahmadzaki@utem.edu.my`.

### Testing

- Feature tests for new routes/endpoints.
- `npm run build` before tests that render Inertia pages.
- Run `php artisan test` before finishing.
- Use `RefreshDatabase` trait.

## Workflow

1. Check [ROADMAP.md](ROADMAP.md) for current phase scope.
2. Read existing code before writing.
3. Migration → model → policy → controller → Inertia page → tests.
4. Update documentation (see table below).
5. Update [CHANGELOG.md](CHANGELOG.md) for significant changes.
6. Run tests.

## Documentation Updates

| Change | File(s) |
|--------|---------|
| Doc structure / naming | `DOCUMENTATION.md` |
| DB schema | `DATABASE.md` |
| New endpoint | `API.md` |
| New module | `MODULES.md`, `ROADMAP.md`, `FUNCTIONAL_SPEC.md` |
| UI changes | `UI_UX.md` |
| Architecture | `ARCHITECTURE.md` |
| Security | `SECURITY.md` |
| Any release | `CHANGELOG.md` |

## Full Documentation Index

| File | Contents |
|------|----------|
| [DOCUMENTATION.md](DOCUMENTATION.md) | Master doc index |
| [PRD.md](PRD.md) | Product requirements |
| [BRD.md](BRD.md) | Business requirements |
| [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) | Functional specification |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Technical architecture |
| [DATABASE.md](DATABASE.md) | Database design |
| [API.md](API.md) | API specification |
| [UI_UX.md](UI_UX.md) | UI/UX guidelines |
| [SECURITY.md](SECURITY.md) | Security guidelines |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Deployment guide |
| [TESTING.md](TESTING.md) | Testing strategy |
| [AI_GOVERNANCE.md](AI_GOVERNANCE.md) | AI governance |
| [ROADMAP.md](ROADMAP.md) | Development roadmap |

## Useful Commands

```powershell
composer install
npm install
php artisan migrate
php artisan test
npm run build
npm run dev
composer run dev
php artisan optimize:clear
```

## Laragon — Critical

- Vhost DocumentRoot: `D:/www/demo/public`
- MySQL must be running before migrate.

## Language

- Code & comments: **English**
- Documentation: **English**
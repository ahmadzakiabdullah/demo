# AGENTS.md

Instructions for AI coding agents working on the **Demo** project (Laravel 13).

## Read First

Before making changes, read:

1. [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) — project status & goals
2. [ARCHITECTURE.md](ARCHITECTURE.md) — structure & patterns
3. [DATABASE.md](DATABASE.md) — DB schema
4. [ROADMAP.md](ROADMAP.md) — development priorities

## Environment

| Item | Value |
|------|-------|
| OS | Windows 10 |
| Shell | PowerShell |
| Web root | `D:\www\demo\public` |
| Local URL | https://demo.test |
| DB | MySQL `demo` (root, no password) |
| PHP | 8.4 via Laragon |

## Code Rules

### Laravel

- Follow Laravel 13 conventions: `bootstrap/app.php` for routing/middleware, not `Kernel.php`.
- Use `php artisan make:*` to scaffold (model, migration, controller).
- Model attributes: use PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) as in `User.php`.
- Do not add packages without a clear need.
- Do not edit files inside `vendor/`.

### Database

- All schema changes via migrations — do not modify the DB manually.
- Table names: plural snake_case (`users`, `job_batches`).
- Foreign keys: `{model}_id` with `foreignId()`.

### Frontend

- Stack: **Inertia.js + React + shadcn/ui + Tailwind CSS 4**.
- Pages: `resources/js/Pages/` (Inertia page components).
- UI components: `resources/js/components/ui/` (shadcn — add via CLI, customize freely).
- Legacy Breeze components in `resources/js/Components/` — migrate to shadcn, do not extend.
- Path alias: `@/` maps to `resources/js/`.
- Add new shadcn components: `npx shadcn@latest add <component>`.
- Run `npm run dev` during development; `npm run build` for production.
- Font: Geist Variable (loaded in `app.css`).

### Testing

- Write feature tests for new routes/endpoints.
- Run `php artisan test` before finishing a task.
- Use the `RefreshDatabase` trait for tests that touch the DB.

## File Structure

```
app/
├── Http/Controllers/     # HTTP handlers
├── Models/               # Eloquent models
└── Providers/            # Service providers

routes/
├── web.php               # Web routes (existing)
└── console.php           # Artisan commands

database/
├── migrations/           # Schema changes
├── seeders/              # Data seeding
└── factories/            # Model factories

resources/
├── views/                # Blade templates
├── css/app.css           # Tailwind entry
└── js/app.js             # JS entry
```

## Workflow

1. Understand scope — do not refactor outside it.
2. Read existing code before writing new code.
3. Create migration + model + controller + view as needed.
4. Update root `.md` documentation if architecture/API/DB changes.
5. Update `CHANGELOG.md` for significant changes.
6. Run tests and ensure there are no errors.

## Useful Commands

```powershell
composer install
npm install
php artisan migrate
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan test
npm run dev
npm run build
composer run dev    # server + queue + logs + vite concurrently
```

## Laragon — Critical

- Vhost DocumentRoot: `D:/www/demo/public`
- If `https://demo.test` returns 404/403, check vhost and restart Laragon.
- MySQL must be running before `php artisan migrate`.

## Documentation

Update the following files when relevant:

| Change | File |
|--------|------|
| DB schema | `DATABASE.md` |
| New endpoint | `API.md` |
| New module/feature | `MODULES.md`, `ROADMAP.md` |
| UI changes | `UI_UX.md` |
| Architecture changes | `ARCHITECTURE.md` |
| Any release | `CHANGELOG.md` |

## Do Not

- Commit `.env` or secrets.
- Delete migrations that have been deployed.
- Write documentation inside `vendor/`.
- Assume an API exists — check `routes/` and `API.md` first.

## Language

- Code & technical comments: **English**.
- Project documentation: **English**.
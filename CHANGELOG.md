# Changelog

All notable changes to the **Demo** project are documented here.

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

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

[Unreleased]: https://github.com/example/demo/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/example/demo/releases/tag/v0.1.0
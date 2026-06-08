# Modules

List of modules and components in the **Demo** project.

## Existing Modules

### 1. Core / Bootstrap

| Component | Location | Description |
|-----------|----------|-------------|
| Application bootstrap | `bootstrap/app.php` | Routing, middleware, exceptions |
| Inertia middleware | `app/Http/Middleware/HandleInertiaRequests.php` | Shared props to React |
| App service provider | `app/Providers/AppServiceProvider.php` | Service registration |
| Base controller | `app/Http/Controllers/Controller.php` | Abstract controller |
| Root template | `resources/views/app.blade.php` | Inertia HTML shell |

### 2. Authentication (Breeze)

| Component | Location | Description |
|-----------|----------|-------------|
| Auth routes | `routes/auth.php` | Login, register, password reset |
| Auth controllers | `app/Http/Controllers/Auth/` | 8 auth controllers |
| Login page | `resources/js/Pages/Auth/Login.jsx` | shadcn/ui form |
| Register page | `resources/js/Pages/Auth/Register.jsx` | shadcn/ui form |
| Password reset | `resources/js/Pages/Auth/ForgotPassword.jsx` | shadcn/ui form |
| Email verification | `resources/js/Pages/Auth/VerifyEmail.jsx` | shadcn/ui |
| Auth tests | `tests/Feature/Auth/` | 16 auth tests |

### 3. Dashboard & Profile

| Component | Location | Description |
|-----------|----------|-------------|
| Dashboard route | `routes/web.php` | `GET /dashboard` (auth + verified) |
| Dashboard page | `resources/js/Pages/Dashboard.jsx` | shadcn Card |
| Profile controller | `app/Http/Controllers/ProfileController.php` | Edit/update/delete |
| Profile pages | `resources/js/Pages/Profile/` | Profile forms |
| Profile tests | `tests/Feature/ProfileTest.php` | 5 profile tests |

### 4. Web — Welcome

| Component | Location | Description |
|-----------|----------|-------------|
| Home route | `routes/web.php` | `GET /` → Welcome (Inertia) |
| Welcome page | `resources/js/Pages/Welcome.jsx` | Landing page |
| Health check | `/up` | Laravel health endpoint |

### 5. UI — shadcn/ui

| Component | Location | Description |
|-----------|----------|-------------|
| CLI config | `components.json` | shadcn configuration |
| Utils | `resources/js/lib/utils.js` | `cn()` class merge helper |
| Button | `components/ui/button.jsx` | Primary/secondary/destructive |
| Input | `components/ui/input.jsx` | Text inputs |
| Label | `components/ui/label.jsx` | Form labels |
| Card | `components/ui/card.jsx` | Content containers |
| Checkbox | `components/ui/checkbox.jsx` | Boolean inputs |
| Dialog | `components/ui/dialog.jsx` | Delete account modal |
| Dropdown Menu | `components/ui/dropdown-menu.jsx` | User nav menu |
| Sheet | `components/ui/sheet.jsx` | Mobile nav |
| Separator | `components/ui/separator.jsx` | Dividers |

### 6. Layouts

| Component | Location | Description |
|-----------|----------|-------------|
| Guest layout | `resources/js/Layouts/GuestLayout.jsx` | Auth pages (shadcn Card) |
| Authenticated layout | `resources/js/Layouts/AuthenticatedLayout.jsx` | Dropdown + Sheet nav |

### 7. User (Data Layer)

| Component | Location | Description |
|-----------|----------|-------------|
| User model | `app/Models/User.php` | Authenticatable user |
| User migration | `database/migrations/0001_01_01_000000_create_users_table.php` | `users` table |
| User factory | `database/factories/UserFactory.php` | Fake user data |
| Database seeder | `database/seeders/DatabaseSeeder.php` | 1 test user |

### 8. Infrastructure Tables

| Table | Migration | Purpose |
|-------|-----------|---------|
| `sessions` | `0001_01_01_000000` | Session storage |
| `password_reset_tokens` | `0001_01_01_000000` | Password reset |
| `cache`, `cache_locks` | `0001_01_01_000001` | Cache storage |
| `jobs`, `job_batches`, `failed_jobs` | `0001_01_01_000002` | Queue system |

### 9. Frontend Assets

| Component | Location | Description |
|-----------|----------|-------------|
| CSS entry | `resources/css/app.css` | Tailwind 4 + shadcn theme |
| JS entry | `resources/js/app.jsx` | Inertia + React bootstrap |
| Vite config | `vite.config.js` | Vite + React + Tailwind |

### 10. Testing

| Component | Location | Description |
|-----------|----------|-------------|
| Auth tests | `tests/Feature/Auth/` | Login, register, reset, verify |
| Profile tests | `tests/Feature/ProfileTest.php` | Profile CRUD |
| Example test | `tests/Feature/ExampleTest.php` | `GET /` returns 200 |

**Total: 25 tests passing.**

---

## Planned Modules

See [ROADMAP.md](ROADMAP.md) for the full schedule.

| Module | Priority | Description |
|--------|----------|-------------|

| User Management | Medium | Admin CRUD with shadcn Table |
| API Layer | Medium | REST API with Sanctum |
| Notifications | Low | Email / in-app notifications |

---

## Module Pattern

```
app/Http/Controllers/          # Laravel controllers
resources/js/Pages/            # Inertia React pages
resources/js/Layouts/          # Page layouts
resources/js/components/ui/    # shadcn components (via CLI)
routes/web.php                 # Web routes
routes/auth.php                # Auth routes
tests/Feature/                 # Feature tests
```

## Module Dependencies

```
┌─────────────┐
│   Welcome   │  ← active
└─────────────┘

┌─────────────┐     ┌──────────────┐
│    Auth     │ ──► │  Dashboard   │  ← active (Breeze + partial shadcn)
└──────┬──────┘     └──────┬───────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌──────────────┐
│ User Mgmt   │     │     API      │  ← planned
└─────────────┘     └──────────────┘
```
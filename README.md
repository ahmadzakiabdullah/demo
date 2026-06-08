# Demo Project

**Laravel 13** — A Laravel project using Laragon for local development.

## Project Info

| Item              | Details                          |
|-------------------|----------------------------------|
| Project Name      | Demo                             |
| Local URL         | https://demo.test                |
| Framework         | Laravel 13.14                    |
| PHP Version       | 8.4 (Laragon)                    |
| Database          | MySQL 8.0.30                     |
| Web Server        | Laragon (Apache / Nginx)         |
| Frontend          | React 18 + Inertia.js            |
| UI Library        | shadcn/ui + Tailwind CSS 4       |

## Documentation

| File | Contents |
|------|----------|
| [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) | Project context & status |
| [ARCHITECTURE.md](ARCHITECTURE.md) | System architecture |
| [MODULES.md](MODULES.md) | Modules & components |
| [DATABASE.md](DATABASE.md) | Database schema |
| [API.md](API.md) | API endpoints |
| [UI_UX.md](UI_UX.md) | UI/UX guidelines |
| [ROADMAP.md](ROADMAP.md) | Development roadmap |
| [AGENTS.md](AGENTS.md) | Instructions for AI agents |
| [CLAUDE.md](CLAUDE.md) | Quick reference for Claude |
| [CHANGELOG.md](CHANGELOG.md) | Change history |

## Requirements

- [Laragon](https://laragon.org/) (Full version recommended)
- PHP 8.3 / 8.4 (provided by Laragon)
- Composer
- Node.js (for Vite & frontend assets)
- Git

## Local Setup (Laragon)

### 1. Clone / Setup Project

```powershell
# Place this folder at:
# D:\www\demo   (or according to your Laragon Document Root)
```

### 2. Install Dependencies

```powershell
composer install
npm install
```

### 3. Environment Configuration

The `.env` file should be configured as follows:

```env
APP_URL=https://demo.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demo
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Create Database

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or create it manually via HeidiSQL / Laragon MySQL.

### 5. Run Migrations

```powershell
php artisan migrate
```

### 6. Laragon Configuration (IMPORTANT)

For `https://demo.test` to work correctly with Laravel:

1. Open **Laragon**
2. Ensure Laragon **Document Root** is set to `D:\www`
3. Find `demo.test` in the site list
4. **Right click** → **Open vhost file**
5. Change the following line:

   ```apache
   DocumentRoot "D:/www/demo"
   ```

   to:

   ```apache
   DocumentRoot "D:/www/demo/public"
   ```

   Also update `<Directory "D:/www/demo/public">` if present.

6. **Restart All** in Laragon.

### 7. Enable HTTPS (Optional)

- Right click Laragon tray icon → **Menu → SSL**
- Select **Enable SSL** for all sites
- Restart Laragon

### 8. Open Project

Open your browser and go to:

**https://demo.test**

You should see the default Laravel welcome page.

---

## Useful Commands

### Artisan

```powershell
# Clear cache
php artisan optimize:clear

# Migration
php artisan migrate
php artisan migrate:fresh --seed

# Tinker (REPL)
php artisan tinker

# Create model + migration + controller
php artisan make:model Post -mcr

# Queue & Schedule
php artisan queue:work
php artisan schedule:work
```

### Frontend (Vite + React + shadcn/ui)

```powershell
# Install (one-time)
npm install

# Development mode (auto reload)
npm run dev

# Production build
npm run build

# Add shadcn/ui component
npx shadcn@latest add button dialog table
```

UI components live in `resources/js/components/ui/`. Pages are React/Inertia components in `resources/js/Pages/`. See [UI_UX.md](UI_UX.md) for full guidelines.

### Testing

```powershell
# Run tests
php artisan test

# or
vendor/bin/phpunit
```

---

## Important Folder Structure

```
demo/
├── app/                  # Business logic, Models, Controllers
├── bootstrap/            # App bootstrap & providers
├── config/               # Application configuration
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/               # Web root (DocumentRoot must point here!)
├── resources/
│   ├── views/            # Blade templates (app.blade.php)
│   ├── js/
│   │   ├── Pages/        # Inertia React pages
│   │   ├── components/ui/  # shadcn/ui components
│   │   └── app.jsx       # React entry point
│   └── css/              # Tailwind + shadcn theme
├── routes/
│   ├── web.php
│   ├── auth.php
│   └── console.php
├── storage/              # Logs, cache, uploaded files
└── tests/
```

---

## Development Notes

- Use `php artisan` for most tasks.
- Do not edit files inside `vendor/` or `public/build/`.
- For new assets, edit files in `resources/` then run `npm run dev`.
- Storage link (if using file uploads):

  ```powershell
  php artisan storage:link
  ```

- Laravel logs are at `storage/logs/laravel.log`

---

## Git

This project has not been initialized as a Git repository yet.

```powershell
git init
git add .
git commit -m "Initial Laravel 13 setup"
```

Suggested branches: `main` or `develop`

---

## Deployment (Coming Soon)

- Production server (Forge, Ploi, VPS, etc.)
- Environment: `APP_ENV=production`
- `php artisan config:cache`
- `php artisan route:cache`
- `npm run build`

---

## Troubleshooting

If you encounter issues during setup:

1. Ensure Laragon MySQL is running
2. Ensure DocumentRoot in vhost points to `/public`
3. Run `php artisan optimize:clear`
4. Check `storage/logs/laravel.log`

---

**Prepared for local development using Laragon**  
Setup date: 2026
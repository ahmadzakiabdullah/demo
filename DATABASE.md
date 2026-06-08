# Database

Database schema for the **Demo** project.

## Configuration

| Item | Value |
|------|-------|
| Engine | MySQL 8.0.30 |
| Database | `demo` |
| Host | `127.0.0.1:3306` |
| Charset | `utf8mb4` |
| Collation | `utf8mb4_unicode_ci` |
| User | `root` (no password) |

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demo
DB_USERNAME=root
DB_PASSWORD=
```

## Create Database

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
```

## Relationship Diagram

```
users (1) ──────< sessions (0..n)
  │
  └── email ──── password_reset_tokens (0..1)
```

Tables `cache`, `jobs`, and related tables are standalone (no FK to `users`).

---

## Tables

### `users`

Migration: `0001_01_01_000000_create_users_table.php`  
Model: `App\Models\User`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `bigint unsigned` | PK, auto-increment | Primary key |
| `name` | `varchar(255)` | NOT NULL | User name |
| `email` | `varchar(255)` | UNIQUE, NOT NULL | Login email |
| `email_verified_at` | `timestamp` | NULLABLE | Email verification date |
| `password` | `varchar(255)` | NOT NULL | Bcrypt hash |
| `remember_token` | `varchar(100)` | NULLABLE | Remember me token |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Eloquent casts:** `email_verified_at` → datetime, `password` → hashed.

---

### `password_reset_tokens`

Migration: `0001_01_01_000000_create_users_table.php`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `email` | `varchar(255)` | PK | User email |
| `token` | `varchar(255)` | NOT NULL | Reset token |
| `created_at` | `timestamp` | NULLABLE | |

---

### `sessions`

Migration: `0001_01_01_000000_create_users_table.php`  
Driver: `SESSION_DRIVER=database`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `varchar(255)` | PK | Session ID |
| `user_id` | `bigint unsigned` | NULLABLE, INDEX, FK → users | Logged-in user |
| `ip_address` | `varchar(45)` | NULLABLE | Client IP |
| `user_agent` | `text` | NULLABLE | Browser UA |
| `payload` | `longtext` | NOT NULL | Session data |
| `last_activity` | `int` | INDEX | Unix timestamp |

---

### `cache`

Migration: `0001_01_01_000001_create_cache_table.php`  
Driver: `CACHE_STORE=database`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `key` | `varchar(255)` | PK | Cache key |
| `value` | `mediumtext` | NOT NULL | Serialized value |
| `expiration` | `bigint` | INDEX | Unix expiry |

---

### `cache_locks`

Migration: `0001_01_01_000001_create_cache_table.php`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `key` | `varchar(255)` | PK | Lock key |
| `owner` | `varchar(255)` | NOT NULL | Lock owner |
| `expiration` | `bigint` | INDEX | Unix expiry |

---

### `jobs`

Migration: `0001_01_01_000002_create_jobs_table.php`  
Driver: `QUEUE_CONNECTION=database`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `bigint unsigned` | PK | Job ID |
| `queue` | `varchar(255)` | INDEX | Queue name |
| `payload` | `longtext` | NOT NULL | Job data |
| `attempts` | `smallint unsigned` | NOT NULL | Retry count |
| `reserved_at` | `int unsigned` | NULLABLE | Reserved timestamp |
| `available_at` | `int unsigned` | NOT NULL | Available timestamp |
| `created_at` | `int unsigned` | NOT NULL | Created timestamp |

---

### `job_batches`

Migration: `0001_01_01_000002_create_jobs_table.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | `varchar(255)` PK | Batch UUID |
| `name` | `varchar(255)` | Batch name |
| `total_jobs` | `int` | Total jobs |
| `pending_jobs` | `int` | Pending count |
| `failed_jobs` | `int` | Failed count |
| `failed_job_ids` | `longtext` | Failed job IDs |
| `options` | `mediumtext` | Batch options |
| `cancelled_at` | `int` | Cancelled timestamp |
| `created_at` | `int` | Created timestamp |
| `finished_at` | `int` | Finished timestamp |

---

### `failed_jobs`

Migration: `0001_01_01_000002_create_jobs_table.php`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `bigint unsigned` | PK | |
| `uuid` | `varchar(255)` | UNIQUE | Job UUID |
| `connection` | `varchar(255)` | NOT NULL | Queue connection |
| `queue` | `varchar(255)` | NOT NULL | Queue name |
| `payload` | `longtext` | NOT NULL | Job data |
| `exception` | `longtext` | NOT NULL | Exception trace |
| `failed_at` | `timestamp` | DEFAULT CURRENT | |

Index: `(connection, queue, failed_at)`

---

## Seeding

```powershell
php artisan db:seed
# or
php artisan migrate:fresh --seed
```

**Default data** (`DatabaseSeeder`):

| Table | Record |
|-------|--------|
| `users` | name: `Test User`, email: `test@example.com` |

---

## Useful Commands

```powershell
# Migration status
php artisan migrate:status

# Rollback one batch
php artisan migrate:rollback

# Reset & re-seed
php artisan migrate:fresh --seed

# REPL
php artisan tinker
```

## Conventions (New Modules)

- Table names: plural snake_case
- Primary key: `id` (bigint unsigned)
- Foreign key: `{singular}_id` with `foreignId()->constrained()`
- Timestamps: `timestamps()` or `timestampsTz()` if timezone needed
- Soft delete: `softDeletes()` if needed
- Indexes: add for frequently queried columns
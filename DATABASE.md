# Database Design

# SportOS

Database architecture for the SportOS platform.

> **Status legend:** tables in §4–5 are **Implemented**. Tables in §6–8 are **Planned** (designed, not migrated).

---

## 1. Configuration

| Item | Value |
|------|-------|
| Engine | MySQL 8.0.30 |
| Database | `demo` (local) / `sportos` (production) |
| Host | `127.0.0.1:3306` |
| Charset | `utf8mb4` |
| Collation | `utf8mb4_unicode_ci` |

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demo
DB_USERNAME=root
DB_PASSWORD=
```

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
```

---

## 2. Design Principles

| Principle | Rule |
|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |--|------|
| Naming | Plural snake_case tables; `{model}_id` foreign keys |
| Tenancy | `organization_id` on all domain tables (planned) |
| Timestamps | `created_at`, `updated_at` on all tables |
| Soft delete | `deleted_at` on user-facing entities where audit required |
| Indexing | Composite indexes on `(organization_id, status)`, FK columns |
| Migrations only | Never modify DB manually |

---

## 3. Entity Relationship Diagram (Target)

```
┌─────────────────┐       ┌──────────────────┐
│  organizations  │───┬──<│    branches      │
└────────┬────────┘   │   └──────────────────┘
         │            │
         │            │   ┌──────────────────┐
         ├────────────┼──<│ organization_user│>──┐
         │            │   └──────────────────┘  │
         │            │                           │
         │            │   ┌──────────┐  ┌─────────▼──┐
         │            └──<│  roles   │  │   users    │
         │                └────┬─────┘  └────────────┘
         │                     │
         │                ┌────▼─────────┐
         │                │role_permission│>── permissions
         │                └──────────────┘
         │
         ├────< events ────< event_categories
         │         │
         │         ├────< event_participants ────< participant_sport_entries  (planned)
         │         │              │
         │         │              └── optional branch_id → branches
         │         │
         │         ├────< sports ────< disciplines ────< categories
         │         │
         │         ├────< venues ────< facilities
         │         │
         │         ├────< teams ────< team_athlete (pivot)
         │         │         └── event_participant_id (planned)
         │         │
         │         ├────< athletes (via registrations; event_participant_id planned)
         │         │
         │         ├────< officials
         │         │
         │         ├────< competitions ────< groups
         │         │              │
         │         │              └───< fixtures ────< matches ────< results ────< result_appeals
         │         │
         │         ├────< rankings
         │         ├────< medals
         │         ├────< accreditations
         │         ├────< certificates
         │         ├────< announcements
         │         └────< media
         │
         ├────< audit_logs (polymorphic)
         ├────< notifications
         └────< settings

Infrastructure (no tenant FK):
  sessions, cache, jobs, password_reset_tokens, personal_access_tokens
```

---

## 4. Implemented Tables — Core

### `users`

Migrations: `0001_01_01_000000_create_users_table.php`, `2026_06_08_032134_migrate_users_to_rbac_and_drop_role_column.php`  
Model: `App\Models\User`

| Column | Type | Constraints | Description |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK | Primary key |
| `name` | `varchar(255)` | NOT NULL | Display name |
| `email` | `varchar(255)` | UNIQUE, NOT NULL | Login email |
| `email_verified_at` | `timestamp` | NULLABLE | Verification date |
| `password` | `varchar(255)` | NOT NULL | Bcrypt hash |
| `remember_token` | `varchar(100)` | NULLABLE | Remember me |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

> System roles assigned via `role_user` pivot. Owner `ahmadzaki@utem.edu.my` is `system_owner`. Do not delete.

### `password_reset_tokens`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `email` | `varchar(255)` | PK |
| `token` | `varchar(255)` | NOT NULL |
| `created_at` | `timestamp` | NULLABLE |

### `sessions`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `varchar(255)` | PK |
| `user_id` | `bigint unsigned` | NULLABLE, FK → users |
| `ip_address` | `varchar(45)` | NULLABLE |
| `user_agent` | `text` | NULLABLE |
| `payload` | `longtext` | NOT NULL |
| `last_activity` | `int` | INDEX |

### `cache` / `cache_locks`

Standard Laravel cache tables. Driver: `CACHE_STORE=database`.

### `jobs` / `job_batches` / `failed_jobs`

Standard Laravel queue tables. Driver: `QUEUE_CONNECTION=database`.

---

## 5. Implemented Tables — Organizations (Phase 1.2)

Migration: `2026_06_08_031123_create_organizations_table.php`

### `organizations`

Model: `App\Models\Organization`

| Column | Type | Constraints | Description |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK | |
| `name` | `varchar(255)` | NOT NULL | Organization name |
| `slug` | `varchar(255)` | UNIQUE | URL identifier |
| `type` | `varchar(50)` | NOT NULL | `OrganizationType` enum |
| `logo_path` | `varchar(255)` | NULLABLE | Branding (future) |
| `timezone` | `varchar(50)` | DEFAULT `Asia/Kuala_Lumpur` | |
| `locale` | `varchar(10)` | DEFAULT `en` | |
| `status` | `varchar(20)` | DEFAULT `active` | `OrganizationStatus` enum |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |
| `deleted_at` | `timestamp` | NULLABLE | Soft delete |

### `branches`

Model: `App\Models\Branch`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `name` | `varchar(255)` | NOT NULL |
| `code` | `varchar(50)` | NULLABLE |
| `parent_id` | `bigint unsigned` | NULLABLE, FK → branches |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

### `organization_user`

Migration: `2026_06_08_032133_upgrade_organization_user_for_rbac.php`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `organization_id` | `bigint unsigned` | FK → organizations |
| `user_id` | `bigint unsigned` | FK → users |
| `role_id` | `bigint unsigned` | FK → roles |
| `status` | `varchar(20)` | DEFAULT `active` |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**PK:** `(organization_id, user_id)` — Pilot: `OrganizationSeeder` links UTeM to owner as `org_admin`.

---

## 6. Implemented Tables — RBAC (Phase 1.3)

Migrations: `2026_06_08_032132_create_roles_and_permissions_tables.php`, `2026_06_08_032133_upgrade_organization_user_for_rbac.php`  
Seeder: `RolesAndPermissionsSeeder`

### `roles`

Model: `App\Models\Role`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `description` | `text` | NULLABLE |
| `organization_id` | `bigint unsigned` | NULLABLE, FK → organizations |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**Unique:** `(slug, organization_id)` — `organization_id` NULL = system role template.

**System roles:** `system_owner`, `org_admin`, `event_organizer`, `sports_manager`, `team_manager`, `athlete`, `official`, `volunteer`, `media`

### `permissions`

Model: `App\Models\Permission`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | UNIQUE |
| `module` | `varchar(50)` | NOT NULL |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**Format:** `{module}.{action}` — actions: `view`, `create`, `update`, `delete`, `manage`

### `role_permission`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `role_id` | `bigint unsigned` | FK → roles |
| `permission_id` | `bigint unsigned` | FK → permissions |

**PK:** `(role_id, permission_id)`

### `role_user`

System/global role assignments.

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `role_id` | `bigint unsigned` | FK → roles |
| `user_id` | `bigint unsigned` | FK → users |

**PK:** `(role_id, user_id)` — e.g. `system_owner` for platform admins.

---

## 7. Implemented Tables — Audit Logs (Phase 1.4)

Migration: `2026_06_08_033040_create_audit_logs_table.php`  
Model: `App\Models\AuditLog` · Trait: `App\Models\Concerns\Auditable` · Service: `App\Support\AuditLogger`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | NULLABLE, FK → organizations |
| `user_id` | `bigint unsigned` | NULLABLE, FK → users |
| `action` | `varchar(50)` | NOT NULL (`created`, `updated`, `deleted`) |
| `auditable_type` | `varchar(255)` | NOT NULL |
| `auditable_id` | `bigint unsigned` | NOT NULL |
| `old_values` | `json` | NULLABLE |
| `new_values` | `json` | NULLABLE |
| `ip_address` | `varchar(45)` | NULLABLE |
| `user_agent` | `text` | NULLABLE |
| `created_at` | `timestamp` | NOT NULL (append-only; no `updated_at`) |

**Indexes:** `(organization_id, created_at)`, `(auditable_type, auditable_id)`, `action`

**Audited models:** `User`, `Organization`, `Branch`

---

## 8. Implemented Tables — Events (Phase 1.5)

Migration: `2026_06_08_033908_create_events_tables.php`  
Seeder: `EventReferenceDataSeeder`

### `event_types`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | UNIQUE |

**Seeded:** `multi-sport`, `tournament`, `league`, `friendly`

### `event_categories`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | UNIQUE |

**Seeded:** `school`, `university`, `elite`

### `events`

Model: `App\Models\Event`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `event_type_id` | `bigint unsigned` | FK → event_types |
| `event_category_id` | `bigint unsigned` | FK → event_categories |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `status` | `varchar(20)` | DEFAULT `draft` (`EventStatus` enum) |
| `location` | `varchar(255)` | NULLABLE |
| `description` | `text` | NULLABLE |
| `starts_at` | `timestamp` | NULLABLE |
| `ends_at` | `timestamp` | NULLABLE |
| `edition_year` | `smallint unsigned` | NULLABLE → **planned NOT NULL** |
| `cadence` | `varchar(20)` | NULLABLE — `annual`, `biennial`, `quadrennial`, `one_off` (planned) |
| `event_series_id` | `bigint unsigned` | FK → event_series, NULLABLE (planned) |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |
| `deleted_at` | `timestamp` | NULLABLE (soft delete) |

**Unique:** `(organization_id, slug)` · **Indexes:** `(organization_id, status)`, `(organization_id, edition_year)` (planned)

> **Edition year:** Nominal session year for sorting (e.g. SUKMA 2026 → `edition_year = 2026`). Distinct from `starts_at` — a biennial games may run Dec 2025–Jan 2026 but still label as edition 2026. Slug should include year: `sukma-selangor-2026`.

**Lifecycle:** `draft` → `published` → `active` → `completed` → `archived`

### `event_user`

Event team assignments.

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `event_id` | `bigint unsigned` | FK → events |
| `user_id` | `bigint unsigned` | FK → users |
| `role` | `varchar(50)` | `event_organizer`, `sports_manager`, or `team_manager` |

**PK:** `(event_id, user_id)`

### `sports`

Model: `App\Models\Sport` · Scoped to `event_id`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `event_id` | `bigint unsigned` | FK → events |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `template_slug` | `varchar(255)` | NULLABLE |
| `status` | `varchar(20)` | DEFAULT `active` |
| `rules` | `json` | NULLABLE |
| `deleted_at` | `timestamp` | NULLABLE |

**Unique:** `(event_id, slug)`

### `sport_disciplines`

Model: `App\Models\SportDiscipline`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `sport_id` | `bigint unsigned` | FK → sports |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `sort_order` | `smallint unsigned` | DEFAULT 0 |

**Unique:** `(sport_id, slug)`

### `sport_categories`

Model: `App\Models\SportCategory`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `sport_discipline_id` | `bigint unsigned` | FK → sport_disciplines |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `gender` | `varchar(20)` | DEFAULT `open` |
| `min_age` | `tinyint unsigned` | NULLABLE |
| `max_age` | `tinyint unsigned` | NULLABLE |
| `min_weight` | `decimal(5,2)` | NULLABLE |
| `max_weight` | `decimal(5,2)` | NULLABLE |
| `sort_order` | `smallint unsigned` | DEFAULT 0 |

**Unique:** `(sport_discipline_id, slug)`

### `sport_divisions`

Model: `App\Models\SportDivision`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `sport_category_id` | `bigint unsigned` | FK → sport_categories |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `sort_order` | `smallint unsigned` | DEFAULT 0 |

**Unique:** `(sport_category_id, slug)`

### `athletes`

Model: `App\Models\Athlete` · Scoped to `organization_id`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `user_id` | `bigint unsigned` | FK → users, NULLABLE |
| `name` | `varchar(255)` | NOT NULL |
| `dob` | `date` | NULLABLE |
| `gender` | `varchar(20)` | NULLABLE |
| `nationality` | `varchar(100)` | NULLABLE |
| `id_number` | `varchar(100)` | NULLABLE |
| `photo_path` | `varchar(255)` | NULLABLE |
| `medical_clearance` | `boolean` | DEFAULT false |
| `deleted_at` | `timestamp` | NULLABLE |

**Unique:** `(organization_id, id_number)`

### `registrations`

Model: `App\Models\Registration` · Polymorphic `registrable` (athlete, team, official)

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `event_id` | `bigint unsigned` | FK → events |
| `sport_id` | `bigint unsigned` | FK → sports |
| `registrable_type` | `varchar(255)` | NOT NULL |
| `registrable_id` | `bigint unsigned` | NOT NULL |
| `sport_category_id` | `bigint unsigned` | FK → sport_categories, NULLABLE |
| `sport_division_id` | `bigint unsigned` | FK → sport_divisions, NULLABLE |
| `status` | `varchar(20)` | DEFAULT `draft` |
| `notes` | `text` | NULLABLE |
| `rejected_reason` | `text` | NULLABLE |
| `submitted_at` | `timestamp` | NULLABLE |
| `verified_at` | `timestamp` | NULLABLE |
| `approved_at` | `timestamp` | NULLABLE |
| `deleted_at` | `timestamp` | NULLABLE |

**Unique:** `(event_id, sport_id, registrable_type, registrable_id)`

**Status enum:** `draft`, `submitted`, `verified`, `approved`, `rejected`

### `teams`

Model: `App\Models\Team` · Scoped to `organization_id` (host tenant), `event_id`, `sport_id`  
> **Planned:** `event_participant_id` FK for competing unit (fakulti/negeri/negara). See [§9](#9-planned-tables--event-participants-refactor).

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `event_id` | `bigint unsigned` | FK → events |
| `sport_id` | `bigint unsigned` | FK → sports |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `coach_user_id` | `bigint unsigned` | FK → users, NULLABLE |
| `manager_user_id` | `bigint unsigned` | FK → users, NULLABLE |
| `notes` | `text` | NULLABLE |
| `deleted_at` | `timestamp` | NULLABLE |

**Unique:** `(event_id, sport_id, slug)`

### `team_athlete`

Roster pivot between teams and athletes.

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `team_id` | `bigint unsigned` | FK → teams |
| `athlete_id` | `bigint unsigned` | FK → athletes |
| `role` | `varchar(20)` | DEFAULT `member` (`member`, `captain`) |
| `jersey_number` | `varchar(10)` | NULLABLE |

**Unique:** `(team_id, athlete_id)`

### `officials`

Model: `App\Models\Official` · Scoped to `organization_id`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `user_id` | `bigint unsigned` | FK → users, NULLABLE |
| `name` | `varchar(255)` | NOT NULL |
| `email` | `varchar(255)` | NULLABLE |
| `type` | `varchar(30)` | NOT NULL (`referee`, `judge`, `technical_officer`, `timekeeper`) |
| `certification_level` | `varchar(255)` | NULLABLE |
| `certification_expires_at` | `date` | NULLABLE |
| `deleted_at` | `timestamp` | NULLABLE |

**Index:** `(organization_id, name)`, `(organization_id, type)`

Event registration via polymorphic `registrations` (same workflow as athletes/teams). Expired certification blocks submit.

### `venues`

Model: `App\Models\Venue` · Scoped to `organization_id`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `address` | `varchar(255)` | NULLABLE |
| `capacity` | `unsigned int` | NULLABLE |
| `timezone` | `varchar(255)` | DEFAULT `UTC` |
| `notes` | `text` | NULLABLE |
| `deleted_at` | `timestamp` | NULLABLE |

**Unique:** `(organization_id, slug)`

### `facilities`

Model: `App\Models\Facility` · Child of `venues`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `venue_id` | `bigint unsigned` | FK → venues |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `type` | `varchar(20)` | NOT NULL (`court`, `field`, `lane`, `track`, `pool`, `other`) |
| `capacity` | `unsigned int` | NULLABLE |
| `sort_order` | `unsigned int` | DEFAULT 0 |

**Unique:** `(venue_id, slug)`

### `event_venue`

Pivot: attach org venues to events.

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `event_id` | `bigint unsigned` | FK → events |
| `venue_id` | `bigint unsigned` | FK → venues |
| `is_primary` | `boolean` | DEFAULT false |
| `notes` | `text` | NULLABLE |

**Unique:** `(event_id, venue_id)`

### `event_sport_venue`

Pivot: link event venues to sports within an event.

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `event_id` | `bigint unsigned` | FK → events |
| `sport_id` | `bigint unsigned` | FK → sports |
| `venue_id` | `bigint unsigned` | FK → venues |

**Unique:** `(event_id, sport_id, venue_id)`

### `competition_formats`

Global reference table (seeded). Model: `App\Models\CompetitionFormat`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | UNIQUE (`league`, `round_robin`, `knockout`, `group_stage`) |
| `description` | `varchar(255)` | NULLABLE |
| `sort_order` | `smallint unsigned` | DEFAULT 0 |

### `competitions`

Model: `App\Models\Competition` · Scoped to `organization_id`, `event_id`, `sport_id`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `event_id` | `bigint unsigned` | FK → events |
| `sport_id` | `bigint unsigned` | FK → sports |
| `competition_format_id` | `bigint unsigned` | FK → competition_formats |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `status` | `varchar(20)` | DEFAULT `draft` |
| `notes` | `text` | NULLABLE |
| `deleted_at` | `timestamp` | NULLABLE |

**Unique:** `(event_id, sport_id, slug)` · **Index:** `(organization_id, status)`

### `groups`

Model: `App\Models\CompetitionGroup` (table `groups`) · For group-stage competitions

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `competition_id` | `bigint unsigned` | FK → competitions |
| `name` | `varchar(255)` | NOT NULL |
| `slug` | `varchar(255)` | NOT NULL |
| `sort_order` | `smallint unsigned` | DEFAULT 0 |

**Unique:** `(competition_id, slug)`

### `fixtures`

Model: `App\Models\Fixture`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `competition_id` | `bigint unsigned` | FK → competitions |
| `group_id` | `bigint unsigned` | FK → groups, NULLABLE |
| `name` | `varchar(255)` | NOT NULL |
| `round` | `varchar(255)` | NULLABLE |
| `sort_order` | `smallint unsigned` | DEFAULT 0 |

### `matches`

Model: `App\Models\MatchGame` (table `matches`)

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `fixture_id` | `bigint unsigned` | FK → fixtures |
| `venue_id` | `bigint unsigned` | FK → venues, NULLABLE |
| `facility_id` | `bigint unsigned` | FK → facilities, NULLABLE |
| `scheduled_at` | `timestamp` | NULLABLE |
| `duration_minutes` | `smallint unsigned` | DEFAULT 60 |
| `status` | `varchar(20)` | DEFAULT `scheduled` |
| `notes` | `text` | NULLABLE |
| `winner_advances_to_match_id` | `bigint unsigned` | FK → matches, NULLABLE (knockout bracket) |
| `winner_advances_side` | `varchar(10)` | NULLABLE (`home` / `away`) |

**Index:** `(scheduled_at, venue_id)`, `(fixture_id, status)`

### `match_participants`

Polymorphic participants (team or athlete). Model: `App\Models\MatchParticipant`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `match_id` | `bigint unsigned` | FK → matches |
| `participant_type` | `varchar(255)` | NOT NULL |
| `participant_id` | `bigint unsigned` | NOT NULL |
| `side` | `varchar(10)` | `home` / `away` |
| `sort_order` | `smallint unsigned` | DEFAULT 0 |

**Unique:** `(match_id, side)`

### `match_officials`

Model: `App\Models\MatchOfficial`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `match_id` | `bigint unsigned` | FK → matches |
| `official_id` | `bigint unsigned` | FK → officials |
| `role` | `varchar(30)` | DEFAULT `referee` |

**Unique:** `(match_id, official_id)`

### `results`

Model: `App\Models\Result` · One result per match

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `match_id` | `bigint unsigned` | FK → matches, UNIQUE |
| `entered_by` | `bigint unsigned` | FK → users, NULLABLE |
| `data` | `json` | NOT NULL (`home_score`, `away_score`, `winner_side`) |
| `status` | `varchar(20)` | DEFAULT `pending` |
| `confirmed_by` | `bigint unsigned` | FK → users, NULLABLE |
| `confirmed_at` | `timestamp` | NULLABLE |
| `published_at` | `timestamp` | NULLABLE |
| `notes` | `text` | NULLABLE |

**Status enum:** `pending`, `confirmed`, `published`

### `result_appeals`

Migration: `2026_06_08_064824_create_result_appeals_table.php`  
Model: `App\Models\ResultAppeal` · Appeals against confirmed/published results

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `result_id` | `bigint unsigned` | FK → results |
| `submitted_by` | `bigint unsigned` | FK → users |
| `reason` | `text` | NOT NULL |
| `status` | `varchar(20)` | DEFAULT `submitted` |
| `proposed_home_score` | `smallint unsigned` | NULLABLE |
| `proposed_away_score` | `smallint unsigned` | NULLABLE |
| `reviewed_by` | `bigint unsigned` | FK → users, NULLABLE |
| `reviewed_at` | `timestamp` | NULLABLE |
| `resolution_notes` | `text` | NULLABLE |

**Status enum:** `submitted`, `under_review`, `upheld`, `overturned`

### `competition_participants`

Migration: `2026_06_08_065249_create_competition_participants_table.php`  
Model: `App\Models\CompetitionParticipant` · Seeding, Swiss points, ladder rank

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `competition_id` | `bigint unsigned` | FK → competitions |
| `participant_type` | `varchar` | Polymorphic type |
| `participant_id` | `bigint unsigned` | Polymorphic id |
| `seed` | `smallint unsigned` | DEFAULT 0 |
| `ladder_rank` | `smallint unsigned` | DEFAULT 0 |
| `swiss_points` | `decimal(5,1)` | DEFAULT 0 |
| `swiss_buchholz` | `smallint unsigned` | DEFAULT 0 |

### `medal_ceremonies`

Migration: `2026_06_08_065250_create_medal_ceremonies_table.php`  
Model: `App\Models\MedalCeremony`

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `organization_id` | `bigint unsigned` | FK → organizations |
| `event_id` | `bigint unsigned` | FK → events |
| `sport_id` | `bigint unsigned` | FK → sports, NULLABLE |
| `venue_id` | `bigint unsigned` | FK → venues, NULLABLE |
| `name` | `varchar` | NOT NULL |
| `scheduled_at` | `timestamp` | NULLABLE |
| `duration_minutes` | `smallint unsigned` | DEFAULT 60 |
| `notes` | `text` | NULLABLE |

### Phase 3 column additions

| Table | Column | Purpose |
|-------|--------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering ||
| `competitions` | `settings` JSON | Seeding, ranking rules, Swiss rounds, group advance count |
| `sports` | `score_schema` JSON | Sport-specific score entry schema |
| `matches` | `loser_advances_to_match_id`, `loser_advances_side`, `bracket_lane` | Double elimination progression |

### `rankings`

Model: `App\Models\Ranking` · Polymorphic `rankable` (team or athlete)

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `competition_id` | `bigint unsigned` | FK → competitions |
| `rankable_type` | `varchar(255)` | NOT NULL |
| `rankable_id` | `bigint unsigned` | NOT NULL |
| `position` | `smallint unsigned` | DEFAULT 0 |
| `points` | `smallint unsigned` | DEFAULT 0 |
| `played` | `smallint unsigned` | DEFAULT 0 |
| `won` | `smallint unsigned` | DEFAULT 0 |
| `drawn` | `smallint unsigned` | DEFAULT 0 |
| `lost` | `smallint unsigned` | DEFAULT 0 |
| `scored_for` | `smallint unsigned` | DEFAULT 0 |
| `scored_against` | `smallint unsigned` | DEFAULT 0 |

**Unique:** `(competition_id, rankable_type, rankable_id)`

### `medals`

Model: `App\Models\Medal` · Polymorphic `medalable` (team or athlete)

| Column | Type | Constraints |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK |
| `event_id` | `bigint unsigned` | FK → events |
| `sport_id` | `bigint unsigned` | FK → sports |
| `competition_id` | `bigint unsigned` | FK → competitions, NULLABLE |
| `medalable_type` | `varchar(255)` | NOT NULL |
| `medalable_id` | `bigint unsigned` | NOT NULL |
| `type` | `varchar(10)` | `gold`, `silver`, `bronze` |

**Unique:** `(competition_id, medalable_type, medalable_id, type)`

---

## 9. Implemented Tables — Event Participants

> **Status:** Implemented. Replaces modelling contingents (negeri/fakulti/negara) as separate `organizations`. See [FUNCTIONAL_SPEC.md §0](FUNCTIONAL_SPEC.md#0-unified-competition-lifecycle-event-first).

### `event_participants`

Competing unit registered for an event (fakulti, negeri, negara).

| Column | Type | Constraints | Description |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK | |
| `organization_id` | `bigint unsigned` | FK → organizations | Host tenant |
| `event_id` | `bigint unsigned` | FK → events | |
| `branch_id` | `bigint unsigned` | FK → branches, NULLABLE | SAF: link to fakulti branch |
| `type` | `varchar(20)` | NOT NULL | `faculty`, `state`, `country`, `club`, `other` |
| `name` | `varchar(255)` | NOT NULL | Display name (e.g. Selangor, FTK) |
| `code` | `varchar(20)` | NULLABLE | Short code (e.g. SLG, MAS) |
| `status` | `varchar(20)` | DEFAULT `active` | `active`, `inactive` |
| `metadata` | `json` | NULLABLE | Flag URL, colors, contact |
| `deleted_at` | `timestamp` | NULLABLE | Soft delete |

**Unique:** `(event_id, code)` where `code` is not null  
**Index:** `(event_id, type)`, `(organization_id, event_id)`

### `participant_sport_entries`

Participant declares which sports (and optional category/division) they enter — step 4 of canonical flow.

| Column | Type | Constraints | Description |
|--------|------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `id` | `bigint unsigned` | PK | |
| `event_participant_id` | `bigint unsigned` | FK → event_participants | |
| `sport_id` | `bigint unsigned` | FK → sports | |
| `sport_category_id` | `bigint unsigned` | FK → sport_categories, NULLABLE | |
| `sport_division_id` | `bigint unsigned` | FK → sport_divisions, NULLABLE | |
| `status` | `varchar(20)` | DEFAULT `draft` | Same as `registrations` workflow |
| `notes` | `text` | NULLABLE | |
| `rejected_reason` | `text` | NULLABLE | |
| `submitted_at` | `timestamp` | NULLABLE | |
| `approved_at` | `timestamp` | NULLABLE | |
| `deleted_at` | `timestamp` | NULLABLE | |

**Unique:** `(event_participant_id, sport_id, sport_category_id, sport_division_id)`

### Implemented column additions (existing tables)

| Table | Column | Purpose | Status |
|-------|--------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering ||--------|
| `events` | `participant_unit_label` | UI copy: `faculty`, `state`, `country` | Implemented |
| `events` | `edition_year` | Nominal session year for sorting | Implemented |
| `events` | `cadence` | `annual`, `biennial`, `quadrennial`, `one_off` | Implemented |
| `teams` | `event_participant_id` | FK → event_participants; replaces contingent-as-org pattern | Implemented |
| `athletes` | `event_participant_id` | Nullable; set when athlete registers for event via participant | Implemented |
| `medals` | `event_participant_id` | Medal table by negeri/fakulti/negara | Implemented |
| `rankings` | `event_participant_id` | Ranking by contingent | Implemented |

### Migration notes

- `Sukma2026Seeder` refactored: 16 negeri from `organizations` → `event_participants` on MSN tenant
- `teams.organization_id` retains host tenant; competing unit moved to `event_participant_id`
- `registrations` may link to `participant_sport_entry_id` (optional future FK)

---

## 11. Planned Tables — Phase 4 (Operations)

| Table | Key Columns |
|-------|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |----|
| `accreditations` | `id`, `event_id`, `accreditable_type`, `accreditable_id`, `type`, `qr_code`, `status` |
| `certificates` | `id`, `event_id`, `certifiable_type`, `certifiable_id`, `type`, `file_path` |
| `announcements` | `id`, `organization_id`, `event_id`, `title`, `body`, `published_at` |
| `media` | `id`, `event_id`, `type`, `title`, `url`, `file_path` |
| `notifications` | `id`, `user_id`, `type`, `data` (json), `read_at` |
| `settings` | `id`, `organization_id`, `key`, `value` (json) |

---

## 10. Indexing Strategy

| Pattern | Example | Reason |
|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering ||## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering ||--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |

---

## 11. Foreign Key Conventions

```php
$table->foreignId('organization_id')->constrained()->cascadeOnDelete();
$table->foreignId('event_id')->constrained()->cascadeOnDelete();
$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
```

- Cascade delete within same tenant hierarchy (event → sports → matches)
- Null on delete for optional user references
- Never cascade across organizations

---

## 12. Seeding Strategy

### Current (`DatabaseSeeder`)

| Account | Purpose |
|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering ||## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering ||
| `test@example.com` | Test admin for `migrate:fresh --seed` in dev/CI |
| `ahmadzaki@utem.edu.my` | **Project owner** — created manually; set `admin` via migration |

**Rule:** Never overwrite or delete the owner account in seeders or tests.

### Planned Seeders

| Seeder | Data |
|--------|------|
| `RolesAndPermissionsSeeder` | System roles + core module permissions (implemented) |
| `SportTemplatesSeeder` | Football, Badminton, Swimming, Athletics, Esports |
| `CompetitionFormatSeeder` | League, knockout, round robin, Swiss, etc. |
| `OrganizationSeeder` | Pilot org: sample university federation |
| `DatabaseSeeder` | Orchestrates above; preserves owner account |

---

## 13. Useful Commands

```powershell
php artisan migrate:status
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh    # Dev only — destroys data
php artisan db:seed
php artisan tinker
```

---

## 14. Related Documents

| Document | Link |
|## 10. Indexing Strategy

| Pattern | Example | Reason |
|---------|---------|--------|
| Tenant + status | `(organization_id, status)` | Filter active records per org |
| Tenant + slug | `(organization_id, slug)` UNIQUE | URL lookup per org |
| Event scope | `(event_id, sport_id)` | Event dashboard queries |
| Match schedule | `(scheduled_at, venue_id)` | Conflict detection |
| Audit timeline | `(organization_id, created_at)` | Audit log pagination |
| Polymorphic | `(auditable_type, auditable_id)` | Audit lookups |
| **Participant Lookup** | `(event_id, event_participant_id)` | Fast filter for contingent-specific views |
| **Athlete Roster** | `(event_participant_id, sport_id)` | Quick roster retrieval per sport |
| **Result Performance** | `(event_id, sport_category_id, results_value)` | Fast ranking calculation for medals |
| **Fixture Timeline** | `(event_id, scheduled_at)` | Efficient calendar/timeline rendering |-|------|
| Architecture | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Functional spec | [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) |
| Roadmap | [ROADMAP.md](ROADMAP.md) |
| Security | [SECURITY.md](SECURITY.md) |

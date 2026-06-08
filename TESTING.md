# Testing Strategy

# SportOS

Testing approach for the SportOS platform across all phases.

> Never use `ahmadzaki@utem.edu.my` in tests. Use `User::factory()` or `test@example.com` seed account.

---

## 1. Testing Philosophy

- **Test behavior, not implementation** — feature tests for workflows, unit tests for algorithms.
- **Tenant isolation is non-negotiable** — every new model gets a cross-tenant access test.
- **Competition engine must be bulletproof** — bracket and ranking logic require exhaustive unit coverage.
- **CI gate** — no merge without passing tests.

**Current status:** 35 PHPUnit tests passing (auth, profile, admin user management).

---

## 2. Test Pyramid

```
        ┌─────────┐
        │  E2E    │  Few — critical user journeys
        ├─────────┤
        │ Feature │  Many — API, policies, workflows
        ├─────────┤
        │  Unit   │  Most — services, algorithms
        └─────────┘
```

| Layer | Tool | Target Coverage |
|-------|------|-----------------|
| Unit | PHPUnit | Services, bracket engine, ranking, eligibility |
| Feature | PHPUnit + Inertia assertions | Routes, policies, CRUD, API endpoints |
| Browser | Laravel Dusk or Pest Browser | Auth flow, admin CRUD, public portal |
| Frontend | Vitest + Testing Library | Bracket UI, schedule calendar, complex forms |
| Load | k6 or Artillery | API under concurrent event load |
| Security | PHPUnit + OWASP ZAP | Policy bypass, rate limits, injection |

---

## 3. PHPUnit Structure

```
tests/
├── Unit/
│   ├── Bracket/KnockoutGeneratorTest.php      (planned)
│   ├── Ranking/PointsTableCalculatorTest.php  (planned)
│   └── Eligibility/AgeRuleTest.php            (planned)
├── Feature/
│   ├── Auth/                                  (16 tests — active)
│   ├── Admin/UserManagementTest.php           (10 tests — active)
│   ├── ProfileTest.php                        (5 tests — active)
│   ├── Organization/                          (planned)
│   ├── Event/                                   (planned)
│   ├── Api/V1/                                  (planned)
│   └── TenantIsolationTest.php                  (planned)
└── Browser/                                     (planned)
```

---

## 4. Test Categories by Module

### 4.1 Core (Phase 1)

| Test | Type | Priority |
|------|------|----------|
| Organization CRUD | Feature | High |
| User cannot access other org data | Feature | Critical |
| RBAC permission enforcement | Feature | Critical |
| Audit log created on mutation | Feature | High |
| API token auth flow | Feature | High |

### 4.2 Sports & Registration (Phase 2)

| Test | Type | Priority |
|------|------|----------|
| Athlete registration workflow | Feature | High |
| Eligibility rule rejection | Unit + Feature | High |
| Team roster limits | Unit | Medium |
| Official conflict detection | Unit | High |
| Venue double-booking prevention | Unit | High |

### 4.3 Competition Engine (Phase 3)

| Test | Type | Priority |
|------|------|----------|
| Knockout bracket generation (8, 16, 32 teams) | Unit | Critical |
| Double elimination bracket | Unit | Critical |
| Round robin standings calculation | Unit | Critical |
| Swiss pairing (round N) | Unit | High |
| Result confirmation updates ranking | Feature | High |
| Medal auto-tally accuracy | Unit + Feature | Critical |

### 4.4 Operations (Phase 4)

| Test | Type | Priority |
|------|------|----------|
| QR accreditation validation | Feature | High |
| Certificate PDF generation | Feature | Medium |
| Report export (CSV, PDF) | Feature | Medium |

### 4.5 Public Portal (Phase 5)

| Test | Type | Priority |
|------|------|----------|
| Unpublished event returns 404 | Feature | High |
| Live results endpoint returns latest | Feature | High |
| Accessibility audit (axe-core) | Browser | Medium |

---

## 5. Testing Conventions

### 5.1 Database

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
}
```

- Use factories with `admin()` state and future `organization()` state.
- Never depend on production data in tests.

### 5.2 Inertia Assertions

```php
$response->assertInertia(fn ($page) => $page
    ->component('Admin/Users/Index')
    ->has('users.data', 3));
```

### 5.3 API Assertions

```php
$response->assertJsonStructure(['data' => ['id', 'name']]);
$response->assertStatus(403); // cross-tenant access
```

### 5.4 Policy Tests

```php
$this->assertTrue($orgAdmin->can('update', $event));
$this->assertFalse($athlete->can('delete', $event));
```

---

## 6. Factory Strategy (Planned)

| Factory | States |
|---------|--------|
| `OrganizationFactory` | `federation()`, `university()`, `school()` |
| `UserFactory` | `admin()` (current), `orgAdmin()`, `athlete()` |
| `EventFactory` | `draft()`, `published()`, `active()` |
| `AthleteFactory` | `verified()`, `approved()` |
| `MatchFactory` | `completed()`, `scheduled()` |

---

## 7. CI Pipeline (Planned)

```yaml
# Runs on every push / PR
steps:
  1. composer install
  2. npm ci && npm run build    # Required for Inertia page tests
  3. php artisan test
  4. composer audit
  5. npm audit --audit-level=high
```

---

## 8. Load Testing (Phase 3+)

Simulate live event scenario:

| Scenario | Target |
|----------|--------|
| 1,000 concurrent public result page views | p95 < 500ms |
| 100 officials submitting scores simultaneously | No data loss |
| API rate limit enforcement | 429 after threshold |

Tool: k6 scripts in `tests/load/` (planned).

---

## 9. Coverage Targets

| Phase | Service Layer | Feature Layer |
|-------|---------------|---------------|
| Phase 1 | 60% | 80% of routes |
| Phase 3 | 80% | 90% of routes |
| Phase 5 | 85% | 95% of routes |

```powershell
php artisan test --coverage  # Requires Xdebug or PCOV
```

---

## 10. Manual Testing Checklist

Before each release:

- [ ] Login / logout / password reset
- [ ] Admin user CRUD
- [ ] Organization switcher (when available)
- [ ] Event create → publish workflow
- [ ] Mobile responsive layout (admin + public)
- [ ] `npm run build` + verify assets load

---

## 11. Commands

```powershell
# Run all tests
php artisan test

# Run specific suite
php artisan test --filter=UserManagement

# Run with coverage (PCOV)
php artisan test --coverage --min=80

# Frontend tests (when configured)
npm run test
```

---

## 12. Related Documents

| Document | Link |
|----------|------|
| Security testing | [SECURITY.md](SECURITY.md) |
| Functional requirements | [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) |
| Roadmap | [ROADMAP.md](ROADMAP.md) |
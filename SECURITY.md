# Security Guidelines

# SportOS

Security architecture and requirements for the SportOS platform.

---

## 0. Protected Accounts

These accounts must never be deleted, demoted, or overwritten by seeders, migrations, or tests:

| User | Email | Notes |
|------|-------|-------|
| Ahmad Zaki Abdullah | `ahmadzaki@utem.edu.my` | Project owner; `system_owner` + UTeM `org_admin` |

Test fixtures must use separate accounts (e.g. `test@example.com` via factory).

---

## 1. Security Principles

| Principle | Implementation |
|-----------|----------------|
| Secure by Design | Threat modeling per module; security review in PR process |
| Least Privilege | RBAC with organization and event scoping |
| Defense in Depth | Middleware + policies + form requests + DB constraints |
| Audit Everything | Immutable audit log for all mutations |
| Fail Secure | Default deny; explicit permission grants only |
| Zero Trust (API) | Token validation on every request; no implicit trust |

---

## 2. Authentication

### 2.1 Web (Inertia)

| Control | Status | Notes |
|---------|--------|-------|
| Session-based auth (Breeze) | Active | Cookie + CSRF |
| Password hashing (bcrypt) | Active | Eloquent `hashed` cast |
| Email verification | Available | Breeze flow |
| Password reset | Active | Token expiry enforced |
| MFA (TOTP) | Planned | Phase 1 — optional per user |
| Session timeout | Planned | Configurable idle timeout |
| Concurrent session limit | Planned | Optional per org |

### 2.2 API (Sanctum)

| Control | Status | Notes |
|---------|--------|-------|
| Bearer token auth | Planned | `Authorization: Bearer {token}` |
| Token scopes | Planned | Per-organization, per-permission |
| Token rotation | Planned | Refresh token pattern |
| Token revocation | Planned | On logout, password change, admin revoke |
| Personal access tokens table | Installed | Via Breeze/Sanctum |

---

## 3. Authorization (RBAC)

### 3.1 Role Hierarchy

```
System Owner (global)
└── Organization Administrator (per org)
    ├── Event Organizer (per event)
    ├── Sports Manager (per sport)
    ├── Team Manager (per team)
    ├── Athlete (self)
    ├── Official (assigned matches)
    ├── Volunteer (event ops)
    └── Media (read + upload)
```

### 3.2 Enforcement Layers

1. **Middleware** — `EnsureUserIsAdmin`, `EnsureUserHasPermission`, `SetCurrentOrganization` (partial)
2. **Policies** — Laravel Policy per model (`UserPolicy` implemented)
3. **Form Requests** — `authorize()` method per request
4. **Global Scopes** — `OrganizationScope` on all tenant models (planned)
5. **Database** — Foreign keys + `organization_id` NOT NULL constraints

### 3.3 Current vs Target

| Aspect | Current | Target |
|--------|---------|--------|
| Roles | 9 system roles + `role_user` / `organization_user` | Event-scoped roles (planned) |
| Scope | System + organization | Organization + event scoped |
| Self-delete | Blocked for admin | Policy-driven per role |

---

## 4. Multi-Tenancy Security

| Requirement | Implementation |
|-------------|----------------|
| Tenant isolation | `organization_id` on all domain tables |
| Query scoping | Global Eloquent scope; bypass only for System Owner |
| Cross-tenant test | Feature test: user A cannot read org B data |
| Slug uniqueness | Unique per organization, not globally |
| File storage | Prefix uploads with `organizations/{id}/` |

---

## 5. Data Protection

| Data Type | Protection |
|-----------|------------|
| Passwords | bcrypt hash; never logged |
| API tokens | Hashed in DB; show plain text once on creation |
| PII (name, email, DOB, ID number) | Encrypted at rest (planned); access via policy |
| Audit logs | Append-only; no user deletion |
| Session data | Database driver; encrypted cookie |

### PDPA / GDPR Alignment

- Data minimization: collect only required athlete fields
- Right to erasure: anonymize athlete PII on request (retain audit)
- Data export: user can request own data export
- Consent: registration includes data processing consent checkbox

---

## 6. API Security

| Control | Requirement |
|---------|-------------|
| Versioning | `/api/v1/` — breaking changes require v2 |
| Rate limiting | 60 req/min auth; 10 req/min login |
| CORS | Whitelist allowed origins per environment |
| Input validation | Form requests + JSON schema validation |
| Output filtering | API Resources exclude hidden fields |
| Error responses | No stack traces in production |

---

## 7. OWASP Top 10 Mitigations

| Risk | Mitigation |
|------|------------|
| A01 Broken Access Control | RBAC policies + org scoping + tests |
| A02 Cryptographic Failures | HTTPS, bcrypt, encrypted PII fields |
| A03 Injection | Eloquent ORM; parameterized queries; input validation |
| A04 Insecure Design | Threat modeling; secure defaults |
| A05 Security Misconfiguration | Production env hardening checklist |
| A06 Vulnerable Components | `composer audit`, `npm audit` in CI |
| A07 Auth Failures | Rate limiting, MFA, session management |
| A08 Data Integrity Failures | CSRF tokens, signed URLs for sensitive actions |
| A09 Logging Failures | Audit logs + structured application logging |
| A10 SSRF | Validate external URLs; no user-controlled fetch |

---

## 8. Audit Trail

### 8.1 Logged Events

- User login / logout / failed login
- CRUD on all domain models
- Permission changes
- Result confirmation / appeal resolution
- Accreditation scan events
- API token creation / revocation

### 8.2 Audit Log Schema (Planned)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | PK |
| `organization_id` | bigint | Tenant scope |
| `user_id` | bigint | Actor (nullable for system) |
| `action` | varchar | created, updated, deleted, login, etc. |
| `auditable_type` | varchar | Model class |
| `auditable_id` | bigint | Record ID |
| `old_values` | json | Before state |
| `new_values` | json | After state |
| `ip_address` | varchar | Client IP |
| `user_agent` | text | Browser UA |
| `created_at` | timestamp | Immutable timestamp |

---

## 9. Infrastructure Security

| Control | Local (Laragon) | Production |
|---------|-----------------|------------|
| HTTPS | Optional SSL | Required (TLS 1.2+) |
| Firewall | OS default | Allow 80/443 only |
| DB access | localhost only | Private network / managed DB |
| Redis | localhost | Password + private network |
| Secrets | `.env` (gitignored) | Environment variables / vault |
| File permissions | Standard | `storage/` writable only by app user |

---

## 10. Security Testing

| Test Type | Tool | Frequency |
|-----------|------|-----------|
| Policy tests | PHPUnit | Every PR |
| Cross-tenant access tests | PHPUnit | Every PR |
| Dependency audit | `composer audit`, `npm audit` | Weekly CI |
| Penetration test | OWASP ZAP | Pre-release |
| Rate limit tests | PHPUnit | Every PR |

See [TESTING.md](TESTING.md) for full strategy.

---

## 11. Incident Response

1. Detect via monitoring (Sentry, logs)
2. Contain — revoke tokens, disable affected accounts
3. Investigate — audit log review
4. Notify — affected organizations within 72 hours (PDPA)
5. Remediate — patch, deploy, post-mortem

---

## 12. Related Documents

| Document | Link |
|----------|------|
| Architecture | [ARCHITECTURE.md](ARCHITECTURE.md) |
| API Security | [API.md](API.md) |
| Testing | [TESTING.md](TESTING.md) |
| Deployment | [DEPLOYMENT.md](DEPLOYMENT.md) |
| AI Governance | [AI_GOVERNANCE.md](AI_GOVERNANCE.md) |
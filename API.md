# API Specification

# SportOS

REST API documentation for SportOS. Base path: `/api/v1/`.

> **Build strategy:** API endpoints are implemented **alongside each web module** in the same phase — not as a separate late phase. Admin UI (Inertia) is primary; API enables mobile and integrations.

---

## 1. Status

| Aspect | Status |
|--------|--------|
| API routing | **Active** — `routes/api.php` at `/api/v1/` |
| Sanctum | Active — bearer token auth |
| Versioning | `/api/v1/` URL prefix |
| Web admin routes | **Active** — see Section 3 |
| Implemented endpoints | Auth, organizations, users, events, audit-logs |

---

## 2. Conventions

| Aspect | Standard |
|--------|----------|
| Format | JSON |
| Base path | `/api/v1/` |
| Auth | `Authorization: Bearer {sanctum_token}` |
| Content-Type | `application/json` |
| Versioning | URL prefix; breaking changes require new version |
| Pagination | `?page=1&per_page=25` |
| Sorting | `?sort=name&direction=asc` |
| Filtering | Resource-specific query params |
| Tenant scope | `X-Organization-Id: {id}` header or token scope |
| Rate limiting | 60 req/min (authenticated), 10 req/min (login) |
| Errors | Laravel JSON error format |

### Response Envelope

**Success (single):**
```json
{
  "data": { "id": 1, "name": "..." },
  "message": "Success"
}
```

**Success (collection):**
```json
{
  "data": [ ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 25,
    "total": 120
  }
}
```

**Validation error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Unauthorized (401):**
```json
{ "message": "Unauthenticated." }
```

**Forbidden (403):**
```json
{ "message": "This action is unauthorized." }
```

---

## 3. Active Web Endpoints (Inertia — Not API)

| Method | Path | Response | Auth |
|--------|------|----------|------|
| `GET` | `/` | Inertia (`Welcome`) | No |
| `GET` | `/dashboard` | Inertia (`Dashboard`) | Yes |
| `GET` | `/login` | Inertia (`Auth/Login`) | No |
| `GET` | `/register` | Inertia (`Auth/Register`) | No |
| `GET` | `/profile` | Inertia (`Profile/Edit`) | Yes |
| `GET` | `/admin/organizations` | Inertia (`Admin/Organizations/Index`) | Admin |
| `GET` | `/admin/organizations/create` | Inertia (`Admin/Organizations/Create`) | Admin |
| `POST` | `/admin/organizations` | Create organization → redirect | Admin |
| `GET` | `/admin/organizations/{organization}/edit` | Inertia (`Admin/Organizations/Edit`) | Admin |
| `PUT` | `/admin/organizations/{organization}` | Update organization → redirect | Admin |
| `DELETE` | `/admin/organizations/{organization}` | Delete organization → redirect | Admin |
| `POST` | `/admin/organizations/{organization}/branches` | Add branch → redirect | Admin |
| `DELETE` | `/admin/organizations/{organization}/branches/{branch}` | Remove branch → redirect | Admin |
| `GET` | `/admin/users` | Inertia (`Admin/Users/Index`) | Admin |
| `GET` | `/admin/users/create` | Inertia (`Admin/Users/Create`) | Admin |
| `POST` | `/admin/users` | Redirect | Admin |
| `GET` | `/admin/users/{user}/edit` | Inertia (`Admin/Users/Edit`) | Admin |
| `PUT` | `/admin/users/{user}` | Redirect | Admin |
| `DELETE` | `/admin/users/{user}` | Redirect | Admin |
| `GET` | `/up` | `{"status":"ok"}` | No |

---

## 4. Active API — Phase 1 (Foundation)

All endpoints below are implemented and covered by feature tests in `tests/Feature/Api/V1/`.

### 4.1 Authentication

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `POST` | `/api/v1/auth/login` | Email + password → token | No |
| `POST` | `/api/v1/auth/register` | Register user | No |
| `POST` | `/api/v1/auth/logout` | Revoke current token | Yes |
| `POST` | `/api/v1/auth/refresh` | Rotate token | Yes |
| `GET` | `/api/v1/auth/me` | Current user + org memberships | Yes |

**Login request:**
```json
{
  "email": "user@example.com",
  "password": "password",
  "device_name": "mobile-app"
}
```

**Login response:**
```json
{
  "data": {
    "token": "1|abc...",
    "user": { "id": 1, "name": "...", "email": "..." },
    "organizations": [ { "id": 1, "name": "...", "role": "org_admin" } ]
  }
}
```

### 4.2 Organizations

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/organizations` | List organizations | System Owner |
| `POST` | `/api/v1/organizations` | Create organization | System Owner |
| `GET` | `/api/v1/organizations/{id}` | Organization details | Org member |
| `PUT` | `/api/v1/organizations/{id}` | Update organization | Org Admin |
| `DELETE` | `/api/v1/organizations/{id}` | Deactivate organization | System Owner |
| `GET` | `/api/v1/organizations/{id}/branches` | List branches | Org member |

### 4.3 Users

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/users` | List users (org-scoped) | Org Admin |
| `POST` | `/api/v1/users` | Create / invite user | Org Admin |
| `GET` | `/api/v1/users/{id}` | User details | Org Admin |
| `PUT` | `/api/v1/users/{id}` | Update user | Org Admin |
| `DELETE` | `/api/v1/users/{id}` | Deactivate user | Org Admin |
| `GET` | `/api/v1/users?search=&role=` | Search + filter | Org Admin |

### 4.4 Events

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/events` | List events | Org member |
| `POST` | `/api/v1/events` | Create event | Event Organizer |
| `GET` | `/api/v1/events/{id}` | Event details | Org member |
| `PUT` | `/api/v1/events/{id}` | Update event | Event Organizer |
| `DELETE` | `/api/v1/events/{id}` | Archive event | Org Admin |
| `PATCH` | `/api/v1/events/{id}/status` | Change lifecycle status | Event Organizer |

### 4.5 Audit Logs

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/audit-logs` | List audit logs | Org Admin |

### Tenant context (API)

Org-scoped list/detail endpoints honour the `X-Organization-Id` header when the authenticated user is a member (or system owner). Example:

```bash
curl -H "Authorization: Bearer {token}" \
     -H "X-Organization-Id: 1" \
     https://demo.test/api/v1/users
```

---

## 5. Active API — Phase 2 (Sports)

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/events/{event}/sports` | List sports for event | Org member |
| `POST` | `/api/v1/events/{event}/sports` | Create sport (optional `template_slug`) | Sports Manager |
| `GET` | `/api/v1/events/{event}/sports/{id}` | Sport with disciplines/categories/divisions | Org member |
| `PUT` | `/api/v1/events/{event}/sports/{id}` | Update sport | Sports Manager |
| `DELETE` | `/api/v1/events/{event}/sports/{id}` | Delete sport | Org Admin |

## 6. Active API — Phase 2 (Athletes)

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/events/{event}/athletes` | List athletes registered for event | Org member |
| `POST` | `/api/v1/events/{event}/athletes` | Register athlete (profile + draft registration) | Team Manager / Org Admin |
| `GET` | `/api/v1/events/{event}/athletes/{id}` | Athlete profile with event registrations + history | Org member |
| `PUT` | `/api/v1/events/{event}/athletes/{id}` | Update athlete profile | Org Admin / self |
| `DELETE` | `/api/v1/events/{event}/athletes/{id}` | Delete athlete profile | Org Admin |
| `PATCH` | `/api/v1/events/{event}/registrations/{id}/status` | Advance registration workflow | Org Admin / Event staff |

## 7. Active API — Phase 2 (Teams)

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/events/{event}/teams` | List teams for event | Org member |
| `POST` | `/api/v1/events/{event}/teams` | Register team (profile + draft registration) | Team Manager / Org Admin |
| `GET` | `/api/v1/events/{event}/teams/{id}` | Team with roster and registration | Org member |
| `PUT` | `/api/v1/events/{event}/teams/{id}` | Update team profile | Org Admin / coach/manager |
| `DELETE` | `/api/v1/events/{event}/teams/{id}` | Delete team | Org Admin |
| `POST` | `/api/v1/events/{event}/teams/{id}/athletes` | Add athlete to roster | Team Manager |
| `DELETE` | `/api/v1/events/{event}/teams/{id}/athletes/{athlete}` | Remove athlete from roster | Team Manager |

## 8. Active API — Phase 2 (Officials)

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/events/{event}/officials` | List officials registered for event | Org member |
| `POST` | `/api/v1/events/{event}/officials` | Register official (profile + draft registration) | Event staff / Org Admin |
| `GET` | `/api/v1/events/{event}/officials/{id}` | Official profile with event registrations | Org member |
| `PUT` | `/api/v1/events/{event}/officials/{id}` | Update official profile | Org Admin |
| `DELETE` | `/api/v1/events/{event}/officials/{id}` | Delete official profile | Org Admin |
| `PATCH` | `/api/v1/events/{event}/registrations/{id}/status` | Advance registration workflow | Org Admin / Event staff |

## 9. Active API — Phase 2 (Venues)

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/venues` | List org venues (with facility count) | Org member |
| `POST` | `/api/v1/venues` | Create venue | Org Admin |
| `GET` | `/api/v1/venues/{id}` | Venue with facilities | Org member |
| `PUT` | `/api/v1/venues/{id}` | Update venue | Org Admin |
| `DELETE` | `/api/v1/venues/{id}` | Delete venue | Org Admin |
| `POST` | `/api/v1/venues/{venue}/facilities` | Add facility to venue | Org Admin |
| `DELETE` | `/api/v1/venues/{venue}/facilities/{facility}` | Remove facility | Org Admin |
| `GET` | `/api/v1/events/{event}/venues` | Venues attached to event | Org member |
| `POST` | `/api/v1/events/{event}/venues` | Attach venue to event | Event staff / Org Admin |
| `GET` | `/api/v1/events/{event}/venues/{venue}` | Event venue detail with sport links | Org member |
| `DELETE` | `/api/v1/events/{event}/venues/{venue}` | Detach venue from event | Org Admin |
| `POST` | `/api/v1/events/{event}/venues/{venue}/sports` | Link venue to sport for event | Event staff |
| `DELETE` | `/api/v1/events/{event}/venues/{venue}/sports/{sport}` | Unlink venue from sport | Event staff |

## 10. Active API — Phase 2 (Competitions & Scheduling)

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `GET` | `/api/v1/competition-formats` | List competition formats | Org member |
| `GET` | `/api/v1/events/{event}/competitions` | List competitions | Org member |
| `POST` | `/api/v1/events/{event}/competitions` | Create competition | Event staff / Org Admin |
| `GET` | `/api/v1/events/{event}/competitions/{id}` | Competition with fixtures/matches | Org member |
| `PUT` | `/api/v1/events/{event}/competitions/{id}` | Update competition | Org Admin |
| `DELETE` | `/api/v1/events/{event}/competitions/{id}` | Delete competition | Org Admin |
| `POST` | `/api/v1/events/{event}/competitions/{id}/fixtures` | Create fixture | Event staff |
| `POST` | `/api/v1/events/{event}/competitions/{id}/fixtures/{fixture}/matches` | Schedule match | Event staff |
| `PUT` | `/api/v1/events/{event}/competitions/{id}/fixtures/{fixture}/matches/{match}` | Update match | Event staff |
| `GET` | `/api/v1/events/{event}/schedule` | Week schedule (filter by sport/date) | Org member |

---

## 11. Active API — Phase 3 (Competition Engine)

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `POST` | `/api/v1/events/{event}/competitions/{id}/draw` | Generate draw from approved participants | Event staff |
| `POST` | `/api/v1/events/{event}/competitions/{id}/knockout-phase` | Generate knockout phase from group standings | Event staff |
| `GET` | `/api/v1/events/{event}/competitions/{id}/bracket` | Bracket rounds with match results | Org member |
| `POST` | `/api/v1/events/{event}/matches/{match}/result` | Submit or update match score | Official / Event staff |
| `PATCH` | `/api/v1/results/{id}/status` | Confirm or publish result (`confirmed`, `published`) | Org Admin |
| `POST` | `/api/v1/results/{id}/appeals` | Submit appeal against confirmed/published result | Team manager / participant / Event staff |
| `PATCH` | `/api/v1/appeals/{id}/status` | Review appeal (`under_review`, `upheld`, `overturned`) | Org Admin / Event staff |
| `GET` | `/api/v1/events/{event}/rankings` | Competition standings (filter by sport) | Org member |
| `GET` | `/api/v1/events/{event}/medals` | Medals + tally by recipient/org/country | Org member |

## 12. Planned API — Phase 3 (Remaining)

| Method | Path | Description |
|--------|------|-------------|
| `GET/POST` | `/api/v1/matches` | Global match listing / creation |

---

## 13. Planned API — Phase 4 (Operations)

| Method | Path | Description |
|--------|------|-------------|
| `GET/POST` | `/api/v1/events/{event}/accreditations` | Accreditation passes |
| `POST` | `/api/v1/accreditations/verify` | QR scan validation |
| `GET/POST` | `/api/v1/events/{event}/certificates` | Certificates |
| `GET` | `/api/v1/events/{event}/reports/{type}` | Generate report |
| `GET` | `/api/v1/events/{event}/announcements` | Announcements |

---

## 14. Planned API — Phase 5 (Public — No Auth)

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/public/events` | Published events list |
| `GET` | `/api/v1/public/events/{slug}` | Event details |
| `GET` | `/api/v1/public/events/{slug}/results` | Live results |
| `GET` | `/api/v1/public/events/{slug}/rankings` | Rankings |
| `GET` | `/api/v1/public/events/{slug}/medals` | Medal table |
| `GET` | `/api/v1/public/events/{slug}/schedule` | Fixture schedule |

---

## 15. API Setup (Done — Phase 1.6)

| Step | Status |
|------|--------|
| `routes/api.php` + `apiPrefix: 'api/v1'` in `bootstrap/app.php` | Done |
| Sanctum `HasApiTokens` on `User` | Done |
| Controllers in `app/Http/Controllers/Api/V1/` | Done |
| Resources in `app/Http/Resources/Api/V1/` | Done |
| Rate limiters (`auth`, `api`) in `AppServiceProvider` | Done |
| Feature tests in `tests/Feature/Api/V1/` | Done |
| OpenAPI / Scribe generator | Planned |

---

## 15. Testing

```powershell
npm run build              # Required for Inertia tests
php artisan test --filter=Api
```

```bash
curl -X POST https://demo.test/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user@example.com","password":"password","device_name":"cli"}'

curl -X GET https://demo.test/api/v1/events \
  -H "Authorization: Bearer {token}" \
  -H "X-Organization-Id: 1" \
  -H "Accept: application/json"
```

---

## 16. Future: GraphQL

The service layer will be separated from controllers to enable a future GraphQL endpoint without duplicating business logic. Not planned for v1.

---

## 17. Maintenance

Update this document when endpoints are implemented. Move items from "Planned" to "Active" with request/response examples.
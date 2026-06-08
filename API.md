# API

API endpoint documentation for the **Demo** project.

## Status

**No API endpoints have been implemented yet.**

This project only has web routes in `routes/web.php` and `routes/auth.php`. The `routes/api.php` file does not exist and API routing is not registered in `bootstrap/app.php`.

> **Note:** Laravel Sanctum is installed (via Breeze) but API endpoints are not implemented yet.

## Web Endpoints (Not API)

| Method | Path | Response | Auth |
|--------|------|----------|------|
| `GET` | `/` | Inertia (`Welcome`) | No |
| `GET` | `/dashboard` | Inertia (`Dashboard`) | Yes |
| `GET` | `/login` | Inertia (`Auth/Login`) | No |
| `GET` | `/register` | Inertia (`Auth/Register`) | No |
| `GET` | `/profile` | Inertia (`Profile/Edit`) | Yes |
| `GET` | `/up` | JSON health check | No |

### Health Check

```http
GET /up
```

Example response:

```json
{
  "status": "ok"
}
```

---

## Planned API

When the API module is added, follow these conventions.

### Conventions

| Aspect | Standard |
|--------|----------|
| Format | JSON |
| Base path | `/api/v1/` |
| Auth | Laravel Sanctum (Bearer token) |
| Error format | Laravel default JSON errors |
| Versioning | URL prefix (`v1`) |

### Planned Endpoints

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| `POST` | `/api/v1/auth/login` | Login, return token | No |
| `POST` | `/api/v1/auth/register` | Register user | No |
| `POST` | `/api/v1/auth/logout` | Logout, revoke token | Yes |
| `GET` | `/api/v1/user` | Current user profile | Yes |
| `GET` | `/api/v1/users` | List users | Yes (admin) |
| `POST` | `/api/v1/users` | Create user | Yes (admin) |
| `GET` | `/api/v1/users/{id}` | User details | Yes |
| `PUT` | `/api/v1/users/{id}` | Update user | Yes |
| `DELETE` | `/api/v1/users/{id}` | Delete user | Yes (admin) |

> The endpoints above are **proposals** — not yet implemented.

### Response Format (Proposed)

**Success:**

```json
{
  "data": { },
  "message": "Success"
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
{
  "message": "Unauthenticated."
}
```

---

## API Setup (Future Steps)

1. Install Sanctum: `composer require laravel/sanctum`
2. Create `routes/api.php`
3. Register in `bootstrap/app.php`:

   ```php
   ->withRouting(
       web: __DIR__.'/../routes/web.php',
       api: __DIR__.'/../routes/api.php',
       // ...
   )
   ```

4. Add `personal_access_tokens` migration
5. Write controllers in `app/Http/Controllers/Api/`
6. Write feature tests in `tests/Feature/Api/`
7. Update this document with actual endpoints

## Testing API

```powershell
# Example (when API is ready)
php artisan test --filter=Api
```

```bash
# cURL example
curl -X GET https://demo.test/api/v1/user \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

## Maintenance

Update the endpoint table each time a new API route is added. Remove items from "Planned" and mark them as "Active" when complete.
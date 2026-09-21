# Admin Panel Setup

## 1. Register the `admin` middleware alias

Open `bootstrap/app.php` and add the alias inside `withMiddleware`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
})
```

This is what makes `Route::middleware('admin')` in `routes/api.php` resolve to `EnsureUserIsAdmin`.

## 2. Create the first admin account

Admin accounts are **never** created through public `/register` — there's no `role=admin` option on that form, on purpose. Use the bundled artisan command instead:

```bash
php artisan admin:create "Jane Doe" jane@salonbook.co.ke 0712345678 "a-strong-password"
```

They can then log in at the normal `/login` screen — the frontend router sends `role: admin` users to `/admin` instead of the client/provider home.

## 3. What's protected

Every route under `/api/v1/admin/*` requires both:
- `auth:sanctum` — a valid bearer token
- `admin` — the authenticated user's `role` column equals `'admin'`

Anyone else hitting these routes gets a `403`.

## 4. Endpoint map

| Endpoint | Purpose |
|---|---|
| `GET /admin/analytics/overview` | Headline counters (users, appointments, revenue, breakdowns) |
| `GET /admin/analytics/revenue?days=30` | Daily revenue series for a line chart |
| `GET /admin/analytics/bookings?days=30` | Daily bookings by status for a stacked bar chart |
| `GET /admin/analytics/user-growth?days=30` | Daily signups, client vs provider |
| `GET /admin/analytics/top-providers` | Top 10 providers by completed bookings |
| `GET /admin/users` | Paginated user list, filter by `role`/`search`, includes suspended |
| `PATCH /admin/users/{id}/suspend` | Soft-deletes the account (blocks login) |
| `PATCH /admin/users/{id}/reactivate` | Restores a suspended account |
| `GET /admin/providers` | Paginated provider list, filter by `type`/`unverified_only`/`search` |
| `PATCH /admin/providers/{id}/verify` | Sets `is_verified = true` |
| `GET /admin/appointments` | Platform-wide appointment list, filter by `status`/date range |
| `PATCH /admin/appointments/{id}/cancel` | Force-cancels a booking with an optional reason |

## 5. Suspension behavior

Suspending a user reuses the `SoftDeletes` trait already on `User` — Eloquent's global scope excludes soft-deleted rows from normal queries, so a suspended user's login lookup (`User::where('email', ...)->first()`) simply returns nothing. No separate "is_active" flag needed. `AdminUserController@index` explicitly calls `withTrashed()` so admins can still see and reactivate suspended accounts.

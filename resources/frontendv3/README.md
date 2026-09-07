# SalonBook — Vue 3 Frontend

Talks to the Laravel API scaffolded earlier (`salonapp-scaffold.zip`).

## Setup
```bash
npm install
npm run dev
```
Vite proxies `/api/*` to `http://localhost:8000` (your `php artisan serve` port) — see `vite.config.js`.

## Structure
- `src/api/` — one file per resource, thin axios wrappers (`client.js` holds the shared instance + auth header injection)
- `src/stores/` — Pinia stores: `auth`, `providers`, `appointments` (add `messages`, `catalogue` stores as the app grows — the views currently call the API modules directly for those)
- `src/router/` — role-based route guards (`client` vs `provider`)
- `src/views/auth/` — login/register (single form handles both roles)
- `src/views/client/` — discover, provider profile + booking + M-Pesa deposit, my appointments, messages
- `src/views/provider/` — dashboard (bookings + check-in visibility), catalogue CRUD, client book, profile + open/closed toggle
- `src/components/shared/MpesaDepositModal.vue` — STK push trigger + status polling, drop this in anywhere a payment is needed

## Feature → view map
| # | Feature | View |
|---|---------|------|
| 1 | Arrival shown to provider | `provider/DashboardView.vue` (checked_in_at badge) |
| 2 & 6 | Browse/nearby providers | `client/DiscoverView.vue` |
| 3 | Catalogue (with style photos) | `provider/CatalogueView.vue`, shown to clients in `client/ProviderProfileView.vue` |
| 4 | Booking + chat | `client/ProviderProfileView.vue`, `client/MessagesView.vue` |
| 5 | Open/closed | `provider/ProfileEditView.vue` |
| 7 | Manage clients | `provider/ClientsView.vue` |
| — | M-Pesa deposit | `components/shared/MpesaDepositModal.vue` |
| — | Admin panel | `views/admin/*` — overview analytics, users, providers, appointments |

## Admin panel
- Only reachable by accounts with `role: admin` — there's no public signup path for this role (see `ADMIN_SETUP.md` in the backend scaffold for creating the first one via `php artisan admin:create`)
- `views/admin/DashboardView.vue` — headline counters plus four charts (revenue line, appointment-status doughnut, bookings-by-status stacked bar, signup growth line) built with `chart.js` + `vue-chartjs`, wrapped in `components/charts/*` using the app's own color tokens instead of Chart.js defaults
- `views/admin/UsersView.vue` — search/filter users, suspend (soft-delete) or reactivate any non-admin account
- `views/admin/ProvidersView.vue` — search/filter providers, toggle verification badge
- `views/admin/AppointmentsView.vue` — platform-wide booking oversight with a force-cancel override for disputes

## Branding
- `public/logo.jpg` is the app logo — shown in the nav bar, the login/register screens, and set as the favicon in `index.html`. Replace this file directly to rebrand; nothing else needs to change since every reference points at the static `/logo.jpg` path.
- The same graceful-fallback pattern used for style photos (see below) means a missing or renamed logo file would just show a broken image in those three spots — if you replace it, keep the filename or update the three references (`NavBar.vue`, `LoginView.vue`, `RegisterView.vue`, `index.html`).

## Image loading — graceful fallback
Every place a style photo renders (`ProviderCard`, `CatalogueView`, `ProviderProfileView`, the `DiscoverView` hero/trending strip) tracks failed image loads via an `@error` handler and falls back to a placeholder tile instead of a broken image icon. This covers the common causes of "the photo doesn't show": a missing `php artisan storage:link`, an `APP_URL` that isn't fully-qualified, or a file that's since been deleted. If photos aren't appearing at all (not even the fallback flickering in), check those three things on the backend first — the frontend fallback hides the symptom but doesn't fix a genuinely misconfigured storage setup.

## Not yet wired
- **Client search in "Add a client manually"**: currently takes a raw user ID — replace with a phone/name lookup input once you add a client-search endpoint.
- **Reviews UI**: `appointmentsApi.review()` exists but no form calls it yet — add a rating modal on completed appointments in `MyAppointmentsView.vue`.

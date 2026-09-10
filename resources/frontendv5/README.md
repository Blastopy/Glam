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

## Global loading indicator
Every API call — anywhere in the app — shows a slim animated bar at the top of the page automatically. `stores/loading.js` tracks the in-flight request count, `components/shared/LoadingBar.vue` renders it, and `api/client.js`'s axios interceptor increments/decrements the counter. No per-view wiring needed; it Just Works for any new API call you add.

## Payments: booking deposits vs. provider subscription
- **Booking is free.** The old M-Pesa deposit-on-booking flow is disabled (the backend endpoint returns a 422 explaining why). `client/ProviderProfileView.vue` books an appointment and shows a plain confirmation — no payment step, no `MpesaDepositModal`. That component still exists in `components/shared/` if you want to re-enable deposits later.
- **Providers pay KES 70/month instead.** `views/provider/ActivateView.vue` is where they pay via M-Pesa (same STK-push-then-poll pattern the deposit modal used, just as a full page). New providers land here right after registering; the router (`router/index.js`) hard-redirects any `/dashboard/*` route back to activation if `auth.isProviderSubscriptionActive` is false, so there's no way to reach the dashboard unpaid.

## Google Sign-In
`components/shared/GoogleSignInButton.vue` wraps Google Identity Services and is used identically on both `/login` and `/register` — it's a single "continue with Google" action that finds-or-creates a client account and logs in. Requires `VITE_GOOGLE_CLIENT_ID` in `.env` (see `GOOGLE_OAUTH_SETUP.md` in the backend scaffold). Not shown on the provider registration form — Google sign-in only ever creates client accounts; providers register with email/password.

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

## Responsiveness
The app targets phone, tablet, and desktop from a single Tailwind breakpoint set (`sm`/`md`/`lg`) rather than a separate mobile build:
- **Nav**: collapses to a hamburger menu below `md` (`components/layout/NavBar.vue`) instead of squeezing every role's links into one row.
- **Messages**: the conversation list and active thread share one screen on desktop but swap as two separate full-width views on mobile, with a "← Back" link — see `client/MessagesView.vue`.
- **Admin tables** (`admin/UsersView.vue`, `admin/AppointmentsView.vue`): wrapped in `overflow-x-auto` with a `min-w-[...]` floor on the table itself, so columns stay legible and scroll horizontally instead of squashing.
- **Card rows** throughout (appointments, catalogue items, provider cards) use `min-w-0` on flexible text columns and `shrink-0` on fixed-width badges/buttons — the standard fix for flex children silently overflowing their container with long names or long content.
- Grids that are 3–4 columns on desktop (admin overview cards, provider/service listings) drop to 1–2 columns via `sm:`/`lg:` prefixes rather than shrinking cards to unreadable widths.

## Not yet wired
- **Client search in "Add a client manually"**: currently takes a raw user ID — replace with a phone/name lookup input once you add a client-search endpoint.
- **Reviews UI**: `appointmentsApi.review()` exists but no form calls it yet — add a rating modal on completed appointments in `MyAppointmentsView.vue`.

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

## Maps
Uses Leaflet + OpenStreetMap (`components/shared/ProviderMap.vue`) — free, no API key, which matters since this project already juggles M-Pesa and Google OAuth credentials.
- **`client/DiscoverView.vue`** has a List/Map toggle on the "Near you" section — Map mode drops a custom clay-colored pin for every currently-loaded provider with a set location, centered on the user's location if "Use my location" was used, clicking a pin navigates to that provider's page.
- **`client/ProviderProfileView.vue`** shows a single-pin map of the exact address plus a "Get directions" button — a plain `https://www.google.com/maps/dir/?api=1&destination=lat,lng` deep link, which opens the Google Maps app on mobile or maps.google.com on desktop with turn-by-turn directions from the user's current location. No API key needed for this either.
- Providers without a set `latitude`/`longitude` (haven't filled in their location yet, in `provider/ProfileEditView.vue`) simply don't get a map section — handled gracefully, not shown as broken.
- Laravel's `decimal` cast returns lat/lng as strings, not numbers — both map usages coerce with `parseFloat()`/`Number.isFinite()` before handing coordinates to Leaflet.

## Messaging — provider visibility fix
Two real bugs meant providers effectively couldn't see messages or notice new bookings, now fixed:
- **`components/layout/NavBar.vue`** had no "Messages" link for providers at all — the route worked, it just wasn't reachable from the UI. Added, alongside a "Bookings" link with a pending-count badge and a "Messages" link with an unread-count badge, both polled every 30s so they don't depend on a live websocket connection to stay accurate.
- **The conversations list API was broken** — it used `selectRaw` to compute `other_user_id` but then tried to eager-load `sender`/`receiver` relations that need `sender_id`/`receiver_id` in the select, which weren't there. It silently returned unusable data, which is why the frontend had fallen back to showing "Conversation #5" instead of a name. Rewritten in `MessageController::index` to properly return each conversation's other-party info, last message, and unread count.
- Providers previously had no way to **start** a conversation, only react to one a client had already begun. Added a "Message" link on each client row (`provider/ClientsView.vue`) and each booking card (`provider/DashboardView.vue`).

If bookings/messages still don't show up in real time after this, check that Reverb is actually running (`php artisan reverb:start`) — the nav badges above are a deliberate fallback that works either way, but the toast notifications and instant chat updates do need it.

## Public homepage & guest browsing
The homepage and every provider profile page are public — no login needed to browse styles, prices, photos, the map, reviews, or contact details. `App.vue` shows a minimal `PublicHeader.vue` (logo + Log in / Sign up) instead of the full `NavBar` for guests on these pages. Only the act of picking a style to book is gated: `ProviderProfileView.vue`'s `pickService()` checks `auth.isAuthenticated` and, if false, redirects to `/login?redirect=<current-page>` — both `LoginView.vue` and `RegisterView.vue` read that query param on success and send the person right back instead of their generic role-based home. The Login↔Register link on each page also carries the query forward.

## Reviews & featured providers
`ProviderProfileView.vue` renders a public reviews section (star rating, comment, name, date) — no new endpoint was needed since `GET /providers/{id}` already returned this data, it just wasn't displayed. `ProviderCard.vue` and the profile page both show a "★ Featured" badge for providers an admin has promoted via `admin/ProvidersView.vue`'s Feature/Unfeature toggle; featured providers also sort first in `DiscoverView.vue`'s results.

## Subscription days-left
`provider/ActivateView.vue` and `admin/ProvidersView.vue` both display days remaining on a provider's subscription (`ProviderProfile::subscription_days_left`, auto-included on every API response involving a provider — no extra fetch needed). After a successful payment, `ActivateView.vue` auto-redirects to the dashboard after a 3-second countdown, with an immediate manual button alongside it.

## Booking guardrails & client cancellation
Past-time booking is blocked both ways: the datetime picker in `ProviderProfileView.vue` has a `min` attribute so a past slot is never offered, plus a client-side re-check right before submit, backing up the server's own `after:now` validation. Double-booking prevention lives entirely server-side (a locked transaction in `AppointmentController::store`) — nothing for the frontend to do there. `MyAppointmentsView.vue` now has a Cancel button for clients; building it surfaced a real gap where `updateStatus` let a client set *any* status on their own booking (including marking it "completed") — now fixed to only allow `cancelled` from that side.

## Forgot / reset password
`views/auth/ForgotPasswordView.vue` and `views/auth/ResetPasswordView.vue`, linked from a "Forgot password?" link on the login form. The reset link's `token`/`email` come from the URL query string (set by the backend's emailed link — see `GOOGLE_OAUTH_SETUP.md`'s sibling doc, `SETUP.md` section 22, for the mail setup this depends on).

## Footer
`components/layout/Footer.vue` — contact email/phone and social links, shown on every page via `App.vue`. Update the placeholder values in that file directly.

## Streamlined M-Pesa
`ActivateView.vue` now listens for a `subscription.confirmed` websocket event (via the same `provider.{id}` private channel used elsewhere) and resolves the payment instantly when Reverb is running, rather than waiting on its next poll tick. The existing 3-second polling loop still runs alongside it as a fallback. Failure messages also read better now — the backend maps common M-Pesa result codes (cancelled prompt, wrong PIN, insufficient balance) to plain language instead of Safaricom's raw description text.

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

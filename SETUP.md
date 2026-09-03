# SalonBook — Full Project Setup Guide

Covers the Laravel API (`salonapp`), the Vue 3 SPA (`salon-frontend`), M-Pesa deposits, and real-time (Reverb + Echo) — end to end, from a clean machine to a working local dev environment.

---

## 0. Prerequisites

| Tool | Version | Check |
|---|---|---|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18+ | `node -v` |
| npm | 9+ | `npm -v` |
| MySQL or PostgreSQL | any recent | `mysql --version` |
| ngrok (or similar) | for M-Pesa callback in local dev | `ngrok version` |

You'll run **four processes** side by side during development:
1. `php artisan serve` — the API
2. `php artisan reverb:start` — the websocket server
3. `npm run dev` (in `salon-frontend`) — the SPA
4. `ngrok http 8000` — public tunnel so Safaricom can reach your M-Pesa callback

---

## 1. Create the Laravel project and drop in the scaffold

```bash
composer create-project laravel/laravel salonapp
cd salonapp
```

Unzip `salonapp-scaffold.zip` and copy its contents into this fresh project, merging/overwriting:
- `database/migrations/*` → your `database/migrations/`
- `app/Models/*` → your `app/Models/` (overwrites the default `User.php`)
- `app/Http/Controllers/Api/*` → your `app/Http/Controllers/Api/` (create the `Api` folder)
- `app/Services/*` → your `app/Services/` (create the folder)
- `app/Events/*` → your `app/Events/` (create the folder)
- `routes/api.php` → overwrite your `routes/api.php`
- `routes/channels.php` → overwrite your `routes/channels.php`
- `config/mpesa.php` → your `config/`
- `.env.mpesa.example`, `.env.reverb.example`, `REALTIME_SETUP.md`, `README.md` → project root, for reference

```bash
composer require laravel/sanctum laravel/reverb
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan reverb:install
```

`reverb:install` will append `BROADCAST_CONNECTION` and `REVERB_*` keys to your `.env` automatically. It also publishes `config/reverb.php` and registers the `BroadcastServiceProvider`.

---

## 2. Configure `.env`

Open `.env` and set:

```env
APP_NAME=SalonBook
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=salonapp
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

Then append the contents of **`.env.mpesa.example`** and **`.env.reverb.example`** (both included in the scaffold) to your `.env`, filling in real values where noted:

```env
# --- M-Pesa (Daraja sandbox — get these from https://developer.safaricom.co.ke) ---
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=your_sandbox_consumer_key
MPESA_CONSUMER_SECRET=your_sandbox_consumer_secret
MPESA_SHORTCODE=174379
MPESA_PASSKEY=your_sandbox_passkey
MPESA_CALLBACK_URL=https://your-ngrok-subdomain.ngrok-free.app/api/v1/payments/mpesa/callback
MPESA_DEPOSIT_RATE=0.3

# --- Reverb (real-time) ---
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=salonapp
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

> `MPESA_CALLBACK_URL` **must** be a public HTTPS URL — Safaricom's servers can't reach `localhost`. This is what ngrok is for (step 6).

Generate the app key and create the database:

```bash
php artisan key:generate
mysql -u root -e "CREATE DATABASE salonapp"
```

---

## 3. Migrate

```bash
php artisan migrate
```

This runs, in order: users role/phone columns → provider_profiles → services → appointments → messages → client_provider → reviews → payments → appointment deposit columns.

Confirm broadcasting is wired: open `bootstrap/providers.php` and make sure `App\Providers\BroadcastServiceProvider::class` is listed (Reverb's installer should have added it). This is what makes `/broadcasting/auth` respond.

---

## 4. Sanity-check the API

```bash
php artisan serve
```

In another terminal:
```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Barber","email":"barber@test.com","phone":"0712345678","password":"password123","password_confirmation":"password123","role":"provider","provider_type":"barber"}'
```

You should get back a `user` object and a `token`. Save the token and confirm auth works:
```bash
curl http://localhost:8000/api/v1/me -H "Authorization: Bearer <token>"
```

---

## 5. Start Reverb

```bash
php artisan reverb:start
```

Leave this running. By default it listens on `localhost:8080` (matches `REVERB_HOST`/`REVERB_PORT` in `.env`). You should see `Starting server on 0.0.0.0:8080` with no errors.

---

## 6. Expose your API for M-Pesa (ngrok)

```bash
ngrok http 8000
```

Copy the `https://xxxx.ngrok-free.app` URL it gives you, and update `.env`:
```env
MPESA_CALLBACK_URL=https://xxxx.ngrok-free.app/api/v1/payments/mpesa/callback
```

Restart `php artisan serve` after changing `.env` (config is cached at boot in some setups — run `php artisan config:clear` if changes don't take effect).

> Re-run this step every time your ngrok URL changes (free ngrok URLs rotate on restart). For a stable URL, use a paid ngrok plan or deploy to a real server with a fixed domain.

---

## 7. Set up the Vue 3 frontend

```bash
cd ..
```
Unzip `salon-frontend-scaffold.zip` next to `salonapp/` (as a sibling directory).

```bash
cd salon-frontend
npm install
cp .env.example .env
```

Edit `salon-frontend/.env` so the Reverb values **match** what's in `salonapp/.env`:
```env
VITE_REVERB_APP_KEY=local-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
VITE_API_URL=http://localhost:8000
```

```bash
npm run dev
```

Vite serves on `http://localhost:5173` and proxies `/api/*` requests to `http://localhost:8000` (configured in `vite.config.js`) — so the SPA's axios calls to `/api/v1/...` reach the Laravel backend without CORS headaches in dev.

---

## 8. Full local dev checklist

Run these four in separate terminals, in this order:

```bash
# Terminal 1 — API
cd salonapp && php artisan serve

# Terminal 2 — websockets
cd salonapp && php artisan reverb:start

# Terminal 3 — public tunnel for M-Pesa callback
ngrok http 8000

# Terminal 4 — frontend
cd salon-frontend && npm run dev
```

Visit `http://localhost:5173`, register as a provider, register a second account as a client (use a different browser or incognito tab), and walk through: add a service → open the shop → book from the client account → pay the deposit (sandbox M-Pesa number `254708374149` works with Daraja's sandbox) → confirm the provider dashboard shows it in real time.

---

## 9. Common issues

| Symptom | Likely cause |
|---|---|
| `419` or CORS errors on login | `SANCTUM_STATEFUL_DOMAINS` doesn't match your frontend's origin, or you're using token auth but accidentally hitting a `web` route |
| M-Pesa STK push returns `500` | Wrong `MPESA_CONSUMER_KEY`/`SECRET`, or `MPESA_SHORTCODE`/`PASSKEY` mismatch — sandbox shortcode `174379` only works with Safaricom's published sandbox passkey |
| Callback never arrives | `MPESA_CALLBACK_URL` isn't publicly reachable (ngrok not running, or URL not updated after ngrok restarted) |
| Echo won't connect / stays "connecting" | `VITE_REVERB_*` values in `salon-frontend/.env` don't match `REVERB_*` in `salonapp/.env`, or `reverb:start` isn't running |
| `403` subscribing to a private channel | `/broadcasting/auth` rejected the user — check `routes/channels.php` logic matches the channel name being requested, and confirm the Sanctum bearer token is attached (see `echo.js`'s `auth.headers`) |
| Migrations fail on `foreignId` constraints | Run migrations in the exact order the scaffold provides them (they're numbered 000001–000009) — don't let Laravel's default migration alphabetize away from that |

---

## 10. Production notes (not covered in depth here, but worth flagging)

- Swap `MPESA_ENV=sandbox` → `production` and use live Daraja credentials + your real paybill/till shortcode
- Run `reverb:start` behind Supervisor, and reverse-proxy WebSocket upgrade headers through Nginx (`proxy_set_header Upgrade $http_upgrade;` etc.)
- Set `APP_ENV=production`, `APP_DEBUG=false`, and run `php artisan config:cache route:cache`
- Build the frontend for production with `npm run build` and serve the `dist/` folder as static files (or via the same Nginx that proxies the API)
- Move `MPESA_CALLBACK_URL` to your real domain — no more ngrok

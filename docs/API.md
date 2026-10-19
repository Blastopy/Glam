# Glam API Documentation

**Project:** Glam / SalonBook  
**Source repository:** https://github.com/Blastopy/Glam  
**API version:** v1  
**Framework:** Laravel  
**Authentication:** Laravel Sanctum personal access tokens

> This document is based on the repository's `routes/api.php` and the controllers inspected for authentication, provider profiles, services, appointments, and payments. For endpoints whose controller validation rules have not been inspected, the endpoint is documented from the registered route only. Confirm those request/response details against the current code before treating them as a formal contract.

---

## 1. Base URL

The API route prefix is `/api/v1`.

Set `BASE_URL` to your deployed Laravel backend, for example:

```text
https://YOUR-BACKEND-DOMAIN
```

Then the base API URL is:

```text
https://YOUR-BACKEND-DOMAIN/api/v1
```

The repository has also been used with the Render deployment `https://glam-ceok.onrender.com`. If that deployment is still current, use:

```text
https://glam-ceok.onrender.com/api/v1
```

## 2. Request conventions

For JSON requests, send:

```http
Accept: application/json
Content-Type: application/json
```

For authenticated endpoints, include the token returned by registration or login:

```http
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

For image uploads, use `multipart/form-data` rather than JSON.

### Authentication and access

- **Public:** no user token required.
- **Authenticated:** requires a valid Sanctum bearer token.
- **Admin:** requires a valid token and the `admin` middleware.
- **Signed action link:** the quick accept/decline GET routes require a valid Laravel signed URL. Do not call them as ordinary unsigned URLs.

## 3. Authentication

### Register

`POST /register` — **Public**

Creates a user and returns a Sanctum token. A provider registration also creates an initial provider profile.

Request:

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "+254700000000",
  "password": "example-password",
  "password_confirmation": "example-password",
  "role": "client"
}
```

For a provider, set `"role": "provider"`. The optional `provider_type` field defaults to `"barber"` when creating the initial provider profile.

Validation:
- `name`: required string, maximum 255 characters
- `email`: required valid email, unique in `users`
- `phone`: required string, unique in `users`
- `password`: required, minimum 8 characters, must match `password_confirmation`
- `role`: `client` or `provider`

Success: `201 Created`

Response shape:

```json
{
  "user": {},
  "token": "SANCTUM_TOKEN"
}
```

The `user` object contains the created user fields. Exact serialized fields depend on the model's hidden/cast configuration.

Rate limit: `throttle:6,1` (six requests per minute, according to Laravel's throttle syntax).

### Log in

`POST /login` — **Public**

Request:

```json
{
  "email": "jane@example.com",
  "password": "example-password"
}
```

Success: `200 OK`

```json
{
  "user": {},
  "token": "SANCTUM_TOKEN"
}
```

Invalid credentials return `401 Unauthorized`:

```json
{
  "message": "Invalid credentials"
}
```

Rate limit: `throttle:6,1`.

### Google sign-in

`POST /auth/google` — **Public**

Request:

```json
{
  "credential": "GOOGLE_ID_TOKEN"
}
```

The backend verifies the Google Identity Services ID token with Google's token-info endpoint and checks that the email is verified. New Google accounts are created as clients. An existing account with that email is signed into, unless the account is suspended.

Success: `200 OK` for an existing account or `201 Created` for a new account. The response contains `user` and `token`.

Possible errors include `422 Unprocessable Entity` for an invalid/unverified credential and `403 Forbidden` for a suspended account.

Rate limit: `throttle:10,1`.

### Request a password reset

`POST /forgot-password` — **Public**

Request:

```json
{
  "email": "jane@example.com"
}
```

Returns a generic message to avoid revealing whether an account exists:

```json
{
  "message": "If an account exists for that email, a password reset link has been sent."
}
```

Rate limit: `throttle:5,1`.

### Reset password

`POST /reset-password` — **Public**

Request:

```json
{
  "token": "TOKEN_FROM_RESET_EMAIL",
  "email": "jane@example.com",
  "password": "new-example-password",
  "password_confirmation": "new-example-password"
}
```

Password must be at least 8 characters and match its confirmation. On success, previously issued Sanctum tokens are deleted, so the user must log in again.

### Get current user

`GET /me` — **Authenticated**

Returns the authenticated user with their provider profile relationship loaded.

### Log out

`POST /logout` — **Authenticated**

Deletes the current access token.

Success:

```json
{
  "message": "Logged out"
}
```

---

## 4. Provider discovery and profiles

### List/discover providers

`GET /providers` — **Public**

Query parameters (all optional unless noted):

| Parameter | Type | Meaning |
|---|---|---|
| `type` | string | Filter provider type, such as `barber`, `salon`, `manicurist`, or `other` |
| `open_only` | boolean | When true, return only providers currently open |
| `home_service` | boolean | When true, return providers offering home service |
| `search` | string | Search business names and active service names |
| `lat` | number | Latitude for nearby search; use together with `lng` |
| `lng` | number | Longitude for nearby search; use together with `lat` |
| `radius_km` | number | Search radius in kilometres; defaults to 10 when coordinates are supplied |
| `page` | integer | Pagination page |

Example:

```http
GET /api/v1/providers?type=barber&open_only=true&home_service=false&search=fade&lat=-1.2864&lng=36.8172&radius_km=10
```

Returns a paginated JSON result (15 providers per page). Results include active services, selected user fields, and an appointment count. Providers with inactive subscriptions are excluded; featured providers are ordered first.

### Show one provider

`GET /providers/{providerProfile}` — **Public**

Replace `{providerProfile}` with the provider profile's route key/ID. Returns the provider profile with active services, selected user fields, and reviews.

### List a provider's services

`GET /providers/{providerProfileId}/services` — **Public**

Returns active services for the specified provider profile as a JSON array.

### Update your provider profile

`PATCH /provider/profile` — **Authenticated; provider account required**

JSON fields supported:

| Field | Type | Notes |
|---|---|---|
| `business_name` | string | Maximum 255 characters |
| `provider_type` | string | `barber`, `salon`, `manicurist`, or `other` |
| `bio` | string/null | Optional description |
| `address` | string/null | Optional address |
| `latitude` | number/null | Between -90 and 90 |
| `longitude` | number/null | Between -180 and 180 |
| `business_hours` | array/null | Business-hours data |
| `offers_home_service` | boolean | Whether home visits are offered |
| `home_service_fee` | number/null | Must be zero or greater |

Example:

```json
{
  "business_name": "Jane's Salon",
  "provider_type": "salon",
  "bio": "Hair styling and care",
  "address": "Nairobi, Kenya",
  "latitude": -1.2864,
  "longitude": 36.8172,
  "offers_home_service": true,
  "home_service_fee": 300
}
```

Only supplied fields are updated. Turning off home service clears the home service fee.

### Open/close your provider listing

`PATCH /provider/status` — **Authenticated; provider account required**

Request:

```json
{
  "is_open": true,
  "closed_reason": null
}
```

`is_open` is required and boolean. `closed_reason` is optional and limited to 255 characters. The change is broadcast as a provider-status event.

---

## 5. Services / style catalogue

### Create a service

`POST /services` — **Authenticated; provider account required**

Use `multipart/form-data` if uploading an image. Fields:

- `name` — required string, maximum 255 characters
- `description` — optional string
- `price` — required number, minimum 0
- `duration_minutes` — required integer from 5 to 600
- `image` — optional image file, maximum 4096 KB (4 MB)

Example JSON fields (without image):

```json
{
  "name": "Skin fade",
  "description": "Skin fade haircut",
  "price": 500,
  "duration_minutes": 45
}
```

Success: `201 Created`, returning the created service. Uploaded style images are handled by the configured Cloudinary service.

### Update a service

`PATCH /services/{service}` — **Authenticated; owner provider only**

Optional fields: `name`, `description`, `price`, `duration_minutes`, `is_active`, `image`. Validation limits are the same as service creation where applicable. Only the provider who owns the service may update it.

### Delete a service

`DELETE /services/{service}` — **Authenticated; owner provider only**

Success:

```json
{
  "message": "Service removed"
}
```

---

## 6. Appointments / bookings

### List appointments

`GET /appointments` — **Authenticated**

Optional query parameter: `status`.

- Providers receive appointments associated with their provider profile.
- Clients receive their own bookings.
- Results are ordered by scheduled time and paginated at 20 records per page.

Example:

```http
GET /api/v1/appointments?status=pending
```

### Create a booking

`POST /appointments` — **Authenticated**

Request:

```json
{
  "service_id": 12,
  "scheduled_at": "2026-11-01T10:30:00+03:00",
  "client_notes": "Please keep the sides short",
  "is_home_service": false
}
```

Supported fields:
- `service_id`: required and must exist in `services`
- `scheduled_at`: required date/time in the future
- `client_notes`: optional string, maximum 1000 characters
- `is_home_service`: optional boolean
- `home_address`: required when `is_home_service` is true; otherwise optional/nullable, maximum 255 characters
- `home_latitude`: optional number from -90 to 90
- `home_longitude`: optional number from -180 to 180

Example home visit:

```json
{
  "service_id": 12,
  "scheduled_at": "2026-11-01T10:30:00+03:00",
  "client_notes": "Please call on arrival",
  "is_home_service": true,
  "home_address": "Example address, Nairobi",
  "home_latitude": -1.2864,
  "home_longitude": 36.8172
}
```

Success: `201 Created`, returning the appointment with its service and provider profile.

Important booking rules:
- The provider must be open and have an active subscription.
- The provider must support home visits when `is_home_service` is true.
- The chosen time cannot overlap another non-cancelled/non-no-show appointment for that provider.
- The price is recorded at booking time. If a home-service fee applies, it is added to the service price.
- New bookings start with `pending` status.
- A booking notification/event is emitted after creation.

### Show one appointment

`GET /appointments/{appointment}` — **Authenticated; booking client or provider only**

Returns the appointment with related client, service, provider profile, and review data.

### Change appointment status

`PATCH /appointments/{appointment}/status` — **Authenticated; booking client or provider only**

Request example:

```json
{
  "status": "confirmed",
  "provider_notes": "See you then"
}
```

Provider-allowed statuses: `confirmed`, `in_progress`, `completed`, `cancelled`, `no_show`.

Client-allowed status: `cancelled` only. Clients can cancel only while the booking is `pending` or `confirmed`. `provider_notes` is optional and limited to 1000 characters; client-supplied provider notes are ignored.

When a booking is marked completed, the client is automatically added to the provider's client book.

### Client check-in

`POST /appointments/{appointment}/check-in` — **Authenticated; booking client only**

No request body is specified by the route/controller. Records the client's check-in and broadcasts a check-in event to the provider.

### Quick accept / decline from a notification

- `GET /appointments/{appointment}/quick-accept` — **Signed URL required**
- `GET /appointments/{appointment}/quick-decline` — **Signed URL required**

These routes are intended for signed links in push notifications. They are not ordinary public endpoints; use the exact signed URLs generated by the application.

### Review an appointment

`POST /appointments/{appointment}/review` — **Authenticated**

The route exists. Request validation, review eligibility, and response fields should be confirmed in `ReviewController.php`.

---

## 7. Messages

All three routes require authentication.

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/messages` | List messages/conversations |
| `GET` | `/messages/{user}` | Retrieve a thread with a user |
| `POST` | `/messages` | Send a message |

The exact request fields and response structure should be checked in `MessageController.php`.

---

## 8. Push notifications

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/push/subscribe` | Register a push subscription |
| `DELETE` | `/push/unsubscribe` | Remove a push subscription |

Both routes require authentication. The subscription payload schema is defined by `PushSubscriptionController.php` and should be checked before implementing a separate client.

---

## 9. Provider client book and analytics

All routes below require authentication.

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/provider/clients` | List provider's saved clients |
| `POST` | `/provider/clients` | Add a client to the book |
| `PATCH` | `/provider/clients/{client}` | Update client notes |
| `DELETE` | `/provider/clients/{client}` | Remove a client from the book |
| `GET` | `/provider/analytics/clients` | Provider client analytics |

The route names and access middleware are confirmed in `routes/api.php`; check `ClientBookController.php` and `ProviderAnalyticsController.php` for exact body fields and response schemas.

---

## 10. Payments and provider subscriptions

### Payment system status

`GET /system-settings` — **Public**

Returns the current payment-system status, as implemented by `SystemSettingsController`.

### Initiate booking deposit

`POST /appointments/{appointment}/deposit` — **Authenticated**

**Currently disabled in the inspected code.** The endpoint returns `422 Unprocessable Entity` with a message explaining that booking deposits are disabled and no upfront payment is needed to book.

### Payment status

`GET /payments/{payment}/status` — **Authenticated**

Returns payment status. Check `PaymentController.php` for the exact response fields and ownership rules.

### M-Pesa callback

`POST /payments/mpesa/callback` — **Public webhook; called server-to-server**

Safaricom posts its STK callback to this route. It is intentionally outside user-token authentication. The handler reads `Body.stkCallback`, matches `CheckoutRequestID`, and processes success/failure data and any M-Pesa receipt number. Do not call this endpoint as a normal client payment initiation request.

### Provider subscription status

`GET /provider/subscription` — **Authenticated**

Returns the provider's subscription status.

### Initiate provider subscription payment

`POST /provider/subscription/pay` — **Authenticated**

Starts the provider subscription payment flow. The route comments state a monthly fee of **KES 70**, subject to the system-wide payment setting. Check `ProviderSubscriptionController.php` for the required request fields.

### Poll a subscription payment

`GET /provider/subscription/{payment}/poll` — **Authenticated**

Polls the status of a subscription payment. Exact response schema is defined in `ProviderSubscriptionController.php`.

---

## 11. Administrator endpoints

All endpoints in this section require both a valid Sanctum token and the `admin` middleware. They are prefixed with `/admin`.

### Analytics

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/admin/analytics/overview` | Dashboard overview |
| `GET` | `/admin/analytics/revenue` | Revenue time series |
| `GET` | `/admin/analytics/subscription-payments` | Subscription payment series |
| `GET` | `/admin/analytics/bookings` | Booking series |
| `GET` | `/admin/analytics/user-growth` | User growth series |
| `GET` | `/admin/analytics/top-providers` | Top providers |

### User management

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/admin/users` | List users |
| `GET` | `/admin/users/{id}` | View one user |
| `PATCH` | `/admin/users/{id}/suspend` | Suspend a user |
| `PATCH` | `/admin/users/{id}/reactivate` | Reactivate a user |

### Provider management

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/admin/providers` | List providers |
| `GET` | `/admin/providers/{providerProfile}` | View a provider |
| `PATCH` | `/admin/providers/{providerProfile}/verify` | Verify a provider |
| `PATCH` | `/admin/providers/{providerProfile}/unverify` | Remove verification |
| `PATCH` | `/admin/providers/{providerProfile}/feature` | Feature a provider |
| `PATCH` | `/admin/providers/{providerProfile}/unfeature` | Remove featured status |

### Appointment management

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/admin/appointments` | List appointments |
| `PATCH` | `/admin/appointments/{appointment}/cancel` | Cancel an appointment |

### System settings

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/admin/settings` | View system settings |
| `PATCH` | `/admin/settings/payments` | Change the system-wide payments setting |

Request schemas and response details for these admin operations should be verified in their corresponding admin controllers.

---

## 12. Common HTTP status codes

| Status | Meaning |
|---|---|
| `200 OK` | Request succeeded |
| `201 Created` | A resource was created |
| `401 Unauthorized` | Missing/invalid authentication or invalid login |
| `403 Forbidden` | Authenticated user lacks permission, or account is suspended |
| `404 Not Found` | Resource or route was not found |
| `422 Unprocessable Entity` | Validation failed or a business rule rejected the operation |
| `429 Too Many Requests` | Rate limit exceeded |
| `500 Internal Server Error` | Unexpected server error |

Validation responses commonly use a JSON `errors` object or a `message` field, depending on the controller and Laravel's exception handling.

## 13. Example cURL calls

### Log in

```bash
curl -X POST "https://YOUR-BACKEND-DOMAIN/api/v1/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"jane@example.com","password":"example-password"}'
```

### List providers

```bash
curl "https://YOUR-BACKEND-DOMAIN/api/v1/providers?type=barber&open_only=true" \
  -H "Accept: application/json"
```

### List appointments with a token

```bash
curl "https://YOUR-BACKEND-DOMAIN/api/v1/appointments" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

### Create a booking

```bash
curl -X POST "https://YOUR-BACKEND-DOMAIN/api/v1/appointments" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -d '{
    "service_id": 12,
    "scheduled_at": "2026-11-01T10:30:00+03:00",
    "client_notes": "Please keep the sides short",
    "is_home_service": false
  }'
```

Replace the domain, token, IDs, and example values with valid values for your deployment.

## 14. Generate the definitive route list locally

Run these commands from the Laravel project directory:

```bash
php artisan route:list --path=api -vv
```

To save the output for review:

```bash
php artisan route:list --path=api -vv > api-routes.txt
```

This shows the routes registered by the running checkout and their middleware. To publish an accurate, complete API contract, inspect each controller's validation rules, authorization checks, and response payloads, then add example responses and schemas to this document.

## 15. Source files used

- `routes/api.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/AppointmentController.php`
- `app/Http/Controllers/Api/ProviderProfileController.php`
- `app/Http/Controllers/Api/ServiceController.php`
- `app/Http/Controllers/Api/PaymentController.php`

Repository: https://github.com/Blastopy/Glam

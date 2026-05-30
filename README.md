# ONINS — Web Admin & Customer API

Symfony backend for the ONINS repair/inventory platform. Powers the web admin/staff dashboards and the React Native customer app (APPDEV).

## Requirements

- PHP 8.2+
- Composer
- MySQL 8 (Docker Compose included)
- Node.js (optional, for asset tooling)

## Quick start

```bash
# Start database
docker compose up -d

# Install dependencies
composer install

# Configure environment (copy and edit secrets locally)
# .env already contains DATABASE_URL — adjust if needed

# Generate JWT keys (once per machine)
php bin/console lexik:jwt:generate-keypair --skip-if-exists

# Run migrations and load demo data
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
# Or append demo customer only:
php bin/console doctrine:fixtures:load --append --group=customer --no-interaction

# Start API server
symfony server:start
# or: php -S 127.0.0.1:8000 -t public
```

CORS for mobile: set `CORS_ALLOW_ORIGIN` in `.env` (e.g. `*` for local dev).

## Realtime & push (mobile app)

| Channel | Technology | Example |
|---------|------------|---------|
| System push | **Firebase FCM** | Admin approves/rejects order → notification on phone |
| In-app live UI | **Mercure** (SSE) | Admin adds product or changes order → app refreshes without pull |

Start Mercure with MySQL: `docker compose up -d mysql mercure` (hub on port **3000**).

Configure in `.env`:

- `MERCURE_*` — must match `MERCURE_JWT_SECRET` in `docker-compose.yaml`
- `FIREBASE_PROJECT_ID` + `config/firebase-credentials.json` (copy from `config/firebase-credentials.json.example`) for FCM

Admin order actions: **Admin → Orders** (Approve / Reject) or `PATCH /api/admin/orders/{id}/status` with `{"status":"APPROVED"}`.

## Demo accounts

| Role     | Email                      | Password     |
|----------|----------------------------|--------------|
| Admin    | admin@onins                  | admin123     |
| Customer | customer@onins.com         | customer123  |
| Admin    | stockadmin@cabajon.com     | admin123     |
| Staff    | stockmanager@cabajon.com   | staff123     |

Web login: `/login` — Admin `/admin`, Staff `/staff`.

### Google Sign-In

Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_OAUTH_CALLBACK_BASE=http://127.0.0.1:8000` in `.env`. Web **Continue with Google** uses `/connect/google` → `/connect/google/check`.

In [Google Cloud Console](https://console.cloud.google.com/) → OAuth client → **Authorized redirect URIs**, add:

- `http://127.0.0.1:8000/connect/google/check`
- `http://localhost:8000/connect/google/check`

`GOOGLE_OAUTH_CALLBACK_BASE` forces that host even when you open the site via a LAN IP (e.g. `10.x.x.x:8000`), so one redirect URI in Google is enough for local dev.

**Mobile app (APPDEV):** set `EXPO_PUBLIC_GOOGLE_CLIENT_ID` in APPDEV `.env` to the same value as `GOOGLE_CLIENT_ID`. Use **Continue with Google** on the login screen (`POST /api/auth/google` with ID token).

## Customer API (JWT)

Base URL: `http://127.0.0.1:8000/api`  
Android emulator: `http://10.0.2.2:8000/api`

### Authentication

**POST** `/api/login` (public)

```json
{ "email": "customer@onins.com", "password": "customer123" }
```

Response:

```json
{
  "status": "success",
  "token": "<jwt>",
  "user": { "id": 1, "email": "...", "fullName": "...", "roles": ["ROLE_USER"], "isVerified": true }
}
```

**POST** `/api/register` (public) — body: `name`, `email`, `password` (min 8 chars).

All other customer routes require header: `Authorization: Bearer <token>`  
Staff/admin accounts receive **403** on customer routes (use web dashboard).

### Customer endpoints

| Method | Path            | Description                    |
|--------|-----------------|--------------------------------|
| GET    | `/api/profile`  | Current customer profile       |
| PUT    | `/api/profile`  | Update name / password         |
| GET    | `/api/products` | List repair services/products  |
| GET    | `/api/bookings` | List customer bookings         |
| POST   | `/api/bookings` | Create booking                 |
| GET    | `/api/orders`   | List customer orders           |
| POST   | `/api/orders`   | Create order                   |
| GET    | `/api/payments` | List payments                  |
| POST   | `/api/payments` | Pay an order                   |

**POST** `/api/bookings` body:

```json
{ "productId": 1, "scheduledAt": "2026-06-01T10:00:00+00:00", "notes": "optional" }
```

**POST** `/api/orders` body:

```json
{ "productId": 1, "quantity": 1 }
```

**POST** `/api/payments` body:

```json
{ "orderId": 1, "amount": 99.99, "method": "card" }
```

`amount` must match the order `totalAmount`.

**PUT** `/api/profile` body:

```json
{
  "fullName": "Jane Doe",
  "password": "newpassword123",
  "currentPassword": "customer123"
}
```

`password` is optional; when provided, `currentPassword` is required.

### Postman collection

Import `docs/ONINS-Customer-API.postman_collection.json` into Postman. Run **Login** first — it saves the JWT to the `token` variable.

### Staff API

| Method | Path                   | Role        |
|--------|------------------------|-------------|
| GET    | `/api/staff/bookings`  | STAFF/ADMIN |
| GET    | `/api/staff/orders`    | STAFF/ADMIN |

### Admin API

| Method | Path                | Role  |
|--------|---------------------|-------|
| GET    | `/api/admin/users`  | ADMIN |

### Error format

```json
{ "status": "error", "message": "...", "errors": { "field": "..." } }
```

## Railway (webdev + MySQL)

Production API / web admin: **https://webdev-production-c694.up.railway.app**

1. In the Railway project, open the **webdev** service → **Variables** → **Add Reference** → select **MySQL** → add `DATABASE_URL` (or all `MYSQL*` variables). Without this, the container cannot reach the database.
2. Set `APP_SECRET` to a long random string (see `railway.env.example`).
3. `RAILWAY_PUBLIC_DOMAIN` is set automatically; `scripts/railway-start.sh` uses it for `GOOGLE_OAUTH_CALLBACK_BASE` and runs migrations + demo fixtures (same flow as local `start-api.ps1`).
4. Health check: `/health.html` · API: `https://webdev-production-c694.up.railway.app/api`

**Google OAuth on Railway:** add `https://webdev-production-c694.up.railway.app/connect/google/check` to authorized redirect URIs and set `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` on webdev.

**Mobile app against Railway:** in APPDEV `.env` set  
`EXPO_PUBLIC_API_URL=https://webdev-production-c694.up.railway.app/api` and restart Metro.

## Mobile app

See `../APPDEV/README.md` for React Native setup. Start the API with `start-api-for-mobile.bat` or `scripts/start-api.ps1`. Data from the app uses the same MySQL database and appears in the web admin after refresh.

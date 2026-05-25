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

## Demo accounts

| Role     | Email                      | Password     |
|----------|----------------------------|--------------|
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

**Mobile app:** run `appdevv1/enable-usb-api.bat` (adb reverse), set `EXPO_PUBLIC_OAUTH_URL=http://127.0.0.1:8000`, then use Continue with Google. Without adb, add `http://<LAN-IP>:8000/connect/google/check` to Google Console and set `EXPO_PUBLIC_OAUTH_URL` to that LAN URL.

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

## Mobile app

See `../appdevv1/README.md` for React Native setup. Data created in the mobile app is stored in the same MySQL database and visible in the web admin after refresh.

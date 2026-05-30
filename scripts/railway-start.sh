#!/bin/sh
set -e

PORT="${PORT:-8080}"

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"
export APP_SECRET="${APP_SECRET:-buildtime_secret_replace_on_railway}"

# Railway injects MYSQL* when the MySQL service is linked; build DATABASE_URL if missing.
if [ -z "${DATABASE_URL:-}" ]; then
  _host="${MYSQLHOST:-${MYSQL_HOST:-}}"
  if [ -n "$_host" ]; then
    _port="${MYSQLPORT:-${MYSQL_PORT:-3306}}"
    _user="${MYSQLUSER:-${MYSQL_USER:-root}}"
    _pass="${MYSQLPASSWORD:-${MYSQL_PASSWORD:-}}"
    _db="${MYSQLDATABASE:-${MYSQL_DATABASE:-railway}}"
    export DATABASE_URL="mysql://${_user}:${_pass}@${_host}:${_port}/${_db}?serverVersion=8.0&charset=utf8mb4"
  fi
fi

export DATABASE_URL="${DATABASE_URL:-mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0&charset=utf8mb4}"
export CORS_ALLOW_ORIGIN="${CORS_ALLOW_ORIGIN:-'^https?://.*$'}"
export MERCURE_JWT_SECRET="${MERCURE_JWT_SECRET:-buildtime_mercure_secret}"
export MERCURE_URL="${MERCURE_URL:-http://127.0.0.1:3000/.well-known/mercure}"
export MERCURE_PUBLIC_URL="${MERCURE_PUBLIC_URL:-http://127.0.0.1:3000/.well-known/mercure}"
export GOOGLE_CLIENT_ID="${GOOGLE_CLIENT_ID:-not-configured}"
export GOOGLE_CLIENT_SECRET="${GOOGLE_CLIENT_SECRET:-not-configured}"
export GOOGLE_OAUTH_CALLBACK_BASE="${GOOGLE_OAUTH_CALLBACK_BASE:-http://127.0.0.1:8080}"
export JWT_PASSPHRASE="${JWT_PASSPHRASE:-build}"
export JWT_TOKEN_TTL="${JWT_TOKEN_TTL:-604800}"
export MESSENGER_TRANSPORT_DSN="${MESSENGER_TRANSPORT_DSN:-sync://}"
export MAILER_DSN="${MAILER_DSN:-null://null}"

printf '%s\n' \
  "APP_ENV=${APP_ENV}" \
  "APP_DEBUG=${APP_DEBUG}" \
  'JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem' \
  'JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem' \
  "JWT_PASSPHRASE=${JWT_PASSPHRASE}" \
  "JWT_TOKEN_TTL=${JWT_TOKEN_TTL}" \
  "DATABASE_URL=${DATABASE_URL}" \
  "CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN}" \
  "MERCURE_JWT_SECRET=${MERCURE_JWT_SECRET}" \
  "MERCURE_URL=${MERCURE_URL}" \
  "MERCURE_PUBLIC_URL=${MERCURE_PUBLIC_URL}" \
  "GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}" \
  "GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}" \
  "GOOGLE_OAUTH_CALLBACK_BASE=${GOOGLE_OAUTH_CALLBACK_BASE}" \
  "MESSENGER_TRANSPORT_DSN=${MESSENGER_TRANSPORT_DSN}" \
  "MAILER_DSN=${MAILER_DSN}" \
  > .env

mkdir -p config/jwt
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

echo "Starting server on 0.0.0.0:${PORT} (health: /health.html)"
php -S "0.0.0.0:${PORT}" -t public public/router.php &
SERVER_PID=$!

echo "Waiting for database..."
i=0
db_ready=0
while [ "$i" -lt 45 ]; do
  if php bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; then
    echo "Database is ready."
    db_ready=1
    break
  fi
  i=$((i + 1))
  sleep 2
done

if [ "$db_ready" -eq 0 ]; then
  echo "Database not reachable — continuing without migrations."
else
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration \
    || echo "Migrations skipped (database may already be initialized)."

  if ! php bin/console dbal:run-sql "SELECT 1 FROM \`user\` LIMIT 1" >/dev/null 2>&1; then
    echo "Base schema missing — syncing from entity mappings..."
    php bin/console doctrine:schema:update --force --no-interaction \
      || echo "Schema sync skipped."
  fi

  if [ "${RUN_FIXTURES:-0}" = "1" ]; then
    php bin/console doctrine:fixtures:load --no-interaction || echo "Fixtures skipped."
  fi

  php bin/console app:ensure-demo-customer --no-interaction || echo "Demo customer seed skipped."
fi

php bin/console cache:clear --env=prod --no-warmup || true
php bin/console cache:warmup --env=prod || true

wait "$SERVER_PID"

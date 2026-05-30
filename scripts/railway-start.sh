#!/bin/sh
set -e

PORT="${PORT:-8080}"

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"
export APP_SECRET="${APP_SECRET:-buildtime_secret_replace_on_railway}"

# --- Public base URL (HTTPS on Railway) ---------------------------------------
# Prefer explicit APP_PUBLIC_URL, then Railway's domain, then the webdev hostname.
if [ -z "${APP_PUBLIC_URL:-}" ]; then
  if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    export APP_PUBLIC_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
  else
    export APP_PUBLIC_URL="${APP_PUBLIC_URL:-https://webdev-production-c694.up.railway.app}"
  fi
fi
APP_PUBLIC_URL="${APP_PUBLIC_URL%/}"
export APP_DEFAULT_URI="${APP_DEFAULT_URI:-$APP_PUBLIC_URL}"
export GOOGLE_OAUTH_CALLBACK_BASE="${GOOGLE_OAUTH_CALLBACK_BASE:-$APP_PUBLIC_URL}"

# --- MySQL / DATABASE_URL -----------------------------------------------------
# Link MySQL in Railway: webdev → Variables → Add Reference → MySQL (DATABASE_URL or MYSQL*).
normalize_database_url() {
  _url="$1"
  case "$_url" in
    mysql://*|mysqli://*)
      case "$_url" in
        *\?*) ;;
        *) _url="${_url}?serverVersion=8.0&charset=utf8mb4" ;;
      esac
      printf '%s' "$_url"
      ;;
    *)
      printf '%s' "$_url"
      ;;
  esac
}

if [ -z "${DATABASE_URL:-}" ]; then
  if [ -n "${MYSQL_URL:-}" ]; then
    export DATABASE_URL="$(normalize_database_url "$MYSQL_URL")"
  elif [ -n "${MYSQLHOST:-${MYSQL_HOST:-}}" ]; then
    export DATABASE_URL="$(php -r '
      $host = getenv("MYSQLHOST") ?: getenv("MYSQL_HOST");
      $port = getenv("MYSQLPORT") ?: getenv("MYSQL_PORT") ?: "3306";
      $user = getenv("MYSQLUSER") ?: getenv("MYSQL_USER") ?: "root";
      $pass = getenv("MYSQLPASSWORD") ?: getenv("MYSQL_PASSWORD") ?: "";
      $db = getenv("MYSQLDATABASE") ?: getenv("MYSQL_DATABASE") ?: "railway";
      $dsn = sprintf(
        "mysql://%s:%s@%s:%s/%s?serverVersion=8.0&charset=utf8mb4",
        rawurlencode($user),
        rawurlencode($pass),
        $host,
        $port,
        rawurlencode($db)
      );
      echo $dsn;
    ')"
  fi
fi

if [ -n "${DATABASE_URL:-}" ]; then
  export DATABASE_URL="$(normalize_database_url "$DATABASE_URL")"
else
  export DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0&charset=utf8mb4"
  echo "WARNING: No MySQL variables found. Link the MySQL service to webdev on Railway (Variables → Add Reference)."
fi

export CORS_ALLOW_ORIGIN="${CORS_ALLOW_ORIGIN:-'^https?://.*$'}"
export MERCURE_JWT_SECRET="${MERCURE_JWT_SECRET:-buildtime_mercure_secret}"
export MERCURE_URL="${MERCURE_URL:-http://127.0.0.1:3000/.well-known/mercure}"
export MERCURE_PUBLIC_URL="${MERCURE_PUBLIC_URL:-http://127.0.0.1:3000/.well-known/mercure}"
export GOOGLE_CLIENT_ID="${GOOGLE_CLIENT_ID:-not-configured}"
export GOOGLE_CLIENT_SECRET="${GOOGLE_CLIENT_SECRET:-not-configured}"
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
  "APP_DEFAULT_URI=${APP_DEFAULT_URI}" \
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

echo "Public URL: ${APP_PUBLIC_URL}"
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
  echo "Database not reachable — check MySQL is linked and DATABASE_URL is set on Railway."
else
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration \
    || echo "Migrations skipped (database may already be initialized)."

  if ! php bin/console dbal:run-sql "SELECT 1 FROM \`user\` LIMIT 1" >/dev/null 2>&1; then
    echo "Base schema missing — syncing from entity mappings..."
    php bin/console doctrine:schema:update --force --no-interaction \
      || echo "Schema sync skipped."
  fi

  # Same demo data as local: full fixtures on empty catalog, otherwise ensure accounts only.
  if ! php bin/console dbal:run-sql "SELECT 1 FROM product LIMIT 1" >/dev/null 2>&1; then
    echo "Empty catalog — loading demo fixtures (same as local doctrine:fixtures:load)..."
    php bin/console doctrine:fixtures:load --no-interaction || echo "Fixtures skipped."
  elif [ "${RUN_FIXTURES:-0}" = "1" ]; then
    php bin/console doctrine:fixtures:load --no-interaction || echo "Fixtures skipped."
  fi

  php bin/console app:ensure-demo-customer --no-interaction || echo "Demo customer seed skipped."
fi

php bin/console cache:clear --env=prod --no-warmup || true
php bin/console cache:warmup --env=prod || true

wait "$SERVER_PID"

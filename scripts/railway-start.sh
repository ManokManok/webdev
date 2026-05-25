#!/bin/sh
set -e

PORT="${PORT:-8080}"

if [ ! -f .env ]; then
  printf '%s\n' \
    'APP_ENV=prod' \
    'APP_DEBUG=0' \
    'JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem' \
    'JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem' \
    > .env
fi

mkdir -p config/jwt
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

echo "Waiting for database..."
i=0
while [ "$i" -lt 30 ]; do
  if php bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; then
    echo "Database is ready."
    break
  fi
  i=$((i + 1))
  sleep 2
done

if ! php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
  echo "Migrations skipped (database may already be initialized)."
fi

if [ "${RUN_FIXTURES:-0}" = "1" ]; then
  php bin/console doctrine:fixtures:load --no-interaction || echo "Fixtures skipped."
fi

# Safe seed: never purges existing data (mobile demo login).
php bin/console doctrine:fixtures:load --append --group=customer --no-interaction 2>/dev/null || echo "Customer seed skipped."

php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

echo "Starting server on 0.0.0.0:${PORT}"
exec php -S "0.0.0.0:${PORT}" -t public public/index.php

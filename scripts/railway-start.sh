#!/bin/sh
set -e

PORT="${PORT:-8080}"

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

php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

if [ "${RUN_FIXTURES:-0}" = "1" ]; then
  php bin/console doctrine:fixtures:load --no-interaction
fi

php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

echo "Starting server on 0.0.0.0:${PORT}"
exec php -S "0.0.0.0:${PORT}" -t public

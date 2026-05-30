FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql intl zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

COPY . .

# Symfony bootstraps .env even in prod; Railway injects real values at runtime.
RUN printf '%s\n' \
    'APP_ENV=prod' \
    'APP_DEBUG=0' \
    'JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem' \
    'JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem' \
    'JWT_PASSPHRASE=build' \
    'JWT_TOKEN_TTL=604800' \
    'CORS_ALLOW_ORIGIN=^https?://.*$' \
    'MERCURE_JWT_SECRET=!ChangeThisMercureHubJWTSecretKey!' \
    'MERCURE_URL=http://127.0.0.1:3000/.well-known/mercure' \
    'MERCURE_PUBLIC_URL=http://127.0.0.1:3000/.well-known/mercure' \
    'GOOGLE_CLIENT_ID=not-configured' \
    'GOOGLE_CLIENT_SECRET=not-configured' \
    'MESSENGER_TRANSPORT_DSN=sync://' \
    'MAILER_DSN=null://null' \
    > .env \
    && mkdir -p config/jwt

RUN composer dump-autoload --optimize --classmap-authoritative

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV APP_SECRET=buildtime_secret_replace_on_railway
ENV DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0&charset=utf8mb4"

RUN composer run-script post-install-cmd --no-interaction || true

RUN chmod +x scripts/railway-start.sh

EXPOSE 8080
CMD ["scripts/railway-start.sh"]

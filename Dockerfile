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

RUN composer dump-autoload --optimize --classmap-authoritative

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV APP_SECRET=buildtime_secret_replace_on_railway
ENV DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0&charset=utf8mb4"

RUN composer run-script post-install-cmd --no-interaction || true

RUN chmod +x scripts/railway-start.sh

EXPOSE 8080
CMD ["scripts/railway-start.sh"]

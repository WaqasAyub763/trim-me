# syntax=docker/dockerfile:1.6

# ---------- builder: install composer deps ------------------------------------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist

# ---------- runtime: PHP 8.3 CLI + SQLite -------------------------------------
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libsqlite3-dev \
        libzip-dev \
        libicu-dev \
        unzip \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        zip \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer vendor cache, then app code, then regenerate the autoloader.
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p \
        storage/app \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# The platform sets $PORT; default to 8080 for local docker runs.
ENV PORT=8080
EXPOSE 8080

# On boot:
#   1. Make sure the SQLite file exists on the mounted volume.
#   2. Run pending migrations (idempotent — does nothing on subsequent boots).
#   3. Try to seed; non-fatal if rows already exist.
#   4. Start the artisan dev server bound to the platform port.
CMD ["sh", "-c", "\
    mkdir -p /app/storage/app && \
    touch /app/storage/app/database.sqlite && \
    php artisan config:clear && \
    php artisan migrate --force && \
    (php artisan db:seed --force --class=DatabaseSeeder || true) && \
    php artisan serve --host 0.0.0.0 --port ${PORT}"]

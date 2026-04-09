# ── Stage 1: Composer dependencies ──────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

# ── Stage 2: Production image ────────────────────────────────────────────────
FROM php:8.2-apache

# System libraries needed by PHP extensions + Maatwebsite Excel (zip, gd, xml)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libxml2-dev \
        libzip-dev \
        libonig-dev \
        libicu-dev \
        zip \
        unzip \
        curl \
        git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by Laravel 9 + Maatwebsite Excel 3.x
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        gd \
        bcmath \
        intl \
        opcache \
        exif \
        pcntl

# Production PHP settings
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/php.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/php.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/php.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/php.ini \
    && echo "upload_max_filesize=20M" >> /usr/local/etc/php/php.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/php.ini

# Configure Apache to listen on port 8080 (Railway requirement)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

# Point Apache document root at Laravel's public/ folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/sites-available/default-ssl.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides (needed for Laravel routing)
RUN sed -i '/<Directory ${APACHE_DOCUMENT_ROOT}>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf 2>/dev/null || true \
    && a2enmod rewrite

WORKDIR /var/www/html

# Copy application source
COPY . .

# Copy pre-built vendor directory from Stage 1
COPY --from=vendor /app/vendor ./vendor

# Ensure storage directories exist and fix permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
             storage/logs \
             bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Write the entrypoint script inline so no external file needs to be tracked
RUN printf '%s\n' \
    '#!/bin/bash' \
    'set -e' \
    '' \
    'mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache' \
    'chown -R www-data:www-data storage bootstrap/cache' \
    'chmod -R 775 storage bootstrap/cache' \
    '' \
    'if [ -z "$APP_KEY" ]; then' \
    '    export APP_KEY=$(php artisan key:generate --show --no-interaction)' \
    'fi' \
    '' \
    'if [ ! -f .env ]; then' \
    '    cat > .env <<ENVEOF' \
    'APP_NAME=${APP_NAME:-Laravel}' \
    'APP_ENV=${APP_ENV:-production}' \
    'APP_KEY=${APP_KEY}' \
    'APP_DEBUG=${APP_DEBUG:-false}' \
    'APP_URL=${APP_URL:-http://localhost:8080}' \
    'LOG_CHANNEL=${LOG_CHANNEL:-stack}' \
    'LOG_LEVEL=${LOG_LEVEL:-error}' \
    'DB_CONNECTION=${DB_CONNECTION:-mysql}' \
    'DB_HOST=${DB_HOST:-127.0.0.1}' \
    'DB_PORT=${DB_PORT:-3306}' \
    'DB_DATABASE=${DB_DATABASE:-scheduling_system}' \
    'DB_USERNAME=${DB_USERNAME:-root}' \
    'DB_PASSWORD=${DB_PASSWORD:-}' \
    'CACHE_DRIVER=${CACHE_DRIVER:-file}' \
    'SESSION_DRIVER=${SESSION_DRIVER:-file}' \
    'SESSION_LIFETIME=${SESSION_LIFETIME:-120}' \
    'QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}' \
    'FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}' \
    'MAIL_MAILER=${MAIL_MAILER:-smtp}' \
    'MAIL_HOST=${MAIL_HOST:-localhost}' \
    'MAIL_PORT=${MAIL_PORT:-587}' \
    'MAIL_USERNAME=${MAIL_USERNAME:-}' \
    'MAIL_PASSWORD=${MAIL_PASSWORD:-}' \
    'MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}' \
    'MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-hello@example.com}' \
    'STRIPE_KEY=${STRIPE_KEY:-}' \
    'STRIPE_SECRET=${STRIPE_SECRET:-}' \
    'ENVEOF' \
    'fi' \
    '' \
    'php artisan config:cache' \
    'php artisan route:cache' \
    'php artisan view:cache' \
    'php artisan migrate --force --no-interaction' \
    'php artisan storage:link --no-interaction 2>/dev/null || true' \
    '' \
    'exec "$@"' \
    > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

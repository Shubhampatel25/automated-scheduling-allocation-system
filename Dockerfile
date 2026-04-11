FROM php:8.2-apache

# System dependencies + PHP extensions needed by Laravel 9 + Maatwebsite Excel
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libicu-dev \
        zip \
        unzip \
        curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring xml zip gd bcmath intl opcache exif pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Apache: listen on 8080 (Railway exposes this port)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' \
        /etc/apache2/sites-available/000-default.conf

# Apache: point document root at Laravel public/ and allow .htaccess
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' \
        /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        >> /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

WORKDIR /var/www/html

# Install dependencies first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# Copy full application
COPY . .

# Finish composer (autoloader + scripts now that full app is present)
RUN composer dump-autoload --optimize --no-dev

# Storage permissions
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Write the startup script inline — no external file needed
RUN printf '#!/bin/sh\nset -e\n\n\
# Build .env from Railway-injected environment variables\n\
cat > /var/www/html/.env <<ENVEOF\n\
APP_NAME="${APP_NAME:-Laravel}"\n\
APP_ENV="${APP_ENV:-production}"\n\
APP_KEY="${APP_KEY}"\n\
APP_DEBUG="${APP_DEBUG:-false}"\n\
APP_URL="${APP_URL:-http://localhost:8080}"\n\
LOG_CHANNEL="${LOG_CHANNEL:-stack}"\n\
LOG_LEVEL="${LOG_LEVEL:-error}"\n\
DB_CONNECTION="${DB_CONNECTION:-mysql}"\n\
DB_HOST="${DB_HOST:-127.0.0.1}"\n\
DB_PORT="${DB_PORT:-3306}"\n\
DB_DATABASE="${DB_DATABASE:-scheduling_system}"\n\
DB_USERNAME="${DB_USERNAME:-root}"\n\
DB_PASSWORD="${DB_PASSWORD:-}"\n\
CACHE_DRIVER="${CACHE_DRIVER:-file}"\n\
SESSION_DRIVER="${SESSION_DRIVER:-file}"\n\
SESSION_LIFETIME="${SESSION_LIFETIME:-120}"\n\
QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"\n\
FILESYSTEM_DISK="${FILESYSTEM_DISK:-local}"\n\
MAIL_MAILER="${MAIL_MAILER:-smtp}"\n\
MAIL_HOST="${MAIL_HOST:-localhost}"\n\
MAIL_PORT="${MAIL_PORT:-587}"\n\
MAIL_USERNAME="${MAIL_USERNAME:-}"\n\
MAIL_PASSWORD="${MAIL_PASSWORD:-}"\n\
MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-tls}"\n\
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-hello@example.com}"\n\
STRIPE_KEY="${STRIPE_KEY:-}"\n\
STRIPE_SECRET="${STRIPE_SECRET:-}"\n\
ENVEOF\n\
\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
php artisan migrate --force --no-interaction\n\
php artisan storage:link --no-interaction 2>/dev/null || true\n\
\n\
exec apache2-foreground\n' \
    > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]

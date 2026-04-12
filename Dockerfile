FROM php:8.2-fpm

# System dependencies + PHP extensions needed by Laravel 9 + Maatwebsite Excel
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
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

# Nginx: write the Laravel site config inline
RUN { \
    echo 'server {'; \
    echo '    listen 8080;'; \
    echo '    root /var/www/html/public;'; \
    echo '    index index.php index.html;'; \
    echo ''; \
    echo '    location / {'; \
    echo '        try_files $uri $uri/ /index.php?$query_string;'; \
    echo '    }'; \
    echo ''; \
    echo '    location ~ \.php$ {'; \
    echo '        fastcgi_pass 127.0.0.1:9000;'; \
    echo '        fastcgi_index index.php;'; \
    echo '        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;'; \
    echo '        include fastcgi_params;'; \
    echo '    }'; \
    echo ''; \
    echo '    location ~ /\.ht {'; \
    echo '        deny all;'; \
    echo '    }'; \
    echo '}'; \
} > /etc/nginx/sites-available/laravel \
    && ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel \
    && rm -f /etc/nginx/sites-enabled/default

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
RUN { \
    echo '#!/bin/sh'; \
    echo 'set -e'; \
    echo ''; \
    echo '# ── Build .env from Railway-injected environment variables ──────────────────'; \
    echo 'cat > /var/www/html/.env << ENVEOF'; \
    echo 'APP_NAME="${APP_NAME:-Laravel}"'; \
    echo 'APP_ENV="${APP_ENV:-production}"'; \
    echo 'APP_KEY="${APP_KEY}"'; \
    echo 'APP_DEBUG="${APP_DEBUG:-false}"'; \
    echo 'APP_URL="${APP_URL:-http://localhost:8080}"'; \
    echo 'LOG_CHANNEL="${LOG_CHANNEL:-stack}"'; \
    echo 'LOG_LEVEL="${LOG_LEVEL:-error}"'; \
    echo 'DB_CONNECTION="${DB_CONNECTION:-mysql}"'; \
    echo 'DB_HOST="${DB_HOST:-127.0.0.1}"'; \
    echo 'DB_PORT="${DB_PORT:-3306}"'; \
    echo 'DB_DATABASE="${DB_DATABASE:-scheduling_system}"'; \
    echo 'DB_USERNAME="${DB_USERNAME:-root}"'; \
    echo 'DB_PASSWORD="${DB_PASSWORD:-}"'; \
    echo 'CACHE_DRIVER="${CACHE_DRIVER:-file}"'; \
    echo 'SESSION_DRIVER="${SESSION_DRIVER:-file}"'; \
    echo 'SESSION_LIFETIME="${SESSION_LIFETIME:-120}"'; \
    echo 'QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"'; \
    echo 'FILESYSTEM_DISK="${FILESYSTEM_DISK:-local}"'; \
    echo 'MAIL_MAILER="${MAIL_MAILER:-smtp}"'; \
    echo 'MAIL_HOST="${MAIL_HOST:-localhost}"'; \
    echo 'MAIL_PORT="${MAIL_PORT:-587}"'; \
    echo 'MAIL_USERNAME="${MAIL_USERNAME:-}"'; \
    echo 'MAIL_PASSWORD="${MAIL_PASSWORD:-}"'; \
    echo 'MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-tls}"'; \
    echo 'MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-hello@example.com}"'; \
    echo 'STRIPE_KEY="${STRIPE_KEY:-}"'; \
    echo 'STRIPE_SECRET="${STRIPE_SECRET:-}"'; \
    echo 'ENVEOF'; \
    echo ''; \
    echo '# ── Wait for MySQL (up to 60 s) using PHP PDO — no extra packages needed ────'; \
    echo 'MAX_TRIES=30'; \
    echo 'TRIES=0'; \
    echo 'echo "Waiting for MySQL at ${DB_HOST:-127.0.0.1}:${DB_PORT:-3306}..."'; \
    echo 'until php -r "'; \
    echo '  \$h = getenv(\"DB_HOST\") ?: \"127.0.0.1\";'; \
    echo '  \$p = getenv(\"DB_PORT\") ?: \"3306\";'; \
    echo '  \$u = getenv(\"DB_USERNAME\") ?: \"root\";'; \
    echo '  \$w = getenv(\"DB_PASSWORD\") ?: \"\";'; \
    echo '  new PDO(\"mysql:host=\$h;port=\$p\", \$u, \$w);'; \
    echo '" 2>/dev/null; do'; \
    echo '  TRIES=$((TRIES + 1))'; \
    echo '  if [ "$TRIES" -ge "$MAX_TRIES" ]; then'; \
    echo '    echo "ERROR: MySQL unreachable after 60 s — starting anyway."'; \
    echo '    break'; \
    echo '  fi'; \
    echo '  echo "MySQL not ready, retry $TRIES/$MAX_TRIES in 2 s..."'; \
    echo '  sleep 2'; \
    echo 'done'; \
    echo 'echo "MySQL is ready."'; \
    echo ''; \
    echo '# ── Artisan bootstrap ────────────────────────────────────────────────────────'; \
    echo 'php artisan config:cache'; \
    echo 'php artisan route:cache'; \
    echo 'php artisan view:cache'; \
    echo ''; \
    echo '# Migrate — failures are logged but do NOT prevent startup'; \
    echo 'php artisan migrate --force --no-interaction \'; \
    echo '  || echo "WARNING: migrate failed — starting with the existing schema."'; \
    echo ''; \
    echo 'php artisan storage:link --no-interaction 2>/dev/null || true'; \
    echo ''; \
    echo '# ── Start php-fpm and nginx ──────────────────────────────────────────────────'; \
    echo 'php-fpm -D'; \
    echo 'exec nginx -g "daemon off;"'; \
} > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]

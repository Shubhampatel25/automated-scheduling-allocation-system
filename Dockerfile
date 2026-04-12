FROM php:8.2-fpm-alpine

# ── System packages ──────────────────────────────────────────────────────────
# gettext  → provides envsubst (used in start.sh to inject PORT into nginx config)
# unzip    → required by Composer
# libpng-dev   → gd (PNG support)
# libxml2-dev  → xml extension
# libzip-dev   → zip extension
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    gettext \
    unzip \
    libpng-dev \
    libxml2-dev \
    libzip-dev

# ── PHP extensions ────────────────────────────────────────────────────────────
# mbstring is compiled into php:8.2-fpm-alpine — no install needed.
# pdo_mysql → MySQL driver
# bcmath    → Laravel core (arbitrary-precision math)
# xml       → maatwebsite/excel
# zip       → maatwebsite/excel
# gd        → maatwebsite/excel (image/chart cells)
RUN docker-php-ext-install \
    pdo_mysql \
    bcmath \
    xml \
    zip \
    gd

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── PHP dependencies (separate layer for Docker cache efficiency) ─────────────
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# ── Application code ──────────────────────────────────────────────────────────
COPY . .

# Run post-autoload scripts (package:discover, etc.)
RUN composer run-script post-autoload-dump

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/docker/start.sh

# Railway injects PORT at runtime; 8080 is the fallback default
EXPOSE 8080

CMD ["/var/www/html/docker/start.sh"]

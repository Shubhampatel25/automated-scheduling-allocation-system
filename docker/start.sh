#!/bin/sh
set -e

# Railway injects PORT at runtime; fall back to 8080 if not set
export PORT=${PORT:-8080}

echo "==> Starting Laravel on PORT $PORT"

# ── Generate nginx config from template ──────────────────────────────────────
# envsubst with an explicit variable list ensures only ${PORT} is replaced.
# All nginx variables like $uri, $query_string, $realpath_root are left intact.
envsubst '${PORT}' < /var/www/html/docker/nginx.conf.template > /etc/nginx/nginx.conf

# ── Laravel bootstrap ─────────────────────────────────────────────────────────
cd /var/www/html

# Clear stale cached config/views from any previous build layer.
# Using || true so a missing cache file never aborts the boot sequence.
php artisan config:clear  || true
php artisan cache:clear   || true
php artisan view:clear    || true

# Create the public/storage symlink (safe to re-run; fails silently if exists)
php artisan storage:link  || true

# ── Database migrations ───────────────────────────────────────────────────────
php artisan migrate --force || true

# ── Ensure default admin user exists (safe: uses firstOrCreate, no duplicates) ─
php artisan db:seed --class=AdminUserSeeder --force || true

# ── Start process supervisor (nginx + php-fpm) ────────────────────────────────
exec /usr/bin/supervisord -c /var/www/html/docker/supervisord.conf

#!/bin/sh
set -e

# Run database migrations, seeders, and ensure admin exists on first container initialization
if [ -n "$DB_HOST" ] || [ -n "$DB_URL" ]; then
    if [ ! -f /app/storage/framework/.bootstrapped ]; then
        echo "Running initial database migrations..."
        php artisan migrate --force || true

        echo "Seeding catalog templates and plans..."
        php artisan db:seed --force || true

        echo "Ensuring initial admin account is created..."
        php artisan user:admin --no-interaction || true

        mkdir -p /app/storage/framework && touch /app/storage/framework/.bootstrapped || true
    fi
fi

# Cache config, routes, and views for optimal production performance.
# Always re-cache on every restart so env-var changes (e.g. APP_URL) are
# picked up immediately without needing a full redeploy.
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"

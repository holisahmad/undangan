#!/bin/sh
set -e

# Run database migrations when database is configured
if [ -n "$DB_HOST" ] || [ -n "$DB_URL" ]; then
    echo "Running database migrations on PostgreSQL..."
    php artisan migrate --force || true
fi

# Cache config, routes, and views for optimal production performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"


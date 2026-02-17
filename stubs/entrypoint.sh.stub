#!/usr/bin/env bash
set -e

APP_PATH="/app"

echo "Preparing FrankenPHP environment..."

# Ensure storage folders exist
mkdir -p $APP_PATH/storage/framework/{sessions,views,cache}
mkdir -p $APP_PATH/storage/app/public
mkdir -p $APP_PATH/storage/app/livewire-tmp

# Fix permissions
chown -R www-data:www-data $APP_PATH/storage $APP_PATH/bootstrap/cache 2>/dev/null || true
chmod -R 775 $APP_PATH/storage $APP_PATH/bootstrap/cache

# Handle .env and Key
if [ ! -f .env ]; then
    cp .env.example .env
fi
php artisan key:generate --force

# Link storage
echo "Linking Storage..."
php artisan storage:link 2>/dev/null || true

# Clear and cache config for production
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Start FrankenPHP with Octane
echo "Starting FrankenPHP Octane..."
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000 --admin-port=2019 --workers=auto --max-requests=500

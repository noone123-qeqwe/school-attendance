#!/bin/sh

echo "Starting Smart Classroom Attendance System..."

# Default PORT (Render assigns this)
export PORT=${PORT:-10000}

# Create storage directories if missing
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Create storage link
php artisan storage:link 2>/dev/null || true

# Run database migrations
echo "Running migrations..."
php artisan migrate --force 2>&1 || echo "Migration warning (may be OK if tables exist)"

# Cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting web server on port $PORT..."
php artisan serve --host=0.0.0.0 --port=$PORT

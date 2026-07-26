#!/bin/sh

# Default PORT to 10000 if not set (Render default)
export PORT=${PORT:-10000}

echo "Starting application on port $PORT..."

# Create storage link if it doesn't exist
php artisan storage:link 2>/dev/null || true

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Replace PORT variable in nginx config
envsubst '${PORT}' < /etc/nginx/http.d/default.conf > /etc/nginx/http.d/default.conf.tmp
mv /etc/nginx/http.d/default.conf.tmp /etc/nginx/http.d/default.conf

# Start PHP-FPM
php-fpm -D

# Start Nginx in foreground so Docker container doesn't exit
nginx -g "daemon off;"

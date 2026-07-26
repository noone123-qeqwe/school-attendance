#!/bin/sh

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Start PHP-FPM
php-fpm -D

# Start Nginx in foreground so Docker container doesn't exit
nginx -g "daemon off;"

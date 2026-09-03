#!/bin/sh
set -e

echo "🚀 Starting Smart Classroom Attendance System..."

# Default port to 80 if PORT is not provided by Render/Railway
PORT="${PORT:-80}"
echo "🌐 Configuring Nginx to listen on port ${PORT}..."
sed -i "s/listen 80;/listen ${PORT};/g" /etc/nginx/http.d/default.conf
sed -i "s/listen \[::\]:80;/listen [::]:${PORT};/g" /etc/nginx/http.d/default.conf
sed -i "s/\${PORT}/${PORT}/g" /etc/nginx/http.d/default.conf 2>/dev/null || true

# Setup required storage directories
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

# Ensure write permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate temporary APP_KEY fallback if not provided in environment
if [ -z "$APP_KEY" ]; then
    echo "🔑 APP_KEY not provided, generating temporary fallback key..."
    export APP_KEY=$(php artisan key:generate --show)
fi

# Run storage symlink
echo "🔗 Ensuring storage symlink..."
php artisan storage:link || true

# Ensure SQLite file exists if using sqlite
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown -R www-data:www-data "$(dirname "$DB_FILE")"
    chmod -R 775 "$(dirname "$DB_FILE")"
fi

# Run database migrations if configured
if [ -n "$DB_HOST" ] || [ -n "$DATABASE_URL" ] || [ -n "$DB_URL" ] || [ -n "$MYSQLHOST" ] || [ -n "$MYSQL_URL" ] || [ "$DB_CONNECTION" = "sqlite" ]; then
    echo "🗄️ Database configured, running migrations..."
    RETRY_COUNT=0
    MAX_RETRIES=6
    until php artisan migrate --force || [ $RETRY_COUNT -eq $MAX_RETRIES ]; do
        RETRY_COUNT=$((RETRY_COUNT+1))
        echo "Database not ready yet... retry $RETRY_COUNT/$MAX_RETRIES in 5 seconds..."
        sleep 5
    done
fi

# Cache config, routes, and views for production performance
echo "⚡ Optimizing Laravel caches..."
php artisan optimize:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start PHP-FPM in daemon mode
echo "🐘 Starting PHP-FPM..."
mkdir -p /var/run
rm -f /var/run/php-fpm.sock
php-fpm -D

# Wait briefly for PHP-FPM socket to initialize
RETRY_FPM=0
while [ ! -e /var/run/php-fpm.sock ] && [ $RETRY_FPM -lt 10 ]; do
    sleep 0.5
    RETRY_FPM=$((RETRY_FPM+1))
done
chmod 666 /var/run/php-fpm.sock 2>/dev/null || true

# Start Nginx in the foreground
echo "🚀 Starting Nginx..."
exec nginx -g "daemon off;"
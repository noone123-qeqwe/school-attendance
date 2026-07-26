#!/bin/bash

echo "🚀 Starting Smart Classroom Attendance System..."

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
until php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected';" 2>/dev/null; do
  echo "Database not ready, waiting 5 seconds..."
  sleep 5
done

echo "✅ Database connected successfully"

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Create storage link
echo "🔗 Creating storage symbolic link..."
php artisan storage:link

# Clear and cache for production
echo "⚡ Optimizing application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set correct permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

echo "✅ Application startup complete!"

# Start Apache
echo "🌐 Starting web server..."
exec apache2-foreground
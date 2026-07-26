#!/bin/bash

echo "🚀 Starting Smart Classroom Attendance System on Render..."

# Wait for database to be available
echo "⏳ Checking database connection..."
max_attempts=30
attempt=1

while [ $attempt -le $max_attempts ]; do
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected';" 2>/dev/null; then
        echo "✅ Database connected successfully"
        break
    else
        echo "Attempt $attempt/$max_attempts: Database not ready, waiting..."
        sleep 10
        ((attempt++))
    fi
done

if [ $attempt -gt $max_attempts ]; then
    echo "❌ Database connection failed after $max_attempts attempts"
    exit 1
fi

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Clear caches (in case of deployment updates)
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Recache for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Ensure storage link exists
echo "🔗 Ensuring storage link..."
php artisan storage:link

echo "✅ Application ready!"

# Start the web server
echo "🌐 Starting web server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
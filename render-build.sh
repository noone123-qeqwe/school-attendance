#!/bin/bash

echo "🏗️ Building Smart Classroom Attendance System for Render..."

# Install Composer dependencies
echo "📦 Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# Generate application key if not set
echo "🔑 Setting up application key..."
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --no-interaction
fi

# Cache configuration for production
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# Set proper permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache

echo "✅ Build completed successfully!"
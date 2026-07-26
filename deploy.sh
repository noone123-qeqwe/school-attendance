#!/bin/bash

echo "🚀 Starting deployment of Smart Classroom Attendance System..."

# 1. Pull latest changes (if using Git)
echo "📥 Pulling latest changes..."
# git pull origin main

# 2. Install/update Composer dependencies
echo "📦 Installing production dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# 3. Generate application key (if not set)
echo "🔑 Generating application key..."
php artisan key:generate --force

# 4. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 5. Seed default data (optional)
echo "🌱 Seeding database..."
# php artisan db:seed --force

# 6. Clear and optimize caches
echo "🧹 Clearing and optimizing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 7. Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 8. Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# 9. Set proper permissions
echo "🔒 Setting file permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# 10. Restart services
echo "🔄 Restarting services..."
# sudo systemctl restart nginx
# sudo systemctl restart php8.2-fpm
# sudo supervisorctl restart reverb:*

echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Post-deployment checklist:"
echo "   ✓ Update .env with production values"
echo "   ✓ Configure web server (Nginx/Apache)"
echo "   ✓ Set up SSL certificate"
echo "   ✓ Configure process manager for Reverb"
echo "   ✓ Set up database backups"
echo "   ✓ Configure monitoring"
echo ""
echo "🌐 Your Smart Classroom Attendance System is ready!"
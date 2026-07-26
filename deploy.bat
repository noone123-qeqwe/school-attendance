@echo off
echo 🚀 Starting deployment of Smart Classroom Attendance System...

echo.
echo 📦 Installing production dependencies...
composer install --optimize-autoloader --no-dev --no-interaction

echo.
echo 🔑 Generating application key...
php artisan key:generate --force

echo.
echo 🗄️ Running database migrations...
php artisan migrate --force

echo.
echo 🧹 Clearing caches...
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo.
echo ⚡ Optimizing for production...
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo.
echo 🔗 Creating storage link...
php artisan storage:link

echo.
echo ✅ Deployment completed successfully!
echo.
echo 📋 Next steps:
echo    1. Copy .env.production to .env and configure
echo    2. Set up web server (IIS/Apache)
echo    3. Configure SSL certificate
echo    4. Start Reverb WebSocket server
echo    5. Set up scheduled tasks for cron jobs
echo.
echo 🌐 Your Smart Classroom Attendance System is ready!
pause
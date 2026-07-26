@echo off
REM Smart Classroom Attendance System - Render Deployment Script (Windows)
REM This script prepares the application for Render deployment

echo 🚀 Preparing Smart Classroom Attendance System for Render deployment...
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo ❌ This script must be run from the Laravel project root directory
    pause
    exit /b 1
)

echo 📋 Step 1: Installing dependencies
composer install --optimize-autoloader --no-dev --no-interaction
if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies
    pause
    exit /b 1
)
echo ✅ Dependencies installed

echo.
echo 📋 Step 2: Setting up environment
if not exist ".env" (
    copy ".env.production" ".env" >nul
    echo ⚠️ Created .env from .env.production
)

echo.
echo 📋 Step 3: Generating application key
php artisan key:generate --ansi
if %errorlevel% neq 0 (
    echo ❌ Failed to generate application key
    pause
    exit /b 1
)
echo ✅ Application key generated

echo.
echo 📋 Step 4: Caching configuration
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi
if %errorlevel% neq 0 (
    echo ❌ Failed to cache configuration
    pause
    exit /b 1
)
echo ✅ Configuration cached

echo.
echo 📋 Step 5: Creating storage link
php artisan storage:link --ansi
if %errorlevel% neq 0 (
    echo ⚠️ Storage link may already exist or failed to create
)
echo ✅ Storage link ready

echo.
echo 📋 Step 6: Optimizing application
php artisan optimize --ansi
if %errorlevel% neq 0 (
    echo ❌ Failed to optimize application
    pause
    exit /b 1
)
echo ✅ Application optimized

echo.
echo 📋 Step 7: Setting up Git repository
if not exist ".git" (
    git init
    git add .
    git commit -m "Initial commit: Smart Classroom Attendance System"
    echo ✅ Git repository initialized
) else (
    echo ⚠️ Git repository already exists
)

echo.
echo 🎉 Deployment preparation complete!
echo.
echo Next steps:
echo 1. Push your code to GitHub/GitLab:
echo    git remote add origin https://github.com/yourusername/attendance-system.git
echo    git branch -M main
echo    git push -u origin main
echo.
echo 2. Deploy to Render:
echo    - Go to render.com and connect your repository
echo    - Use the render-blueprint.yaml for quick deployment
echo    - Or follow the RENDER_CHECKLIST.md step by step
echo.
echo Deployment files ready:
echo    📄 render.yaml - Full deployment configuration
echo    📄 render-blueprint.yaml - One-click deployment
echo    📋 RENDER_CHECKLIST.md - Step-by-step guide
echo    📖 RENDER_DEPLOYMENT.md - Complete documentation
echo.
echo Remember to configure:
echo    🔐 Database credentials
echo    📧 Email settings
echo    🔑 API keys
echo    🌐 Custom domain (optional)
echo.
echo Your Smart Classroom Attendance System is ready for production! 🚀
echo.
pause
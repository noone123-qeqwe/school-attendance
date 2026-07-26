#!/bin/bash

# Smart Classroom Attendance System - Render Deployment Script
# This script prepares the application for Render deployment

echo "🚀 Preparing Smart Classroom Attendance System for Render deployment..."

# Set error handling
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_step() {
    echo -e "${BLUE}📋 Step $1: $2${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the Laravel project root directory"
    exit 1
fi

print_step 1 "Installing dependencies"
composer install --optimize-autoloader --no-dev --no-interaction
print_success "Dependencies installed"

print_step 2 "Generating application key"
if [ ! -f ".env" ]; then
    cp .env.production .env
    print_warning "Created .env from .env.production"
fi

php artisan key:generate --ansi
print_success "Application key generated"

print_step 3 "Caching configuration"
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi
print_success "Configuration cached"

print_step 4 "Creating storage link"
php artisan storage:link --ansi
print_success "Storage link created"

print_step 5 "Optimizing application"
php artisan optimize --ansi
print_success "Application optimized"

print_step 6 "Setting up Git repository"
if [ ! -d ".git" ]; then
    git init
    git add .
    git commit -m "Initial commit: Smart Classroom Attendance System"
    print_success "Git repository initialized"
else
    print_warning "Git repository already exists"
fi

echo ""
echo "🎉 Deployment preparation complete!"
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo "1. Push your code to GitHub/GitLab:"
echo "   git remote add origin https://github.com/yourusername/attendance-system.git"
echo "   git branch -M main"
echo "   git push -u origin main"
echo ""
echo "2. Deploy to Render:"
echo "   - Go to render.com and connect your repository"
echo "   - Use the render-blueprint.yaml for quick deployment"
echo "   - Or follow the RENDER_CHECKLIST.md step by step"
echo ""
echo -e "${BLUE}Deployment files ready:${NC}"
echo "   📄 render.yaml - Full deployment configuration"
echo "   📄 render-blueprint.yaml - One-click deployment"  
echo "   📋 RENDER_CHECKLIST.md - Step-by-step guide"
echo "   📖 RENDER_DEPLOYMENT.md - Complete documentation"
echo ""
echo -e "${YELLOW}Remember to configure:${NC}"
echo "   🔐 Database credentials"
echo "   📧 Email settings" 
echo "   🔑 API keys"
echo "   🌐 Custom domain (optional)"
echo ""
echo "Your Smart Classroom Attendance System is ready for production! 🚀"
# 🚀 Smart Classroom Attendance System - Deployment Guide

## 📋 Table of Contents
1. [System Requirements](#system-requirements)
2. [Pre-Deployment Setup](#pre-deployment-setup)
3. [Database Configuration](#database-configuration)
4. [Web Server Configuration](#web-server-configuration)
5. [SSL Configuration](#ssl-configuration)
6. [WebSocket Setup](#websocket-setup)
7. [Scheduled Tasks](#scheduled-tasks)
8. [Production Deployment](#production-deployment)
9. [Post-Deployment](#post-deployment)
10. [Troubleshooting](#troubleshooting)

## 🖥️ System Requirements

### Minimum Requirements
- **PHP**: 8.2 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.3+
- **Web Server**: Nginx 1.18+ / Apache 2.4+
- **Memory**: 2GB RAM minimum
- **Storage**: 10GB available space
- **SSL Certificate**: Required for HTTPS

### PHP Extensions Required
```bash
php-mysql, php-mbstring, php-xml, php-bcmath, php-curl, 
php-gd, php-zip, php-intl, php-redis (optional)
```

## 🔧 Pre-Deployment Setup

### 1. Clone/Upload Files
```bash
# If using Git
git clone [your-repository-url] /path/to/attendance
cd /path/to/attendance

# Or upload files via FTP/SFTP to your web directory
```

### 2. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Set File Permissions (Linux/Unix)
```bash
sudo chown -R www-data:www-data /path/to/attendance
sudo chmod -R 755 /path/to/attendance/storage
sudo chmod -R 755 /path/to/attendance/bootstrap/cache
```

## 🗄️ Database Configuration

### 1. Create Database
```sql
CREATE DATABASE attendance_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'attendance_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON attendance_production.* TO 'attendance_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Configure Environment
```bash
# Copy production environment template
cp .env.production .env

# Edit .env file with your production values
nano .env
```

### 3. Generate Application Key
```bash
php artisan key:generate --force
```

### 4. Run Migrations
```bash
php artisan migrate --force
```

## 🌐 Web Server Configuration

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /path/to/attendance/public;

    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    index index.html index.htm index.php;

    charset utf-8;

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Hide sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # File upload size
    client_max_body_size 64M;

    # WebSocket proxy for Reverb
    location /app/ {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_pass http://127.0.0.1:8080;
    }
}
```

### Apache Configuration (.htaccess)
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## 🔒 SSL Configuration

### Using Let's Encrypt (Certbot)
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal setup
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 🔌 WebSocket Setup (Reverb)

### 1. Create Supervisor Configuration
```bash
sudo nano /etc/supervisor/conf.d/reverb.conf
```

```ini
[program:reverb]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/attendance/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/attendance/storage/logs/reverb.log
stopwaitsecs=3600
```

### 2. Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb:*
```

### 3. Configure Firewall
```bash
# Allow WebSocket port
sudo ufw allow 8080
```

## ⏰ Scheduled Tasks

### Linux/Unix Cron Jobs
```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /path/to/attendance && php artisan schedule:run >> /dev/null 2>&1

# Add specific tasks
0 0 * * * cd /path/to/attendance && php artisan attendance:mark-absent
0 2 * * * cd /path/to/attendance && php artisan attendance:cleanup-holidays
```

### Windows Task Scheduler
1. Open Task Scheduler
2. Create Basic Task
3. Set trigger to "Daily" at 12:00 AM
4. Action: Start a program
5. Program: `php`
6. Arguments: `artisan attendance:mark-absent`
7. Start in: `C:\path\to\attendance`

## 🚀 Production Deployment

### Automated Deployment
```bash
# Make deployment script executable (Linux)
chmod +x deploy.sh
./deploy.sh

# Windows
deploy.bat
```

### Manual Deployment Steps
```bash
# 1. Optimize for production
composer install --optimize-autoloader --no-dev
php artisan key:generate --force
php artisan migrate --force

# 2. Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 3. Create storage link
php artisan storage:link

# 4. Clear temporary caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## ✅ Post-Deployment

### 1. Verify Installation
- ✅ Visit your domain and check login page loads
- ✅ Test admin login: `admin@school.edu` / `admin123`
- ✅ Test teacher login: `sandy.rosa@school.edu` / `password123`  
- ✅ Test student registration and login
- ✅ Verify QR code generation works
- ✅ Test real-time notifications
- ✅ Check file uploads work

### 2. Security Checklist
- ✅ Change default admin password
- ✅ Update all .env credentials
- ✅ Configure firewall rules
- ✅ Set up SSL monitoring
- ✅ Enable server security headers
- ✅ Configure backup strategy

### 3. Monitoring Setup
- ✅ Set up log monitoring
- ✅ Configure uptime monitoring
- ✅ Set up database backup automation
- ✅ Monitor WebSocket connections
- ✅ Set up error alerting

## 🔧 Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Common fixes
php artisan config:clear
php artisan cache:clear
chmod -R 755 storage
```

#### 2. Database Connection Error
```bash
# Check database credentials in .env
# Test connection
php artisan tinker
DB::connection()->getPdo();
```

#### 3. WebSocket Not Working
```bash
# Check Reverb status
sudo supervisorctl status reverb:*

# Restart if needed
sudo supervisorctl restart reverb:*

# Check logs
tail -f storage/logs/reverb.log
```

#### 4. File Upload Issues
```bash
# Check permissions
chmod -R 755 storage
chown -R www-data:www-data storage

# Check PHP upload limits
php -i | grep upload_max_filesize
```

### Performance Optimization

#### 1. Enable OPcache
```ini
; Add to php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
```

#### 2. Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_attendance_date ON attendances(date);
CREATE INDEX idx_attendance_user ON attendances(user_id);
CREATE INDEX idx_attendance_subject ON attendances(subject_code);
```

## 📞 Support

For deployment support:
- Check Laravel documentation: https://laravel.com/docs
- Reverb WebSocket: https://laravel.com/docs/reverb
- Server configuration guides specific to your hosting provider

---

**🎉 Congratulations! Your Smart Classroom Attendance System is now deployed!**

Remember to:
- Keep your system updated
- Monitor logs regularly
- Backup your database
- Test all features after deployment
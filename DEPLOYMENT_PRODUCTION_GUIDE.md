# Production Deployment & School Operations Guide
## Smart Classroom Attendance & School Management System

This comprehensive guide covers everything required to deploy, secure, scale, and operate the **Smart Classroom Attendance Management System** in an actual school or institutional environment.

---

## 1. System Architecture & Requirements

### Software Requirements
- **PHP**: 8.2 or 8.3 with extensions:
  - `pdo_mysql`, `bcmath`, `mbstring`, `openssl`, `xml`, `curl`, `zip`, `gd`, `gmp` (required for WebPush VAPID crypto), `pcntl` (for queue workers)
- **Database**: MySQL 8.0+ or MariaDB 10.5+
  - Default character set: `utf8mb4`
  - Default collation: `utf8mb4_unicode_ci`
- **Web Server**: Nginx (recommended) or Apache 2.4+
- **Process Manager**: Supervisor or systemd (for background queue workers and scheduler)
- **Real-Time WebSockets**: Laravel Reverb (included) or network polling fallback

### Recommended Hardware Specifications
| Deployment Scale | Concurrent Students | CPU Cores | RAM | Storage |
| :--- | :--- | :--- | :--- | :--- |
| **Small School** (up to 500 students) | ~100/min | 2 vCPU | 2 GB | 25 GB SSD |
| **Medium School** (500 – 3,000 students) | ~500/min | 4 vCPU | 4 GB | 50 GB SSD |
| **Large Campus** (3,000+ students) | ~1,500/min | 8 vCPU | 8 GB | 100 GB NVMe |

---

## 2. Linux Server Deployment (Ubuntu 22.04 / 24.04 LTS)

### Step 1: Install Required Packages
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server git curl unzip supervisor
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-bcmath php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-gmp php8.2-intl
```

### Step 2: Configure MySQL Database
```sql
sudo mysql -u root
CREATE DATABASE school_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'school_user'@'localhost' IDENTIFIED BY 'Strong_Production_Password_Here';
GRANT ALL PRIVILEGES ON school_attendance.* TO 'school_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Clone Codebase & Configure Permissions
```bash
cd /var/www
sudo git clone https://github.com/your-org/school-attendance.git attendance
cd /var/www/attendance

# Set directory ownership to web server user
sudo chown -R www-data:www-data /var/www/attendance
sudo find /var/www/attendance -type d -exec chmod 755 {} \;
sudo find /var/www/attendance -type f -exec chmod 644 {} \;

# Writable storage and cache directories
sudo chmod -R 775 /var/www/attendance/storage /var/www/attendance/bootstrap/cache
```

### Step 4: Install Dependencies & Setup Environment
```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data cp .env.example .env

# Edit .env with your production database credentials, APP_URL, etc.
sudo -u www-data nano .env

# Generate application cryptographic encryption key
sudo -u www-data php artisan key:generate

# Generate public storage symlink for avatars, photos, and QR codes
sudo -u www-data php artisan storage:link

# Run database migrations
sudo -u www-data php artisan migrate --force

# Seed default active academic year and core school settings
sudo -u www-data php artisan db:seed --class=DemoSeeder --force
```

### Step 5: Configure Nginx Virtual Host
Create `/etc/nginx/sites-available/attendance.conf`:
```nginx
server {
    listen 80;
    server_name attendance.school.edu;
    root /var/www/attendance/public;

    index index.php index.html;
    charset utf-8;

    # Maximum file upload size (for CSV rosters and excuse medical slips)
    client_max_body_size 25M;

    # Gzip compression for fast loading over school Wi-Fi
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml image/svg+xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Pass PHP scripts to FastCGI server
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files (.env, .git)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2|svg)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```
Enable the site and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/attendance.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 6: Install Free SSL Certificate (Certbot)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d attendance.school.edu
```

---

## 3. Background Services & Daemons

For push notifications, schedule calculations, and queue processing, set up Supervisor:

### Queue Worker Daemon (`/etc/supervisor/conf.d/attendance-worker.conf`)
```ini
[program:attendance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/attendance/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/attendance/storage/logs/worker.log
stopwaitsecs=3600
```

### WebSockets Server Daemon (`/etc/supervisor/conf.d/attendance-reverb.conf`)
```ini
[program:attendance-reverb]
process_name=%(program_name)s
command=php /var/www/attendance/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/attendance/storage/logs/reverb.log
```

Activate Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### Laravel Scheduler (Crontab)
Run `sudo crontab -u www-data -e` and add:
```cron
* * * * * cd /var/www/attendance && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Cloud & PaaS Deployment (Railway / Docker)

### Deploying on Railway
1. **Connect GitHub Repository** to your Railway Project.
2. **Add MySQL Plugin**: Click `+ New` -> `Database` -> `MySQL`.
3. **Configure Environment Variables** in your Web Service:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=${{MySQL.MYSQLHOST}}`
   - `DB_PORT=${{MySQL.MYSQLPORT}}`
   - `DB_DATABASE=${{MySQL.MYSQLDATABASE}}`
   - `DB_USERNAME=${{MySQL.MYSQLUSER}}`
   - `DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}`
   - `APP_KEY`: Leave blank (auto-generated by `start.sh`) or supply a 32-character base64 key.
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}`
   - `TRUSTED_PROXIES=*`
   - `SESSION_DRIVER=database`
   - `CACHE_STORE=database`
   - `QUEUE_CONNECTION=database`
4. Deploy! The container runs `docker/start.sh`, automatically runs migrations, links storage, launches background workers, and binds Nginx to `$PORT`.

---

## 5. Security Hardening Checklist

- [x] **`APP_DEBUG=false`**: Prevents stack traces, database schemas, and credentials from ever leaking to students or public users.
- [x] **CSP Script Nonces**: Cryptographic nonces generated per request to eliminate cross-site scripting (XSS).
- [x] **Account Deactivation Immediate Session Kill**: When an admin disables a student or teacher account, `CheckAccountStatus` middleware immediately destroys all active sessions and rejects authentication.
- [x] **SQL Injection Defense**: All user inputs sanitized and parametrized through Eloquent and Query Builder.
- [x] **Device Binding Anti-Proxying**: Students can only register and clock in from their verified mobile device, preventing friends from scanning QR codes on their behalf.
- [x] **Tamper-Proof Time Window**: Dynamic QR codes rotate every 15–30 seconds with signed payloads to prevent photos/screenshots from being forwarded.
- [x] **Comprehensive Audit Trail**: Spatie ActivityLog permanently records all manual overrides, term changes, account deactivations, and grade/attendance corrections with administrative justifications.

---

## 6. School Operations & Administration Runbook

### Managing Academic Terms (Years & Semesters)
1. Navigate to **Admin Dashboard -> Academic Terms** (`/admin/academic-years`).
2. Click **Add Term** and enter the school year (e.g. `2026-2027`), select the semester (`1st Semester`), and specify start and end dates.
3. Click **Set Current** to activate the new term. This atomically updates all class schedules, resets missed attendance calculations, synchronizes system settings, and purges term cache.

### Enrolling Students via Bulk CSV Import
1. Navigate to **Admin Dashboard -> Students** (`/admin/students`).
2. Click **Download Template** to obtain the standardized format.
3. Upload your CSV via **Import CSV**. Columns supported: `Name`, `Email`, `Student Number`, `Course`, `Year Level`, `Semester`, `Section`, `Password`.
4. Existing students are updated; new student profiles and accounts are created automatically.

### Printing Badges & QR Codes
1. Navigate to **Admin Dashboard -> QR Management** (`/admin/qr`).
2. Filter by Course and Year Level.
3. Select students and click **Bulk Print Badges** to generate printable ID cards with embedded permanent student identity QR codes.

### Handling Teacher Attendance Overrides & Excuses
1. Navigate to **Admin Dashboard -> Attendance Records** (`/admin/attendance?tab=records`).
2. Search for the student by name or student ID.
3. Click **Override** on the record, select the new status (`Present`, `Late`, `Absent`, or `Excused`), enter the administrative justification, and save.
4. The change is audited and reflects immediately on student percentages and reports.

### Generating & Exporting Reports
1. Navigate to **Admin Dashboard -> Reports & Analytics** (`/admin/reports`).
2. Select report type: `Daily`, `Weekly`, `Monthly`, `Date Range`, `Late List`, `Absenteeism`, `Class Summary`, `Subject Summary`, or `Student %`.
3. Filter by Course, Year Level, or Subject Code.
4. Click **Export CSV** for Excel data manipulation, or **Export PDF** for official printed records.

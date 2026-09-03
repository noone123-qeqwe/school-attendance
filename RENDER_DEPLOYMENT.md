# 🚀 Deploying Smart Classroom Attendance System to Render.com

This guide provides step-by-step instructions to deploy your Laravel application to [Render](https://render.com).

---

## 🏗️ Architecture Overview
* **Runtime**: Docker (Render requires Docker for PHP applications).
* **Web Server**: Nginx + PHP-FPM 8.2 (Alpine-based, fast, lightweight).
* **Database Options**:
  * **Option 1 (Render Native)**: Render Free PostgreSQL Database (`DB_CONNECTION=pgsql`).
  * **Option 2 (External MySQL)**: Free Cloud MySQL such as TiDB Serverless, Aiven, or Railway (`DB_CONNECTION=mysql`).
* **Port Routing**: Dynamic port assignment via Render's `$PORT` environment variable (auto-configured in `docker/start.sh`).
* **SSL & Reverse Proxy**: Render provides free automatic SSL certificates. Trusted proxies are enabled in `bootstrap/app.php`.

---

## 📋 Prerequisites
1. A [GitHub](https://github.com) account with this repository pushed.
2. A free [Render.com](https://render.com) account.

---

## ⚡ Deployment Methods

You can deploy using either **Method A (Blueprint - 1 Click)** or **Method B (Manual Dashboard)**.

---

### 🔹 Method A: 1-Click Blueprint Deployment (Recommended)

1. Log in to your [Render Dashboard](https://dashboard.render.com).
2. Click **New +** at the top right, then select **Blueprint**.
3. Connect your GitHub repository (`school-attendance`).
4. Render will automatically detect [`render.yaml`](file:///c:/Users/mcg45/Desktop/attendance/school_attendance/render.yaml) and display the services to be created:
   * **`attendance-db`**: Render Managed PostgreSQL Database (Free tier)
   * **`attendance-system`**: Docker Web Service (Free tier)
5. Click **Apply**.
6. Render will create the database, inject the `DATABASE_URL`, build the Docker container, run migrations, and deploy your site!

---

### 🔹 Method B: Manual Web Service Setup (Best for External MySQL)

If you prefer using an external MySQL database (like TiDB Serverless or Aiven) or configuring services manually:

#### Step 1: Create the Web Service
1. In Render Dashboard, click **New +** -> **Web Service**.
2. Select **Build and deploy from a Git repository** and connect your repository.
3. Configure the service settings:
   * **Name**: `attendance-system` (or your chosen name)
   * **Region**: Choose the closest region to your users (e.g. `Singapore` or `Oregon`)
   * **Branch**: `main`
   * **Root Directory**: Leave blank (uses repo root)
   * **Runtime**: **Docker**
   * **Instance Type**: **Free**
   * **Health Check Path**: `/up`

#### Step 2: Add Environment Variables
Scroll down to the **Environment Variables** section and add the following:

| Key | Value | Description |
| :--- | :--- | :--- |
| `APP_NAME` | `Smart Classroom Attendance System` | Application Name |
| `APP_ENV` | `production` | Production environment |
| `APP_DEBUG` | `false` | Disable debug mode |
| `APP_URL` | `https://your-service-name.onrender.com` | Your live Render URL |
| `APP_KEY` | *(Generate via `php artisan key:generate --show` or leave blank for auto-fallback)* | Laravel encryption key |
| `APP_TIMEZONE` | `Asia/Manila` | Local timezone |
| `TRUSTED_PROXIES` | `*` | Ensures HTTPS asset/redirect generation |
| `SESSION_DRIVER` | `database` | Stores user sessions in database |
| `SESSION_SECURE_COOKIE` | `true` | Enforce HTTPS cookies |
| `LOG_CHANNEL` | `stderr` | Outputs logs to Render dashboard |
| `LOG_LEVEL` | `error` | Production log level |
| `QUEUE_CONNECTION` | `sync` | Sync queue handling |
| `CACHE_STORE` | `database` | Cache store |

#### Step 3: Configure Database Variables

##### If using Render PostgreSQL:
1. In Render Dashboard, click **New +** -> **PostgreSQL**.
2. Set Database Name: `attendance`, User: `attendance_user`. Plan: **Free**.
3. Once created, copy the **Internal Database URL** (format: `postgres://...`).
4. In your Web Service Environment Variables, add:
   * `DB_CONNECTION`: `pgsql`
   * `DATABASE_URL`: *(paste the Internal Database URL)*

##### If using External MySQL (TiDB / Aiven / Railway):
In your Web Service Environment Variables, add:
* `DB_CONNECTION`: `mysql`
* `DB_HOST`: *(your database host)*
* `DB_PORT`: `3306` (or provided port)
* `DB_DATABASE`: *(your database name)*
* `DB_USERNAME`: *(your database user)*
* `DB_PASSWORD`: *(your database password)*
* `MYSQL_ATTR_SSL_CA`: `/etc/ssl/certs/ca-certificates.crt` (if SSL required)

#### Step 4: Deploy
Click **Create Web Service**. Render will pull your repo, build the Docker image, run migrations, and bring the application live.

---

## 🔑 Post-Deployment: Create Your Admin Account

Once the deployment completes and status says **Live**:

1. Go to your Web Service in Render Dashboard.
2. Click the **Shell** tab on the left sidebar.
3. Run the following command to open Laravel Tinker:
   ```bash
   php artisan tinker
   ```
4. Create your Super Admin account:
   ```php
   \App\Models\User::create([
       'name' => 'System Administrator',
       'email' => 'admin@example.com',
       'password' => bcrypt('YourSecurePasswordHere123!'),
       'role' => 'admin',
       'email_verified_at' => now(),
   ]);
   ```
5. Type `exit` to close Tinker.
6. Open your live Render URL (`https://your-service.onrender.com/login`) and log in!

---

## 🛠️ Useful Commands via Render Shell

To run Artisan commands inside the live container, use the **Shell** tab:

```bash
# Run latest database migrations manually
php artisan migrate --force

# Seed initial database data
php artisan db:seed --force

# Clear and rebuild application caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check database connection status
php artisan db:show
```

---

## 🔍 Troubleshooting

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| **502 Bad Gateway** | Nginx or PHP-FPM failed to bind to port `$PORT` | Render automatically sets `$PORT`. The `docker/start.sh` script dynamically updates Nginx. Check Render logs to verify Nginx started. |
| **Health check timed out (`/up`)** | The app took longer than 5 minutes to boot or database was unreachable | Verify database credentials (`DATABASE_URL` or `DB_HOST`). Ensure migrations succeeded in the logs. |
| **CSS/JS styling missing or mixed content** | App is generating `http://` URLs instead of `https://` | Ensure `TRUSTED_PROXIES=*` is set in Render environment variables. |
| **Free Tier Spin-Down** | Render free web services spin down after 15 minutes of inactivity | The first request after sleep takes 30-50 seconds to boot. You can use an uptime monitor (e.g. UptimeRobot or Cron-job.org) to ping `/up` every 10 minutes to keep it warm. |

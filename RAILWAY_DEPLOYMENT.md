# 🚂 Step-by-Step Railway Deployment Guide

This guide walks you through deploying your **Smart Classroom Attendance System** to [Railway.app](https://railway.app).

---

## 📑 Prerequisites
1. A [GitHub](https://github.com) account (your repository is already at `https://github.com/noone123-qeqwe/school-attendance`).
2. A free or Hobby [Railway](https://railway.app) account (you can sign in directly with GitHub).

---

## 🚀 Step 1: Commit and Push the Deployment Files to GitHub

Run these commands in your project terminal:

```powershell
git add .
git commit -m "feat: configure production Dockerfile, Nginx, and Railway deployment"
git push origin main
```

---

## 🗄️ Step 2: Create a New Project on Railway

1. Open [railway.app](https://railway.app) and sign in with GitHub.
2. On your Railway dashboard, click **"New Project"**.
3. Select **"Provision MySQL"**.
   - Railway will immediately provision a managed MySQL database instance.
   - Wait ~10 seconds until the MySQL card displays as active.

---

## 🌐 Step 3: Deploy the Web Application

1. In the same project canvas, click **"+ Create"** (or **"New Service"**).
2. Select **"GitHub Repo"**.
3. Choose your repository: **`school-attendance`**.
4. Railway will detect the `Dockerfile` at the root of your project and begin the initial build.

---

## 🔑 Step 4: Configure Environment Variables

1. Click on your newly created web service card (e.g., `school-attendance`).
2. Navigate to the **"Variables"** tab.
3. Click the **"RAW Editor"** button (in the top right corner of the variables panel).
4. Copy and paste the configuration below (also available in `.env.railway`):

```env
APP_NAME="Smart Attendance"
APP_SUBTITLE="QR, GPS, and Biometric-Based Attendance Monitoring"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Manila
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_KEY=base64:Xk5qO8qM2Z7/Y9w1r+3P0t4V6x8bQ2dF5h7j9k1l3m4=
TRUSTED_PROXIES=*

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

LOG_CHANNEL=errorlog
LOG_LEVEL=error

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=smart_attendance_id
REVERB_APP_KEY=smart_attendance_key
REVERB_APP_SECRET=smart_attendance_secret
REVERB_HOST=${{RAILWAY_PUBLIC_DOMAIN}}
REVERB_PORT=443
REVERB_SCHEME=https

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Smart Classroom Attendance System"

SEMAPHORE_API_KEY=your_semaphore_api_key_here
SEMAPHORE_SENDER_NAME=OsmenaAtt
```

> **Note on Database Variables:**
> The syntax `${{MySQL.MYSQLHOST}}` automatically pulls the host, port, database, user, and password directly from the MySQL service you created in Step 2. You don't need to manually copy passwords!

5. Click **"Save Changes"**. Railway will automatically trigger a redeploy with these settings.

---

## 🔒 Step 5: Generate Public HTTPS Domain

WebAuthn (biometric passkeys) and PWA camera access strictly require HTTPS.

1. Click your web service card -> go to the **"Settings"** tab.
2. Scroll down to **"Networking"** -> click **"Generate Domain"**.
3. Railway will generate a public URL like:
   `https://school-attendance-production.up.railway.app`
4. The application will be reachable at this URL!

---

## 💾 Step 6: Add Persistent Storage Volume (Recommended)

To ensure student avatars, teacher materials, and uploaded photos persist across deploys:

1. Click on your web service card -> go to the **"Settings"** tab.
2. Scroll down to **"Volumes"** and click **"Add Volume"**.
3. Set **Mount Path** to:
   ```
   /var/www/html/storage/app/public
   ```
4. Click **"Save"**.

---

## 👤 Step 7: Initialize Database & Seed First Admin

Once the deployment completes:

1. Click your web service -> go to the **"Deployments"** tab -> click the active deployment.
2. In the top right, open the **"Exec"** tab (this is a live Linux terminal inside your container).
3. Run database seeders (if you have demo data):
   ```bash
   php artisan db:seed --force
   ```
4. Or create an admin account directly using Tinker:
   ```bash
   php artisan tinker
   ```
   Inside tinker:
   ```php
   \App\Models\User::create([
       'name' => 'System Administrator',
       'email' => 'admin@school.edu',
       'password' => bcrypt('YourSecurePassword123!'),
       'role' => 'admin',
       'status' => 'active',
       'must_change_password' => false
   ]);
   exit
   ```

---

## 📡 Step 8 (Optional): Real-Time WebSocket Server (Reverb)

If you want live instant attendance updates across multiple devices:

1. In the same Railway project, click **"+ Create"** -> **"GitHub Repo"** -> select `school-attendance` again.
2. Rename this service to `attendance-reverb`.
3. In **"Settings"** -> **"Deploy"** -> **"Custom Start Command"**, enter:
   ```bash
   php artisan reverb:start --host=0.0.0.0 --port=$PORT
   ```
4. In **"Variables"**, click **"Reference Variables"** to share all variables from the main web service.
5. In **"Networking"**, generate a domain and update `REVERB_HOST` on the main web service to this domain.

---

## ✅ Post-Deployment Verification Checklist

- [ ] Visit `https://your-domain.up.railway.app/login` and verify the page loads with a valid SSL padlock.
- [ ] Log in with your admin credentials.
- [ ] Test PWA installation prompt or service worker registration.
- [ ] Test biometric / WebAuthn passkey registration on profile.
- [ ] Test QR code attendance scanning.

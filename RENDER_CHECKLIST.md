# 🚀 Render Deployment Checklist

## Pre-Deployment Setup

### 1. Repository Preparation
- [ ] **Push to GitHub/GitLab**: Ensure all code is committed and pushed
- [ ] **Environment Files**: Verify `.env.production` exists
- [ ] **Dependencies**: Run `composer install` locally to test
- [ ] **Configuration**: Check all config files are present

### 2. Account Setup
- [ ] **Render Account**: Sign up at [render.com](https://render.com)
- [ ] **Connect Repository**: Link your GitHub/GitLab account
- [ ] **Payment Method**: Add billing information (required for databases)

## Deployment Steps

### Step 1: Deploy Database First
1. **Create Database Service**
   - [ ] Go to Render Dashboard → "New +"
   - [ ] Select "MySQL"
   - [ ] Name: `attendance-db`
   - [ ] Database: `attendance_production`  
   - [ ] User: `attendance_user`
   - [ ] Plan: **Starter** ($7/month)
   - [ ] Region: Choose closest to users
   - [ ] **Wait for deployment** (5-10 minutes)

2. **Note Database Credentials**
   - [ ] Copy Host, Port, Database Name
   - [ ] Copy Username and Password
   - [ ] Save these for web service setup

### Step 2: Deploy Web Application
1. **Create Web Service**
   - [ ] Click "New +" → "Web Service"
   - [ ] Connect your repository
   - [ ] Name: `attendance-web`
   - [ ] Region: Same as database
   - [ ] Branch: `main`
   - [ ] Runtime: **PHP**

2. **Configure Build Settings**
   - [ ] **Build Command**:
     ```bash
     composer install --optimize-autoloader --no-dev --no-interaction && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link
     ```
   - [ ] **Start Command**:
     ```bash
     php artisan migrate --force && php artisan optimize && php artisan serve --host=0.0.0.0 --port=$PORT
     ```

3. **Set Environment Variables**
   ```env
   APP_NAME=Smart Classroom Attendance System
   APP_SUBTITLE=QR, GPS, and Biometric-Based Attendance Monitoring
   APP_ENV=production
   APP_DEBUG=false
   APP_TIMEZONE=Asia/Manila
   LOG_CHANNEL=stderr
   LOG_LEVEL=error
   
   # Database (use values from Step 1)
   DB_CONNECTION=mysql
   DB_HOST=[your-db-host]
   DB_PORT=[your-db-port]
   DB_DATABASE=attendance_production
   DB_USERNAME=[your-db-username]
   DB_PASSWORD=[your-db-password]
   
   # Session & Security
   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   
   # Broadcasting
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=attendance-prod
   REVERB_APP_KEY=base64:your-reverb-key
   REVERB_APP_SECRET=your-reverb-secret
   ```

4. **Deploy Web Service**
   - [ ] Plan: **Starter** ($7/month)
   - [ ] Auto-Deploy: **Yes**
   - [ ] Click "Create Web Service"
   - [ ] **Wait for deployment** (10-15 minutes)

### Step 3: Test Basic Functionality
1. **Access Application**
   - [ ] Visit your Render app URL
   - [ ] Verify login page loads
   - [ ] Check for any errors in logs

2. **Create Admin User** (via Render Shell)
   - [ ] Go to web service → "Shell"
   - [ ] Run: `php artisan tinker`
   - [ ] Create admin:
     ```php
     \App\Models\User::create([
         'name' => 'System Administrator',
         'email' => 'admin@school.edu',
         'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
         'role' => 'admin'
     ]);
     exit
     ```

3. **Test Login**
   - [ ] Login with admin credentials
   - [ ] Verify dashboard loads correctly
   - [ ] Check navigation menu

### Step 4: Deploy WebSocket Service (Optional)
1. **Create Background Worker**
   - [ ] Click "New +" → "Background Worker"
   - [ ] Connect same repository
   - [ ] Name: `attendance-websocket`
   - [ ] Build Command: `composer install --optimize-autoloader --no-dev --no-interaction`
   - [ ] Start Command: `php artisan reverb:start --host=0.0.0.0 --port=8080`

2. **Environment Variables**
   - [ ] Copy database variables from web service
   - [ ] Add `PORT=8080`
   - [ ] Plan: **Starter** ($7/month)

### Step 5: Deploy Scheduled Tasks
1. **Create Cron Job**
   - [ ] Click "New +" → "Cron Job"
   - [ ] Connect same repository
   - [ ] Name: `attendance-cron`
   - [ ] Schedule: `0 * * * *` (hourly)
   - [ ] Build Command: `composer install --optimize-autoloader --no-dev --no-interaction`
   - [ ] Start Command: `php artisan schedule:run`

2. **Environment Variables**
   - [ ] Copy database variables from web service

## Post-Deployment Configuration

### 1. Email Configuration
- [ ] **Gmail Setup**: Enable 2FA and create App Password
- [ ] **Add Email Variables**:
  ```env
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_USERNAME=your_email@gmail.com
  MAIL_PASSWORD=your_app_password
  MAIL_FROM_ADDRESS=your_email@gmail.com
  MAIL_FROM_NAME=Smart Classroom Attendance System
  ```

### 2. SMS Configuration (Optional)
- [ ] **Semaphore API**: Sign up and get API key
- [ ] **Add SMS Variables**:
  ```env
  SEMAPHORE_API_KEY=your_semaphore_key
  SEMAPHORE_SENDER_NAME=AttendSys
  ```

### 3. Create Teacher Account
- [ ] Login as admin
- [ ] Go to User Management (if available) or use Shell:
  ```php
  \App\Models\User::create([
      'name' => 'Ma. Sandy Mae Santa Rosa',
      'email' => 'sandy.rosa@school.edu',
      'password' => \Illuminate\Support\Facades\Hash::make('password123'),
      'role' => 'teacher',
      'employee_id' => 'T-2024-003',
      'department' => 'Computer Science',
      'position' => 'Instructor',
      'specialization' => 'Software Development'
  ]);
  ```

## Testing Checklist

### Basic Functionality
- [ ] **Admin Login**: Test admin account access
- [ ] **Teacher Login**: Test teacher account access
- [ ] **Dashboard Loading**: Verify all dashboards work
- [ ] **Student Management**: Test CRUD operations
- [ ] **Subject Management**: Test subject creation
- [ ] **Navigation**: Test all menu items

### Advanced Features
- [ ] **QR Generation**: Test QR code creation (teachers)
- [ ] **File Uploads**: Test profile image uploads
- [ ] **PDF Generation**: Test report exports
- [ ] **Real-time Features**: Test live updates (if WebSocket deployed)
- [ ] **Email Notifications**: Test email sending
- [ ] **Mobile Responsiveness**: Test on different devices

### Performance Testing
- [ ] **Page Load Speed**: Check response times
- [ ] **Database Queries**: Monitor for N+1 issues
- [ ] **Memory Usage**: Check resource consumption
- [ ] **Error Handling**: Test invalid inputs

## Security Verification

### Application Security
- [ ] **HTTPS Enabled**: Verify SSL certificate active
- [ ] **Environment Variables**: Confirm no secrets in code
- [ ] **Session Security**: Test secure cookies
- [ ] **CSRF Protection**: Verify forms are protected
- [ ] **SQL Injection**: Test input sanitization

### Database Security
- [ ] **Connection Encryption**: Verify secure DB connection
- [ ] **User Permissions**: Check database user has minimum required permissions
- [ ] **Backup Strategy**: Ensure automatic backups are enabled

## Custom Domain Setup (Optional)

### 1. Domain Configuration
- [ ] **Purchase Domain**: Get your school domain
- [ ] **DNS Access**: Ensure you can modify DNS records
- [ ] **SSL Certificate**: Will be auto-generated by Render

### 2. Render Configuration
- [ ] **Custom Domain**: Add in web service settings
- [ ] **DNS Records**: Add CNAME record:
  ```
  Type: CNAME
  Name: attendance (or www)
  Value: your-service-name.onrender.com
  ```
- [ ] **Verification**: Wait for DNS propagation (up to 48 hours)

## Monitoring Setup

### Render Dashboard
- [ ] **Service Health**: Check all services are running
- [ ] **Log Monitoring**: Set up log alerts
- [ ] **Performance Metrics**: Review CPU/memory usage
- [ ] **Deploy History**: Verify successful deployments

### Application Monitoring
- [ ] **Error Tracking**: Monitor Laravel logs
- [ ] **Performance**: Check slow query logs
- [ ] **Uptime**: Set up external monitoring (optional)

## Backup Strategy

### Database Backups
- [ ] **Automatic Backups**: Verify Render backup schedule
- [ ] **Manual Backup**: Test manual backup creation
- [ ] **Backup Retention**: Configure retention policy

### File Backups
- [ ] **Storage Files**: Backup uploaded files
- [ ] **Configuration**: Backup environment settings
- [ ] **Code Repository**: Ensure Git repository is up to date

## Cost Optimization

### Current Setup Cost
- **Web Service**: $7/month (Starter)
- **Database**: $7/month (Starter MySQL)
- **WebSocket**: $7/month (optional)
- **Cron Jobs**: Free
- **Total**: $14-21/month

### Scaling Considerations
- [ ] **Traffic Monitoring**: Track usage patterns
- [ ] **Plan Upgrades**: Monitor when to upgrade plans
- [ ] **Resource Optimization**: Optimize queries and caching

## Support & Maintenance

### Documentation
- [ ] **Deployment Notes**: Document any custom configurations
- [ ] **User Accounts**: Document admin/teacher credentials
- [ ] **Domain Settings**: Document DNS configurations
- [ ] **API Keys**: Securely store all API keys

### Maintenance Schedule
- [ ] **Weekly**: Check logs and performance
- [ ] **Monthly**: Review and update dependencies
- [ ] **Quarterly**: Security audit and updates
- [ ] **Annually**: Plan capacity and cost review

## 🎉 Deployment Complete!

### Success Criteria
- [ ] ✅ Application accessible via HTTPS
- [ ] ✅ Admin panel functional
- [ ] ✅ Teacher panel functional  
- [ ] ✅ Database operations working
- [ ] ✅ File uploads working
- [ ] ✅ Email notifications working
- [ ] ✅ Real-time features working (if enabled)
- [ ] ✅ Mobile responsive design
- [ ] ✅ SSL certificate active
- [ ] ✅ Monitoring set up

### Next Steps
1. **User Training**: Provide training for administrators and teachers
2. **Data Migration**: Import existing student/subject data (if applicable)
3. **Integration**: Set up any required integrations
4. **Scaling**: Monitor usage and plan for growth

Your **Smart Classroom Attendance System** is now live and ready for production use! 🚀

---

**Need Help?**
- Render Documentation: [render.com/docs](https://render.com/docs)
- Laravel Deployment: [laravel.com/docs/deployment](https://laravel.com/docs/deployment)
- Support: Check logs in Render dashboard or contact support
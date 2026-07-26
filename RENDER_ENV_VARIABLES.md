# 🔧 Render Environment Variables Configuration

Copy and paste these environment variables into your Render service dashboard.

## 🌐 Web Service Environment Variables

### Application Configuration
```
APP_NAME=Smart Classroom Attendance System
APP_SUBTITLE=QR, GPS, and Biometric-Based Attendance Monitoring
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Manila
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

### Logging Configuration
```
LOG_CHANNEL=stderr
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null
```

### Security & Session
```
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
BCRYPT_ROUNDS=12
```

### Cache & Queue
```
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
```

### Database Configuration
**Note: These will be auto-populated by Render when you connect your database**
```
DB_CONNECTION=mysql
DB_HOST=[AUTO-FILLED-BY-RENDER]
DB_PORT=[AUTO-FILLED-BY-RENDER]
DB_DATABASE=[AUTO-FILLED-BY-RENDER]
DB_USERNAME=[AUTO-FILLED-BY-RENDER]
DB_PASSWORD=[AUTO-FILLED-BY-RENDER]
```

### WebSocket Configuration (Reverb)
```
REVERB_APP_ID=attendance-prod
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
```

**🔐 Generate Secure Keys for these:**
```
REVERB_APP_KEY=[GENERATE-32-CHAR-KEY]
REVERB_APP_SECRET=[GENERATE-64-CHAR-SECRET]
```

**Key Generation Commands:**
```bash
# Generate Reverb App Key (32 characters)
openssl rand -base64 32

# Generate Reverb App Secret (64 characters)  
openssl rand -base64 64
```

## 📧 Mail Configuration (Required for Notifications)

### SMTP Configuration
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME=Smart Classroom Attendance System
```

**🔐 Add Your Email Credentials:**
```
MAIL_USERNAME=[YOUR-EMAIL@gmail.com]
MAIL_PASSWORD=[YOUR-APP-PASSWORD]
MAIL_FROM_ADDRESS=[YOUR-EMAIL@gmail.com]
```

**📝 Gmail Setup Instructions:**
1. Enable 2-Factor Authentication on your Gmail account
2. Generate an "App Password" in your Google Account settings
3. Use the App Password (not your regular password) for `MAIL_PASSWORD`

## 📱 SMS Configuration (Optional)

### Semaphore SMS Service
```
SEMAPHORE_SENDER_NAME=AttendSys
```

**🔐 Add Your SMS Credentials:**
```
SEMAPHORE_API_KEY=[YOUR-SEMAPHORE-API-KEY]
```

## 🔄 Auto-Generated Variables

**⚠️ Don't set these manually - Render will generate them automatically:**
- `APP_KEY` - Laravel application encryption key
- `APP_URL` - Your app's public URL
- `PORT` - Web server port (managed by Render)

## 🎯 Quick Copy Templates

### Essential Variables (Copy this block)
```
APP_NAME=Smart Classroom Attendance System
APP_SUBTITLE=QR, GPS, and Biometric-Based Attendance Monitoring
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Manila
LOG_CHANNEL=stderr
LOG_LEVEL=error
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=attendance-prod
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME=Smart Classroom Attendance System
```

### Security Keys (Generate and add these)
```
REVERB_APP_KEY=[PASTE-GENERATED-KEY-HERE]
REVERB_APP_SECRET=[PASTE-GENERATED-SECRET-HERE]
MAIL_USERNAME=[YOUR-EMAIL@gmail.com]
MAIL_PASSWORD=[YOUR-GMAIL-APP-PASSWORD]
MAIL_FROM_ADDRESS=[YOUR-EMAIL@gmail.com]
```

## 🛠️ WebSocket Service Environment Variables

For your WebSocket background service, copy these essential variables:

```
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stderr
REVERB_APP_ID=attendance-prod
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_KEY=[SAME-AS-WEB-SERVICE]
REVERB_APP_SECRET=[SAME-AS-WEB-SERVICE]
```

**Note:** Database variables will be auto-populated when you connect the same database.

## 🕒 Cron Service Environment Variables

For your scheduled tasks service:

```
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stderr
```

**Note:** Only database variables are needed for cron jobs - they'll be auto-populated.

## 🔍 Variable Validation

After setting up, verify these are configured:
- ✅ All APP_* variables set
- ✅ Database connection variables populated by Render
- ✅ Mail credentials configured
- ✅ WebSocket keys generated and set
- ✅ LOG_CHANNEL set to "stderr" for Render logging

## 📋 Deployment Checklist

1. **Copy essential variables** to Render web service
2. **Generate security keys** using OpenSSL commands
3. **Configure email credentials** with Gmail app password
4. **Connect database** service (auto-populates DB_* variables)
5. **Deploy and test** the application
6. **Set up WebSocket service** with same keys
7. **Configure cron service** for automated tasks

---

## 🎉 Ready to Deploy!

Once all environment variables are configured in Render:
1. **Deploy** your web service
2. **Check logs** for any configuration errors
3. **Test login** and core functionality  
4. **Verify real-time features** work (if WebSocket deployed)
5. **Set up monitoring** and alerts

Your Smart Classroom Attendance System will be live and ready for your educational institution! 🚀
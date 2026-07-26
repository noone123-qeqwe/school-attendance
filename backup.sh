#!/bin/bash

# Smart Classroom Attendance System - Backup Script
# Run daily via cron: 0 2 * * * /path/to/attendance/backup.sh

# Configuration
APP_PATH="/path/to/attendance"
BACKUP_PATH="/path/to/backups/attendance"
DB_NAME="attendance_production"
DB_USER="attendance_user"
DB_PASS="your_db_password"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p "$BACKUP_PATH"

echo "🔄 Starting backup process..."

# 1. Database backup
echo "📊 Backing up database..."
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_PATH/db_backup_$DATE.sql"

if [ $? -eq 0 ]; then
    echo "✅ Database backup completed"
    gzip "$BACKUP_PATH/db_backup_$DATE.sql"
else
    echo "❌ Database backup failed"
    exit 1
fi

# 2. Application files backup
echo "📁 Backing up application files..."
cd "$APP_PATH"
tar -czf "$BACKUP_PATH/app_backup_$DATE.tar.gz" \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    .

if [ $? -eq 0 ]; then
    echo "✅ Application backup completed"
else
    echo "❌ Application backup failed"
    exit 1
fi

# 3. Storage files backup (uploads, etc.)
echo "💾 Backing up storage files..."
cd "$APP_PATH"
tar -czf "$BACKUP_PATH/storage_backup_$DATE.tar.gz" storage/app/public

if [ $? -eq 0 ]; then
    echo "✅ Storage backup completed"
else
    echo "❌ Storage backup failed"
fi

# 4. Clean old backups
echo "🧹 Cleaning old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_PATH" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_PATH" -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "✅ Backup process completed!"
echo "📍 Backups stored in: $BACKUP_PATH"
ls -lh "$BACKUP_PATH"/*$DATE*
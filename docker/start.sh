#!/bin/sh
set -e

echo "🚀 Starting Smart Classroom Attendance System..."

APP_PROCESS_ROLE="${APP_PROCESS_ROLE:-all}"
case "$APP_PROCESS_ROLE" in
    all|web|worker|scheduler|migrate|cron|reverb) ;;
    *) echo "APP_PROCESS_ROLE must be all, web, worker, scheduler, migrate, cron, or reverb." >&2; exit 1 ;;
esac

case "$APP_PROCESS_ROLE" in
    all) RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}" ;;
    migrate) RUN_MIGRATIONS=true ;;
    *) RUN_MIGRATIONS="${RUN_MIGRATIONS:-false}" ;;
esac

PHP_FPM_PM="${PHP_FPM_PM:-dynamic}"
PHP_FPM_MAX_CHILDREN="${PHP_FPM_MAX_CHILDREN:-4}"
PHP_FPM_START_SERVERS="${PHP_FPM_START_SERVERS:-2}"
PHP_FPM_MIN_SPARE_SERVERS="${PHP_FPM_MIN_SPARE_SERVERS:-1}"
PHP_FPM_MAX_SPARE_SERVERS="${PHP_FPM_MAX_SPARE_SERVERS:-3}"
PHP_FPM_MAX_REQUESTS="${PHP_FPM_MAX_REQUESTS:-500}"

case "$PHP_FPM_PM" in
    dynamic|ondemand|static) ;;
    *) echo "PHP_FPM_PM must be dynamic, ondemand, or static." >&2; exit 1 ;;
esac

for PHP_FPM_VALUE in "$PHP_FPM_MAX_CHILDREN" "$PHP_FPM_START_SERVERS" "$PHP_FPM_MIN_SPARE_SERVERS" "$PHP_FPM_MAX_SPARE_SERVERS" "$PHP_FPM_MAX_REQUESTS"; do
    case "$PHP_FPM_VALUE" in
        ''|*[!0-9]*) echo "PHP-FPM worker settings must be positive integers." >&2; exit 1 ;;
    esac
done

if [ "$PHP_FPM_MAX_CHILDREN" -lt 1 ] || [ "$PHP_FPM_START_SERVERS" -lt 1 ] ||
   [ "$PHP_FPM_MIN_SPARE_SERVERS" -lt 1 ] || [ "$PHP_FPM_MAX_SPARE_SERVERS" -lt 1 ] ||
   [ "$PHP_FPM_MAX_REQUESTS" -lt 1 ]; then
    echo "PHP-FPM worker settings must be greater than zero." >&2
    exit 1
fi

if [ "$PHP_FPM_PM" = "dynamic" ]; then
    if [ "$PHP_FPM_START_SERVERS" -gt "$PHP_FPM_MAX_CHILDREN" ] ||
       [ "$PHP_FPM_MIN_SPARE_SERVERS" -gt "$PHP_FPM_MAX_SPARE_SERVERS" ] ||
       [ "$PHP_FPM_MAX_SPARE_SERVERS" -gt "$PHP_FPM_MAX_CHILDREN" ]; then
        echo "PHP-FPM dynamic worker settings exceed the configured child limit." >&2
        exit 1
    fi
fi

FPM_CONFIG=/usr/local/etc/php-fpm.d/zz-docker.conf
sed -i "s/^pm = .*/pm = ${PHP_FPM_PM}/" "$FPM_CONFIG"
sed -i "s/^pm.max_children = .*/pm.max_children = ${PHP_FPM_MAX_CHILDREN}/" "$FPM_CONFIG"
sed -i "s/^pm.start_servers = .*/pm.start_servers = ${PHP_FPM_START_SERVERS}/" "$FPM_CONFIG"
sed -i "s/^pm.min_spare_servers = .*/pm.min_spare_servers = ${PHP_FPM_MIN_SPARE_SERVERS}/" "$FPM_CONFIG"
sed -i "s/^pm.max_spare_servers = .*/pm.max_spare_servers = ${PHP_FPM_MAX_SPARE_SERVERS}/" "$FPM_CONFIG"
sed -i "s/^pm.max_requests = .*/pm.max_requests = ${PHP_FPM_MAX_REQUESTS}/" "$FPM_CONFIG"

# Default port to 80 if PORT is not provided by Render/Railway
DEFAULT_PORT=80
if [ "$APP_PROCESS_ROLE" = "reverb" ]; then
    DEFAULT_PORT=8080
fi
PORT="${PORT:-$DEFAULT_PORT}"
if [ "$APP_PROCESS_ROLE" = "reverb" ]; then
    REVERB_SERVER_HOST="${REVERB_SERVER_HOST:-0.0.0.0}"
    REVERB_SERVER_PORT="${REVERB_SERVER_PORT:-$PORT}"
    export REVERB_SERVER_HOST REVERB_SERVER_PORT
fi
echo "🌐 Configuring Nginx to listen on port ${PORT}..."
sed -i "s/listen 80;/listen ${PORT};/g" /etc/nginx/http.d/default.conf
sed -i "s/listen \[::\]:80;/listen [::]:${PORT};/g" /etc/nginx/http.d/default.conf
sed -i "s/\${PORT}/${PORT}/g" /etc/nginx/http.d/default.conf 2>/dev/null || true

# Setup required storage directories
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

# Ensure write permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
touch /var/www/html/version.json 2>/dev/null || true
chown www-data:www-data /var/www/html/version.json /var/www/html/package.json /var/www/html/public/manifest.json /var/www/html/public/sw.js 2>/dev/null || true
chmod 664 /var/www/html/version.json /var/www/html/package.json /var/www/html/public/manifest.json /var/www/html/public/sw.js 2>/dev/null || true

# Synchronize live deployed git commit into version.json if provided by cloud environment
DEPLOY_COMMIT="${RENDER_GIT_COMMIT:-${RAILWAY_GIT_COMMIT_SHA:-${VERCEL_GIT_COMMIT_SHA:-${GITHUB_SHA:-${APP_COMMIT:-}}}}}"
if [ -n "$DEPLOY_COMMIT" ] && [ -f /var/www/html/version.json ]; then
    SHORT_COMMIT=$(echo "$DEPLOY_COMMIT" | cut -c1-7)
    echo "📌 Synchronizing deployed commit ($SHORT_COMMIT) into version.json..."
    sed -i "s/\"commit\": *\"[^\"]*\"/\"commit\": \"${SHORT_COMMIT}\"/g" /var/www/html/version.json || true
fi

# A stable APP_KEY is required so sessions and encrypted data survive restarts.
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY must be configured in the deployment environment." >&2
    exit 1
fi

# Ensure outbound SMTP defaults for production OTP delivery if not explicitly set
export MAIL_MAILER="${MAIL_MAILER:-smtp}"
export MAIL_HOST="${MAIL_HOST:-smtp.gmail.com}"
export MAIL_PORT="${MAIL_PORT:-587}"
export MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-tls}"
export MAIL_FROM_NAME="${MAIL_FROM_NAME:-Smart Classroom Attendance System}"
export BREVO_API_KEY="${BREVO_API_KEY:-}"

# Run storage symlink
echo "🔗 Ensuring storage symlink..."
php artisan storage:link || true

# Ensure SQLite file exists if using sqlite
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown -R www-data:www-data "$(dirname "$DB_FILE")"
    chmod -R 775 "$(dirname "$DB_FILE")"
fi

# Run database migrations once in the combined role, or through an explicit
# migrate release process before starting horizontally scaled web replicas.
if [ "$RUN_MIGRATIONS" = "true" ] && { [ -n "$DB_HOST" ] || [ -n "$DATABASE_URL" ] || [ -n "$DB_URL" ] || [ -n "$MYSQLHOST" ] || [ -n "$MYSQL_URL" ] || [ "$DB_CONNECTION" = "sqlite" ]; }; then
    echo "🗄️ Database configured, running migrations..."
    RETRY_COUNT=0
    MAX_RETRIES=6
    until php artisan migrate --force; do
        RETRY_COUNT=$((RETRY_COUNT+1))
        if [ "$RETRY_COUNT" -ge "$MAX_RETRIES" ]; then
            echo "Database migrations failed after $MAX_RETRIES attempts." >&2
            exit 1
        fi
        echo "Database not ready yet... retry $RETRY_COUNT/$MAX_RETRIES in 5 seconds..."
        sleep 5
    done
elif [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "A database is required when RUN_MIGRATIONS=true." >&2
    exit 1
else
    echo "⏭️ Skipping startup migrations for role ${APP_PROCESS_ROLE}."
fi

if [ "$APP_PROCESS_ROLE" = "migrate" ]; then
    echo "✅ Release migrations completed."
    exit 0
fi

# Optimize the framework once. `optimize` already caches config, routes, views,
# and events, so separate event/view cache commands only repeated the work.
echo "⚡ Optimizing Laravel for production..."
php artisan config:clear || true
php artisan view:clear || true
php artisan optimize || true

case "$APP_PROCESS_ROLE" in
    worker)
        if [ "${QUEUE_CONNECTION:-sync}" = "sync" ]; then
            echo "APP_PROCESS_ROLE=worker requires QUEUE_CONNECTION to be database, redis, or another async queue." >&2
            exit 1
        fi
        QUEUE_TIMEOUT="${QUEUE_TIMEOUT:-90}"
        case "$QUEUE_TIMEOUT" in
            ''|*[!0-9]*) echo "QUEUE_TIMEOUT must be a positive integer." >&2; exit 1 ;;
        esac
        if [ "$QUEUE_TIMEOUT" -lt 1 ]; then
            echo "QUEUE_TIMEOUT must be a positive integer." >&2
            exit 1
        fi

        QUEUE_RETRY_AFTER=""
        case "${QUEUE_CONNECTION:-sync}" in
            database) QUEUE_RETRY_AFTER="${DB_QUEUE_RETRY_AFTER:-120}" ;;
            redis) QUEUE_RETRY_AFTER="${REDIS_QUEUE_RETRY_AFTER:-120}" ;;
            beanstalkd) QUEUE_RETRY_AFTER="${BEANSTALKD_QUEUE_RETRY_AFTER:-120}" ;;
        esac
        if [ -n "$QUEUE_RETRY_AFTER" ]; then
            case "$QUEUE_RETRY_AFTER" in
                ''|*[!0-9]*) echo "Queue retry_after must be a positive integer." >&2; exit 1 ;;
            esac
            if [ "$QUEUE_RETRY_AFTER" -le "$((QUEUE_TIMEOUT + 5))" ]; then
                echo "Queue retry_after must exceed QUEUE_TIMEOUT by more than 5 seconds." >&2
                exit 1
            fi
        fi
        echo "📨 Starting dedicated Laravel queue worker..."
        exec php artisan queue:work --sleep="${QUEUE_SLEEP:-3}" --tries="${QUEUE_TRIES:-3}" --backoff="${QUEUE_BACKOFF:-5}" --timeout="$QUEUE_TIMEOUT" --max-jobs="${QUEUE_MAX_JOBS:-1000}" --max-time="${QUEUE_MAX_TIME:-3600}" --memory="${QUEUE_MEMORY:-192}"
        ;;
    scheduler)
        echo "⏱️ Starting dedicated Laravel scheduler..."
        exec php artisan schedule:work
        ;;
    cron)
        echo "⏱️ Running scheduled Laravel tasks..."
        exec php artisan schedule:run
        ;;
    reverb)
        echo "🔌 Starting Laravel Reverb..."
        exec php artisan reverb:start --host="$REVERB_SERVER_HOST" --port="$REVERB_SERVER_PORT"
        ;;
esac

# Start PHP-FPM in daemon mode
echo "🐘 Starting PHP-FPM..."
mkdir -p /var/run
rm -f /var/run/php-fpm.sock
php-fpm -D

# Wait briefly for PHP-FPM socket to initialize
RETRY_FPM=0
while [ ! -e /var/run/php-fpm.sock ] && [ $RETRY_FPM -lt 10 ]; do
    sleep 0.5
    RETRY_FPM=$((RETRY_FPM+1))
done
chmod 666 /var/run/php-fpm.sock 2>/dev/null || true

# The legacy all-in-one mode remains available for a single container. Scaled
# deployments should use separate web, worker, and scheduler process roles.
if [ "$APP_PROCESS_ROLE" = "all" ]; then
    echo "⏱️ Starting Laravel Scheduler Worker..."
    php artisan schedule:work >> /var/www/html/storage/logs/scheduler.log 2>&1 &

    if [ "${QUEUE_CONNECTION:-sync}" != "sync" ]; then
        echo "📨 Starting Laravel Queue Worker..."
        php artisan queue:work --sleep="${QUEUE_SLEEP:-3}" --tries="${QUEUE_TRIES:-3}" --backoff="${QUEUE_BACKOFF:-5}" --timeout="${QUEUE_TIMEOUT:-90}" --max-jobs="${QUEUE_MAX_JOBS:-1000}" --max-time="${QUEUE_MAX_TIME:-3600}" --memory="${QUEUE_MEMORY:-192}" >> /var/www/html/storage/logs/queue.log 2>&1 &
    else
        echo "⏭️ Queue worker disabled because QUEUE_CONNECTION=sync."
    fi

    # Keep the existing single-instance development/free-tier behavior.
    echo "💓 Starting keep-alive pinger (every 10 minutes)..."
    (
        APP_PING_URL="http://localhost:${PORT:-80}/up"
        while true; do
            sleep 600
            curl -sf --max-time 10 "$APP_PING_URL" > /dev/null 2>&1 || true
        done
    ) &
fi

# Start Nginx in the foreground
echo "🚀 Starting Nginx..."
exec nginx -g "daemon off;"

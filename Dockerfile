FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libpq \
    postgresql-dev \
    sqlite-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install and configure PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql pgsql pdo_sqlite exif pcntl bcmath gd zip mbstring intl opcache

# Copy latest Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files for caching layer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy package files and install frontend dependencies
COPY package.json package-lock.json* ./
RUN npm install

# Copy application source code
COPY . .

# Complete composer optimization and build frontend assets
RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && npm cache clean --force \
    && rm -rf node_modules

# Configure Nginx, PHP-FPM, PHP production settings, and Entrypoint
COPY nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Ensure storage directories and permissions
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/app/public \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000 80

ENTRYPOINT ["/usr/local/bin/start.sh"]
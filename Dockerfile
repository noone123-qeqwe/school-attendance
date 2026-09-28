FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libpq \
    postgresql-dev \
    sqlite-dev \
    python3 \
    py3-opencv \
    zip \
    unzip \
    nodejs \
    npm

# Pin the two OpenCV Zoo model files to reviewed upstream bytes.
RUN mkdir -p /opt/face-models \
    && curl -fsSL --retry 3 -o /opt/face-models/yunet.onnx https://media.githubusercontent.com/media/opencv/opencv_zoo/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx \
    && curl -fsSL --retry 3 -o /opt/face-models/sface.onnx https://media.githubusercontent.com/media/opencv/opencv_zoo/main/models/face_recognition_sface/face_recognition_sface_2021dec.onnx \
    && echo '8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4  /opt/face-models/yunet.onnx' | sha256sum -c - \
    && echo '0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79  /opt/face-models/sface.onnx' | sha256sum -c - \
    && curl -fsSL --retry 3 -o /opt/face-models/YUNET-LICENSE https://raw.githubusercontent.com/opencv/opencv_zoo/main/models/face_detection_yunet/LICENSE \
    && curl -fsSL --retry 3 -o /opt/face-models/SFACE-LICENSE https://raw.githubusercontent.com/opencv/opencv_zoo/main/models/face_recognition_sface/LICENSE

# Install and configure PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
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

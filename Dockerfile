FROM php:8.3-fpm-alpine

ARG APP_URL
ENV APP_URL=${APP_URL}

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libxml2-dev \
    oniguruma-dev \
    sqlite-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    shadow # Add shadow package for usermod

# Install Node.js properly from Alpine package
RUN apk add --no-cache nodejs npm

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql bcmath mbstring xml gd zip && \
    docker-php-ext-enable iconv && \
    # Verify extensions are installed
    php -m | grep -E 'gd|iconv|zip' && \
    # Create a marker file to confirm extensions are properly installed
    touch /usr/local/etc/php/conf.d/extensions-installed.ini && \
    echo "; Extensions verified at build time" > /usr/local/etc/php/conf.d/extensions-installed.ini

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    sed -i 's/display_errors = Off/display_errors = On/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/log_errors = Off/log_errors = On/' "$PHP_INI_DIR/php.ini" && \
    echo "error_log = /dev/stderr" >> "$PHP_INI_DIR/php.ini"

# Configure PHP-FPM to run as the same user as nginx
RUN sed -i 's/user = www-data/user = nginx/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = nginx/' /usr/local/etc/php-fpm.d/www.conf && \
    echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "php_admin_value[error_log] = /dev/stderr" >> /usr/local/etc/php-fpm.d/www.conf
# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Create log directories with proper permissions
RUN mkdir -p /var/log/nginx && \
    chown -R nginx:nginx /var/log/nginx

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies and build
RUN composer install --optimize-autoloader --no-dev && \
    npm install && \
    npm run prod && \
    chown -R nginx:nginx /var/www/html

# Make sure storage and cache directories are writable
RUN chmod -R 775 storage bootstrap/cache && \
    chown -R nginx:nginx storage bootstrap/cache

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

COPY docker/init.sh /init.sh
RUN chmod +x /init.sh

COPY docker/ensure-phpspreadsheet.sh /ensure-phpspreadsheet.sh
RUN chmod +x /ensure-phpspreadsheet.sh

EXPOSE 80

# Switch to non-root user for running the container
USER nginx

CMD ["/bin/sh", "-c", "/ensure-phpspreadsheet.sh && /init.sh && /start.sh"]

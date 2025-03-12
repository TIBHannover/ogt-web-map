FROM php:8.3-fpm-alpine

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
    sqlite-dev

# Install Node.js properly from Alpine package
RUN apk add --no-cache nodejs npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql bcmath mbstring xml

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    sed -i 's/display_errors = Off/display_errors = On/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/log_errors = Off/log_errors = On/' "$PHP_INI_DIR/php.ini" && \
    echo "error_log = /dev/stderr" >> "$PHP_INI_DIR/php.ini"

# Configure PHP-FPM
RUN echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "php_admin_value[error_log] = /dev/stderr" >> /usr/local/etc/php-fpm.d/www.conf

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Create log directories
RUN mkdir -p /var/log/nginx && \
    chmod 777 /var/log/nginx

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies and build
RUN composer install --optimize-autoloader --no-dev && \
    npm install && \
    npm run prod && \
    chmod -R 775 storage bootstrap/cache

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]

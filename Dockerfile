# Use PHP 8.3 with FPM
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
# RUN docker-php-ext-install \
#     bcmath \
#     mbstring \
#     pdo_mysql \
#     xml \
#     ctype \
#     fileinfo \
#     json \
#     tokenizer \
#     opcache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev

# Install NPM dependencies and build assets
RUN npm install && \
    npm run prod

# Create storage directories
RUN mkdir -p storage/logs \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    public/images/static

# Set permissions
RUN chown -R www-data:www-data \
    storage \
    public/images/static \
    bootstrap/cache

# Configure PHP
COPY php.ini-production /usr/local/etc/php/php.ini

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]

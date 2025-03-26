#!/bin/sh
# Make sure storage directory has correct permissions
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;

# Generate key if not set
php artisan key:generate --force

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Start services - we need to use supervisord or another method since we're no longer root
php-fpm -D
nginx -g 'daemon off;'
# We can't start nginx as non-root, so we need to modify our approach
exec nginx -g 'daemon off;'

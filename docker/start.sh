#!/bin/sh
# Generate key if not set
php artisan key:generate --force
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
# Start services
php-fpm -D
nginx -g 'daemon off;'

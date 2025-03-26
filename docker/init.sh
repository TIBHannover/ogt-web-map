#!/bin/sh
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
chown -R nginx:nginx /var/www/html/storage
chmod -R 775 /var/www/html/storage

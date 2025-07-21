#!/bin/bash

# This script should be run inside the Docker container to set up the SQLite database

# Create storage directories if they don't exist
mkdir -p /var/www/html/storage/app
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views

# Create SQLite database file if it doesn't exist
TOUCH_DB=0
if [ ! -f /var/www/html/storage/app/database.sqlite ]; then
    echo "Creating SQLite database file..."
    touch /var/www/html/storage/app/database.sqlite
    TOUCH_DB=1
fi

# Set proper permissions
chown -R nginx:nginx /var/www/html/storage
chmod -R 775 /var/www/html/storage
chmod 664 /var/www/html/storage/app/database.sqlite

# Run migrations if database was just created
if [ "$TOUCH_DB" = "1" ]; then
    echo "Running migrations..."
    cd /var/www/html
    php artisan migrate --force
fi

echo "Database setup complete!"
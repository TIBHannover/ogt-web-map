#!/bin/sh
# Make sure storage directory has correct permissions
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;

# Check and create database file if needed
DB_PATH="/var/www/html/storage/app/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database file"
    mkdir -p /var/www/html/storage/app
    touch "$DB_PATH"
    chmod 664 "$DB_PATH"

    # Initialize database with SQL file if it exists
    if [ -f /var/www/html/database/init.sql ]; then
        echo "Initializing database with schema and initial data..."
        sqlite3 "$DB_PATH" < /var/www/html/database/init.sql
        echo "Database initialized with schema and default admin user."
    else
        echo "Database file is empty, running migrations"
        php artisan migrate --force
    fi
fi

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

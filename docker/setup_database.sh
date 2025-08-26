#!/bin/bash

# This script should be run inside the Docker container to set up the SQLite database

# Create storage directories if they don't exist
mkdir -p /var/www/html/storage/app
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views

# Set up the database file
DB_PATH="/var/www/html/storage/app/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database file..."
    touch "$DB_PATH"

    # Initialize database with SQL file if it exists
    if [ -f /var/www/html/database/init.sql ]; then
        echo "Initializing database with schema and initial data..."
        sqlite3 "$DB_PATH" < /var/www/html/database/init.sql
        echo "Database initialized with schema and default admin user."
    fi
fi

# Set proper permissions
chown -R nginx:nginx /var/www/html/storage
chmod -R 775 /var/www/html/storage
chmod 664 "$DB_PATH"

echo "Database setup complete!"

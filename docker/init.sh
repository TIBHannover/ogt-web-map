#!/bin/sh

# Run the database setup script
if [ -f /var/www/html/docker/setup_database.sh ]; then
    echo "Running database setup script..."
    sh /var/www/html/docker/setup_database.sh
fi

# Continue with original init tasks
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app

# Set proper permissions
chown -R nginx:nginx /var/www/html/storage
chmod -R 775 /var/www/html/storage

echo "Storage directory and database file prepared."

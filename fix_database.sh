#!/bin/bash

# This script fixes database issues in the OGT API project
# It ensures the database exists, has the correct schema, and includes all required columns

# Set variables
DB_PATH="/var/www/html/storage/app/database.sqlite"
BACKUP_PATH="/var/www/html/storage/app/database.sqlite.backup.$(date +%Y%m%d_%H%M%S)"
INIT_SQL="/var/www/html/database/init.sql"

# Make sure we're in the right directory
cd /var/www/html

# Display header
echo "====================================================="
echo "OGT-API Database Fix Script"
echo "====================================================="
echo "This script will ensure your database has all required columns."
echo

# Create backup of existing database
if [ -f "$DB_PATH" ]; then
    echo "Creating backup of existing database to $BACKUP_PATH"
    cp "$DB_PATH" "$BACKUP_PATH"
    echo "Backup created successfully."
else
    echo "No existing database found at $DB_PATH"
fi

# Ensure directory exists
mkdir -p /var/www/html/storage/app
chmod 775 /var/www/html/storage/app

# Check if we should create a fresh database
read -p "Do you want to create a fresh database? All data will be lost! (y/N): " create_fresh
if [[ "$create_fresh" =~ ^[Yy]$ ]]; then
    echo "Creating fresh database..."
    rm -f "$DB_PATH"
    touch "$DB_PATH"
    chmod 664 "$DB_PATH"

    if [ -f "$INIT_SQL" ]; then
        echo "Initializing with schema from $INIT_SQL"
        sqlite3 "$DB_PATH" < "$INIT_SQL"
        echo "Database initialized successfully."
    else
        echo "Warning: $INIT_SQL not found, running migrations instead."
        php artisan migrate:fresh --force
    fi
else
    # Run the comprehensive migration to add missing columns
    echo "Running comprehensive migration to ensure all columns exist..."
    php artisan migrate --force
fi

# Create admin user if it doesn't exist
echo "Ensuring admin user exists..."
php artisan db:seed --class=AdminUserSeeder --force

# Clear caches
echo "Clearing Laravel caches..."
php artisan optimize:clear

# Verify database columns
echo "Verifying database structure..."
DB_CHECK=$(sqlite3 "$DB_PATH" "PRAGMA table_info(wikidata_entry_requests);" | grep coordinates_qualifier)
if [ -n "$DB_CHECK" ]; then
    echo "coordinates_qualifier column exists - database structure looks good."
else
    echo "WARNING: coordinates_qualifier column not found! Database structure may still have issues."

    # Last resort - run SQL directly
    echo "Attempting to add missing columns directly..."
    cat > /tmp/add_columns.sql << EOF
ALTER TABLE wikidata_entry_requests ADD COLUMN coordinates_qualifier VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN time_point_qualifier VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN time_period_start_qualifier VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN time_period_end_qualifier VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN victim_count_qualifier VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN survivor_count_qualifier VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN death_count_qualifier VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN arrest_count_qualifier VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN status_token VARCHAR(255);
ALTER TABLE wikidata_entry_requests ADD COLUMN status_history TEXT;
ALTER TABLE wikidata_entry_requests ADD COLUMN status_updated_at DATETIME;
ALTER TABLE wikidata_entry_requests ADD COLUMN comment TEXT;
ALTER TABLE wikidata_entry_requests ADD COLUMN gender TEXT;
ALTER TABLE wikidata_entry_requests ADD COLUMN nationality TEXT;
ALTER TABLE wikidata_entry_requests ADD COLUMN place_of_birth TEXT;
ALTER TABLE wikidata_entry_requests ADD COLUMN place_of_death TEXT;
ALTER TABLE wikidata_entry_requests ADD COLUMN place_of_detention TEXT;
EOF

    sqlite3 "$DB_PATH" < /tmp/add_columns.sql 2>/dev/null
    echo "Direct SQL complete."
fi

echo "====================================================="
echo "Database fix complete!"
echo "Admin credentials:"
echo "  Email: admin@gestapo-terror-orte.de"
echo "  Password: admin123456"
echo "====================================================="
echo "If you still encounter issues, use the backup at:"
echo "$BACKUP_PATH"
echo

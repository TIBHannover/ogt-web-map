#!/bin/bash

# This script fixes database issues in the OGT API project
# It ensures the database exists, has the correct schema, includes all required columns,
# and resolves class naming conflicts in migration files

# Set variables
DB_PATH="/var/www/html/storage/app/database.sqlite"
BACKUP_PATH="/var/www/html/storage/app/database.sqlite.backup.$(date +%Y%m%d_%H%M%S)"
INIT_SQL="/var/www/html/database/init.sql"

# Make sure we're in the right directory
cd /var/www/html

# Check for PHP and SQLite
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed or not in the PATH"
    exit 1
fi

if ! command -v sqlite3 &> /dev/null; then
    echo "Error: SQLite3 is not installed or not in the PATH"
    exit 1
fi

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

# Check for class name conflicts in migration files
echo "Checking for class name conflicts in migration files..."
CONFLICTS=$(grep -r "class FixStatusCheckConstraint" database/migrations/ | wc -l)

if [ $CONFLICTS -gt 0 ]; then
    echo "Found potential class name conflicts in migration files."
    echo "Fixing class naming conflicts..."

    # Fix any conflicting class names
    for FILE in database/migrations/*fix_status_check_constraint*.php; do
        if [ -f "$FILE" ]; then
            FILENAME=$(basename "$FILE")
            DATE_PART=${FILENAME:0:10}
            RANDOM_SUFFIX=$(date +%s)

            # Update class name with unique identifier
            sed -i "s/class FixStatusCheckConstraint[a-zA-Z0-9_]*/class FixStatusConstraint${DATE_PART}_${RANDOM_SUFFIX}/g" "$FILE"
            echo "Updated class name in $FILENAME"
        fi
    done

    echo "Class naming conflicts resolved."
fi

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
    # Check if database exists but is empty
    if [ -f "$DB_PATH" ] && [ ! -s "$DB_PATH" ]; then
        echo "Database file exists but is empty. Initializing..."
        if [ -f "$INIT_SQL" ]; then
            sqlite3 "$DB_PATH" < "$INIT_SQL"
            echo "Database initialized successfully."
        else
            echo "Warning: $INIT_SQL not found, running migrations instead."
            php artisan migrate --force
        fi
    else
        # Run the comprehensive migration to add missing columns
        echo "Running comprehensive migration to ensure all columns exist..."
        php artisan migrate --force
    fi
fi

# Check migration status
echo "Checking migration status..."
PENDING=$(php artisan migrate:status | grep Pending | wc -l)

if [ $PENDING -gt 0 ]; then
    echo "Found $PENDING pending migrations."
    read -p "Do you want to mark all migrations as completed without running them? (y/N): " mark_migrations
    if [[ "$mark_migrations" =~ ^[Yy]$ ]]; then
        echo "Marking all migrations as completed..."

        # Get the latest batch number
        LATEST_BATCH=$(sqlite3 "$DB_PATH" "SELECT MAX(batch) FROM migrations;")
        NEXT_BATCH=$((LATEST_BATCH + 1))

        # Create SQL to insert all pending migrations
        php artisan migrate:status | grep Pending | awk '{print $1}' | while read MIGRATION; do
            sqlite3 "$DB_PATH" "INSERT INTO migrations (migration, batch) VALUES ('$MIGRATION', $NEXT_BATCH);"
            echo "Marked $MIGRATION as completed."
        done

        echo "All migrations marked as completed."
    else
        echo "Skipping migration marking. Some migrations may still show as pending."
    fi
fi

# Create admin user if it doesn't exist
echo "Ensuring admin user exists..."
php artisan db:seed --class=AdminUserSeeder --force

# Clear caches
echo "Clearing Laravel caches..."
php artisan optimize:clear

# Verify database columns
echo "Verifying database structure..."
REQUIRED_COLUMNS=(
    "coordinates_qualifier"
    "time_point_qualifier"
    "time_period_start_qualifier"
    "time_period_end_qualifier"
    "victim_count_qualifier"
    "survivor_count_qualifier"
    "death_count_qualifier"
    "arrest_count_qualifier"
    "status_token"
    "status_history"
    "status_updated_at"
    "comment"
    "gender"
    "nationality"
    "place_of_birth"
    "place_of_death"
    "place_of_detention"
)

MISSING_COLUMNS=()
for COLUMN in "${REQUIRED_COLUMNS[@]}"; do
    DB_CHECK=$(sqlite3 "$DB_PATH" "PRAGMA table_info(wikidata_entry_requests);" | grep "$COLUMN")
    if [ -z "$DB_CHECK" ]; then
        MISSING_COLUMNS+=("$COLUMN")
    fi
done

if [ ${#MISSING_COLUMNS[@]} -eq 0 ]; then
    echo "All required columns exist - database structure looks good."
else
    echo "WARNING: Found ${#MISSING_COLUMNS[@]} missing columns in the database!"
    echo "Missing columns: ${MISSING_COLUMNS[*]}"

    # Last resort - run SQL directly
    echo "Attempting to add missing columns directly..."
    for COLUMN in "${MISSING_COLUMNS[@]}"; do
        if [[ "$COLUMN" == *"_qualifier"* ]] || [[ "$COLUMN" == "status_token" ]]; then
            COLUMN_TYPE="VARCHAR(255)"
        elif [[ "$COLUMN" == "status_updated_at" ]]; then
            COLUMN_TYPE="DATETIME"
        else
            COLUMN_TYPE="TEXT"
        fi

        echo "Adding column $COLUMN ($COLUMN_TYPE)..."
        sqlite3 "$DB_PATH" "ALTER TABLE wikidata_entry_requests ADD COLUMN $COLUMN $COLUMN_TYPE;" 2>/dev/null
    done

    echo "Direct SQL complete."

    # Verify again
    STILL_MISSING=0
    for COLUMN in "${MISSING_COLUMNS[@]}"; do
        DB_CHECK=$(sqlite3 "$DB_PATH" "PRAGMA table_info(wikidata_entry_requests);" | grep "$COLUMN")
        if [ -z "$DB_CHECK" ]; then
            STILL_MISSING=$((STILL_MISSING + 1))
        fi
    done

    if [ $STILL_MISSING -eq 0 ]; then
        echo "All columns successfully added."
    else
        echo "WARNING: $STILL_MISSING columns could not be added."
    fi
fi

# Restart services if needed
read -p "Do you want to restart PHP services to apply changes? (y/N): " restart_services
if [[ "$restart_services" =~ ^[Yy]$ ]]; then
    echo "Restarting services..."
    if command -v supervisorctl &> /dev/null; then
        supervisorctl restart all
    elif command -v systemctl &> /dev/null; then
        systemctl restart php-fpm
        systemctl restart nginx
    else
        echo "Could not detect service manager. Please restart services manually."
    fi
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
echo "For future database issues, you can run this script again."
echo "If problems persist, consider reinstalling from scratch with init.sql."
echo "====================================================="

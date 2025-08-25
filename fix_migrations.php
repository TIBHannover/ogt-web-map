<?php

/**
 * Fix Migrations Script
 *
 * This script marks all migration files in the database/migrations directory
 * as completed in the migrations table, without actually running the migrations.
 *
 * It's useful when the database structure is already correct but the migrations
 * table is out of sync.
 *
 * Usage: php fix_migrations.php
 */

// Bootstrap the Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the database connection
$db = DB::connection();
$migrationsTable = config('database.migrations', 'migrations');

echo "Fixing migrations table state...\n";

try {
    // Check if migrations table exists
    if (!Schema::hasTable($migrationsTable)) {
        echo "Migrations table doesn't exist. Creating it...\n";
        Schema::create($migrationsTable, function ($table) {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    // Get all migration files from the filesystem
    $migrationFiles = [];
    $migrationPath = __DIR__ . '/database/migrations';
    $files = scandir($migrationPath);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || !is_file($migrationPath . '/' . $file)) {
            continue;
        }

        // Extract the migration name without extension
        $migrationName = pathinfo($file, PATHINFO_FILENAME);
        $migrationFiles[] = $migrationName;
    }

    // Sort migrations by name (which includes timestamp)
    sort($migrationFiles);

    // Get existing migrations from the database
    $existingMigrations = $db->table($migrationsTable)
        ->pluck('migration')
        ->toArray();

    // Find migrations that are not in the database
    $missingMigrations = array_diff($migrationFiles, $existingMigrations);

    if (empty($missingMigrations)) {
        echo "All migrations are already recorded in the migrations table.\n";
    } else {
        // Get the highest batch number
        $batch = $db->table($migrationsTable)->max('batch');
        $batch = $batch ? $batch + 1 : 1;

        echo "Found " . count($missingMigrations) . " migrations to add to the migrations table.\n";

        // Insert missing migrations into the migrations table
        foreach ($missingMigrations as $migration) {
            $db->table($migrationsTable)->insert([
                'migration' => $migration,
                'batch' => $batch
            ]);
            echo "Marked migration as completed: $migration (batch $batch)\n";
        }

        echo "Successfully added all missing migrations to the migrations table.\n";
    }

    echo "\nMigration fix completed successfully!\n";
    echo "The migrations table has been updated to reflect all migrations as completed.\n";
    echo "Your database schema should now be in sync with your migration files.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

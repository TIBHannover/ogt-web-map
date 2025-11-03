<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixStatusCheckConstraintAgain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === "sqlite") {
            // For SQLite, we need to recreate the table to modify the check constraint
            Log::info("Running migration FixStatusCheckConstraintAgain for SQLite");

            // First, create a new temporary table with the updated constraint
            // Ensure ALL possible status values are included
            DB::statement(
                "CREATE TABLE wikidata_entry_requests_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    coordinates VARCHAR(255) NULL,
                    coordinates_qualifier VARCHAR(255) NULL,
                    victims JSON NULL,
                    perpetrators JSON NULL,
                    time_point VARCHAR(255) NULL,
                    time_point_qualifier VARCHAR(255) NULL,
                    time_period_start VARCHAR(255) NULL,
                    time_period_start_qualifier VARCHAR(255) NULL,
                    time_period_end VARCHAR(255) NULL,
                    time_period_end_qualifier VARCHAR(255) NULL,
                    victim_count INTEGER NULL,
                    survivor_count INTEGER NULL,
                    death_count INTEGER NULL,
                    arrest_count INTEGER NULL,
                    victim_count_qualifier VARCHAR(255) NULL,
                    survivor_count_qualifier VARCHAR(255) NULL,
                    death_count_qualifier VARCHAR(255) NULL,
                    arrest_count_qualifier VARCHAR(255) NULL,
                    cause TEXT NULL,
                    selected_causes JSON NULL,
                    image_url VARCHAR(255) NULL,
                    image_filename VARCHAR(255) NULL,
                    sources JSON NULL,
                    wikidata_id VARCHAR(255) NULL,
                    created_entities JSON NULL,
                    status VARCHAR(255) NOT NULL CHECK(status IN (
                        'pending', 'in_review', 'revision_needed', 'approved', 'rejected',
                        'error', 'wikidata_submitted', 'wikidata_published', 'wikidata_failed', 'published'
                    )),
                    status_token VARCHAR(255) NULL,
                    status_history JSON NULL,
                    status_updated_at TIMESTAMP NULL,
                    comment TEXT NULL,
                    submitted_by VARCHAR(255) NULL,
                    approved_by VARCHAR(255) NULL,
                    rejected_by VARCHAR(255) NULL,
                    rejection_reason TEXT NULL,
                    error_message TEXT NULL,
                    gender VARCHAR(255) NULL,
                    nationality VARCHAR(255) NULL,
                    place_of_birth VARCHAR(255) NULL,
                    place_of_death VARCHAR(255) NULL,
                    place_of_detention JSON NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    email VARCHAR(255) NULL,
                    notify_on_publish BOOLEAN DEFAULT 0,
                    notification_sent_at TIMESTAMP NULL,
                    occupation VARCHAR(255) NULL,
                    is_standalone_person BOOLEAN DEFAULT 0,
                    place_of_residence VARCHAR(255) NULL,
                    employer JSON NULL,
                    birth_name VARCHAR(255) NULL,
                    commemorates VARCHAR(255) NULL,
                    operator VARCHAR(255) NULL,
                    opening_date VARCHAR(255) NULL,
                    opening_date_qualifier VARCHAR(255) NULL,
                    address VARCHAR(255) NULL,
                    website VARCHAR(255) NULL
                )"
            );

            // Copy data from the old table to the new one
            DB::statement(
                "INSERT INTO wikidata_entry_requests_new SELECT * FROM wikidata_entry_requests"
            );

            // Drop the old table
            DB::statement("DROP TABLE wikidata_entry_requests");

            // Rename the new table to the original name
            DB::statement("ALTER TABLE wikidata_entry_requests_new RENAME TO wikidata_entry_requests");

            // Log that the migration was run
            Log::info('Migration FixStatusCheckConstraintAgain executed - status check constraint updated for SQLite');
        } elseif ($driver === "mysql") {
            // For MySQL, we can just alter the enum
            Log::info("Running migration FixStatusCheckConstraintAgain for MySQL");

            DB::statement(
                "ALTER TABLE wikidata_entry_requests
                MODIFY COLUMN status ENUM(
                    'pending', 'in_review', 'revision_needed', 'approved', 'rejected',
                    'error', 'wikidata_submitted', 'wikidata_published', 'wikidata_failed', 'published'
                ) NOT NULL"
            );

            Log::info('Migration FixStatusCheckConstraintAgain executed - status enum updated for MySQL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration adds more flexibility to the check constraint,
        // so it's best not to roll it back as it would likely break things.
        Log::info('Migration FixStatusCheckConstraintAgain down method called, but no action taken to preserve data integrity');
    }
}

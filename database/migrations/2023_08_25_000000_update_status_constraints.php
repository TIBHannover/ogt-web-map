<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateStatusConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite, we need to recreate the table to modify the check constraint

            // First, create a new temporary table with the updated constraint
            DB::statement(
                "CREATE TABLE wikidata_entry_requests_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    coordinates VARCHAR(255) NULL,
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
                    status VARCHAR(255) NOT NULL CHECK(status IN ('pending', 'approved', 'rejected', 'error', 'published', 'wikidata_published')),
                    submitted_by VARCHAR(255) NULL,
                    approved_by VARCHAR(255) NULL,
                    rejected_by VARCHAR(255) NULL,
                    rejection_reason TEXT NULL,
                    error_message TEXT NULL,
                    gender JSON NULL,
                    nationality JSON NULL,
                    place_of_birth JSON NULL,
                    place_of_death JSON NULL,
                    place_of_detention JSON NULL,
                    status_token VARCHAR(255) NULL,
                    status_history JSON NULL,
                    status_updated_at TIMESTAMP NULL,
                    comment TEXT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    email VARCHAR(255) NULL,
                    notify_on_publish BOOLEAN DEFAULT 0,
                    notification_sent_at TIMESTAMP NULL,
                    coordinates_qualifier VARCHAR(255) NULL
                )"
            );

            // Copy data from the old table to the new one
            DB::statement(
                "INSERT INTO wikidata_entry_requests_new
                SELECT id, title, description, type, coordinates, victims, perpetrators,
                time_point, time_point_qualifier, time_period_start, time_period_start_qualifier,
                time_period_end, time_period_end_qualifier, victim_count, survivor_count,
                death_count, arrest_count, victim_count_qualifier, survivor_count_qualifier,
                death_count_qualifier, arrest_count_qualifier, cause, selected_causes,
                image_url, image_filename, sources, wikidata_id, created_entities,
                status, submitted_by, approved_by, rejected_by, rejection_reason, error_message,
                gender, nationality, place_of_birth, place_of_death, place_of_detention,
                status_token, status_history, status_updated_at, comment, created_at, updated_at,
                email, notify_on_publish, notification_sent_at, coordinates_qualifier
                FROM wikidata_entry_requests"
            );

            // Drop the old table
            DB::statement("DROP TABLE wikidata_entry_requests");

            // Rename the new table to the original name
            DB::statement("ALTER TABLE wikidata_entry_requests_new RENAME TO wikidata_entry_requests");

            // Log that the migration was run
            \Illuminate\Support\Facades\Log::info('Migration UpdateStatusConstraints executed - status check constraint updated');
        } else if ($driver === 'mysql') {
            // For MySQL, we can just alter the enum
            DB::statement(
                "ALTER TABLE wikidata_entry_requests
                MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'error', 'published', 'wikidata_published') NOT NULL"
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // We don't want to reverse this migration as it's a critical fix
        // Just log that the downgrade was attempted but not performed
        \Illuminate\Support\Facades\Log::info('Migration UpdateStatusConstraints down method called, but no action taken to preserve data integrity');
    }
}

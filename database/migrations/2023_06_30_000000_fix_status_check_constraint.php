<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixStatusCheckConstraint2023JuneUnique extends Migration
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

            // First, create a new temporary table with the updated constraint
            DB::statement(
                "CREATE TABLE wikidata_entry_requests_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    coordinates VARCHAR(255),
                    victims TEXT,
                    perpetrators TEXT,
                    time_point VARCHAR(255),
                    time_period_start VARCHAR(255),
                    time_period_end VARCHAR(255),
                    victim_count INTEGER,
                    survivor_count INTEGER,
                    death_count INTEGER,
                    arrest_count INTEGER,
                    cause TEXT,
                    selected_causes TEXT,
                    image_url VARCHAR(255),
                    image_filename VARCHAR(255),
                    sources TEXT,
                    created_entities TEXT,
                    status VARCHAR(255) NOT NULL CHECK(status IN ('pending', 'in_review', 'revision_needed', 'approved', 'rejected', 'error', 'wikidata_submitted', 'wikidata_published', 'wikidata_failed')),
                    wikidata_id VARCHAR(255),
                    submitted_by VARCHAR(255),
                    approved_by VARCHAR(255),
                    rejected_by VARCHAR(255),
                    rejection_reason TEXT,
                    error_message TEXT,
                    created_at DATETIME,
                    updated_at DATETIME,
                    gender TEXT,
                    nationality TEXT,
                    place_of_birth TEXT,
                    place_of_death TEXT,
                    place_of_detention TEXT,
                    victim_count_qualifier VARCHAR(255),
                    survivor_count_qualifier VARCHAR(255),
                    death_count_qualifier VARCHAR(255),
                    arrest_count_qualifier VARCHAR(255)
                )",
            );

            // Copy data from the old table to the new one
            DB::statement(
                "INSERT INTO wikidata_entry_requests_new
                SELECT id, title, description, type, coordinates, victims, perpetrators,
                time_point, time_period_start, time_period_end, victim_count, survivor_count,
                death_count, arrest_count, cause, selected_causes,
                image_url, image_filename, sources, created_entities,
                status, wikidata_id, submitted_by, approved_by, rejected_by, rejection_reason, error_message,
                created_at, updated_at, gender, nationality, place_of_birth, place_of_death, place_of_detention,
                victim_count_qualifier, survivor_count_qualifier, death_count_qualifier, arrest_count_qualifier
                FROM wikidata_entry_requests",
            );

            // Drop the old table
            DB::statement("DROP TABLE wikidata_entry_requests");

            // Rename the new table to the original name
            DB::statement(
                "ALTER TABLE wikidata_entry_requests_new RENAME TO wikidata_entry_requests",
            );

            // Log that the migration was run
            \Illuminate\Support\Facades\Log::info(
                "Migration FixStatusCheckConstraint2023 executed - status check constraint updated",
            );
        } elseif ($driver === "mysql") {
            // For MySQL, we can just alter the enum
            DB::statement(
                "ALTER TABLE wikidata_entry_requests
                MODIFY COLUMN status ENUM('pending', 'in_review', 'revision_needed', 'approved', 'rejected', 'error', 'wikidata_submitted', 'wikidata_published', 'wikidata_failed') NOT NULL",
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
        $driver = DB::connection()->getDriverName();

        if ($driver === "sqlite") {
            // For SQLite, we would need to recreate the table again
            // This is similar to the up method but with the original constraint

            // First, create a new temporary table with the original constraint
            DB::statement(
                "CREATE TABLE wikidata_entry_requests_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    coordinates VARCHAR(255),
                    victims TEXT,
                    perpetrators TEXT,
                    time_point VARCHAR(255),
                    time_period_start VARCHAR(255),
                    time_period_end VARCHAR(255),
                    victim_count INTEGER,
                    survivor_count INTEGER,
                    death_count INTEGER,
                    arrest_count INTEGER,
                    cause TEXT,
                    selected_causes TEXT,
                    image_url VARCHAR(255),
                    image_filename VARCHAR(255),
                    sources TEXT,
                    created_entities TEXT,
                    status VARCHAR(255) NOT NULL CHECK(status IN ('pending', 'approved', 'rejected', 'error')),
                    wikidata_id VARCHAR(255),
                    submitted_by VARCHAR(255),
                    approved_by VARCHAR(255),
                    rejected_by VARCHAR(255),
                    rejection_reason TEXT,
                    error_message TEXT,
                    created_at DATETIME,
                    updated_at DATETIME,
                    gender TEXT,
                    nationality TEXT,
                    place_of_birth TEXT,
                    place_of_death TEXT,
                    place_of_detention TEXT,
                    victim_count_qualifier VARCHAR(255),
                    survivor_count_qualifier VARCHAR(255),
                    death_count_qualifier VARCHAR(255),
                    arrest_count_qualifier VARCHAR(255)
                )",
            );

            // Update any rows with non-original status values
            DB::statement(
                "UPDATE wikidata_entry_requests
                SET status = 'approved'
                WHERE status IN ('wikidata_submitted', 'wikidata_published', 'wikidata_failed')",
            );

            // Copy data from the old table to the new one
            DB::statement(
                "INSERT INTO wikidata_entry_requests_new
                SELECT id, title, description, type, coordinates, victims, perpetrators,
                time_point, time_period_start, time_period_end, victim_count, survivor_count,
                death_count, arrest_count, cause, selected_causes,
                image_url, image_filename, sources, created_entities,
                status, wikidata_id, submitted_by, approved_by, rejected_by, rejection_reason, error_message,
                created_at, updated_at, gender, nationality, place_of_birth, place_of_death, place_of_detention,
                victim_count_qualifier, survivor_count_qualifier, death_count_qualifier, arrest_count_qualifier
                FROM wikidata_entry_requests",
            );

            // Drop the old table
            DB::statement("DROP TABLE wikidata_entry_requests");

            // Rename the new table to the original name
            DB::statement(
                "ALTER TABLE wikidata_entry_requests_new RENAME TO wikidata_entry_requests",
            );
        } elseif ($driver === "mysql") {
            // For MySQL, we can just alter the enum back
            DB::statement(
                "ALTER TABLE wikidata_entry_requests
                MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'error') NOT NULL",
            );
        }
    }
}

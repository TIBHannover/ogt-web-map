<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite, we'll check each column and add it if it doesn't exist
            $this->ensureColumnsExistSqlite();
        } else {
            // For other databases like MySQL, we can use Schema to add columns
            $this->ensureColumnsExistSchema();
        }
    }

    /**
     * Ensure all required columns exist using SQLite-specific approach
     */
    private function ensureColumnsExistSqlite(): void
    {
        // Get current columns in the table
        $columns = DB::select("PRAGMA table_info(wikidata_entry_requests)");
        $existingColumns = [];

        foreach ($columns as $column) {
            $existingColumns[] = $column->name;
        }

        // List of required columns with their types
        $requiredColumns = [
            'coordinates_qualifier' => 'VARCHAR(255)',
            'time_point_qualifier' => 'VARCHAR(255)',
            'time_period_start_qualifier' => 'VARCHAR(255)',
            'time_period_end_qualifier' => 'VARCHAR(255)',
            'victim_count_qualifier' => 'VARCHAR(255)',
            'survivor_count_qualifier' => 'VARCHAR(255)',
            'death_count_qualifier' => 'VARCHAR(255)',
            'arrest_count_qualifier' => 'VARCHAR(255)',
            'status_token' => 'VARCHAR(255)',
            'status_history' => 'TEXT',
            'status_updated_at' => 'DATETIME',
            'comment' => 'TEXT',
            'gender' => 'TEXT',
            'nationality' => 'TEXT',
            'place_of_birth' => 'TEXT',
            'place_of_death' => 'TEXT',
            'place_of_detention' => 'TEXT',
        ];

        // Add each missing column using SQLite ALTER TABLE
        foreach ($requiredColumns as $column => $type) {
            if (!in_array($column, $existingColumns)) {
                try {
                    DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN {$column} {$type}");
                    \Illuminate\Support\Facades\Log::info("Added missing column to wikidata_entry_requests: {$column}");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to add column {$column}: " . $e->getMessage());
                }
            }
        }

        // Also ensure the status ENUM constraint allows all needed values
        $this->ensureStatusConstraint();
    }

    /**
     * Ensure all required columns exist using Laravel Schema
     */
    private function ensureColumnsExistSchema(): void
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            // Check if each column exists and add if missing
            if (!Schema::hasColumn('wikidata_entry_requests', 'coordinates_qualifier')) {
                $table->string('coordinates_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'time_point_qualifier')) {
                $table->string('time_point_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'time_period_start_qualifier')) {
                $table->string('time_period_start_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'time_period_end_qualifier')) {
                $table->string('time_period_end_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'victim_count_qualifier')) {
                $table->string('victim_count_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'survivor_count_qualifier')) {
                $table->string('survivor_count_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'death_count_qualifier')) {
                $table->string('death_count_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'arrest_count_qualifier')) {
                $table->string('arrest_count_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'status_token')) {
                $table->string('status_token')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'status_history')) {
                $table->text('status_history')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'status_updated_at')) {
                $table->dateTime('status_updated_at')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'comment')) {
                $table->text('comment')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'gender')) {
                $table->text('gender')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'nationality')) {
                $table->text('nationality')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'place_of_birth')) {
                $table->text('place_of_birth')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'place_of_death')) {
                $table->text('place_of_death')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'place_of_detention')) {
                $table->text('place_of_detention')->nullable();
            }
        });

        // Ensure status enum constraint for MySQL
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE wikidata_entry_requests
                MODIFY COLUMN status ENUM('pending', 'in_review', 'revision_needed', 'approved', 'rejected', 'error', 'wikidata_submitted', 'wikidata_published', 'wikidata_failed') NOT NULL"
            );
        }
    }

    /**
     * Ensure the status constraint includes all required status values in SQLite
     */
    private function ensureStatusConstraint(): void
    {
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                // For SQLite, we need to recreate the table to modify the check constraint
                // First, create a new temporary table with the updated constraint
                DB::statement(
                    "CREATE TABLE IF NOT EXISTS wikidata_entry_requests_new (
                        id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        description TEXT NOT NULL,
                        type VARCHAR(255) NOT NULL,
                        coordinates VARCHAR(255) NULL,
                        coordinates_qualifier VARCHAR(255) NULL,
                        victims TEXT NULL,
                        perpetrators TEXT NULL,
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
                        selected_causes TEXT NULL,
                        image_url VARCHAR(255) NULL,
                        image_filename VARCHAR(255) NULL,
                        sources TEXT NULL,
                        wikidata_id VARCHAR(255) NULL,
                        created_entities TEXT NULL,
                        status VARCHAR(255) NOT NULL CHECK(status IN ('pending', 'in_review', 'revision_needed', 'approved', 'rejected', 'error', 'wikidata_submitted', 'wikidata_published', 'wikidata_failed')),
                        submitted_by VARCHAR(255) NULL,
                        approved_by VARCHAR(255) NULL,
                        rejected_by VARCHAR(255) NULL,
                        rejection_reason TEXT NULL,
                        error_message TEXT NULL,
                        gender TEXT NULL,
                        nationality TEXT NULL,
                        place_of_birth TEXT NULL,
                        place_of_death TEXT NULL,
                        place_of_detention TEXT NULL,
                        status_token VARCHAR(255) NULL,
                        status_history TEXT NULL,
                        status_updated_at DATETIME NULL,
                        comment TEXT NULL,
                        created_at DATETIME NULL,
                        updated_at DATETIME NULL
                    )"
                );

                // Check if the original table exists and has data
                $tableExists = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='wikidata_entry_requests'");

                if (count($tableExists) > 0) {
                    // Get list of columns from the original table
                    $columns = DB::select("PRAGMA table_info(wikidata_entry_requests)");
                    $columnNames = [];

                    foreach ($columns as $column) {
                        $columnNames[] = $column->name;
                    }

                    // Only include columns that exist in both tables
                    $insertColumns = implode(", ", $columnNames);

                    // Copy data from the old table to the new one
                    DB::statement(
                        "INSERT INTO wikidata_entry_requests_new ({$insertColumns})
                        SELECT {$insertColumns} FROM wikidata_entry_requests"
                    );

                    // Drop the old table
                    DB::statement("DROP TABLE wikidata_entry_requests");

                    // Rename the new table to the original name
                    DB::statement("ALTER TABLE wikidata_entry_requests_new RENAME TO wikidata_entry_requests");

                    \Illuminate\Support\Facades\Log::info("Successfully updated status constraint for wikidata_entry_requests");
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error updating status constraint: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't need to remove columns in down()
        // as this migration is just ensuring they exist
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // For SQLite we can't directly change column types
        // So we need a workaround that works for both SQLite and MySQL
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Create a backup of existing data
            DB::statement('CREATE TABLE wikidata_entry_requests_backup AS SELECT * FROM wikidata_entry_requests');

            // Create a helper function to update JSON columns
            $updateColumns = function($tableName, $columns) {
                foreach ($columns as $column) {
                    // Convert string values to JSON arrays if not already arrays
                    $entries = DB::table($tableName)->whereNotNull($column)->get();

                    foreach ($entries as $entry) {
                        $value = $entry->$column;

                        // Skip if already JSON
                        if (is_array($value) || (is_string($value) && (substr($value, 0, 1) === '[' || substr($value, 0, 1) === '{'))) {
                            continue;
                        }

                        // Convert string to JSON array with single value
                        if (!empty($value)) {
                            DB::table($tableName)
                                ->where('id', $entry->id)
                                ->update([$column => json_encode([$value])]);
                        }
                    }
                }
            };

            // Update the fields we want to convert to arrays
            $arrayFields = [
                'key_event',
                'director',
                'employees',
                'parent_organization',
                'child_organization',
                'predecessor_organization',
                'successor_organization',
            ];

            $updateColumns('wikidata_entry_requests', $arrayFields);
        } else {
            // For MySQL we can use ALTER TABLE directly
            // First update the existing data to JSON arrays
            $arrayFields = [
                'key_event',
                'director',
                'employees',
                'parent_organization',
                'child_organization',
                'predecessor_organization',
                'successor_organization',
            ];

            foreach ($arrayFields as $field) {
                // Convert string values to JSON arrays
                DB::statement("
                    UPDATE wikidata_entry_requests
                    SET {$field} = JSON_ARRAY({$field})
                    WHERE {$field} IS NOT NULL
                    AND JSON_TYPE({$field}) IS NULL
                ");
            }

            // Now change the column types
            Schema::table('wikidata_entry_requests', function (Blueprint $table) use ($arrayFields) {
                foreach ($arrayFields as $field) {
                    $table->json($field)->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite, restore from backup if needed
            if (Schema::hasTable('wikidata_entry_requests_backup')) {
                DB::statement('DROP TABLE IF EXISTS wikidata_entry_requests');
                DB::statement('ALTER TABLE wikidata_entry_requests_backup RENAME TO wikidata_entry_requests');
            }
        } else {
            // For MySQL, we can convert JSON arrays back to strings
            // (taking the first element if it exists)
            $arrayFields = [
                'key_event',
                'director',
                'employees',
                'parent_organization',
                'child_organization',
                'predecessor_organization',
                'successor_organization',
            ];

            Schema::table('wikidata_entry_requests', function (Blueprint $table) use ($arrayFields) {
                foreach ($arrayFields as $field) {
                    $table->string($field, 255)->nullable()->change();
                }
            });

            // Convert JSON arrays back to single values
            foreach ($arrayFields as $field) {
                DB::statement("
                    UPDATE wikidata_entry_requests
                    SET {$field} = JSON_UNQUOTE(JSON_EXTRACT({$field}, '$[0]'))
                    WHERE {$field} IS NOT NULL
                    AND JSON_TYPE({$field}) = 'ARRAY'
                    AND JSON_LENGTH({$field}) > 0
                ");

                DB::statement("
                    UPDATE wikidata_entry_requests
                    SET {$field} = NULL
                    WHERE {$field} IS NOT NULL
                    AND JSON_TYPE({$field}) = 'ARRAY'
                    AND JSON_LENGTH({$field}) = 0
                ");
            }
        }
    }
};

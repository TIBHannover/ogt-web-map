<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddWikidataPublishedStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Since we can't easily modify an enum in SQLite, we'll need to use a database-agnostic approach
        // First, check what database driver we're using
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // For MySQL, we can modify the enum
            $currentEnum = DB::select("SHOW COLUMNS FROM wikidata_entry_requests WHERE Field = 'status'")[0]->Type;
            preg_match("/^enum\((.*)\)$/", $currentEnum, $matches);
            $enumValues = str_getcsv($matches[1], ',', "'");
            
            // Check if 'wikidata_published' is already in the enum
            if (!in_array('wikidata_published', $enumValues)) {
                // Add 'wikidata_published' to the enum
                $enumValues[] = 'wikidata_published';
                $newEnum = "'" . implode("','" , $enumValues) . "'";
                
                // Alter the table to update the enum
                DB::statement("ALTER TABLE wikidata_entry_requests MODIFY COLUMN status ENUM($newEnum) NOT NULL");
            }
        } else {
            // For SQLite and other databases, we can't modify enums directly
            // We'll need to make sure the application code handles the new status value
            // For SQLite specifically, enums aren't enforced at the database level
            
            // Let's just log that this migration was run
            DB::statement("PRAGMA foreign_keys = ON");
            
            // If you're using a check constraint for validation, you could update it here
            // But for SQLite, you'd typically need to recreate the table to change constraints
            
            // Just log the migration in the Laravel log
            \Illuminate\Support\Facades\Log::info('Migration AddWikidataPublishedStatus executed - please ensure your application accepts "wikidata_published" as a valid status');
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
        
        if ($driver === 'mysql') {
            // For MySQL, we can modify the enum
            $currentEnum = DB::select("SHOW COLUMNS FROM wikidata_entry_requests WHERE Field = 'status'")[0]->Type;
            preg_match("/^enum\((.*)\)$/", $currentEnum, $matches);
            $enumValues = str_getcsv($matches[1], ',', "'");
            
            // Remove 'wikidata_published' from the enum if it exists
            $enumValues = array_filter($enumValues, function($value) {
                return $value !== 'wikidata_published';
            });
            
            $newEnum = "'" . implode("','" , $enumValues) . "'";
            
            // Alter the table to update the enum
            DB::statement("ALTER TABLE wikidata_entry_requests MODIFY COLUMN status ENUM($newEnum) NOT NULL");
        } else {
            // For SQLite, just log the rollback since we can't easily modify enums
            \Illuminate\Support\Facades\Log::info('Migration AddWikidataPublishedStatus rolled back');
        }
    }
}
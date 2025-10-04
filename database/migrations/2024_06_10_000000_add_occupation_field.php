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
            // For SQLite, we need to check if the column exists and add it if it doesn't
            $columns = DB::select("PRAGMA table_info(wikidata_entry_requests)");
            $existingColumns = [];

            foreach ($columns as $column) {
                $existingColumns[] = $column->name;
            }

            if (!in_array('occupation', $existingColumns)) {
                DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN occupation TEXT NULL");
                \Illuminate\Support\Facades\Log::info("Added occupation column to wikidata_entry_requests");
            }
        } else {
            // For other databases like MySQL, we can use Schema to add the column
            Schema::table('wikidata_entry_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('wikidata_entry_requests', 'occupation')) {
                    $table->text('occupation')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('wikidata_entry_requests', function (Blueprint $table) {
                $table->dropColumn('occupation');
            });
        }

        // SQLite does not support dropping columns without recreating the table,
        // so we'll skip it for the down method
    }
};

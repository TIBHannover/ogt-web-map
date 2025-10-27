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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite
            $columns = DB::select("PRAGMA table_info(wikidata_entry_requests)");
            $existingColumns = array_map(fn($col) => $col->name, $columns);

            if (!in_array('employer', $existingColumns)) {
                DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN employer JSON NULL");
            }
        } else {
            // For MySQL and others
            Schema::table('wikidata_entry_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('wikidata_entry_requests', 'employer')) {
                    $table->json('employer')->nullable();
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
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('wikidata_entry_requests', function (Blueprint $table) {
                $table->dropColumn('employer');
            });
        }
        // SQLite doesn't support dropping columns without recreating the table
    }
};

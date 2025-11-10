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
            // SQLite implementation
            $this->handleSQLite();
        } else {
            // MySQL implementation
            $this->handleMySQL();
        }
    }

    /**
     * Handle migration for SQLite database
     */
    private function handleSQLite(): void
    {
        // Check if columns exist first
        $columns = DB::select("PRAGMA table_info(wikidata_entry_requests)");
        $existingColumns = array_map(fn($col) => $col->name, $columns);

        // Add commemorates column (P547)
        if (!in_array('commemorates', $existingColumns)) {
            DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN commemorates TEXT NULL");
        }

        // Add operator column (P137)
        if (!in_array('operator', $existingColumns)) {
            DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN operator TEXT NULL");
        }

        // Add opening_date column (P1619)
        if (!in_array('opening_date', $existingColumns)) {
            DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN opening_date TEXT NULL");
        }

        // Add opening_date_qualifier column
        if (!in_array('opening_date_qualifier', $existingColumns)) {
            DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN opening_date_qualifier TEXT NULL");
        }

        // Add address column (P6375)
        if (!in_array('address', $existingColumns)) {
            DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN address TEXT NULL");
        }

        // Add website column (P856)
        if (!in_array('website', $existingColumns)) {
            DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN website TEXT NULL");
        }
    }

    /**
     * Handle migration for MySQL database
     */
    private function handleMySQL(): void
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('wikidata_entry_requests', 'commemorates')) {
                $table->text('commemorates')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'operator')) {
                $table->text('operator')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'opening_date')) {
                $table->string('opening_date')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'opening_date_qualifier')) {
                $table->string('opening_date_qualifier')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'address')) {
                $table->text('address')->nullable();
            }

            if (!Schema::hasColumn('wikidata_entry_requests', 'website')) {
                $table->text('website')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // For MySQL databases, we can drop columns
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('wikidata_entry_requests', function (Blueprint $table) {
                $columns = [
                    'commemorates', 'operator', 'opening_date', 'opening_date_qualifier',
                    'address', 'website'
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('wikidata_entry_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Note: SQLite doesn't support dropping columns without recreating the table
        // For simplicity, we're not implementing the SQLite rollback here
    }
};

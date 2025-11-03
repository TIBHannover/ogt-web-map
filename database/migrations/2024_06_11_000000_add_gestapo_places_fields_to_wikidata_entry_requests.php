<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === "sqlite") {
            // For SQLite
            $columns = DB::select("PRAGMA table_info(wikidata_entry_requests)");
            $existingColumns = array_map(fn($col) => $col->name, $columns);

            // Add the new GestapoPlaces fields if they don't exist
            if (!in_array("entity_subtype", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN entity_subtype TEXT NULL",
                );
            }
            if (!in_array("founding_date", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN founding_date TEXT NULL",
                );
            }
            if (!in_array("founding_date_qualifier", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN founding_date_qualifier TEXT NULL",
                );
            }
            if (!in_array("dissolution_date", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN dissolution_date TEXT NULL",
                );
            }
            if (!in_array("dissolution_date_qualifier", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN dissolution_date_qualifier TEXT NULL",
                );
            }
            if (!in_array("address", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN address TEXT NULL",
                );
            }
            if (!in_array("key_event", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN key_event TEXT NULL",
                );
            }
            if (!in_array("director", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN director TEXT NULL",
                );
            }
            if (!in_array("employees", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN employees TEXT NULL",
                );
            }
            if (!in_array("prisoner_count", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN prisoner_count TEXT NULL",
                );
            }
            if (!in_array("prisoner_count_qualifier", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN prisoner_count_qualifier TEXT NULL",
                );
            }
            if (!in_array("parent_organization", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN parent_organization TEXT NULL",
                );
            }
            if (!in_array("child_organization", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN child_organization TEXT NULL",
                );
            }
            if (!in_array("predecessor_organization", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN predecessor_organization TEXT NULL",
                );
            }
            if (!in_array("successor_organization", $existingColumns)) {
                DB::statement(
                    "ALTER TABLE wikidata_entry_requests ADD COLUMN successor_organization TEXT NULL",
                );
            }
        } else {
            // For MySQL and others
            Schema::table("wikidata_entry_requests", function (
                Blueprint $table,
            ) {
                // Add all fields for GestapoPlaces
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "entity_subtype",
                    )
                ) {
                    $table->text("entity_subtype")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "founding_date",
                    )
                ) {
                    $table->text("founding_date")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "founding_date_qualifier",
                    )
                ) {
                    $table->text("founding_date_qualifier")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "dissolution_date",
                    )
                ) {
                    $table->text("dissolution_date")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "dissolution_date_qualifier",
                    )
                ) {
                    $table->text("dissolution_date_qualifier")->nullable();
                }
                if (!Schema::hasColumn("wikidata_entry_requests", "address")) {
                    $table->text("address")->nullable();
                }
                if (
                    !Schema::hasColumn("wikidata_entry_requests", "key_event")
                ) {
                    $table->text("key_event")->nullable();
                }
                if (!Schema::hasColumn("wikidata_entry_requests", "director")) {
                    $table->text("director")->nullable();
                }
                if (
                    !Schema::hasColumn("wikidata_entry_requests", "employees")
                ) {
                    $table->text("employees")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "prisoner_count",
                    )
                ) {
                    $table->text("prisoner_count")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "prisoner_count_qualifier",
                    )
                ) {
                    $table->text("prisoner_count_qualifier")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "parent_organization",
                    )
                ) {
                    $table->text("parent_organization")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "child_organization",
                    )
                ) {
                    $table->text("child_organization")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "predecessor_organization",
                    )
                ) {
                    $table->text("predecessor_organization")->nullable();
                }
                if (
                    !Schema::hasColumn(
                        "wikidata_entry_requests",
                        "successor_organization",
                    )
                ) {
                    $table->text("successor_organization")->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === "mysql") {
            Schema::table("wikidata_entry_requests", function (
                Blueprint $table,
            ) {
                // Drop all the new columns added in the up method
                $table->dropColumn([
                    "entity_subtype",
                    "founding_date",
                    "founding_date_qualifier",
                    "dissolution_date",
                    "dissolution_date_qualifier",
                    "address",
                    "key_event",
                    "prisoner_count",
                    "prisoner_count_qualifier",
                    "parent_organization",
                    "child_organization",
                    "predecessor_organization",
                    "successor_organization",
                    "director",
                    "employees",
                ]);
            });
        }
        // SQLite doesn't support dropping columns without recreating the table
    }
};

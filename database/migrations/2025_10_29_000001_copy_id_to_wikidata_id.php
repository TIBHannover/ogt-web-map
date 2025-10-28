<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all entry requests from the database
        $entries = DB::table("wikidata_entry_requests")->get();
        $updatedCount = 0;

        foreach ($entries as $entry) {
            $updated = false;
            $victims = json_decode($entry->victims, true);
            $perpetrators = json_decode($entry->perpetrators, true);

            // Process victims - copy id field to wikidata_id if needed
            if (is_array($victims) && !empty($victims)) {
                foreach ($victims as $index => $victim) {
                    if (!is_array($victim)) {
                        continue;
                    }

                    // Copy id to wikidata_id if:
                    // - id exists and matches Q-number format
                    // - wikidata_id is missing or null
                    if (
                        isset($victim["id"]) &&
                        preg_match('/^Q\d+$/', $victim["id"]) &&
                        (!isset($victim["wikidata_id"]) || $victim["wikidata_id"] === null)
                    ) {
                        $victims[$index]["wikidata_id"] = $victim["id"];
                        Log::info("Fixed victim #{$index} in entry #{$entry->id} - copied id {$victim['id']} to wikidata_id");
                        $updated = true;
                    }
                }
            }

            // Process perpetrators - copy id field to wikidata_id if needed
            if (is_array($perpetrators) && !empty($perpetrators)) {
                foreach ($perpetrators as $index => $perpetrator) {
                    if (!is_array($perpetrator)) {
                        continue;
                    }

                    // Copy id to wikidata_id if:
                    // - id exists and matches Q-number format
                    // - wikidata_id is missing or null
                    if (
                        isset($perpetrator["id"]) &&
                        preg_match('/^Q\d+$/', $perpetrator["id"]) &&
                        (!isset($perpetrator["wikidata_id"]) || $perpetrator["wikidata_id"] === null)
                    ) {
                        $perpetrators[$index]["wikidata_id"] = $perpetrator["id"];
                        Log::info("Fixed perpetrator #{$index} in entry #{$entry->id} - copied id {$perpetrator['id']} to wikidata_id");
                        $updated = true;
                    }
                }
            }

            // Update the database if changes were made
            if ($updated) {
                DB::table("wikidata_entry_requests")
                    ->where("id", $entry->id)
                    ->update([
                        "victims" => json_encode($victims),
                        "perpetrators" => json_encode($perpetrators),
                    ]);

                $updatedCount++;
                Log::info("Updated entry #{$entry->id} with wikidata_id fields");
            }
        }

        Log::info("Migration completed: Updated {$updatedCount} entries with wikidata_id fields");
    }

    /**
     * Reverse the migrations.
     *
     * This is a data migration and does not need a down method.
     */
    public function down(): void
    {
        // This is a data migration and does not need to be reversed
        Log::info("No action taken for down migration of copy_id_to_wikidata_id");
    }
};

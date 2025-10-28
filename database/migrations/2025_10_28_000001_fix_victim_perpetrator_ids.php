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

            // Process victims - ensure 'id' field is preserved
            if (is_array($victims) && !empty($victims)) {
                $processedVictims = [];
                foreach ($victims as $victim) {
                    // Skip if not an array
                    if (!is_array($victim)) {
                        continue;
                    }

                    // Create a new victim entry with 'id' field explicitly set
                    $victimData = [
                        "title" =>
                            $victim["title"] ?? ($victim["label"] ?? null),
                        "label" =>
                            $victim["label"] ?? ($victim["title"] ?? null),
                        "description" => $victim["description"] ?? null,
                        "type" => $victim["type"] ?? null,
                        "role" => $victim["role"] ?? "victim",
                        "firstName" => $victim["firstName"] ?? null,
                        "lastName" => $victim["lastName"] ?? null,
                        "birthDate" => $victim["birthDate"] ?? null,
                        "deathDate" => $victim["deathDate"] ?? null,
                        "nationality" => $victim["nationality"] ?? null,
                    ];

                    // Copy the ID field, checking various possible field names
                    if (isset($victim["id"])) {
                        $victimData["wikidata_id"] = $victim["id"];
                        Log::info(
                            "Found victim ID, copying to wikidata_id: {$victim["id"]}",
                        );
                        $updated = true;
                    } elseif (isset($victim["wikidata_id"])) {
                        $victimData["wikidata_id"] = $victim["wikidata_id"];
                        Log::info(
                            "Found victim wikidata_id: {$victim["wikidata_id"]}",
                        );
                        $updated = true;
                    }

                    $processedVictims[] = $victimData;
                }

                // Only update if we have processed victims
                if (!empty($processedVictims)) {
                    $victims = $processedVictims;
                    $updated = true;
                }
            }

            // Process perpetrators - ensure 'id' field is preserved
            if (is_array($perpetrators) && !empty($perpetrators)) {
                $processedPerpetrators = [];
                foreach ($perpetrators as $perpetrator) {
                    // Skip if not an array
                    if (!is_array($perpetrator)) {
                        continue;
                    }

                    // Create a new perpetrator entry with 'id' field explicitly set
                    $perpetratorData = [
                        "title" =>
                            $perpetrator["title"] ??
                            ($perpetrator["label"] ?? null),
                        "label" =>
                            $perpetrator["label"] ??
                            ($perpetrator["title"] ?? null),
                        "description" => $perpetrator["description"] ?? null,
                        "type" => $perpetrator["type"] ?? null,
                        "role" => $perpetrator["role"] ?? "perpetrator",
                        "firstName" => $perpetrator["firstName"] ?? null,
                        "lastName" => $perpetrator["lastName"] ?? null,
                        "birthDate" => $perpetrator["birthDate"] ?? null,
                        "deathDate" => $perpetrator["deathDate"] ?? null,
                        "nationality" => $perpetrator["nationality"] ?? null,
                    ];

                    // Copy the ID field, checking various possible field names
                    if (isset($perpetrator["id"])) {
                        $perpetratorData["wikidata_id"] = $perpetrator["id"];
                        Log::info(
                            "Found perpetrator ID, copying to wikidata_id: {$perpetrator["id"]}",
                        );
                        $updated = true;
                    } elseif (isset($perpetrator["wikidata_id"])) {
                        $perpetratorData["wikidata_id"] =
                            $perpetrator["wikidata_id"];
                        Log::info(
                            "Found perpetrator wikidata_id: {$perpetrator["wikidata_id"]}",
                        );
                        $updated = true;
                    }

                    $processedPerpetrators[] = $perpetratorData;
                }

                // Only update if we have processed perpetrators
                if (!empty($processedPerpetrators)) {
                    $perpetrators = $processedPerpetrators;
                    $updated = true;
                }
            }

            // Update the entry if changes were made
            if ($updated) {
                DB::table("wikidata_entry_requests")
                    ->where("id", $entry->id)
                    ->update([
                        "victims" => json_encode($victims),
                        "perpetrators" => json_encode($perpetrators),
                    ]);

                $updatedCount++;
            }
        }

        Log::info(
            "Migration complete: Fixed victim and perpetrator IDs in {$updatedCount} entries",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as it's a data fix
        Log::info(
            "No action needed for down migration of fix_victim_perpetrator_ids",
        );
    }
};

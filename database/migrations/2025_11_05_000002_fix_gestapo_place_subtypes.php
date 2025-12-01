<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixGestapoPlaceSubtypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $requests = DB::table('wikidata_entry_requests')
            ->where('type', 'Q106996250')
            ->whereNotNull('entity_subtype')
            ->whereNotNull('wikidata_id')
            ->get();

        Log::info("Starting migration to fix GestapoPlace subtypes", [
            'count' => $requests->count(),
        ]);

        foreach ($requests as $request) {
            try {
                // Fetch the current entity data from Wikidata API
                $apiUrl = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids={$request->wikidata_id}&format=json";
                $response = file_get_contents($apiUrl);

                if (!$response) {
                    Log::warning("Failed to fetch entity data for ID: {$request->wikidata_id}");
                    continue;
                }

                $data = json_decode($response, true);

                if (!isset($data['entities'][$request->wikidata_id])) {
                    Log::warning("Entity data not found for ID: {$request->wikidata_id}");
                    continue;
                }

                $entity = $data['entities'][$request->wikidata_id];

                // Check if the entity has P31 claims
                if (!isset($entity['claims']['P31'])) {
                    Log::warning("P31 claims not found for entity: {$request->wikidata_id}");
                    continue;
                }

                // Check if the subtype is already set
                $subtypeExists = false;
                foreach ($entity['claims']['P31'] as $claim) {
                    if (
                        isset($claim['mainsnak']['datavalue']['value']['id']) &&
                        $claim['mainsnak']['datavalue']['value']['id'] === $request->entity_subtype
                    ) {
                        $subtypeExists = true;
                        break;
                    }
                }

                if ($subtypeExists) {
                    Log::info("Subtype already exists for entity: {$request->wikidata_id}");
                    continue;
                }

                // If not, we need to add the subtype as an additional P31 claim
                // For this migration we're just logging the entities that need fixing
                // as actual API updates require authentication
                Log::info("Entity needs subtype update", [
                    'entity_id' => $request->wikidata_id,
                    'subtype' => $request->entity_subtype,
                    'title' => $request->title
                ]);

                // Record entities to update in a separate table for reference
                DB::table('wikidata_entry_requests')
                    ->where('id', $request->id)
                    ->update([
                        'comment' => DB::raw("CONCAT(IFNULL(comment, ''), ' [Entity subtype needs to be added: {$request->entity_subtype}]')")
                    ]);

            } catch (Exception $e) {
                Log::error("Error processing entity {$request->wikidata_id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration only updates comments, nothing to reverse
    }
}

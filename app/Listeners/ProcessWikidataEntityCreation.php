<?php

namespace App\Listeners;

use App\Events\WikidataEntityCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessWikidataEntityCreation implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  WikidataEntityCreated  $event
     * @return void
     */
    public function handle(WikidataEntityCreated $event)
    {
        Log::info('Processing Wikidata entity creation', [
            'entity_id' => $event->entityId,
            'entity_type' => $event->entityType,
            'parent_id' => $event->parentEntityId,
            'relationship' => $event->relationshipType,
            'created_entities_count' => count($event->createdEntities),
        ]);
        
        // You can still send an HTTP callback if needed, but this is more reliable
        // because it can be retried automatically if it fails
        $callbackUrl = config('wikidata.callback_url');
        if ($callbackUrl) {
            try {
                $response = Http::post($callbackUrl, [
                    'entity_id' => $event->entityId,
                    'entity_type' => $event->entityType,
                    'parent_id' => $event->parentEntityId,
                    'relationship' => $event->relationshipType,
                    'created_entities' => $event->createdEntities,
                ]);
                
                if ($response->successful()) {
                    Log::info('Successfully sent external callback notification', [
                        'entity_id' => $event->entityId,
                        'status_code' => $response->status(),
                    ]);
                } else {
                    Log::warning('External callback returned non-success status code', [
                        'entity_id' => $event->entityId,
                        'status_code' => $response->status(),
                        'response' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send external callback notification', [
                    'entity_id' => $event->entityId,
                    'error' => $e->getMessage(),
                ]);
                // Throw the exception to trigger a retry
                throw $e;
            }
        }
        
        // Add here any additional processing needed when an entity is created
        // For example: update a local database, trigger other systems, etc.
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\OrchestrateWikidataEntityCreation;
use Illuminate\Support\Facades\Log;

class WikidataController extends Controller
{
    /**
     * Create a new Wikidata entity using the orchestrated job approach
     *
     * @param Request $request The HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createEntity(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'entity_data' => 'required|array',
            'entity_type' => 'required|string',
            'callback_url' => 'nullable|url',
        ]);

        try {
            // Extract the data from the request
            $entityData = $validated['entity_data'];
            $entityType = $validated['entity_type'];
            $callbackUrl = $validated['callback_url'] ?? null;

            // Create a new request record in the database to track this entity creation
            $requestModel = config(
                "wikidata.request_model",
                "\\App\\Models\\WikidataEntryRequest"
            );
            
            $request = new $requestModel();
            $request->status = 'pending';
            $request->entity_data = json_encode($entityData);
            $request->entity_type = $entityType;
            $request->callback_url = $callbackUrl;
            $request->save();

            // Dispatch the orchestration job
            $job = new OrchestrateWikidataEntityCreation(
                $entityData, 
                $entityType, 
                $callbackUrl,
                $request->id // Pass the request ID for tracking
            );
            
            dispatch($job);

            Log::info("Dispatched orchestrated Wikidata entity creation job", [
                "request_id" => $request->id,
                "entity_type" => $entityType,
            ]);

            // Return a success response with the request ID
            return response()->json([
                'success' => true,
                'message' => 'Entity creation job dispatched successfully',
                'request_id' => $request->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Error dispatching entity creation job", [
                "error" => $e->getMessage(),
            ]);

            // Return an error response
            return response()->json([
                'success' => false,
                'message' => 'Error dispatching entity creation job: ' . $e->getMessage(),
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\WikibaseEntryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EntryStatusController extends Controller
{
    /**
     * Get the status of an entry request by its token.
     * 
     * @param string $token The unique token identifying the entry request
     * @return JsonResponse
     */
    public function getStatus(string $token): JsonResponse
    {
        try {
            // Find the entry request by token
            $entryRequest = WikibaseEntryRequest::where('status_token', $token)->first();
            
            if (!$entryRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Eintrag nicht gefunden. Bitte u00fcberpru00fcfen Sie den Link.'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Prepare the response data
            $responseData = [
                'id' => $entryRequest->id,
                'title' => $entryRequest->title,
                'description' => $entryRequest->description,
                'type' => $entryRequest->type,
                'status' => $entryRequest->status,
                'created_at' => $entryRequest->created_at,
                'status_updated_at' => $entryRequest->status_updated_at,
                'comment' => $entryRequest->comment,
                'status_history' => $entryRequest->status_history,
            ];
            
            // Add Wikidata ID if available
            if ($entryRequest->wikidata_id) {
                $responseData['wikidata_id'] = $entryRequest->wikidata_id;
            }
            
            // Add created entities if available
            if ($entryRequest->created_entities) {
                $responseData['created_entities'] = $entryRequest->created_entities;
            }
            
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
            
        } catch (\Exception $e) {
            Log::error("Entry Status Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Update the status history when a status changes.
     * This is an internal method to be called from other controllers.
     * 
     * @param WikibaseEntryRequest $entryRequest The entry request to update
     * @param string $newStatus The new status
     * @param string|null $comment Optional comment about the status change
     * @return void
     */
    public function updateStatusHistory(WikibaseEntryRequest $entryRequest, string $newStatus, ?string $comment = null): void
    {
        // Get current status history or initialize if empty
        $history = $entryRequest->status_history ?? [];
        
        // Add new status entry
        $history[] = [
            'status' => $newStatus,
            'date' => now(),
            'comment' => $comment
        ];
        
        // Update the entry
        $entryRequest->status = $newStatus;
        $entryRequest->status_history = $history;
        $entryRequest->status_updated_at = now();
        $entryRequest->comment = $comment;
        $entryRequest->save();
    }
    
    /**
     * Static method to update status history from anywhere in the application
     * 
     * @param WikibaseEntryRequest $entryRequest The entry request to update
     * @param string $newStatus The new status
     * @param string|null $comment Optional comment about the status change
     * @return void
     */
    public static function updateStatusHistoryStatic(WikibaseEntryRequest $entryRequest, string $newStatus, ?string $comment = null): void
    {
        // Get current status history or initialize if empty
        $history = $entryRequest->status_history ?? [];
        
        // Add new status entry
        $history[] = [
            'status' => $newStatus,
            'date' => now(),
            'comment' => $comment
        ];
        
        // Update the entry
        $entryRequest->status = $newStatus;
        $entryRequest->status_history = $history;
        $entryRequest->status_updated_at = now();
        $entryRequest->comment = $comment;
        $entryRequest->save();
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Wikidata\EntityFactory;
use App\Models\Wikidata\Event;
use App\Models\Wikidata\Person;
use App\Models\Wikidata\Victim;
use App\Models\Wikidata\Perpetrator;
use App\Models\Wikidata\Source\SourceFactory;
use App\Services\Wikidata\WikidataFormatterModels;
use App\Services\Wikidata\WikidataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WikidataModelExampleController extends Controller
{
    /**
     * Example of how to use the new Wikidata entity models directly
     */
    public function createEventWithModels(Request $request)
    {
        try {
            // Get data from request
            $data = $request->all();
            
            // Create an event entity using our model
            $event = Event::fromArray([
                'title' => $data['title'] ?? 'New Event',
                'description' => $data['description'] ?? 'Historical event',
                'time_point' => $data['time_point'] ?? null,
                'time_point_qualifier' => $data['time_point_qualifier'] ?? null,
                'coordinates' => $data['coordinates'] ?? null,
                'victim_count' => $data['victim_count'] ?? null,
                'death_count' => $data['death_count'] ?? null,
            ]);
            
            // Add a perpetrator
            if (!empty($data['perpetrator'])) {
                $perpetrator = Perpetrator::fromArray([
                    'name' => $data['perpetrator']['name'] ?? 'Unknown Perpetrator',
                    'description' => $data['perpetrator']['description'] ?? 'Person involved in event',
                    'date_of_birth' => $data['perpetrator']['date_of_birth'] ?? null,
                    'occupation' => $data['perpetrator']['occupation'] ?? null,
                ]);
                
                // In a complete implementation, we would:
                // 1. Create the perpetrator entity in Wikidata
                // 2. Get its ID
                // 3. Link it to the event
                
                // For this example, we'll just get the formatted data
                $perpetratorData = $perpetrator->getFormattedData();
            }
            
            // Add a victim
            if (!empty($data['victim'])) {
                $victim = Victim::fromArray([
                    'name' => $data['victim']['name'] ?? 'Unknown Victim',
                    'description' => $data['victim']['description'] ?? 'Victim of event',
                    'date_of_birth' => $data['victim']['date_of_birth'] ?? null,
                    'date_of_death' => $data['victim']['date_of_death'] ?? null,
                ]);
                
                // Get the formatted data
                $victimData = $victim->getFormattedData();
            }
            
            // Process sources if available
            $sources = [];
            if (!empty($data['sources'])) {
                $sources = SourceFactory::createMultipleFromArray($data['sources']);
                
                // In a complete implementation, we would add these sources to the event
            }
            
            // Get the formatted data for Wikidata
            $formattedData = $event->getFormattedData();
            
            // In a real implementation, we would now create the entity in Wikidata
            // using WikidataService::createEntity()
            
            return response()->json([
                'success' => true,
                'message' => 'Event data processed successfully',
                'data' => [
                    'event' => $formattedData,
                    'perpetrator' => $perpetratorData ?? null,
                    'victim' => $victimData ?? null,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing event with models', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing event: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Example of how to use the WikidataFormatterModels service
     */
    public function createEventWithFormatterModels(Request $request)
    {
        try {
            // Get data from request
            $data = $request->all();
            
            // Use the new formatter model implementation
            $formatter = app(WikidataFormatterModels::class);
            
            // Format the entity data using our new implementation
            $result = $formatter->formatEntityData(
                $data,
                WikidataFormatterModels::ENTITY_TYPE_EVENT,
                false // Don't create entities yet (preview mode)
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Event data processed successfully with formatter models',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing event with formatter models', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing event: ' . $e->getMessage(),
            ], 500);
        }
    }
}
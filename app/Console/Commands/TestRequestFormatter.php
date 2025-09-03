<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WikibaseEntryRequest;
use App\Services\Wikibase\WikibaseFormatterModels;
use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;

class TestRequestFormatter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:request-formatter {id? : The ID of the request to format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test formatting a real request';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(EntityMappingProviderInterface $mappingProvider)
    {
        $this->info("Testing WikibaseFormatterModels with a real request...");
        
        // Get a request
        $id = $this->argument('id');
        if ($id) {
            $request = WikibaseEntryRequest::find($id);
            if (!$request) {
                $this->error("Request with ID {$id} not found.");
                return 1;
            }
        } else {
            $request = WikibaseEntryRequest::first();
            if (!$request) {
                $this->error("No requests found in the database.");
                return 1;
            }
        }
        
        $this->info("Using request ID: {$request->id}, Type: {$request->type}");
        
        // Create a formatter
        $formatter = new WikibaseFormatterModels($mappingProvider);
        
        // Debug mode off
        $formatter->setDebugMode(false);
        
        // Extract request data
        $requestData = $request->toArray();
        $excludeKeys = ['id', 'created_at', 'updated_at', 'status', 'submitted_by', 'approved_by', 'rejection_reason', 'error_message', 'wikidata_id', 'wikidata_url'];
        $filteredData = [];
        
        foreach ($requestData as $key => $value) {
            if (!in_array($key, $excludeKeys) && !is_null($value)) {
                $filteredData[$key] = $value;
            }
        }
        
        // Format the data
        $result = $formatter->formatEntityData($filteredData, $request->type, false);
        
        // Check the result
        $this->info("\nFormatted data claims count: " . count($result['formattedData']['claims']));
        $this->info("Claim properties:");
        
        foreach ($result['formattedData']['claims'] as $property => $claims) {
            $this->info("  - {$property}");
        }
        
        // Pretty print the entire result
        $this->info("\nFull formatted data:");
        $this->info(json_encode($result['formattedData'], JSON_PRETTY_PRINT));
        
        return 0;
    }
}
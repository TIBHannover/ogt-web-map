<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Wikibase\WikibaseFormatterModels;
use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;

class TestFormatter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:formatter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the WikibaseFormatterModels class';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(EntityMappingProviderInterface $mappingProvider)
    {
        $this->info("Testing WikibaseFormatterModels...");
        
        // Print the current export target
        $this->info("Current export target: " . config('export.target'));
        
        // Test some property mappings
        $this->info("INSTANCE_OF property: " . $mappingProvider->getPropertyId('INSTANCE_OF'));
        $this->info("GENDER property: " . $mappingProvider->getPropertyId('GENDER'));
        $this->info("BIRTH_DATE property: " . $mappingProvider->getPropertyId('BIRTH_DATE'));
        
        // Create a formatter
        $formatter = new WikibaseFormatterModels($mappingProvider);
        
        // Debug mode off
        $formatter->setDebugMode(false);
        
        // Create a test event entity
        $testData = [
            'title' => 'Test Event with more properties',
            'description' => 'A test event entity with additional properties',
            'type' => $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Event'),
            'date' => '1942-05-15',
            'coordinates' => json_encode(['lat' => 52.5, 'lng' => 13.4]),
            'selected_causes' => json_encode([['id' => 'Q123', 'label' => 'Test Cause']]),
            'victim_count' => 5,
            'death_count' => 2,
            'survivor_count' => 3,
            'sources' => json_encode([
                [
                    'title' => 'Test Source Book',
                    'type' => 'book',
                    'author' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'temp_id' => 'author1'
                    ],
                    'page' => '42',
                    'year' => '1980'
                ],
                [
                    'title' => 'Test Source URL',
                    'type' => 'url',
                    'url' => 'https://example.com',
                    'access_date' => '2023-05-15'
                ]
            ]),
            'victims' => json_encode([
                [
                    'name' => 'Test Victim',
                    'firstName' => 'Victim',
                    'lastName' => 'Test',
                    'birthDate' => '1910-01-01',
                    'deathDate' => '1943-02-15',
                    'gender' => ['id' => 'Q6581097', 'label' => 'male'],
                    'role' => 'victim',
                    'temp_id' => 'victim1'
                ]
            ]),
            'perpetrators' => json_encode([
                [
                    'name' => 'Test Perpetrator',
                    'firstName' => 'Perpetrator',
                    'lastName' => 'Test',
                    'birthDate' => '1900-01-01',
                    'gender' => ['id' => 'Q6581097', 'label' => 'male'],
                    'role' => 'perpetrator',
                    'temp_id' => 'perp1'
                ]
            ])
        ];
        
        // Format the data
        $result = $formatter->formatEntityData($testData, $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Event'), false);
        
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
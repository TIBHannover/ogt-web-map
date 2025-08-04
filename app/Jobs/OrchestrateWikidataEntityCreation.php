<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use App\Services\EntityMapping\EntityMappingProviderInterface;

class OrchestrateWikidataEntityCreation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of attempts for this job
     */
    public $tries = 3;

    /**
     * Maximum number of exceptions to allow before failing
     */
    public $maxExceptions = 1;

    /**
     * Entity data to be created
     */
    protected $entityData;

    /**
     * Entity type
     */
    protected $entityType;

    /**
     * Callback URL to notify when complete (optional)
     */
    protected $callbackUrl;

    /**
     * Request ID in the database
     */
    protected $requestId;

    /**
     * Create a new job instance.
     *
     * @param array $entityData The entity data to create
     * @param string $entityType The entity type (Q-number)
     * @param string|null $callbackUrl Optional URL to notify when complete
     * @param int|null $requestId Optional database ID of the request
     */
    public function __construct(
        array $entityData,
        string $entityType,
        ?string $callbackUrl = null,
        ?int $requestId = null
    ) {
        $this->entityData = $entityData;
        $this->entityType = $entityType;
        $this->callbackUrl = $callbackUrl;
        $this->requestId = $requestId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info("Starting orchestrated Wikidata entity creation", [
                "type" => $this->entityType,
                "title" => $this->entityData["title"] ?? "Unknown",
                "has_victims" => !empty($this->entityData["victims"]),
                "has_perpetrators" => !empty($this->entityData["perpetrators"]),
                "has_sources" => !empty($this->entityData["sources"]) || !empty($this->entityData["books"]),
            ]);

            // If we have victims or perpetrators, make sure we're not using placeholders
            // since we'll be creating the actual entities
            if (!empty($this->entityData['victims']) || !empty($this->entityData['perpetrators'])) {
                // Remove any placeholder text for victim/perpetrator names since we'll create real entities
                if (isset($this->entityData['P2093'])) {
                    // Just remove the P2093 claim without affecting other data
                    Log::info("Removing P2093 placeholder claim since we're creating actual entities", [
                        "title" => $this->entityData['title'] ?? 'Unknown',
                        "preserved_labels" => isset($this->entityData['labels']),
                        "preserved_descriptions" => isset($this->entityData['descriptions'])
                    ]);
                    unset($this->entityData['P2093']);
                }
                
                // Also remove the claim from the claims array if it exists there
                if (isset($this->entityData['claims']) && isset($this->entityData['claims']['P2093'])) {
                    Log::info("Removing P2093 from claims array", [
                        "title" => $this->entityData['title'] ?? 'Unknown'
                    ]);
                    unset($this->entityData['claims']['P2093']);
                }
            }

            // Create the main entity first
            $mainEntityJob = new CreateWikidataEntity(
                $this->entityData,
                $this->entityType,
                $this->callbackUrl,
                null, // No parent ID for the main entity
                null, // No relationship type for the main entity
                $this->requestId
            );

            // Prepare child jobs to be chained after the main entity
            $childJobs = [];

            // Extract and prepare related entities for separate creation
            $childJobs = array_merge($childJobs, $this->prepareRelatedPersonJobs('victims'));
            $childJobs = array_merge($childJobs, $this->prepareRelatedPersonJobs('perpetrators'));
            $childJobs = array_merge($childJobs, $this->prepareRelatedSourceJobs());

            // If we have child jobs, use job chaining
            if (!empty($childJobs)) {
                // In Laravel 8+ we can use the chain method directly on the job
                $mainEntityJob->chain($childJobs);
                Bus::dispatch($mainEntityJob);

                Log::info("Dispatched main entity job with chained related entities", [
                    "related_entities_count" => count($childJobs),
                ]);
            } else {
                // If no related entities, just dispatch the main job
                dispatch($mainEntityJob);

                Log::info("Dispatched main entity job without related entities");
            }
        } catch (\Exception $e) {
            Log::error("Exception in orchestration job", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to allow Laravel to handle the job failure
        }
    }

    /**
     * Prepare jobs for related person entities (victims or perpetrators)
     *
     * @param string $type The type of related entities ('victims' or 'perpetrators')
     * @return array Array of jobs
     */
    protected function prepareRelatedPersonJobs(string $type): array
    {
        $jobs = [];

        if (!empty($this->entityData[$type]) && is_array($this->entityData[$type])) {
            Log::info("Processing {$type} data for job chain", [
                "count" => count($this->entityData[$type])
            ]);

            foreach ($this->entityData[$type] as $index => $personData) {
                // Skip if this person already has a Wikidata ID and doesn't need creation
                if (!empty($personData['id']) && substr($personData['id'], 0, 1) === 'Q' && 
                    empty($personData['_forceCreate'])) {
                    Log::info("Skipping {$type} entity with existing ID", [
                        "id" => $personData['id'],
                        "name" => $personData['name'] ?? $personData['title'] ?? 'Unknown'
                    ]);
                    continue;
                }
                
                // Log what data we're sending to the enrichment function
                Log::info("Processing {$type} entity before enrichment", [
                    "name" => $personData['name'] ?? 'Not set',
                    "title" => $personData['title'] ?? 'Not set',
                    "firstName" => $personData['firstName'] ?? 'Not set',
                    "lastName" => $personData['lastName'] ?? 'Not set',
                    "description" => $personData['description'] ?? 'Not set',
                    "has_name" => !empty($personData['name']),
                    "has_title" => !empty($personData['title']),
                    "data_keys" => array_keys($personData)
                ]);
                
                // Validate and enrich the person data
                $enrichedPersonData = $this->enrichPersonData($personData, $type);
                
                // Get entity type ID from mapping provider
                $mappingProvider = app(EntityMappingProviderInterface::class);
                $personEntityTypeId = $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Person');
                
                // Create a CreateWikidataEntity job for this person
                // We're setting the MainEntityIdPlaceholder which will be replaced with the actual
                // entity ID in the CreateWikidataEntity job after the main entity is created
                $personJob = new CreateWikidataEntity(
                    $enrichedPersonData,
                    $personEntityTypeId, // Always use Person type for victims/perpetrators
                    null, // No callback for child entities
                    '__MAIN_ENTITY_ID__', // This is a placeholder that will be replaced with the actual ID
                    $type === 'victims' ? 'victim' : 'perpetrator', // Relationship type
                    null // No request ID for child entities
                );

                Log::info("Added {$type} entity to job chain", [
                    "name" => $enrichedPersonData['name'] ?? 'Unknown',
                    "title" => $enrichedPersonData['title'] ?? 'Unknown',
                    "role" => $enrichedPersonData['role'] ?? 'Unknown',
                    "has_role_field" => isset($enrichedPersonData['role']),
                    "relationship" => $type === 'victims' ? 'victim' : 'perpetrator',
                    "keys" => array_keys($enrichedPersonData),
                    "enriched" => true
                ]);

                $jobs[] = $personJob;
            }
        }

        return $jobs;
    }

    /**
     * Prepare jobs for related source entities (books or other sources)
     *
     * @return array Array of jobs
     */
    protected function prepareRelatedSourceJobs(): array
    {
        $jobs = [];
        $booksToProcess = [];
        
        // Combine books from both single and multiple book entries
        if (!empty($this->entityData['book']) && is_array($this->entityData['book'])) {
            $booksToProcess[] = $this->entityData['book'];
        }
        
        if (!empty($this->entityData['books']) && is_array($this->entityData['books'])) {
            $booksToProcess = array_merge($booksToProcess, $this->entityData['books']);
        }
        
        // Process all books - only create jobs for new books without Wikidata IDs
        if (!empty($booksToProcess)) {
            Log::info("Processing books data for job chain", [
                "count" => count($booksToProcess)
            ]);
            
            foreach ($booksToProcess as $book) {
                // Only create jobs for manual books without Wikidata IDs or forced creation
                $needsCreation = empty($book['id']) || $book['id'] === 'manual' || 
                                 (substr($book['id'], 0, 1) !== 'Q') || 
                                 !empty($book['_forceCreate']);
                                 
                if ($needsCreation) {
                    // Enrich the book data
                    $enrichedBook = $this->enrichSourceData($book, 'book');
                    
                    // Get book entity type ID
                    $mappingProvider = app(EntityMappingProviderInterface::class);
                    $bookEntityTypeId = $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Book');

                    $bookJob = new CreateWikidataEntity(
                        $enrichedBook,
                        $bookEntityTypeId,
                        null, // No callback for child entities
                        '__MAIN_ENTITY_ID__', // Placeholder for the main entity ID
                        'source', // Relationship type
                        null // No request ID for child entities
                    );

                    Log::info("Added book entity to job chain", [
                        "title" => $enrichedBook['title'] ?? 'Unknown',
                        "enriched" => true
                    ]);

                    $jobs[] = $bookJob;
                } else {
                    // For existing books, we don't add claims here - the WikidataFormatter will handle that
                    Log::info("Using existing book, will be processed by WikidataFormatter", [
                        "id" => $book['id'],
                        "title" => $book['title'] ?? $book['name'] ?? 'Unknown'
                    ]);
                }
            }
        }

        // Process all sources in the sources array
        if (!empty($this->entityData['sources']) && is_array($this->entityData['sources'])) {
            Log::info("Processing sources data for job chain", [
                "count" => count($this->entityData['sources'])
            ]);
            
            foreach ($this->entityData['sources'] as $source) {
                if (empty($source['type'])) {
                    Log::warning("Source is missing type, skipping", [
                        "title" => $source['title'] ?? $source['name'] ?? 'Unknown'
                    ]);
                    continue;
                }
                
                // We only need to create new book entities
                // URL and archive sources will be handled by the WikidataFormatter
                if (strtolower($source['type']) === 'book') {
                    // Only create jobs for manual books without Wikidata IDs or forced creation
                    $needsCreation = empty($source['id']) || $source['id'] === 'manual' || 
                                    (substr($source['id'], 0, 1) !== 'Q') || 
                                    !empty($source['_forceCreate']);
                                    
                    if ($needsCreation) {
                        // Enrich the book data
                        $enrichedSource = $this->enrichSourceData($source, 'book');
                        
                        // Get book entity type ID
                        $mappingProvider = app(EntityMappingProviderInterface::class);
                        $bookEntityTypeId = $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Book');

                        $sourceJob = new CreateWikidataEntity(
                            $enrichedSource,
                            $bookEntityTypeId,
                            null, // No callback for child entities
                            '__MAIN_ENTITY_ID__', // Placeholder for the main entity ID
                            'source', // Relationship type
                            null // No request ID for child entities
                        );

                        Log::info("Added book source entity to job chain", [
                            "title" => $enrichedSource['title'] ?? 'Unknown'
                        ]);

                        $jobs[] = $sourceJob;
                    } else {
                        // For existing books, the WikidataFormatter will handle adding the claims
                        Log::info("Using existing book source, will be processed by WikidataFormatter", [
                            "id" => $source['id'],
                            "title" => $source['title'] ?? $source['name'] ?? 'Unknown'
                        ]);
                    }
                } else {
                    // For URL and archive sources, the WikidataFormatter will handle them
                    Log::info("Source of type '{$source['type']}' will be processed by WikidataFormatter", [
                        "title" => $source['title'] ?? $source['name'] ?? 'Unknown'
                    ]);
                }
            }
        }

        return $jobs;
    }
    

    
    /**
     * Enrich person data with required properties
     * 
     * @param array $personData The original person data
     * @param string $type The relationship type ('victims' or 'perpetrators')
     * @return array The enriched person data
     */
    protected function enrichPersonData(array $personData, string $type): array
    {
        // Start with all the original person data
        $enriched = $personData;
        
        // Standardize date fields (support multiple naming conventions)
        if (empty($enriched['date_of_birth']) && !empty($enriched['birthDate'])) {
            $enriched['date_of_birth'] = $enriched['birthDate'];
        }
        
        if (empty($enriched['date_of_death']) && !empty($enriched['deathDate'])) {
            $enriched['date_of_death'] = $enriched['deathDate'];
        }
        
        // Determine the name from available fields if not already set
        if (empty($enriched['name'])) {
            if (!empty($enriched['firstName']) || !empty($enriched['lastName'])) {
                $enriched['name'] = trim(($enriched['firstName'] ?? '') . ' ' . ($enriched['lastName'] ?? ''));
            } elseif (!empty($enriched['title'])) {
                $enriched['name'] = $enriched['title'];
            } else {
                // Set a default name based on the relationship type
                $enriched['name'] = $type === 'victims' ? 'Unknown victim' : 'Unknown perpetrator';
            }
        }
        
        // Make sure we have a title - THIS IS CRITICAL FOR LABELS
        if (empty($enriched['title'])) {
            $enriched['title'] = $enriched['name'];
        }
        
        // Make sure we have a description
        if (empty($enriched['description'])) {
            if ($type === 'victims') {
                $enriched['description'] = 'Victim of ' . ($this->entityData['title'] ?? 'historical event');
            } else {
                $enriched['description'] = 'Perpetrator of ' . ($this->entityData['title'] ?? 'historical event');
            }
        }
        
        // Get person entity type from mapping provider
        $mappingProvider = app(EntityMappingProviderInterface::class);
        
        // Always set entity type to Person - don't inherit parent entity type
        $enriched['type'] = $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Person');
        
        // Set proper role based on the relationship type
        $enriched['role'] = $type === 'victims' ? 'victim' : 'perpetrator';
        
        // Add control flags for entity creation process
        $enriched['_ensure_correct_P31'] = true;   // Flag to ensure no duplicate P31 claims
        $enriched['_inChain'] = true;              // Prevent recursive job creation
        $enriched['_add_role_claim'] = true;       // Ensure role claim is created
        $enriched['_add_significant_event'] = true; // Ensure significant event claim is created
        
        // Pass sources from the main entity to person entities
        if (!empty($this->entityData['sources'])) {
            $enriched['sources'] = $this->entityData['sources'];
            Log::info("Passing sources from main entity to person entity", [
                "person_name" => $enriched['name'],
                "sources_count" => count($this->entityData['sources']),
                "first_source" => !empty($this->entityData['sources'][0]) ? 
                    ($this->entityData['sources'][0]['title'] ?? 'Unknown') : 'None'
            ]);
        }
        
        // When the main entity is created, add it as a significant event for this person
        $enriched['significant_event_id'] = '__MAIN_ENTITY_ID__';
        
        // Log what data we're sending for the person entity
        Log::info("Enriched person data for entity creation", [
            "name" => $enriched['name'],
            "title" => $enriched['title'],
            "role" => $enriched['role'],
            "type" => $enriched['type'],
            "has_birthdate" => isset($enriched['date_of_birth']),
            "has_deathdate" => isset($enriched['date_of_death']),
            "field_count" => count($enriched),
            "key_fields" => array_intersect_key($enriched, array_flip(['name', 'title', 'description', 'type', 'role', 'date_of_birth', 'date_of_death', 'gender', 'place_of_birth', 'nationality']))
        ]);
        
        return $enriched;
    }


    
    /**
     * Enrich source data with required properties
     * 
     * @param array $sourceData The original source data
     * @param string $type The source type ('book', 'newspaper', etc.)
     * @return array The enriched source data
     */
    protected function enrichSourceData(array $sourceData, string $type): array
    {
        // Make a copy to avoid modifying the original data
        $enriched = $sourceData;

        // Make sure we have at least a title property
        if (empty($enriched['title'])) {
            if (!empty($enriched['name'])) {
                $enriched['title'] = $enriched['name'];
            } else {
                // Set a default title based on the source type
                $enriched['title'] = ucfirst($type) . ' about ' . ($this->entityData['title'] ?? 'historical event');
            }
        }

        // Ensure we have a name if we have a title
        if (empty($enriched['name']) && !empty($enriched['title'])) {
            $enriched['name'] = $enriched['title'];
        }

        // Make sure we have a description
        if (empty($enriched['description'])) {
            if ($type === 'book') {
                $enriched['description'] = 'Book about ' . ($this->entityData['title'] ?? 'historical events');
            } else if (in_array($type, ['newspaper', 'magazine', 'journal'])) {
                $enriched['description'] = ucfirst($type) . ' publication with information about ' . 
                    ($this->entityData['title'] ?? 'historical events');
            } else if ($type === 'website') {
                $enriched['description'] = 'Website with information about ' . 
                    ($this->entityData['title'] ?? 'historical events');
            } else {
                $enriched['description'] = 'Source with information about ' . 
                    ($this->entityData['title'] ?? 'historical events');
            }
        }

        // Flag to ensure no duplicate P31 claims
        $enriched['_ensure_correct_P31'] = true;

        // Add an _inChain flag to prevent recursive job creation
        $enriched['_inChain'] = true;
        
        // When the main entity is created, connect this source to it
        // Use the significant event property for books/sources about the event
        $enriched['significant_event_id'] = '__MAIN_ENTITY_ID__';

        // Add title as text (P1476) if not already present
        if (!isset($enriched['P1476']) && !empty($enriched['title'])) {
            $enriched['P1476'] = [
                [
                    'text' => $enriched['title'],
                    'language' => 'de', // Default to German
                    'type' => 'monolingualtext'
                ]
            ];
        }

        return $enriched;
    }
}
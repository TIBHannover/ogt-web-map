<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Wikibase\WikibaseService;
use App\Services\Wikibase\WikibaseFormatterModels;
use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus; // Add this for job chaining

class CreateWikidataEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Default model class for request tracking
     */
    const DEFAULT_REQUEST_MODEL = "\\App\\Models\\WikibaseEntryRequest";

    /**
     * Number of attempts for this job
     */
    public $tries = 10;

    /**
     * Maximum number of exceptions to allow before failing
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Uses exponential backoff: 30s, 60s, 120s, 240s, 480s
     */
    public function backoff()
    {
        return [30, 60, 120, 240, 480];
    }

    /**
     * Request ID in the database
     */
    protected $requestId;

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
     * Parent entity ID if this entity should be linked to another entity when created
     */
    protected $parentEntityId;

    /**
     * Relationship type if this is a child entity (e.g., "source", "perpetrator", "victim")
     */
    protected $relationshipType;

    /**
     * Create an entity with related entities using job chains
     *
     * This static method creates a main entity and chains jobs to create its related entities
     * after the main entity has been created. This ensures that child entities can correctly
     * reference their parent.
     *
     * Instead of letting the formatter create related entities, each entity is created by its own
     * dedicated job in the chain. This approach is more reliable and avoids race conditions.
     *
     * @param array $entityData Data for the main entity
     * @param string $entityType Type of the main entity
     * @param string|null $callbackUrl Optional callback URL
     * @param int|null $requestId Optional request ID for database tracking
     * @return void
     */
    public static function createEntityWithRelated(
        array $entityData,
        string $entityType,
        ?string $callbackUrl = null,
        ?int $requestId = null
    ): void {
        // First, log that we're starting this method with a unique identifier to find in logs
        $uniqueId = uniqid('chain_', true);
        Log::info("*** IMPORTANT: createEntityWithRelated STARTED ***", [
            "chain_id" => $uniqueId,
            "entity_type" => $entityType,
            "title" => $entityData['title'] ?? 'Untitled',
            "request_id" => $requestId,
            "backtrace" => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
        
        // Extract the related entities before creating the main entity
        $victims = $entityData['victims'] ?? [];
        $perpetrators = $entityData['perpetrators'] ?? [];
        $sources = $entityData['sources'] ?? [];
        $books = [];
        
        // Handle books in both formats (books array or single book)
        if (!empty($entityData['books']) && is_array($entityData['books'])) {
            $books = $entityData['books'];
        } elseif (!empty($entityData['book']) && is_array($entityData['book'])) {
            $books[] = $entityData['book'];
        }
        
        // Log what we're going to process with detailed information
        $relatedCount = count($victims) + count($perpetrators) + count($sources) + count($books);
        Log::info("createEntityWithRelated: Creating entity with related entities", [
            "main_entity_type" => $entityType,
            "title" => $entityData['title'] ?? 'Untitled',
            "related_entities" => $relatedCount,
            "victims" => count($victims),
            "victims_data" => array_map(function($v) { return $v['name'] ?? $v['title'] ?? 'Unknown'; }, $victims),
            "perpetrators" => count($perpetrators),
            "perpetrators_data" => array_map(function($p) { return $p['name'] ?? $p['title'] ?? 'Unknown'; }, $perpetrators),
            "sources" => count($sources),
            "books" => count($books),
            "request_id" => $requestId
        ]);
        
        // Create the main entity job
        $entityData['_inChain'] = true; // Add flag to prevent recursive chain creation
        $mainEntityJob = new self($entityData, $entityType, $callbackUrl, null, null, $requestId);
        
        // Prepare the chain of jobs for related entities
        $chainedJobs = [];
        
        // Helper function to create jobs for each related entity type
        $createRelatedJobs = function($entities, $entityType, $relationshipType) use (&$chainedJobs) {
            Log::info("createEntityWithRelated: Processing entities for job chain", [
                "entity_type" => $entityType,
                "relationship" => $relationshipType,
                "count" => count($entities),
                "entities" => array_map(function($e) {
                    return [
                        'name' => $e['name'] ?? $e['title'] ?? 'Unknown',
                        'id' => $e['id'] ?? 'new',
                        'has_temp_id' => !empty($e['tempId']) || !empty($e['temp_id'])
                    ];
                }, $entities)
            ]);
    
            foreach ($entities as $entity) {
                // Skip entities that already have Wikidata IDs and don't need creation
                if (!empty($entity['id']) && substr($entity['id'], 0, 1) === 'Q' && 
                    empty($entity['_forceCreate']) && empty($entity['tempId']) && empty($entity['temp_id'])) {
                    Log::info("createEntityWithRelated: Skipping entity with existing ID", [
                        "id" => $entity['id'],
                        "name" => $entity['name'] ?? $entity['title'] ?? 'Unknown'
                    ]);
                    continue;
                }
        
                // Make sure we have a title for the entity
                if (empty($entity['title']) && !empty($entity['name'])) {
                    $entity['title'] = $entity['name'];
                }

                // Remove any flags that might confuse the formatter
                unset($entity['_forceCreate']);
                unset($entity['_inQueueWorker']);

                // Add flag to prevent recursive chain creation
                $entity['_inChain'] = true;
                
                // Add flag to disable P2093 placeholder claims
                $entity['_skipPlaceholderClaims'] = true;

                // Create a job for this related entity that will run after the main entity
                // Always use the main entity as parent, not a previous entity in the chain
                $childJob = new self(
                    $entity,
                    $entityType,
                    null, // No callback for child entities
                    '__MAIN_ENTITY_ID__', // Special placeholder for the parent ID
                    $relationshipType,
                    null // No request ID for child entities
                );
        
                Log::info("createEntityWithRelated: Added entity to job chain", [
                    "name" => $entity['name'] ?? $entity['title'] ?? 'Unknown',
                    "type" => $entityType,
                    "relationship" => $relationshipType,
                    "temp_id" => $entity['tempId'] ?? $entity['temp_id'] ?? null
                ]);
        
                $chainedJobs[] = $childJob;
            }
        };
        // Get mapping provider for entity types
        $mappingProvider = app(EntityMappingProviderInterface::class);
        
        // Create jobs for each type of related entity
        $createRelatedJobs($victims, $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Person'), 'victim');
        $createRelatedJobs($perpetrators, $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Person'), 'perpetrator');
        
        // Create jobs for sources - link to main entity, not to a victim or perpetrator
        $createRelatedJobs($sources, $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Book'), 'source');
        
        // If we have related entities, create a job chain
        if (!empty($chainedJobs)) {
            // Log the job chain creation
            Log::info("Creating job chain for entity creation", [
                "main_entity_type" => $entityType,
                "title" => $entityData['title'] ?? 'Untitled',
                "related_entities" => count($chainedJobs),
                "chain_type" => "Bus::chain"
            ]);
            
            // Create the job chain: main entity job followed by all child entity jobs
            Log::info("Dispatching job chain with " . count($chainedJobs) . " related entities", [
                "chain_length" => count($chainedJobs) + 1,
                "entity_type" => $entityType,
                "child_types" => array_map(function($job) {
                    return $job->entityType ?? 'unknown';
                }, $chainedJobs),
                "chain_method" => "Bus::chain"
            ]);
            
            try {
                // Using Bus::chain for the job chain
                $chain = Bus::chain(array_merge([$mainEntityJob], $chainedJobs));
                $chain->dispatch();
                
                // Log success
                Log::info("Successfully dispatched job chain", [
                    "chain_length" => count($chainedJobs) + 1,
                    "main_entity" => $entityData['title'] ?? 'Untitled'
                ]);
            } catch (\Exception $e) {
                // Log any errors with dispatching the chain
                Log::error("Error dispatching job chain", [
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString()
                ]);
                
                // As a fallback, dispatch the main job directly
                Log::info("Falling back to dispatching main job only");
                $mainEntityJob->dispatch();
            }
        } else {
            // If no related entities, just dispatch the main entity job
            Log::info("No related entities to create, dispatching single job", [
                "main_entity_type" => $entityType,
                "title" => $entityData['title'] ?? 'Untitled'
            ]);
            $mainEntityJob->dispatch();
        }
        
        // Log completion of the method with the same unique ID for tracing
        Log::info("*** IMPORTANT: createEntityWithRelated COMPLETED ***", [
            "chain_id" => $uniqueId,
            "entity_type" => $entityType,
            "title" => $entityData['title'] ?? 'Untitled',
            "related_entities_count" => count($chainedJobs)
        ]);
    }

    /**
     * Create a new job instance.
     *
     * @param array $entityData The entity data to create
     * @param string $entityType The entity type (Q-number)
     * @param string|null $callbackUrl Optional URL to notify when complete
     * @param string|null $parentEntityId Optional parent entity ID to link to when created
     * @param string|null $relationshipType Optional relationship type to the parent
     * @param int|null $requestId Optional database ID of the request
     */
    public function __construct(
        array $entityData,
        string $entityType,
        ?string $callbackUrl = null,
        ?string $parentEntityId = null,
        ?string $relationshipType = null,
        ?int $requestId = null
    ) {
        $this->entityData = $entityData;
        $this->entityType = $entityType;
        $this->callbackUrl = $callbackUrl;
        $this->parentEntityId = $parentEntityId;
        $this->relationshipType = $relationshipType;
        $this->requestId = $requestId;
    }

    /**
     * Execute the job.
     *
     * @param WikibaseService $wikidataService
     * @param WikibaseFormatterModels $formatterModels
     * @return void
     */
    public function handle(
        WikibaseService $wikidataService,
        WikibaseFormatterModels $formatterModels
    ) {
        try {
            // Define a global constant to mark that we're in a queue worker
            if (!defined('LARAVEL_QUEUE_WORKER')) {
                define('LARAVEL_QUEUE_WORKER', true);
            }
            
            // Check if we need to replace the parent entity ID placeholder with actual ID
            // This is for child entities that were chained after the main entity was created
            if ($this->parentEntityId === '__MAIN_ENTITY_ID__' && isset($GLOBALS['last_created_entity_id'])) {
                $this->parentEntityId = $GLOBALS['last_created_entity_id'];
                Log::info("Replaced parent entity ID placeholder with actual ID", [
                    "parent_id" => $this->parentEntityId,
                    "entity_type" => $this->entityType,
                    "relationship" => $this->relationshipType
                ]);
            }

            // ***** AUTO-DETECT RELATED ENTITIES *****
            // If this is a main entity (no parent ID) and we have related entities,
            // we should use job chains instead of trying to create them directly in the formatter
            if (empty($this->parentEntityId) && empty($this->entityData['_inChain'])) {
                $hasVictims = !empty($this->entityData['victims']) && is_array($this->entityData['victims']);
                $hasPerpetrators = !empty($this->entityData['perpetrators']) && is_array($this->entityData['perpetrators']);
                $hasSources = !empty($this->entityData['sources']) && is_array($this->entityData['sources']);
                $hasBooks = (!empty($this->entityData['books']) && is_array($this->entityData['books'])) || 
                           (!empty($this->entityData['book']) && is_array($this->entityData['book']));
                
                if ($hasVictims || $hasPerpetrators || $hasSources || $hasBooks) {
                    // We're a main entity with related entities - use job chains
                    Log::info("Auto-detected related entities - using job chain approach", [
                        "type" => $this->entityType,
                        "victims" => $hasVictims ? count($this->entityData['victims']) : 0,
                        "perpetrators" => $hasPerpetrators ? count($this->entityData['perpetrators']) : 0,
                        "sources" => $hasSources ? count($this->entityData['sources']) : 0,
                        "has_books" => $hasBooks
                    ]);
                    
                    // Use our static method to create entity with related entities
                    self::createEntityWithRelated(
                        $this->entityData,
                        $this->entityType,
                        $this->callbackUrl,
                        $this->requestId
                    );
                    
                    // We've dispatched the job chain, so we don't need to continue with this job
                    Log::info("Main job complete - job chain dispatched");
                    return;
                }
            }
            

            Log::info("Starting job to create Wikidata entity", [
                "type" => $this->entityType,
                "title" => $this->entityData["title"] ?? "Unknown",
                "attempt" => $this->attempts(),
                "parent_id" => $this->parentEntityId,
                "relationship" => $this->relationshipType
            ]);

            try {
                // Initialize tracking arrays
                $existingEntityIds = [];
                $newlyCreatedEntityIds = [];
                $createdEntities = [];

                // Collect existing entity IDs from related entities
                $this->collectExistingEntityIds($existingEntityIds);


                // If this is being called from the orchestrator, we're creating a single entity,
                // so we don't need to handle related entities (they'll be created in chained jobs)
                $createRelatedEntities = empty($this->parentEntityId);
                
                // If we're in a job chain (have a parent ID), we don't need to process our own children
                // because each entity in the chain is responsible for itself only
                if (!$createRelatedEntities) {
                    Log::info("Skipping related entity processing since this is a child entity in a job chain", [
                        "type" => $this->entityType,
                        "parent_id" => $this->parentEntityId,
                        "relationship" => $this->relationshipType
                    ]);
                }
                
                // Since we're using job chains to create related entities, we need to prevent
                // the formatter from creating them directly, but we still need to include
                // them so relationships are properly established.
                
                // Make a copy of the entity data that we'll modify
                $entityDataForFormatter = $this->entityData;
                
                // For victims, preserve them but modify to prevent creation
                if (!empty($entityDataForFormatter['victims'])) {
                    foreach ($entityDataForFormatter['victims'] as $key => $victim) {
                        // Mark it to NOT force create
                        $entityDataForFormatter['victims'][$key]['_forceCreate'] = false;
                        // Add a flag that tells the formatter not to create this entity
                        $entityDataForFormatter['victims'][$key]['_skipCreate'] = true;
                    }
                }
                
                // For perpetrators, preserve them but modify to prevent creation
                if (!empty($entityDataForFormatter['perpetrators'])) {
                    foreach ($entityDataForFormatter['perpetrators'] as $key => $perpetrator) {
                        // Mark it to NOT force create
                        $entityDataForFormatter['perpetrators'][$key]['_forceCreate'] = false;
                        // Add a flag that tells the formatter not to create this entity
                        $entityDataForFormatter['perpetrators'][$key]['_skipCreate'] = true;
                    }
                }
                
                // For sources/books, preserve them but modify to prevent creation
                if (!empty($entityDataForFormatter['sources'])) {
                    foreach ($entityDataForFormatter['sources'] as $key => $source) {
                        $entityDataForFormatter['sources'][$key]['_forceCreate'] = false;
                        $entityDataForFormatter['sources'][$key]['_skipCreate'] = true;
                    }
                }
                
                // Log what we're doing
                Log::info("Preparing entity data for formatter - added _skipCreate flags to prevent direct creation", [
                    "entity_type" => $this->entityType,
                    "title" => $entityDataForFormatter['title'] ?? 'Untitled',
                    "victims" => !empty($entityDataForFormatter['victims']) ? count($entityDataForFormatter['victims']) : 0,
                    "perpetrators" => !empty($entityDataForFormatter['perpetrators']) ? count($entityDataForFormatter['perpetrators']) : 0,
                ]);
                
                // Format the entity data with relationships but prevent entity creation
                $formattedResult = $formatterModels->formatEntityData(
                    $entityDataForFormatter,
                    $this->entityType,
                    false // Never try to create related entities
                );

                // Get the formatted data for the WikibaseService
                $formattedData = $formattedResult["formattedData"];

                // Create the entity using the formatted data
                $response = $wikidataService->createEntity(
                    [
                        "title" =>
                            $formattedData["labels"]["de"]["value"] ??
                            ($this->entityData["title"] ?? "Untitled Entity"),
                        "description" =>
                            $formattedData["descriptions"]["de"]["value"] ??
                            ($this->entityData["description"] ?? ""),
                        "type" => $this->entityType,
                        "claims" => $formattedData["claims"] ?? [],
                        "labels" => $formattedData["labels"] ?? [],
                        "descriptions" => $formattedData["descriptions"] ?? [],
                        "aliases" => $formattedData["aliases"] ?? [],
                        "sitelinks" => $formattedData["sitelinks"] ?? [],
                    ],
                    $this->entityType
                );

                if ($response && isset($response["entity"]["id"])) {
                    $entityId = $response["entity"]["id"];
                    
                    // Store the entity ID in a global variable so chained jobs can access it
                    // This allows child jobs in the chain to link to this entity
                    $GLOBALS['last_created_entity_id'] = $entityId;

                    Log::info("Stored entity ID in global variable for job chain", [
                        "entity_id" => $entityId,
                        "type" => $this->entityType,
                        "is_part_of_chain" => !empty($this->parentEntityId) ? 'child' : 'parent'
                    ]);

                    // For debugging - track job chain execution
                    if (empty($this->parentEntityId)) {
                        // This is the main entity in a chain
                        Log::info("Main entity in job chain created - child jobs should execute next", [
                            "entity_id" => $entityId,
                            "type" => $this->entityType
                        ]);
                        
                        // Directly update the status in the database
                        // This serves as a fallback in case the callback fails
                        try {
                            if ($this->requestId) {
                                $requestModel = config("wikidata.request_model", self::DEFAULT_REQUEST_MODEL);
                                $entryRequest = $requestModel::find($this->requestId);
                                
                                if ($entryRequest) {
                                    // Use EntryStatusController's static method to update status
                                    \App\Http\Controllers\EntryStatusController::updateStatusHistoryStatic(
                                        $entryRequest,
                                        'wikidata_published',
                                        "Eintrag erfolgreich erstellt mit Wikidata-ID: {$entityId}"
                                    );
                                    
                                    // Also update the wikidata_id field
                                    $entryRequest->wikidata_id = $entityId;
                                    $entryRequest->created_entities = $createdEntities;
                                    $entryRequest->save();
                                    
                                    Log::info("Direct database update successful", [
                                        "entity_id" => $entityId,
                                        "request_id" => $this->requestId
                                    ]);
                                }
                            }
                        } catch (\Exception $dbEx) {
                            Log::warning("Direct database update failed: " . $dbEx->getMessage(), [
                                "entity_id" => $entityId,
                                "request_id" => $this->requestId
                            ]);
                        }
                    } else {
                        // This is a child entity in a chain
                        Log::info("Child entity in job chain created", [
                            "entity_id" => $entityId,
                            "parent_id" => $this->parentEntityId,
                            "relationship" => $this->relationshipType,
                            "type" => $this->entityType
                        ]);
                    }

                    // Update entity data with the result from formatEntityData
                    $this->entityData = $formattedResult["updatedData"];

                    // If we're creating related entities, track them
                    if (empty($this->parentEntityId)) {
                        // Track created related entities
                        $this->trackCreatedPerpetrators(
                            $existingEntityIds,
                            $newlyCreatedEntityIds,
                            $createdEntities
                        );
                        $this->trackCreatedBookEntities(
                            $existingEntityIds,
                            $newlyCreatedEntityIds,
                            $createdEntities
                        );
                        $this->trackCreatedVictims(
                            $existingEntityIds,
                            $newlyCreatedEntityIds,
                            $createdEntities
                        );
                    }

                    // Update entity data again with any changes made during tracking
                    $this->updateEntityWithFormattedData(
                        $formatterModels,
                        $entityId
                    );

                    Log::info(
                        "Successfully created Wikidata entity in background job",
                        [
                            "entity_id" => $entityId,
                            "type" => $this->entityType,
                            "additional_entities" => count($createdEntities),
                            "referenced_entities" => count($existingEntityIds),
                            "newly_created_ids" => $newlyCreatedEntityIds,
                        ]
                    );

                    // If this entity has a parent entity it should be linked to
                    if ($this->parentEntityId && $this->relationshipType) {
                        $this->linkEntityToParent($wikidataService, $entityId);
                    }

                    // For events, link this entity to any victims or perpetrators that were created
                    if (
                        $this->entityType === app(EntityMappingProviderInterface::class)->getEntityTypeId('App\\Models\\Wikibase\\Event') &&
                        !empty($createdEntities)
                    ) {
                        $this->linkMainEntityToChildren(
                            $wikidataService,
                            $entityId,
                            $createdEntities
                        );
                    }

                    // Update the database status to reflect the entity was published in Wikidata
                    $this->updateRequestStatus($entityId);
                    
                    // For main entities only (not child entities in the chain), dispatch events and callbacks
                    if (empty($this->parentEntityId)) {
                        // Dispatch an event about the entity creation
                        // This replaces the direct HTTP callback with a more reliable event-based approach
                        event(new \App\Events\WikidataEntityCreated(
                            $entityId,
                            $this->entityType,
                            $createdEntities,
                            null, // No parent ID for main entity
                            null, // No relationship type for main entity
                            $this->entityData
                        ));

                        // For backward compatibility, still use the callback if provided
                        if ($this->callbackUrl) {
                            $this->notifyCallback($entityId, $createdEntities);
                        }
                        
                        Log::info("Dispatched event for main entity creation", [
                            "entity_id" => $entityId,
                            "type" => $this->entityType,
                            "created_related_entities" => count($createdEntities)
                        ]);
                    } else {
                        // For child entities, dispatch a different event type that includes parent information
                        event(new \App\Events\WikidataChildEntityCreated(
                            $entityId,
                            $this->entityType,
                            $this->parentEntityId,
                            $this->relationshipType,
                            $this->entityData
                        ));
                        
                        Log::info("Created child entity with parent relationship", [
                            "entity_id" => $entityId,
                            "parent_id" => $this->parentEntityId,
                            "relationship" => $this->relationshipType
                        ]);
                    }
                } else {
                    $this->handleEntityCreationFailure($response);
                }
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        } catch (\Throwable $e) {
            $this->handleFatalError($e);
        }
    }

    /**
     * Collect existing entity IDs from related entities
     *
     * @param array &$existingEntityIds Array to populate with existing IDs
     * @return void
     */
    protected function collectExistingEntityIds(array &$existingEntityIds): void
    {
        // Track existing perpetrators if present
        if (
            !empty($this->entityData["perpetrators"]) &&
            is_array($this->entityData["perpetrators"])
        ) {
            foreach ($this->entityData["perpetrators"] as $perpetrator) {
                if (
                    !empty($perpetrator["id"]) &&
                    substr($perpetrator["id"], 0, 1) === "Q"
                ) {
                    $existingEntityIds[] = $perpetrator["id"];
                }
            }
        }

        // Track existing books (handle both array and single book formats)
        $bookEntities = $this->getBookEntities();

        foreach ($bookEntities as $book) {
            if (!empty($book["id"]) && substr($book["id"], 0, 1) === "Q") {
                $existingEntityIds[] = $book["id"];
            }
        }

        // Track existing victims if present
        if (
            !empty($this->entityData["victims"]) &&
            is_array($this->entityData["victims"])
        ) {
            foreach ($this->entityData["victims"] as $victim) {
                if (
                    !empty($victim["id"]) &&
                    substr($victim["id"], 0, 1) === "Q" &&
                    empty($victim["tempId"]) &&
                    empty($victim["temp_id"])
                ) {
                    $existingEntityIds[] = $victim["id"];
                }
            }
        }
    }

    /**
     * Get book entities from either books array or single book
     *
     * @return array
     */
    protected function getBookEntities(): array
    {
        $bookEntities = [];
        if (
            !empty($this->entityData["books"]) &&
            is_array($this->entityData["books"])
        ) {
            $bookEntities = $this->entityData["books"];
        } elseif (
            !empty($this->entityData["book"]) &&
            is_array($this->entityData["book"])
        ) {
            $bookEntities[] = $this->entityData["book"];
        }

        return $bookEntities;
    }

    /**
     * Track created perpetrator entities
     *
     * @param array $existingEntityIds Previously existing entity IDs
     * @param array &$newlyCreatedEntityIds Array to populate with newly created entity IDs
     * @param array &$createdEntities Array to populate with created entity information
     * @return void
     */
    protected function trackCreatedPerpetrators(
        array $existingEntityIds,
        array &$newlyCreatedEntityIds,
        array &$createdEntities
    ): void {
        if (
            !empty($this->entityData["perpetrators"]) &&
            is_array($this->entityData["perpetrators"])
        ) {
            foreach ($this->entityData["perpetrators"] as $perpetrator) {
                if (
                    !empty($perpetrator["id"]) &&
                    substr($perpetrator["id"], 0, 1) === "Q" &&
                    (!in_array($perpetrator["id"], $existingEntityIds) ||
                        (isset($perpetrator["_wasCreated"]) &&
                            $perpetrator["_wasCreated"] === true))
                ) {
                    // Add to the list of newly created IDs for tracking
                    if (!in_array($perpetrator["id"], $newlyCreatedEntityIds)) {
                        $newlyCreatedEntityIds[] = $perpetrator["id"];

                        // Get name using helper method from WikibaseFormatterModels
                        $name = $this->getEntityName($perpetrator);

                        $createdEntities[] = [
                            "id" => $perpetrator["id"],
                            "type" => "perpetrator",
                            "name" => $name,
                            "label" => $perpetrator["label"] ?? null,
                        ];

                        Log::info(
                            "Added newly created perpetrator to additional entities list",
                            [
                                "id" => $perpetrator["id"],
                                "name" => $name,
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Extract or create a name from a related entity array
     * (Originally from WikibaseFormatterModels for consistency)
     *
     * @param array $entity The entity data
     * @return string The entity name
     */
    protected function getEntityName(array $entity): string
    {
        if (!empty($entity["name"])) {
            return $entity["name"];
        }

        if (!empty($entity["label"])) {
            return $entity["label"];
        }

        // Construct from firstName and lastName if available
        return trim(
            (isset($entity["firstName"]) ? $entity["firstName"] : "") .
                " " .
                (isset($entity["lastName"]) ? $entity["lastName"] : "")
        );
    }

    /**
     * Track created book entities
     *
     * @param array $existingEntityIds Previously existing entity IDs
     * @param array &$newlyCreatedEntityIds Array to populate with newly created entity IDs
     * @param array &$createdEntities Array to populate with created entity information
     * @return void
     */
    protected function trackCreatedBookEntities(
        array $existingEntityIds,
        array &$newlyCreatedEntityIds,
        array &$createdEntities
    ): void {
        $bookEntities = $this->getBookEntities();

        foreach ($bookEntities as $book) {
            if (
                !empty($book["id"]) &&
                substr($book["id"], 0, 1) === "Q" &&
                (!in_array($book["id"], $existingEntityIds) ||
                    (isset($book["_wasCreated"]) &&
                        $book["_wasCreated"] === true))
            ) {
                // Add to the list of newly created IDs for tracking
                if (!in_array($book["id"], $newlyCreatedEntityIds)) {
                    $newlyCreatedEntityIds[] = $book["id"];

                    // Get title using a consistent approach
                    $title = $book["title"] ?? ($book["label"] ?? "Unknown");

                    $createdEntities[] = [
                        "id" => $book["id"],
                        "type" => "book",
                        "name" => $title,
                        "label" => $book["label"] ?? ($book["title"] ?? null),
                    ];

                    Log::info(
                        "Added newly created book to additional entities list",
                        [
                            "id" => $book["id"],
                            "title" => $title,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Track created victim entities
     *
     * @param array $existingEntityIds Previously existing entity IDs
     * @param array &$newlyCreatedEntityIds Array to populate with newly created entity IDs
     * @param array &$createdEntities Array to populate with created entity information
     * @return void
     */
    protected function trackCreatedVictims(
        array $existingEntityIds,
        array &$newlyCreatedEntityIds,
        array &$createdEntities
    ): void {
        if (
            !empty($this->entityData["victims"]) &&
            is_array($this->entityData["victims"])
        ) {
            foreach ($this->entityData["victims"] as $victim) {
                if (
                    !empty($victim["id"]) &&
                    substr($victim["id"], 0, 1) === "Q" &&
                    (!in_array($victim["id"], $existingEntityIds) ||
                        (isset($victim["_wasCreated"]) &&
                            $victim["_wasCreated"] === true))
                ) {
                    // Add to the list of newly created IDs for tracking
                    if (!in_array($victim["id"], $newlyCreatedEntityIds)) {
                        $newlyCreatedEntityIds[] = $victim["id"];

                        // Get name using helper method
                        $name = $this->getEntityName($victim);

                        $createdEntities[] = [
                            "id" => $victim["id"],
                            "type" => "victim",
                            "name" => $name,
                            "label" => $victim["label"] ?? null,
                        ];

                        Log::info(
                            "Added newly created victim to additional entities list",
                            [
                                "id" => $victim["id"],
                                "name" => $name,
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Handle entity creation failure
     *
     * @param mixed $response The response from WikibaseService
     * @return void
     * @throws \Exception
     */
    protected function handleEntityCreationFailure($response): void
    {
        Log::error("Failed to create Wikidata entity in background job", [
            "response" => $response,
            "attempt" => $this->attempts(),
        ]);

        // Try to update the request status to failed if we've exhausted retries
        if ($this->attempts() >= $this->tries) {
            $this->updateRequestStatusToFailed(
                "Failed to create Wikidata entity: " . json_encode($response)
            );
        }

        // Throw an exception to trigger a retry
        throw new \Exception(
            "Failed to create Wikidata entity: " . json_encode($response)
        );
    }

    /**
     * Update the request status to failed
     *
     * @param string $errorMessage The error message
     * @return void
     */
    protected function updateRequestStatusToFailed(string $errorMessage): void
    {
        try {
            // First try to use the dedicated requestId property
            $requestId = $this->requestId;

            // If not available, try to get it from the entityData
            if (empty($requestId) && !empty($this->entityData["id"])) {
                $requestId = $this->entityData["id"];
            }

            if (!empty($requestId)) {
                // Get request model class name from config or use the correct default
                $requestModelClass = config(
                    "wikidata.request_model",
                    self::DEFAULT_REQUEST_MODEL
                );

                // Check if the model class exists before trying to use it
                if (!class_exists($requestModelClass)) {
                    Log::warning("Request model class {$requestModelClass} not found. Skipping status update.", [
                        "error_message" => $errorMessage,
                        "request_id" => $requestId
                    ]);
                    return;
                }

                // Try to update with a simple query first (most reliable approach)
                try {
                    $requestModelClass::where('id', $requestId)
                        ->update([
                            'status' => 'wikidata_failed',
                            'rejection_reason' => $errorMessage // This is likely to exist since it's a standard field
                        ]);
                    
                    Log::info("Updated request status to wikidata_failed using direct update", [
                        "request_id" => $requestId
                    ]);
                    
                    return; // Exit early if successful
                } catch (\Exception $updateEx) {
                    Log::warning("Direct update of status failed, trying full model: " . $updateEx->getMessage());
                    // Continue with model-based approach as fallback
                }

                // Find the request and update it with model approach
                try {
                    $request = $requestModelClass::find($requestId);

                    if ($request) {
                        $request->status = "wikidata_failed";
                        
                        // Check which error field to use based on schema
                        if (\Illuminate\Support\Facades\Schema::hasColumn(
                            (new $requestModelClass())->getTable(),
                            'error_message'
                        )) {
                            $request->error_message = $errorMessage;
                        } else if (\Illuminate\Support\Facades\Schema::hasColumn(
                            (new $requestModelClass())->getTable(),
                            'rejection_reason'
                        )) {
                            $request->rejection_reason = $errorMessage;
                        }
                        
                        $request->save();

                        Log::info("Updated request status to wikidata_failed using model", [
                            "request_id" => $requestId,
                            "error" => $errorMessage,
                        ]);
                    }
                } catch (\Exception $modelEx) {
                    Log::error("Failed to update status using model approach: " . $modelEx->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("Error updating request failure status", [
                "error" => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle exceptions during entity creation
     *
     * @param \Exception $e The exception
     * @return void
     * @throws \Exception
     */
    protected function handleException(\Exception $e): void
    {
        Log::error("Exception while creating Wikidata entity", [
            "error" => $e->getMessage(),
            "attempt" => $this->attempts(),
        ]);

        // If we have remaining attempts, throw the exception to trigger a retry
        if ($this->attempts() < $this->tries) {
            throw $e;
        } else {
            // If we've exhausted all retries, update the status and notify about the failure
            $this->updateRequestStatusToFailed($e->getMessage());

            if ($this->callbackUrl) {
                try {
                    $client = new \GuzzleHttp\Client();
                    $client->post($this->callbackUrl, [
                        "json" => [
                            "entity_id" => null,
                            "status" => "failed",
                            "error" => $e->getMessage(),
                            "original_data" => $this->entityData,
                        ],
                    ]);

                    Log::info("Sent failure notification for entity creation", [
                        "error" => $e->getMessage(),
                        "entity_data" => [
                            "type" => $this->entityType,
                            "title" => $this->entityData["title"] ?? "Unknown",
                        ],
                    ]);
                } catch (\Exception $callbackEx) {
                    Log::error("Failed to send failure callback", [
                        "error" => $callbackEx->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Handle fatal errors during entity creation
     *
     * @param \Throwable $e The error
     * @return void
     */
    protected function handleFatalError(\Throwable $e): void
    {
        // If the error is specifically about model not found,
        // we can log it but not treat it as truly fatal
        $isModelNotFoundError = (
            strpos($e->getMessage(), "WikibaseEntryRequest") !== false ||
            strpos($e->getMessage(), "WikibaseRequest") !== false
        ) && strpos($e->getMessage(), "not found") !== false;
        
        if ($isModelNotFoundError) {
            Log::warning("Non-critical error: Request model not found. Entity creation was likely successful.", [
                "error" => $e->getMessage(),
                "entity_data" => [
                    "type" => $this->entityType,
                    "title" => $this->entityData["title"] ?? "Unknown",
                ],
            ]);
            
            // Still try to notify the callback if available
            if ($this->callbackUrl) {
                try {
                    $client = new \GuzzleHttp\Client();
                    $client->post($this->callbackUrl, [
                        "json" => [
                            "entity_id" => "UNKNOWN", // We don't know the ID but the entity might have been created
                            "status" => "partial_success",
                            "message" => "Entity was likely created but could not update the request record.",
                            "original_data" => $this->entityData,
                        ],
                    ]);
                } catch (\Exception $callbackEx) {
                    Log::error("Failed to send partial success callback", [
                        "error" => $callbackEx->getMessage(),
                    ]);
                }
            }
            
            return;
        }
        
        // For truly fatal errors, proceed with the original error handling
        Log::critical("Fatal error in CreateWikidataEntity job", [
            "error" => $e->getMessage(),
            "trace" => $e->getTraceAsString(),
            "entity_data" => [
                "type" => $this->entityType,
                "title" => $this->entityData["title"] ?? "Unknown",
            ],
        ]);

        // Update the request status to failed
        $this->updateRequestStatusToFailed("Fatal error: " . $e->getMessage());

        // Notify of the failure
        if ($this->callbackUrl) {
            try {
                $client = new \GuzzleHttp\Client();
                $client->post($this->callbackUrl, [
                    "json" => [
                        "entity_id" => null,
                        "status" => "failed",
                        "error" => "Fatal error: " . $e->getMessage(),
                        "original_data" => $this->entityData,
                    ],
                ]);
            } catch (\Exception $callbackEx) {
                Log::error("Failed to send fatal error callback", [
                    "error" => $callbackEx->getMessage(),
                ]);
            }
        }
    }

    /**
     * Update the request status in the database
     *
     * @param string $entityId The Wikidata entity ID
     * @return void
     */
    protected function updateRequestStatus(string $entityId): void
    {
        try {
            // First try to use the dedicated requestId property if it was provided
            $requestId = $this->requestId;

            // If not available, try to get it from the entityData as a fallback
            if (empty($requestId) && !empty($this->entityData["id"])) {
                $requestId = $this->entityData["id"];
            }

            // If we have a request ID, update the database
            if (!empty($requestId)) {
                // Get request model class name from config or use the correct default
                $requestModelClass = config(
                    "wikidata.request_model",
                    self::DEFAULT_REQUEST_MODEL
                );

                // Check if the model class exists before trying to use it
                if (!class_exists($requestModelClass)) {
                    Log::warning("Request model class {$requestModelClass} not found. Skipping status update.", [
                        "entity_id" => $entityId,
                        "request_id" => $requestId
                    ]);
                    return;
                }

                // Use a simple direct query update approach - most reliable, doesn't need schema checks
                try {
                    // Simple update with only the most critical fields
                    $updateData = [
                        'status' => 'wikidata_published',
                        'wikidata_id' => $entityId
                    ];
                    
                    // Try to update the record
                    $affected = $requestModelClass::where('id', $requestId)
                        ->update($updateData);
                    
                    if ($affected) {
                        Log::info("Updated request status to wikidata_published using minimal fields", [
                            "request_id" => $requestId,
                            "entity_id" => $entityId,
                        ]);
                    } else {
                        Log::warning("Could not find request to update status", [
                            "request_id" => $requestId,
                            "entity_id" => $entityId,
                        ]);
                    }
                } catch (\Exception $updateEx) {
                    // If we can't update with a query, last resort is to try a full model update
                    Log::warning("Simple database update failed: " . $updateEx->getMessage(), [
                        "request_id" => $requestId,
                        "entity_id" => $entityId,
                    ]);
                    
                    // Last attempt - try to use the model directly
                    try {
                        $request = $requestModelClass::find($requestId);
                        if ($request) {
                            $request->status = "wikidata_published";
                            $request->wikidata_id = $entityId;
                            // Don't set wikidata_url at all
                            $request->save();
                            
                            Log::info("Updated request with model approach", [
                                "request_id" => $requestId,
                                "entity_id" => $entityId,
                            ]);
                        }
                    } catch (\Exception $modelEx) {
                        Log::error("All update approaches failed: " . $modelEx->getMessage(), [
                            "request_id" => $requestId,
                            "entity_id" => $entityId,
                        ]);
                    }
                }
            } else {
                Log::warning("No request ID available, cannot update status", [
                    "entity_id" => $entityId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error in updateRequestStatus method", [
                "error" => $e->getMessage(),
                "entity_id" => $entityId,
            ]);
        }
    }

    /**
     * Notify a callback URL about the created entity
     *
     * @param string $entityId The created entity ID
     * @param array $createdEntities Additional entities that were created
     * @return void
     */
    protected function notifyCallback(
        string $entityId,
        array $createdEntities = []
    ) {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($this->callbackUrl, [
                "json" => [
                    "entity_id" => $entityId,
                    "status" => "success",
                    "original_data" => $this->entityData,
                    "created_entities" => $createdEntities,
                ],
            ]);

            Log::info("Sent callback notification for created entity", [
                "entity_id" => $entityId,
                "status_code" => $response->getStatusCode(),
                "created_entities_count" => count($createdEntities),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send callback notification", [
                "error" => $e->getMessage(),
                "entity_id" => $entityId,
            ]);
        }
    }

    /**
     * Link this entity to its parent entity
     *
     * @param WikibaseService $wikidataService
     * @param string $entityId The newly created entity ID
     * @return void
     */
    protected function linkEntityToParent(
        WikibaseService $wikidataService,
        string $entityId
    ) {
        try {
            // Check if we have a parent entity ID and relationship type
            if (empty($this->parentEntityId)) {
                Log::warning("Cannot link entity to parent - missing parent ID", [
                    "entity_id" => $entityId,
                    "relationship" => $this->relationshipType ?? 'unknown'
                ]);
                return;
            }
            
            if (empty($this->relationshipType)) {
                Log::warning("Cannot link entity to parent - missing relationship type", [
                    "entity_id" => $entityId,
                    "parent_id" => $this->parentEntityId
                ]);
                return;
            }
            
            Log::info("Linking entity to parent", [
                "entity_id" => $entityId,
                "parent_id" => $this->parentEntityId,
                "relationship" => $this->relationshipType
            ]);
            
            // Different linking properties based on relationship type
            $linkProperty = null;
            $mappingProvider = app(\App\Services\EntityMapping\EntityMappingProviderInterface::class);

            switch ($this->relationshipType) {
                case "source":
                    $linkProperty = $mappingProvider->getPropertyId('DESCRIBED_BY_SOURCE'); // Described by source
                    break;
                case "perpetrator":
                    $linkProperty = $mappingProvider->getPropertyId('PERPETRATOR'); // Has perpetrator
                    break;
                case "victim":
                    $linkProperty = $mappingProvider->getPropertyId('VICTIM'); // Has victim
                    break;
                case "book": // For backwards compatibility
                    $linkProperty = $mappingProvider->getPropertyId('DESCRIBED_BY_SOURCE'); // Treat books as sources
                    break;
                default:
                    Log::warning(
                        "Unknown relationship type for entity linking",
                        [
                            "relationship" => $this->relationshipType,
                            "entity_id" => $entityId,
                            "parent_id" => $this->parentEntityId,
                        ]
                    );
                    return; // Unknown relationship type
            }

            if ($linkProperty) {
                // Create the statement to add to the parent entity
                $statement = [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => $linkProperty,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $entityId,
                                "numeric-id" => intval(substr($entityId, 1)),
                            ],
                            "type" => "wikibase-entityid",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ];

                // Add qualifiers if needed (e.g., page numbers for sources)
                if ($this->relationshipType === "source") {
                    // For book sources, add page numbers if available
                    if (!empty($this->entityData["pages"])) {
                        $statement["qualifiers"] = [
                            "P304" => [
                                // Page property
                                [
                                    "snaktype" => "value",
                                    "property" => "P304",
                                    "datavalue" => [
                                        "value" => $this->entityData["pages"],
                                        "type" => "string",
                                    ],
                                ],
                            ],
                        ];
                    }
                }

                // Call the WikibaseService to add this statement to the parent
                // For the update, we need the parent entity type, not the child entity type
                // We'll use the main event entity type for simplicity (most common case)
                $parentEntityType = null;
                
                // Try to determine parent entity type based on relationship
                switch ($this->relationshipType) {
                    case "victim":
                    case "perpetrator":
                        // Parent is likely an event/incident
                        $parentEntityType = $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Event');
                        break;
                    case "source":
                    case "book":
                        // Parent could be any entity type, default to event but check ID
                        if (substr($this->parentEntityId, 0, 1) === 'Q') {
                            // If we can't determine it, use a generic type that can accept sources
                            $parentEntityType = $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Event');
                        }
                        break;
                }
                
                // Fallback if we couldn't determine the type
                if (!$parentEntityType) {
                    $parentEntityType = $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Event'); // Default to event
                }
                
                $result = $wikidataService->updateEntity(
                    $this->parentEntityId,
                    [
                        "claims" => [
                            $linkProperty => [$statement],
                        ],
                    ],
                    $parentEntityType // Use parent entity type
                );

                if ($result) {
                    Log::info("Successfully linked entity to its parent", [
                        "child_id" => $entityId,
                        "parent_id" => $this->parentEntityId,
                        "relationship" => $this->relationshipType,
                    ]);
                    
                    // For victim or perpetrator entities, also add a significant event (P793) link back to the parent
                    if ($this->relationshipType === "victim" || $this->relationshipType === "perpetrator") {
                        // Create statement to add the main entity as a significant event (P793)
                        $backLinkStatement = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => "P793", // Significant event (Schlüsselevent)
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => $this->parentEntityId,
                                        "numeric-id" => intval(substr($this->parentEntityId, 1)),
                                    ],
                                    "type" => "wikibase-entityid",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ];
                        
                        // Add the P793 back-link (child entity points to parent as significant event)
                        $backLinkResult = $wikidataService->updateEntity(
                            $entityId,
                            [
                                "claims" => [
                                    "P793" => [$backLinkStatement],
                                ],
                            ],
                            $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Person') // Person entity type for victims/perpetrators
                        );
                        
                        if ($backLinkResult) {
                            Log::info("Successfully added P793 significant event back-link", [
                                "person_id" => $entityId,
                                "event_id" => $this->parentEntityId,
                                "relationship" => $this->relationshipType,
                            ]);
                        } else {
                            Log::error("Failed to add P793 significant event back-link", [
                                "person_id" => $entityId,
                                "event_id" => $this->parentEntityId,
                            ]);
                        }
                    }
                } else {
                    Log::error("Failed to link entity to its parent", [
                        "child_id" => $entityId,
                        "parent_id" => $this->parentEntityId,
                        "relationship" => $this->relationshipType,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Exception while linking entity to parent", [
                "error" => $e->getMessage(),
                "child_id" => $entityId,
                "parent_id" => $this->parentEntityId,
            ]);
        }
    }

    /**
     * Link this main entity to any newly created child entities
     * Establishes bidirectional relationships between events and persons
     *
     * @param WikibaseService $wikidataService
     * @param string $mainEntityId The main entity ID
     * @param array $createdEntities List of created related entities
     * @return void
     */
    protected function linkMainEntityToChildren(
        WikibaseService $wikidataService,
        string $mainEntityId,
        array $createdEntities
    ) {
        try {
            $mappingProvider = app(EntityMappingProviderInterface::class);
            // Only perform this operation for event entities
            if ($this->entityType !== $mappingProvider->getEntityTypeId('App\\Models\\Wikibase\\Event')) {
                return;
            }

            $linkedEntities = 0;

            foreach ($createdEntities as $entity) {
                // Only process victim and perpetrator entities
                if (!in_array($entity["type"], ["victim", "perpetrator"])) {
                    continue;
                }

                // Create statement to add the main entity as a significant event (P793)
                $statement = [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => "P793", // Significant event - this is a specific property not in the constants
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $mainEntityId,
                                "numeric-id" => intval(
                                    substr($mainEntityId, 1)
                                ),
                            ],
                            "type" => "wikibase-entityid",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ];

                // Call the WikibaseService to add this statement to the person entity
                $result = $wikidataService->updateEntity($entity["id"], [
                    "claims" => [
                        "P793" => [$statement],
                    ],
                ]);

                if ($result) {
                    $linkedEntities++;
                    Log::info(
                        "Successfully linked main entity as significant event to child entity",
                        [
                            "main_id" => $mainEntityId,
                            "child_id" => $entity["id"],
                            "child_type" => $entity["type"],
                        ]
                    );
                } else {
                    Log::error(
                        "Failed to link main entity as significant event to child entity",
                        [
                            "main_id" => $mainEntityId,
                            "child_id" => $entity["id"],
                            "child_type" => $entity["type"],
                        ]
                    );
                }
            }

            if ($linkedEntities > 0) {
                Log::info("Completed bidirectional linking", [
                    "main_entity_id" => $mainEntityId,
                    "linked_entities" => $linkedEntities,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception while linking main entity to children", [
                "error" => $e->getMessage(),
                "main_id" => $mainEntityId,
            ]);
        }
    }

    /**
     * Update entity data using the WikibaseFormatterModels
     *
     * @param WikibaseFormatterModels $formatterModels The formatter models instance
     * @param string $entityId The newly created entity ID
     * @return void
     */
    protected function updateEntityWithFormattedData(
        WikibaseFormatterModels $formatterModels,
        string $entityId
    ): void {
        try {
            $formattedResult = $formatterModels->formatEntityData(
                $this->entityData,
                $this->entityType,
                false // Don't create related entities again
            );

            // Update the entityData with the formatted data
            $this->entityData = $formattedResult["updatedData"];

            Log::info(
                "Updated entity data with formatted data using WikibaseFormatterModels",
                [
                    "entity_id" => $entityId,
                    "type" => $this->entityType,
                ]
            );
        } catch (\Exception $e) {
            Log::error("Error updating entity with formatted data", [
                "error" => $e->getMessage(),
                "entity_id" => $entityId,
            ]);
        }
    }
}

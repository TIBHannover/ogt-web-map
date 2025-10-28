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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use App\Models\Wikibase\Person;
use App\Models\Wikibase\Source\SourceFactory; // Add this for job chaining
use App\Services\Wikibase\ImageHandler;

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
     * @param string|null $parentEntityId Optional parent entity ID
     * @param string|null $relationshipType Optional relationship type
     * @param int|null $requestId Optional request ID for database tracking
     * @return void
     */
    public static function createEntityWithRelated(
        array $entityData,
        string $entityType,
        ?int $requestId = null,
    ): void {
        // First, log that we're starting this method with a unique identifier to find in logs
        $uniqueId = uniqid("chain_", true);
        Log::info("*** IMPORTANT: createEntityWithRelated STARTED ***", [
            "chain_id" => $uniqueId,
            "entity_type" => $entityType,
            "title" => $entityData["title"] ?? "Untitled",
            "request_id" => $requestId,
            "backtrace" => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
        ]);

        // Extract the related entities before creating the main entity
        $victims = $entityData["victims"] ?? [];
        $perpetrators = $entityData["perpetrators"] ?? [];
        $sources = $entityData["sources"] ?? [];
        $books = [];

        // Handle books in both formats (books array or single book)
        if (!empty($entityData["books"]) && is_array($entityData["books"])) {
            $books = $entityData["books"];
        } elseif (
            !empty($entityData["book"]) &&
            is_array($entityData["book"])
        ) {
            $books[] = $entityData["book"];
        }

        // Log what we're going to process with detailed information
        $relatedCount =
            count($victims) +
            count($perpetrators) +
            count($sources) +
            count($books);
        Log::info(
            "createEntityWithRelated: Creating entity with related entities",
            [
                "main_entity_type" => $entityType,
                "title" => $entityData["title"] ?? "Untitled",
                "related_entities" => $relatedCount,
                "victims" => count($victims),
                "victims_data" => array_map(function ($v) {
                    return $v["name"] ?? ($v["title"] ?? "Unknown");
                }, $victims),
                "perpetrators" => count($perpetrators),
                "perpetrators_data" => array_map(function ($p) {
                    return $p["name"] ?? ($p["title"] ?? "Unknown");
                }, $perpetrators),
                "sources" => count($sources),
                "books" => count($books),
                "request_id" => $requestId,
            ],
        );

        // Create the main entity job
        $entityData["_inChain"] = true; // Add flag to prevent recursive chain creation
        $mainEntityJob = new self(
            $entityData,
            $entityType,
            null,
            null,
            $requestId,
        );

        // Prepare the chain of jobs for related entities
        $chainedJobs = [];

        // Helper function to create jobs for each related entity type
        $createRelatedJobs = function (
            $entities,
            $entityType,
            $relationshipType,
        ) use (&$chainedJobs) {
            Log::info(
                "createEntityWithRelated: Processing entities for job chain",
                [
                    "entity_type" => $entityType,
                    "relationship" => $relationshipType,
                    "count" => count($entities),
                    "entities" => array_map(function ($e) {
                        return [
                            "name" => $e["name"] ?? ($e["title"] ?? "Unknown"),
                            "id" => $e["id"] ?? "new",
                            "has_temp_id" =>
                                !empty($e["tempId"]) || !empty($e["temp_id"]),
                        ];
                    }, $entities),
                ],
            );

            foreach ($entities as $entity) {
                // Skip entities that already have Wikidata IDs and don't need creation
                if (
                    !empty($entity["id"]) &&
                    substr($entity["id"], 0, 1) === "Q" &&
                    empty($entity["_forceCreate"]) &&
                    empty($entity["tempId"]) &&
                    empty($entity["temp_id"])
                ) {
                    Log::info(
                        "createEntityWithRelated: Skipping entity with existing ID",
                        [
                            "id" => $entity["id"],
                            "name" =>
                                $entity["name"] ??
                                ($entity["title"] ?? "Unknown"),
                        ],
                    );
                    continue;
                }

                // Make sure we have a title for the entity
                if (empty($entity["title"]) && !empty($entity["name"])) {
                    $entity["title"] = $entity["name"];
                }

                // Remove any flags that might confuse the formatter
                unset($entity["_forceCreate"]);
                unset($entity["_inQueueWorker"]);

                // Add flag to prevent recursive chain creation
                $entity["_inChain"] = true;

                // Add flag to disable P2093 placeholder claims
                $entity["_skipPlaceholderClaims"] = true;

                // Create a job for this related entity that will run after the main entity
                // Always use the main entity as parent, not a previous entity in the chain

                // Store the main entity ID in a special field to ensure we use it even if globals change
                $entity["_main_entity_id_placeholder"] = "__MAIN_ENTITY_ID__";

                // Ensure the entity data includes the relationship type as the role
                if ($relationshipType) {
                    // Always set both role and relationship_type to ensure consistency
                    $entity["role"] = $relationshipType;
                    $entity["relationship_type"] = $relationshipType;

                    // Store parent relationship info in the entity data
                    $entity["_parent_is_main_event"] = true;
                    $entity["_main_event_relationship"] = $relationshipType;

                    Log::info(
                        "Setting role and relationship properties for job chain entity",
                        [
                            "relationship_type" => $relationshipType,
                            "entity_name" =>
                                $entity["name"] ??
                                ($entity["title"] ?? "Unknown"),
                            "role" => $relationshipType,
                            "temp_id" =>
                                $entity["tempId"] ??
                                ($entity["temp_id"] ?? null),
                        ],
                    );
                }

                // Create an entity key to identify this entity in the chain
                $entityKey = uniqid("entity_", true);

                // Check if this entity already has a wikidata_id we should use instead of creating new
                if (
                    isset($entity["wikidata_id"]) &&
                    preg_match('/^Q\d+$/', $entity["wikidata_id"])
                ) {
                    // Log that we found a wikidata_id and will use it instead of creating a new entity
                    Log::info(
                        "Found existing wikidata_id for {$relationshipType}",
                        [
                            "wikidata_id" => $entity["wikidata_id"],
                            "name" =>
                                $entity["name"] ??
                                ($entity["title"] ?? "Unknown"),
                            "relationship" => $relationshipType,
                        ],
                    );

                    // Skip creation for this entity as it already exists in Wikidata
                    continue;
                }

                // Store the entity data for future reference
                $entity["_entity_chain_key"] = $entityKey;
                $entity["_main_entity_type"] = $entityType;

                $childJob = new self(
                    $entity,
                    $entityType,
                    "__MAIN_ENTITY_ID__", // Special placeholder for the parent ID
                    $relationshipType,
                    null, // No request ID for child entities
                );

                // Store the relationship type and role in entity data for clarity
                $entity["_job_chain_relationship"] = $relationshipType;
                $entity["_parent_placeholder"] = "__MAIN_ENTITY_ID__";

                Log::info(
                    "createEntityWithRelated: Added entity to job chain",
                    [
                        "name" =>
                            $entity["name"] ?? ($entity["title"] ?? "Unknown"),
                        "type" => $entityType,
                        "relationship" => $relationshipType,
                        "temp_id" =>
                            $entity["tempId"] ?? ($entity["temp_id"] ?? null),
                    ],
                );

                $chainedJobs[] = $childJob;
            }
        };
        // Get mapping provider for entity types
        $mappingProvider = app(EntityMappingProviderInterface::class);

        // Create jobs for each type of related entity
        $createRelatedJobs(
            $victims,
            $mappingProvider->getEntityTypeId("App\\Models\\Wikibase\\Person"),
            "victim",
        );
        $createRelatedJobs(
            $perpetrators,
            $mappingProvider->getEntityTypeId("App\\Models\\Wikibase\\Person"),
            "perpetrator",
        );

        // Process sources - only create jobs for non-URL sources
        $filteredSources = [];
        foreach ($sources as $source) {
            // Check if this is a URL source (has type='url' or contains URL fields)
            $isUrlSource =
                (isset($source["type"]) && $source["type"] === "url") ||
                (isset($source["url"]) &&
                    filter_var($source["url"], FILTER_VALIDATE_URL)) ||
                // Also check if it has a source_type of 'url'
                (isset($source["source_type"]) &&
                    $source["source_type"] === "url");

            if ($isUrlSource) {
                Log::info(
                    "Skipping URL source - will be added as reference only",
                    [
                        "source_title" =>
                            $source["title"] ?? ($source["name"] ?? "Unknown"),
                        "url" => $source["url"] ?? "none",
                    ],
                );
                continue;
            }

            // Only include non-URL sources (books, archives, etc.)
            $filteredSources[] = $source;
        }

        // Create jobs only for non-URL sources - link to main entity
        if (!empty($filteredSources)) {
            $createRelatedJobs(
                $filteredSources,
                $mappingProvider->getEntityTypeId(
                    "App\\Models\\Wikibase\\Book",
                ),
                "source",
            );
        }

        // If we have related entities, create a job chain
        if (!empty($chainedJobs)) {
            // Log the job chain creation
            Log::info("Creating job chain for entity creation", [
                "main_entity_type" => $entityType,
                "title" => $entityData["title"] ?? "Untitled",
                "related_entities" => count($chainedJobs),
                "chain_type" => "Bus::chain",
            ]);

            // Create the job chain: main entity job followed by all child entity jobs
            Log::info(
                "Dispatching job chain with " .
                    count($chainedJobs) .
                    " related entities",
                [
                    "chain_length" => count($chainedJobs) + 1,
                    "entity_type" => $entityType,
                    "child_types" => array_map(function ($job) {
                        return $job->entityType ?? "unknown";
                    }, $chainedJobs),
                    "chain_method" => "Bus::chain",
                ],
            );

            try {
                // Using Bus::chain for the job chain
                $chain = Bus::chain(
                    array_merge([$mainEntityJob], $chainedJobs),
                );
                $chain->dispatch();

                // Log success
                Log::info("Successfully dispatched job chain", [
                    "chain_length" => count($chainedJobs) + 1,
                    "main_entity" => $entityData["title"] ?? "Untitled",
                ]);
            } catch (\Exception $e) {
                // Log any errors with dispatching the chain
                Log::error("Error dispatching job chain", [
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]);

                // As a fallback, dispatch the main job directly
                Log::info("Falling back to dispatching main job only");
                self::dispatch(
                    $entityData,
                    $entityType,
                    null,
                    null,
                    $requestId,
                );
            }
        } else {
            // If no related entities, just dispatch the main entity job
            Log::info("No related entities to create, dispatching single job", [
                "main_entity_type" => $entityType,
                "title" => $entityData["title"] ?? "Untitled",
            ]);
            self::dispatch($entityData, $entityType, null, null, $requestId);
        }

        // Log completion of the method with the same unique ID for tracing
        Log::info("*** IMPORTANT: createEntityWithRelated COMPLETED ***", [
            "chain_id" => $uniqueId,
            "entity_type" => $entityType,
            "title" => $entityData["title"] ?? "Untitled",
            "related_entities_count" => count($chainedJobs),
        ]);
    }

    /**
     * Create a new job instance.
     *
     * @param array $entityData The entity data to create
     * @param string $entityType The entity type (Q-number)
     * @param string|null $parentEntityId Optional parent entity ID to link to when created
     * @param string|null $relationshipType Optional relationship type to the parent
     * @param int|null $requestId Optional database ID of the request
     */
    public function __construct(
        array $entityData,
        string $entityType,
        ?string $parentEntityId = null,
        ?string $relationshipType = null,
        $requestId = null,
    ) {
        // Remove type hint to accept both string and int
        $this->entityData = $entityData;
        $this->entityType = $entityType;
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
        WikibaseFormatterModels $formatterModels,
    ) {
        try {
            // Define a global constant to mark that we're in a queue worker
            if (!defined("LARAVEL_QUEUE_WORKER")) {
                define("LARAVEL_QUEUE_WORKER", true);
            }

            // Ensure role and relationship_type are consistent
            if (
                isset($this->entityData["role"]) &&
                isset($this->relationshipType) &&
                $this->entityData["role"] !== $this->relationshipType
            ) {
                Log::warning(
                    "Inconsistent role and relationship_type in job data",
                    [
                        "role" => $this->entityData["role"],
                        "relationship_type" => $this->relationshipType,
                        "entity_type" => $this->entityType,
                        "title" => $this->entityData["title"] ?? "Unknown",
                    ],
                );
            }

            // Check if this is the main entity (no parent ID)
            $isMainEntity = empty($this->parentEntityId);

            // If this is the main event entity (first entity in the chain), store its ID
            // in a dedicated global variable for all person entities to reference
            if ($isMainEntity && $this->entityType === "Q6983405") {
                // We will set the main_event_entity_id global after successful creation
                Log::info("This is the main event entity in the job chain", [
                    "entity_type" => $this->entityType,
                    "title" => $this->entityData["title"] ?? "Unknown",
                ]);
            }

            // Check if this entity already has a wikidata_id (for victims/perpetrators)
            if (
                isset($this->entityData["wikidata_id"]) &&
                !empty($this->entityData["wikidata_id"]) &&
                preg_match('/^Q\d+$/', $this->entityData["wikidata_id"])
            ) {
                Log::info(
                    "Using existing Wikidata entity instead of creating new one",
                    [
                        "wikidata_id" => $this->entityData["wikidata_id"],
                        "title" => $this->entityData["title"] ?? "Unknown",
                        "label" => $this->entityData["label"] ?? "Unknown",
                        "relationship" => $this->relationshipType,
                    ],
                );

                // Store the ID in globals for later use in related entities
                if (
                    isset($this->relationshipType) &&
                    $this->relationshipType === "victim"
                ) {
                    $GLOBALS["last_victim_entity_id"] =
                        $this->entityData["wikidata_id"];
                } elseif (
                    isset($this->relationshipType) &&
                    $this->relationshipType === "perpetrator"
                ) {
                    $GLOBALS["last_perpetrator_entity_id"] =
                        $this->entityData["wikidata_id"];
                }

                // No need to create a new entity, just process relationships if needed
                return;
            }

            // Validate and log environment settings for debugging
            $botUsername = config("wikidata.bot_username");
            Log::info("Entity creation job using bot credentials", [
                "username" => $botUsername,
                "has_password" => !empty(config("wikidata.bot_password")),
                "password_length" => strlen(
                    config("wikidata.bot_password") ?? "",
                ),
                "api_url" => config("wikidata.api_url"),
            ]);

            // Use direct bot login which uses username/password from config
            $csrfToken = $wikidataService->directBotLogin();

            if (!$csrfToken) {
                Log::error(
                    "Failed to login with bot credentials for entity creation",
                    [
                        "type" => $this->entityType,
                        "title" => $this->entityData["title"] ?? "Unknown",
                    ],
                );
                throw new \Exception("Failed to login with bot credentials");
            }

            Log::info("Successfully logged in with bot credentials", [
                "username" => config("wikidata.bot_username"),
                "type" => $this->entityType,
            ]);

            // Check if we need to replace the parent entity ID placeholder with actual ID
            // This is for child entities that were chained after the main entity was created
            if (
                $this->parentEntityId === "__MAIN_ENTITY_ID__" &&
                (isset($GLOBALS["main_event_entity_id"]) ||
                    isset($GLOBALS["last_created_entity_id"]))
            ) {
                // Store original parent ID before updating it
                $originalParentId = $this->parentEntityId;

                // For victims and perpetrators, ALWAYS link to the main event
                // This ensures proper entity relationships regardless of execution order
                // This is the critical fix for the parent-child relationship issue

                // CRITICAL FIX: Always use the main event ID for ALL person entities
                // This ensures all victims and perpetrators link directly to the event
                // regardless of the order in which they are created
                if (isset($GLOBALS["main_event_entity_id"])) {
                    // IMPORTANT: Always use main_event_entity_id for ALL related entities
                    // This is the key fix to ensure proper parent-child relationships
                    $this->parentEntityId = $GLOBALS["main_event_entity_id"];

                    // Store this directly in entity data as well
                    $this->entityData["_main_event_id"] =
                        $GLOBALS["main_event_entity_id"];

                    Log::info(
                        "Using main event ID as parent for related entity",
                        [
                            "entity_type" => $this->entityType,
                            "relationship" => $this->relationshipType,
                            "main_event_id" => $GLOBALS["main_event_entity_id"],
                            "entity_role" =>
                                $this->entityData["role"] ?? "not set",
                        ],
                    );
                } else {
                    // Fallback to last created entity, but log a warning
                    $this->parentEntityId = $GLOBALS["last_created_entity_id"];
                    Log::warning(
                        "Main event ID global variable not found - using fallback",
                        [
                            "fallback_id" => $GLOBALS["last_created_entity_id"],
                            "entity_type" => $this->entityType,
                            "relationship" => $this->relationshipType,
                        ],
                    );
                }

                // Also ensure that relationship_type is set correctly in the entity data
                if (
                    !isset($this->entityData["relationship_type"]) &&
                    $this->relationshipType
                ) {
                    $this->entityData["relationship_type"] =
                        $this->relationshipType;
                }

                // Ensure role matches relationship_type for consistency
                if (
                    !isset($this->entityData["role"]) &&
                    $this->relationshipType
                ) {
                    $this->entityData["role"] = $this->relationshipType;
                }

                Log::info(
                    "Replaced parent entity ID placeholder with actual ID",
                    [
                        "parent_id" => $this->parentEntityId,
                        "entity_type" => $this->entityType,
                        "relationship" => $this->relationshipType,
                        "original_parent_id" => $originalParentId,
                        "has_role" => isset($this->entityData["role"])
                            ? "yes"
                            : "no",
                        "role" => $this->entityData["role"] ?? "not set",
                    ],
                );

                // Also replace significant_event_id placeholder with the actual entity ID
                // This is needed for person entities to link back to the main event
                if (
                    isset($this->entityData["significant_event_id"]) &&
                    $this->entityData["significant_event_id"] ===
                        "__MAIN_ENTITY_ID__"
                ) {
                    $this->entityData["significant_event_id"] =
                        $GLOBALS["last_created_entity_id"];
                    Log::info(
                        "Replaced significant_event_id placeholder with actual ID",
                        [
                            "significant_event_id" =>
                                $this->entityData["significant_event_id"],
                            "entity_type" => $this->entityType,
                            "relationship" => $this->relationshipType,
                        ],
                    );
                }
            }

            // ***** AUTO-DETECT RELATED ENTITIES *****
            // If this is a main entity (no parent ID) and we have related entities,
            // we should use job chains instead of trying to create them directly in the formatter
            if (
                empty($this->parentEntityId) &&
                empty($this->entityData["_inChain"])
            ) {
                $hasVictims =
                    !empty($this->entityData["victims"]) &&
                    is_array($this->entityData["victims"]);
                $hasPerpetrators =
                    !empty($this->entityData["perpetrators"]) &&
                    is_array($this->entityData["perpetrators"]);
                $hasSources =
                    !empty($this->entityData["sources"]) &&
                    is_array($this->entityData["sources"]);
                $hasBooks =
                    (!empty($this->entityData["books"]) &&
                        is_array($this->entityData["books"])) ||
                    (!empty($this->entityData["book"]) &&
                        is_array($this->entityData["book"]));

                if (
                    $hasVictims ||
                    $hasPerpetrators ||
                    $hasSources ||
                    $hasBooks
                ) {
                    // We're a main entity with related entities - use job chains
                    Log::info(
                        "Auto-detected related entities - using job chain approach",
                        [
                            "type" => $this->entityType,
                            "victims" => $hasVictims
                                ? count($this->entityData["victims"])
                                : 0,
                            "perpetrators" => $hasPerpetrators
                                ? count($this->entityData["perpetrators"])
                                : 0,
                            "sources" => $hasSources
                                ? count($this->entityData["sources"])
                                : 0,
                            "has_books" => $hasBooks,
                        ],
                    );

                    // Use our static method to create entity with related entities
                    self::createEntityWithRelated(
                        $this->entityData,
                        $this->entityType,
                        $this->requestId,
                    );

                    // We've dispatched the job chain, so we don't need to continue with this job
                    Log::info("Main job complete - job chain dispatched");
                    return;
                }
            }

            Log::info("Starting job to create Wikidata entity", [
                "type" => $this->entityType,
                "title" => $this->entityData["title"] ?? "Untitled",
                "attempt" => $this->attempts(),
                "parent_id" => $this->parentEntityId,
                "relationship" => $this->relationshipType,
                "request_id" => $this->requestId,
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
                    Log::info(
                        "Skipping related entity processing since this is a child entity in a job chain",
                        [
                            "type" => $this->entityType,
                            "parent_id" => $this->parentEntityId,
                            "relationship" => $this->relationshipType,
                        ],
                    );
                }

                // Since we're using job chains to create related entities, we need to prevent
                // the formatter from creating them directly, but we still need to include
                // them so relationships are properly established.

                // Make a copy of the entity data that we'll modify
                $entityDataForFormatter = $this->entityData;

                // Check if there are images and handle them with our ImageHandler
                if (
                    isset($entityDataForFormatter["image"]) ||
                    isset($entityDataForFormatter["imageFilename"]) ||
                    isset($entityDataForFormatter["image_filename"])
                ) {
                    Log::info("Preprocessing images with ImageHandler");
                }

                // For victims, preserve them but modify to prevent creation
                if (!empty($entityDataForFormatter["victims"])) {
                    foreach (
                        $entityDataForFormatter["victims"]
                        as $key => $victim
                    ) {
                        // Mark it to NOT force create
                        $entityDataForFormatter["victims"][$key][
                            "_forceCreate"
                        ] = false;
                        // Add a flag that tells the formatter not to create this entity
                        $entityDataForFormatter["victims"][$key][
                            "_skipCreate"
                        ] = true;
                    }
                }

                // For perpetrators, preserve them but modify to prevent creation
                if (!empty($entityDataForFormatter["perpetrators"])) {
                    foreach (
                        $entityDataForFormatter["perpetrators"]
                        as $key => $perpetrator
                    ) {
                        // Mark it to NOT force create
                        $entityDataForFormatter["perpetrators"][$key][
                            "_forceCreate"
                        ] = false;
                        // Add a flag that tells the formatter not to create this entity
                        $entityDataForFormatter["perpetrators"][$key][
                            "_skipCreate"
                        ] = true;
                    }
                }

                // For sources/books, preserve them but modify to prevent creation
                if (!empty($entityDataForFormatter["sources"])) {
                    foreach (
                        $entityDataForFormatter["sources"]
                        as $key => $source
                    ) {
                        $entityDataForFormatter["sources"][$key][
                            "_forceCreate"
                        ] = false;
                        $entityDataForFormatter["sources"][$key][
                            "_skipCreate"
                        ] = true;
                    }
                }

                // Log what we're doing
                Log::info(
                    "Preparing entity data for formatter - added _skipCreate flags to prevent direct creation",
                    [
                        "entity_type" => $this->entityType,
                        "title" =>
                            $entityDataForFormatter["title"] ?? "Untitled",
                        "victims" => !empty($entityDataForFormatter["victims"])
                            ? count($entityDataForFormatter["victims"])
                            : 0,
                        "perpetrators" => !empty(
                            $entityDataForFormatter["perpetrators"]
                        )
                            ? count($entityDataForFormatter["perpetrators"])
                            : 0,
                    ],
                );

                // Format the entity data with relationships but prevent entity creation
                // First, make sure sources are properly included in the entity data
                if (!empty($this->entityData["sources"])) {
                    $entityDataForFormatter["sources"] =
                        $this->entityData["sources"];
                    Log::info(
                        "Including sources in entity data for formatting",
                        [
                            "sources_count" => count(
                                $this->entityData["sources"],
                            ),
                            "first_source" => !empty(
                                $this->entityData["sources"][0]
                            )
                                ? $this->entityData["sources"][0]["title"] ??
                                    "Unknown"
                                : "None",
                        ],
                    );
                }

                $formattedResult = $formatterModels->formatEntityData(
                    $entityDataForFormatter,
                    $this->entityType,
                    false, // Never try to create related entities directly
                );

                // Get the formatted data for the WikibaseService
                $formattedData = $formattedResult["formattedData"];

                // Create the entity using the formatted data
                // Enhanced image handling using the ImageHandler class
                // Extract image filename from any available source
                $imageFilename = ImageHandler::extractImageFilename(
                    $this->entityData,
                );

                // If we found an image, make sure it's properly set in the formatted data
                if ($imageFilename) {
                    // Set the image in the formatted data without the File: prefix
                    $formattedData[
                        "image"
                    ] = ImageHandler::prepareImageFilename(
                        $imageFilename,
                        false,
                    );
                    Log::info("Set image in formatted data", [
                        "image" => $formattedData["image"],
                        "original_filename" => $imageFilename,
                        "entity_type" => $this->entityType,
                    ]);
                }

                // Check if we have an image in the claims
                $imagePropertyId = $wikidataService
                    ->getMappingProvider()
                    ->getPropertyId("IMAGE");
                if (!empty($imagePropertyId) && !empty($imageFilename)) {
                    // Use the ImageHandler to add the image claim with proper formatting
                    $formattedData = ImageHandler::addImageClaim(
                        $formattedData,
                        $imageFilename,
                        $imagePropertyId,
                    );
                } elseif (!empty($imageFilename)) {
                    Log::warning(
                        "Could not find IMAGE property ID for image claim",
                        [
                            "image" => $imageFilename,
                            "entity_type" => $this->entityType,
                        ],
                    );
                }

                // Log the full entity data being sent
                Log::info("Sending formatted entity data to Wikidata", [
                    "entity_type" => $this->entityType,
                    "title" => $formattedData["labels"]["de"] ?? "Unknown",
                    "has_image" => !empty($formattedData["image"]),
                    "image_value" => $formattedData["image"] ?? "None",
                    "has_image_claim" => !empty(
                        $formattedData["claims"][$imagePropertyId]
                    )
                        ? "yes"
                        : "no",
                    "data_keys" => implode(", ", array_keys($formattedData)),
                ]);

                // The entity data is already properly formatted by the formatter/optimizer
                // Just pass it directly to the WikibaseService
                $response = $wikidataService->createEntity(
                    $formattedData, // Use the formatted data with our image fixes
                    $this->entityType,
                );

                if ($response && isset($response["entity"]["id"])) {
                    $entityId = $response["entity"]["id"];

                    // Store the entity ID in a global variable so chained jobs can access it
                    // This allows child jobs in the chain to link to this entity
                    $GLOBALS["last_created_entity_id"] = $entityId;

                    Log::info(
                        "Stored entity ID in global variable for job chain",
                        [
                            "entity_id" => $entityId,
                            "type" => $this->entityType,
                            "is_part_of_chain" => !empty($this->parentEntityId)
                                ? "child"
                                : "parent",
                        ],
                    );

                    // For debugging - track job chain execution
                    if (empty($this->parentEntityId)) {
                        // This is the main entity in a chain
                        // Store the main event ID in a global variable for all entities in the chain to use
                        $GLOBALS["main_event_entity_id"] = $entityId;

                        // Indicate this is the main entity in a job chain
                        Log::info(
                            "Main entity in job chain created - child jobs should execute next",
                            [
                                "entity_id" => $entityId,
                                "type" => $this->entityType,
                                "stored_as_main_event" => "yes",
                                "main_event_id" => $entityId,
                            ],
                        );

                        // Directly update the status in the database
                        // This serves as a fallback in case the callback fails
                        try {
                            if ($this->requestId) {
                                $requestModel =
                                    "\\App\\Models\\WikibaseEntryRequest";
                                $entryRequest = $requestModel::find(
                                    $this->requestId,
                                );

                                if ($entryRequest) {
                                    // Use EntryStatusController's static method to update status
                                    \App\Http\Controllers\EntryStatusController::updateStatusHistoryStatic(
                                        $entryRequest,
                                        "published",
                                        "Eintrag erfolgreich erstellt mit Wikidata-ID: {$entityId}",
                                    );

                                    // Also update the wikidata_id field
                                    $entryRequest->wikidata_id = $entityId;
                                    $entryRequest->created_entities = $createdEntities;
                                    $entryRequest->save();

                                    Log::info(
                                        "Direct database update successful",
                                        [
                                            "entity_id" => $entityId,
                                            "request_id" => $this->requestId,
                                        ],
                                    );
                                }
                            }
                        } catch (\Exception $dbEx) {
                            Log::warning(
                                "Direct database update failed: " .
                                    $dbEx->getMessage(),
                                [
                                    "entity_id" => $entityId,
                                    "request_id" => $this->requestId,
                                ],
                            );
                        }
                    } else {
                        // This is a child entity in a chain
                        Log::info("Child entity in job chain created", [
                            "entity_id" => $entityId,
                            "parent_id" => $this->parentEntityId,
                            "relationship" => $this->relationshipType,
                            "type" => $this->entityType,
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
                            $createdEntities,
                        );
                        $this->trackCreatedBookEntities(
                            $existingEntityIds,
                            $newlyCreatedEntityIds,
                            $createdEntities,
                        );
                        $this->trackCreatedVictims(
                            $existingEntityIds,
                            $newlyCreatedEntityIds,
                            $createdEntities,
                        );
                    }

                    // Update entity data again with any changes made during tracking
                    // Call updateEntityWithFormattedData with the newly created entity ID
                    $this->updateEntityWithFormattedData(
                        $formatterModels,
                        $entityId,
                        $this->entityData,
                        $this->entityType,
                    );

                    Log::info(
                        "Successfully created Wikidata entity in background job",
                        [
                            "entity_id" => $entityId,
                            "type" => $this->entityType,
                            "additional_entities" => count($createdEntities),
                            "referenced_entities" => count($existingEntityIds),
                            "newly_created_ids" => $newlyCreatedEntityIds,
                        ],
                    );

                    // If this entity has a parent entity it should be linked to
                    if ($this->parentEntityId && $this->relationshipType) {
                        Log::info("About to link entity to parent", [
                            "entity_id" => $entityId,
                            "parent_id" => $this->parentEntityId,
                            "relationship" => $this->relationshipType,
                            "parent_is_placeholder" =>
                                $this->parentEntityId === "__MAIN_ENTITY_ID__",
                        ]);
                        $this->linkEntityToParent($wikidataService, $entityId);
                    } else {
                        Log::warning(
                            "Skipping linking entity to parent - missing parent ID or relationship",
                            [
                                "has_parent_id" => !empty(
                                    $this->parentEntityId
                                ),
                                "has_relationship" => !empty(
                                    $this->relationshipType
                                ),
                                "entity_id" => $entityId,
                            ],
                        );
                    }

                    // For events, link this entity to any victims or perpetrators that were created
                    if (
                        $this->entityType ===
                            app(
                                EntityMappingProviderInterface::class,
                            )->getEntityTypeId(
                                "App\\Models\\Wikibase\\Event",
                            ) &&
                        !empty($createdEntities)
                    ) {
                        $this->linkMainEntityToChildren(
                            $wikidataService,
                            $entityId,
                            $createdEntities,
                        );
                    }

                    // Update the database status to reflect the entity was published in Wikidata
                    // This ensures the status is updated even if the callback fails
                    $this->updateRequestStatus($entityId);

                    // Log that we've updated the status directly
                    Log::info(
                        "Status updated directly in job to wikidata_published",
                        [
                            "entity_id" => $entityId,
                            "request_id" => $this->requestId,
                        ],
                    );

                    // For main entities only (not child entities in the chain), dispatch events
                    if (empty($this->parentEntityId)) {
                        // Dispatch an event about the entity creation
                        event(
                            new \App\Events\WikidataEntityCreated(
                                $entityId,
                                $this->entityType,
                                $createdEntities,
                                null, // No parent ID for main entity
                                null, // No relationship type for main entity
                                $this->entityData,
                            ),
                        );

                        Log::info("Dispatched event for main entity creation", [
                            "entity_id" => $entityId,
                            "type" => $this->entityType,
                            "created_related_entities" => count(
                                $createdEntities,
                            ),
                        ]);
                    } else {
                        // For child entities, dispatch a different event type that includes parent information
                        event(
                            new \App\Events\WikidataChildEntityCreated(
                                $entityId,
                                $this->entityType,
                                $this->parentEntityId,
                                $this->relationshipType,
                                $this->entityData,
                            ),
                        );

                        Log::info(
                            "Created child entity with parent relationship",
                            [
                                "entity_id" => $entityId,
                                "parent_id" => $this->parentEntityId,
                                "relationship" => $this->relationshipType,
                            ],
                        );
                    }
                } else {
                    // Check if the error is because the entity already exists
                    if (
                        $response &&
                        isset($response["error"]) &&
                        isset($response["error"]["info"]) &&
                        strpos(
                            $response["error"]["info"],
                            "already has label",
                        ) !== false &&
                        strpos(
                            $response["error"]["info"],
                            "using the same description text",
                        ) !== false
                    ) {
                        // Extract the existing entity ID from the error message
                        $existingEntityId = null;
                        $errorInfo = $response["error"]["info"];
                        if (
                            preg_match(
                                "/\[\[Q\d+\|?(Q\d+)?\]\]/",
                                $errorInfo,
                                $matches,
                            )
                        ) {
                            $existingEntityId =
                                isset($matches[1]) && !empty($matches[1])
                                    ? $matches[1]
                                    : substr($matches[0], 2, -2);
                        }
                        if ($existingEntityId) {
                            Log::info(
                                "Entity already exists, using existing entity",
                                [
                                    "existing_entity_id" => $existingEntityId,
                                    "request_id" => $this->requestId,
                                ],
                            );
                            // Update the request with the existing entity ID
                            $this->updateRequestStatus($existingEntityId);
                            return;
                        }
                    }

                    // Handle other failure cases
                    // Check if the entity was successfully created
                    if (isset($entityId) && !empty($entityId)) {
                        $this->updateRequestStatus($entityId);
                    } else {
                        // Check if the error is because the entity already exists
                        if (
                            $response &&
                            isset($response["error"]) &&
                            isset($response["error"]["info"])
                        ) {
                            $errorInfo = $response["error"]["info"];

                            // Check for duplicate entity error pattern
                            if (
                                strpos($errorInfo, "already has label") !==
                                    false &&
                                strpos(
                                    $errorInfo,
                                    "using the same description text",
                                ) !== false
                            ) {
                                // Extract the existing entity ID
                                $existingEntityId = null;
                                if (
                                    preg_match(
                                        "/\[\[Q\d+\|?(Q\d+)?\]\]/",
                                        $errorInfo,
                                        $matches,
                                    )
                                ) {
                                    $existingEntityId =
                                        isset($matches[1]) &&
                                        !empty($matches[1])
                                            ? $matches[1]
                                            : substr($matches[0], 2, -2);
                                }

                                // Create error message
                                $errorMessage =
                                    "Entity already exists in Wikidata";
                                if ($existingEntityId) {
                                    $errorMessage .=
                                        " with ID: " .
                                        $existingEntityId .
                                        ". You can view it at https://www.wikidata.org/wiki/" .
                                        $existingEntityId;
                                }

                                // Log the duplicate entity
                                Log::info("Duplicate entity detected", [
                                    "existing_entity_id" => $existingEntityId,
                                    "entity_title" =>
                                        $this->entityData["title"] ?? "Unknown",
                                    "entity_type" => $this->entityType,
                                    "request_id" => $this->requestId,
                                ]);

                                // Update the request status with the duplicate entity information
                                $this->updateRequestStatusToFailed(
                                    $errorMessage,
                                    $existingEntityId,
                                );
                            } else {
                                // Handle other types of failures
                                $this->handleEntityCreationFailure($response);
                            }
                        } else {
                            // Handle other types of failures
                            $this->handleEntityCreationFailure($response);
                        }
                    }
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
        array &$createdEntities,
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
                            ],
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
                (isset($entity["lastName"]) ? $entity["lastName"] : ""),
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
        array &$createdEntities,
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
                        ],
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
        array &$createdEntities,
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
                            ],
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
                "Failed to create Wikidata entity: " .
                    ($response ? json_encode($response) : "null"),
                null,
            );
        }

        // Throw an exception to trigger a retry
        throw new \Exception(
            "Failed to create Wikidata entity: " .
                ($response ? json_encode($response) : "null"),
        );
    }

    /**
     * Update the request status to failed
     *
     * @param string $errorMessage The error message
     * @param string|null $existingEntityId Optional existing entity ID for duplicate cases
     * @return void
     */
    protected function updateRequestStatusToFailed(
        string $errorMessage,
        ?string $existingEntityId = null,
    ): void {
        try {
            // First try to use the dedicated requestId property
            $requestId = $this->requestId;

            // Convert to integer if it's a string but numeric
            if (is_string($requestId) && is_numeric($requestId)) {
                $requestId = (int) $requestId;
            }

            // If not available, try to get it from the entityData
            if (empty($requestId) && !empty($this->entityData["id"])) {
                $requestId = $this->entityData["id"];

                // Also convert this to integer if needed
                if (is_string($requestId) && is_numeric($requestId)) {
                    $requestId = (int) $requestId;
                }
            }

            // Log the request ID being used
            Log::info("Updating request status to failed", [
                "request_id" => $requestId,
                "request_id_type" => gettype($requestId),
                "error_message" => $errorMessage,
            ]);

            if (!empty($requestId)) {
                // Get request model class name from config or use the correct default
                $requestModelClass = "\\App\\Models\\WikibaseEntryRequest";

                // Check if the model class exists before trying to use it
                if (!class_exists($requestModelClass)) {
                    Log::warning(
                        "Request model class {$requestModelClass} not found. Skipping status update.",
                        [
                            "error_message" => $errorMessage,
                            "request_id" => $requestId,
                        ],
                    );
                    return;
                }

                // Try to update with a simple query first (most reliable approach)
                try {
                    // Use direct DB query which is more reliable
                    // Note: We use "error" status for failures because SQLite CHECK constraints
                    // only allow specific status values (pending, approved, rejected, error, published)
                    $updateData = [
                        "status" => "error", // Use 'error' instead of 'wikidata_failed' due to database constraints
                        "rejection_reason" => $errorMessage,
                        "updated_at" => now(),
                        "status_updated_at" => now(),
                    ];

                    // If we have an existing entity ID, store it for reference
                    if ($existingEntityId) {
                        $updateData["wikidata_id"] = $existingEntityId;
                    }

                    // Try using Eloquent's query builder for more reliable updates
                    $affected = \DB::table("wikidata_entry_requests")
                        ->where("id", $requestId)
                        ->update($updateData);

                    Log::info(
                        "Updated request status to rejected using direct update",
                        [
                            "request_id" => $requestId,
                            "affected_rows" => $affected,
                        ],
                    );

                    if ($affected > 0) {
                        return; // Exit early if successful
                    } else {
                        Log::warning(
                            "Direct update did not affect any rows. Trying model approach.",
                            ["request_id" => $requestId],
                        );
                    }
                } catch (\Exception $updateEx) {
                    Log::warning(
                        "Direct update of status failed, trying full model: " .
                            $updateEx->getMessage(),
                    );
                    // Continue with model-based approach as fallback
                }

                // Find the request and update it with model approach
                try {
                    $request = $requestModelClass::find($requestId);

                    if ($request) {
                        $request->status = "error"; // Use 'error' instead of 'wikidata_failed' due to database constraints

                        // Check which error field to use based on schema
                        $table = $request->getTable();
                        if (
                            \Illuminate\Support\Facades\Schema::hasColumn(
                                $table,
                                "error_message",
                            )
                        ) {
                            $request->error_message = $errorMessage;
                        } elseif (
                            \Illuminate\Support\Facades\Schema::hasColumn(
                                $table,
                                "rejection_reason",
                            )
                        ) {
                            $request->rejection_reason = $errorMessage;
                        }

                        $request->save();

                        Log::info(
                            "Updated request status to rejected using model approach",
                            [
                                "request_id" => $requestId,
                            ],
                        );
                    }
                } catch (\Exception $modelEx) {
                    Log::error(
                        "Failed to update status using model approach: " .
                            $modelEx->getMessage(),
                    );
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
            $this->updateRequestStatusToFailed(
                "Error creating entity: " . $e->getMessage(),
                null,
            );

            // Log the failure for monitoring purposes
            Log::error("Entity creation failed after multiple retries", [
                "error" => $e->getMessage(),
            ]);
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
        $isModelNotFoundError =
            (strpos($e->getMessage(), "WikibaseEntryRequest") !== false ||
                strpos($e->getMessage(), "WikibaseRequest") !== false) &&
            strpos($e->getMessage(), "not found") !== false;

        if ($isModelNotFoundError) {
            Log::warning(
                "Non-critical error: Request model not found. Entity creation was likely successful.",
                [
                    "error" => $e->getMessage(),
                    "entity_data" => [
                        "type" => $this->entityType,
                        "title" => $this->entityData["title"] ?? "Unknown",
                    ],
                ],
            );

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
        $this->updateRequestStatusToFailed(
            "Fatal error during entity creation: " . $e->getMessage(),
            null,
        );

        // Log the fatal error
        Log::critical("Fatal error in CreateWikidataEntity job", [
            "error" => $e->getMessage(),
        ]);
    }

    /**
     * Update the status of the entry request to reflect the entity was published
     *
     * @param string $entityId The Wikidata entity ID
     * @param array $createdEntities Optional array of created entities
     * @return void
     */
    protected function updateRequestStatus(
        string $entityId,
        array $createdEntities = [],
    ): void {
        try {
            // First try to use the dedicated requestId property if it was provided
            $requestId = $this->requestId;

            // Convert to integer if it's a string but numeric
            if (is_string($requestId) && is_numeric($requestId)) {
                $requestId = (int) $requestId;
            }

            // If not available, try to get it from the entityData as a fallback
            if (empty($requestId) && !empty($this->entityData["id"])) {
                $requestId = $this->entityData["id"];

                // Also convert this to integer if needed
                if (is_string($requestId) && is_numeric($requestId)) {
                    $requestId = (int) $requestId;
                }
            }

            // Log the request ID being used
            Log::info("Updating request status", [
                "request_id" => $requestId,
                "entity_id" => $entityId,
                "request_id_type" => gettype($requestId),
            ]);

            // If we have a request ID, update the database
            if (!empty($requestId)) {
                // Get request model class name from config or use the correct default
                $requestModelClass = "\\App\\Models\\WikibaseEntryRequest";

                // Log the model class and table being used
                Log::info("Using model class for status update", [
                    "model_class" => $requestModelClass,
                    "table_name" => "wikidata_entry_requests",
                    "request_id" => $requestId,
                ]);

                // Check if the model class exists before trying to use it
                if (!class_exists($requestModelClass)) {
                    Log::warning(
                        "Request model class {$requestModelClass} not found. Skipping status update.",
                        [
                            "entity_id" => $entityId,
                            "request_id" => $requestId,
                        ],
                    );
                    return;
                }

                // Try to load the entry request model
                try {
                    $entryRequest = $requestModelClass::find($requestId);

                    // Log whether we found the entry request
                    Log::info("Entry request lookup result", [
                        "request_id" => $requestId,
                        "found" => $entryRequest ? "yes" : "no",
                        "status" => $entryRequest
                            ? $entryRequest->status
                            : "N/A",
                    ]);
                } catch (\Exception $e) {
                    Log::error("Error finding entry request", [
                        "request_id" => $requestId,
                        "error" => $e->getMessage(),
                    ]);
                    $entryRequest = null;
                }

                // Use a simple direct query update approach - most reliable, doesn't need schema checks
                try {
                    // Simple update with only the most critical fields
                    // Using "wikidata_published" status after fixing SQLite CHECK constraints
                    // The previous workaround of using "approved" is no longer needed
                    $updateData = [
                        "status" => "wikidata_published", // Now using proper status after fixing constraints
                        "wikidata_id" => $entityId,
                        "created_entities" => !empty($createdEntities)
                            ? json_encode($createdEntities)
                            : null,
                        "status_updated_at" => now(), // Ensure status_updated_at is set
                        "updated_at" => now(), // Also update the updated_at timestamp
                    ];

                    // Make sure we have a notification flag set for successful publications
                    if (!isset($updateData["notify_on_publish"])) {
                        $updateData["notify_on_publish"] = true;
                    }

                    // Try to update the record
                    // Log what we're about to update
                    Log::info("Attempting to update request status", [
                        "model_class" => $requestModelClass,
                        "request_id" => $requestId,
                        "new_status" => "wikidata_published", // Explicitly log the status we're setting
                        "entity_id" => $entityId,
                    ]);

                    // Log what we're trying to update
                    Log::info("Updating entry request directly", [
                        "table" => "wikidata_entry_requests",
                        "request_id" => $requestId,
                        "data" => $updateData,
                    ]);

                    // Try using Eloquent's query builder for more reliable updates
                    $affected = \DB::table("wikidata_entry_requests")
                        ->where("id", $requestId)
                        ->update($updateData);

                    // Log the result
                    Log::info("Direct update result", [
                        "request_id" => $requestId,
                        "affected_rows" => $affected,
                    ]);

                    // Reload the entry request to make sure we have the latest data
                    $entryRequest = $requestModelClass::find($requestId);

                    if ($affected) {
                        Log::info(
                            "Updated request status to published using model approach",
                            [
                                "request_id" => $this->requestId,
                                "entity_id" => $entityId,
                                "previous_status" =>
                                    $entryRequest->status ?? "unknown",
                            ],
                        );

                        // Ensure the model status was actually updated
                        if (
                            $entryRequest &&
                            isset($entryRequest->status) &&
                            $entryRequest->status !== "wikidata_published"
                        ) {
                            Log::warning(
                                "Status update not reflected in model - forcing update",
                                [
                                    "request_id" => $this->requestId,
                                    "entity_id" => $entityId,
                                    "current_status" => $entryRequest->status,
                                ],
                            );

                            // Force update with raw query as last resort
                            try {
                                \DB::table("wikidata_entry_requests")
                                    ->where("id", $requestId)
                                    ->update([
                                        "status" => "published",
                                        "wikidata_id" => $entityId,
                                        "created_entities" => !empty(
                                            $createdEntities
                                        )
                                            ? json_encode($createdEntities)
                                            : null,
                                        "updated_at" => now(),
                                        "status_updated_at" => now(),
                                    ]);

                                Log::info(
                                    "Forced status update via direct DB query",
                                );
                            } catch (\Exception $dbEx) {
                                Log::error(
                                    "Failed even with direct DB update: " .
                                        $dbEx->getMessage(),
                                );
                            }
                        }

                        // Notification handling using EntryStatusController
                        try {
                            if ($entryRequest) {
                                $statusController = app(
                                    \App\Http\Controllers\EntryStatusController::class,
                                );
                                $statusController->sendStatusChangeNotifications(
                                    $entryRequest,
                                    "wikidata_published",
                                    true, // Force notification
                                );
                            }
                        } catch (\Exception $e) {
                            Log::error(
                                "Failed to send notification: " .
                                    $e->getMessage(),
                            );
                        }

                        // Ensure the model status was actually updated
                        if (
                            $entryRequest &&
                            isset($entryRequest->status) &&
                            $entryRequest->status !== "wikidata_published"
                        ) {
                            Log::warning(
                                "Status update not reflected in model - forcing update",
                                [
                                    "request_id" => $this->requestId,
                                    "entity_id" => $entityId,
                                    "current_status" => $entryRequest->status,
                                ],
                            );

                            // Force update with raw query as last resort
                            try {
                                \DB::table("wikidata_entry_requests")
                                    ->where("id", $requestId)
                                    ->update([
                                        "status" => "wikidata_published", // Using proper wikidata_published status
                                        "wikidata_id" => $entityId,
                                        "updated_at" => now(),
                                        "status_updated_at" => now(),
                                    ]);

                                Log::info(
                                    "Forced status update via direct DB query",
                                );
                            } catch (\Exception $dbEx) {
                                Log::error(
                                    "Failed even with direct DB update: " .
                                        $dbEx->getMessage(),
                                );
                            }
                        }

                        // Force trigger notification manually after successful status update
                        try {
                            $statusController = app(
                                \App\Http\Controllers\EntryStatusController::class,
                            );
                            // Reload the entry request to make sure we have the latest data
                            $entryRequest = \App\Models\WikibaseEntryRequest::find(
                                $requestId,
                            );
                            if ($entryRequest) {
                                Log::info(
                                    "Manually triggering notification after status update",
                                    [
                                        "request_id" => $requestId,
                                        "entity_id" => $entityId,
                                        "has_email" => !empty(
                                            $entryRequest->email
                                        )
                                            ? "yes"
                                            : "no",
                                        "email" =>
                                            $entryRequest->email ?? "not set",
                                    ],
                                );

                                // Force notification for published status
                                $statusController->sendStatusChangeNotifications(
                                    $entryRequest,
                                    "wikidata_published",
                                    true, // Force notification
                                );
                            }
                        } catch (\Exception $notifyEx) {
                            Log::error(
                                "Failed to send manual notification after status update",
                                [
                                    "request_id" => $requestId,
                                    "entity_id" => $entityId,
                                    "error" => $notifyEx->getMessage(),
                                ],
                            );

                            // Force trigger notification manually after model update
                            try {
                                $statusController = app(
                                    \App\Http\Controllers\EntryStatusController::class,
                                );
                                Log::info(
                                    "Manually triggering notification after model update",
                                    [
                                        "request_id" => $requestId,
                                        "entity_id" => $entityId,
                                        "has_email" =>
                                            $entryRequest &&
                                            !empty($entryRequest->email)
                                                ? "yes"
                                                : "no",
                                        "email" => $entryRequest
                                            ? $entryRequest->email ?? "not set"
                                            : "not set",
                                    ],
                                );

                                if ($entryRequest) {
                                    $statusController->sendStatusChangeNotifications(
                                        $entryRequest,
                                        "wikidata_published",
                                        true, // Force notification
                                    );
                                }
                            } catch (\Exception $notifyEx) {
                                Log::error(
                                    "Failed to send manual notification after model update",
                                    [
                                        "request_id" => $requestId,
                                        "entity_id" => $entityId,
                                        "error" => $notifyEx->getMessage(),
                                    ],
                                );
                            }
                        }
                    } else {
                        Log::warning(
                            "Could not find request to update status",
                            [
                                "request_id" => $requestId,
                                "entity_id" => $entityId,
                            ],
                        );
                    }
                } catch (\Exception $updateEx) {
                    // If we can't update with a query, last resort is to try a full model update
                    Log::warning(
                        "Simple database update failed: " .
                            $updateEx->getMessage(),
                        [
                            "request_id" => $requestId,
                            "entity_id" => $entityId,
                        ],
                    );

                    // Last attempt - try to use the model directly
                    try {
                        $request = $requestModelClass::find($requestId);
                        if ($request) {
                            $request->status = "wikidata_published"; // Using wikidata_published status directly
                            $request->wikidata_id = $entityId;
                            $request->created_entities = !empty(
                                $createdEntities
                            )
                                ? $createdEntities
                                : null;
                            $request->status_updated_at = now(); // Ensure status_updated_at is set
                            // Reset notification sent status to ensure a notification is sent
                            if (
                                Schema::hasColumn(
                                    "wikidata_entry_requests",
                                    "notification_sent_at",
                                )
                            ) {
                                $request->notification_sent_at = null;
                            }
                            // Don't set wikidata_url at all
                            $request->save();

                            Log::info("Updated request with model approach", [
                                "request_id" => $requestId,
                                "entity_id" => $entityId,
                            ]);
                        }
                    } catch (\Exception $modelEx) {
                        Log::error(
                            "All update approaches failed: " .
                                $modelEx->getMessage(),
                            [
                                "request_id" => $requestId,
                                "entity_id" => $entityId,
                            ],
                        );
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
     * Add sources to a person entity using the BaseEntity.addSource method
     *
     * @param WikibaseService $wikidataService The wikidata service
     * @param string $entityId The entity ID to add sources to
     * @param array $sources The sources to add
     * @return void
     */
    protected function addSourcesUsingBaseEntity(
        WikibaseService $wikidataService,
        string $entityId,
        array $sources,
    ): void {
        try {
            // Ensure we have sources to add
            if (empty($sources)) {
                Log::warning(
                    "No sources provided to addSourcesUsingBaseEntity",
                    [
                        "entity_id" => $entityId,
                    ],
                );
                return;
            }

            // Log the sources we're working with
            Log::info("Sources data for person entity", [
                "entity_id" => $entityId,
                "sources_count" => count($sources),
                "sources_data" => json_encode(
                    array_map(function ($source) {
                        return [
                            "type" => $source["type"] ?? "unknown",
                            "title" => $source["title"] ?? "untitled",
                        ];
                    }, $sources),
                ),
            ]);

            // Create a Person object for the entity
            $mappingProvider = app(EntityMappingProviderInterface::class);
            $person = new Person(
                "Person entity {$entityId}",
                "Person entity from Wikidata",
                $mappingProvider,
            );

            // Get entity data if available and set it to the person
            if (!empty($this->entityData["title"])) {
                $person = new Person(
                    $this->entityData["title"],
                    $this->entityData["description"] ??
                        "Person entity from Wikidata",
                    $mappingProvider,
                );
            }

            // Create source models from the source data
            $sourceModels = SourceFactory::createMultipleFromArray($sources);

            Log::info("Created source models for person entity", [
                "entity_id" => $entityId,
                "source_models_count" => count($sourceModels),
                "source_models_types" => implode(
                    ", ",
                    array_map(function ($source) {
                        return $source->getType() ?? "unknown";
                    }, $sourceModels),
                ),
            ]);

            // Direct approach - create all claims directly
            $updateData = ["claims" => []];

            // Add URL sources (P973)
            $urlProperty = $mappingProvider->getPropertyId("STATED_IN_URL");
            $updateData["claims"][$urlProperty] = [];

            // Add described by sources (P1343)
            $describedByProperty = $mappingProvider->getPropertyId(
                "DESCRIBED_BY_SOURCE",
            );
            $updateData["claims"][$describedByProperty] = [];

            // Process each source directly
            foreach ($sourceModels as $source) {
                // For URL sources
                if (
                    $source->getType() === "url" &&
                    method_exists($source, "getUrl")
                ) {
                    $url = $source->getUrl();
                    if (!empty($url)) {
                        // Create a URL claim
                        $urlClaim = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => $urlProperty,
                                "datavalue" => [
                                    "value" => $url,
                                    "type" => "string",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ];

                        // Add qualifiers if needed (language, retrieval date)
                        if (
                            method_exists($source, "getRetrievalDate") ||
                            method_exists($source, "getLanguage")
                        ) {
                            $urlClaim["qualifiers"] = [];
                            $urlClaim["qualifiers-order"] = [];

                            // Add language qualifier (German - Q188)
                            $languageProperty = $mappingProvider->getPropertyId(
                                "LANGUAGE",
                            );
                            $urlClaim["qualifiers"][$languageProperty] = [
                                [
                                    "snaktype" => "value",
                                    "property" => $languageProperty,
                                    "datavalue" => [
                                        "value" => [
                                            "entity-type" => "item",
                                            "id" => "Q188", // German
                                            "numeric-id" => 188,
                                        ],
                                        "type" => "wikibase-entityid",
                                    ],
                                ],
                            ];
                            $urlClaim["qualifiers-order"][] = $languageProperty;

                            // Add retrieval date qualifier
                            $retrievalDateProperty = $mappingProvider->getPropertyId(
                                "RETRIEVAL_DATE",
                            );
                            $retrievalDate = method_exists(
                                $source,
                                "getRetrievalDate",
                            )
                                ? $source->getRetrievalDate()
                                : date("Y-m-d");
                            $urlClaim["qualifiers"][$retrievalDateProperty] = [
                                [
                                    "snaktype" => "value",
                                    "property" => $retrievalDateProperty,
                                    "datavalue" => [
                                        "value" => [
                                            "time" =>
                                                "+" .
                                                $retrievalDate .
                                                "T00:00:00Z",
                                            "timezone" => 0,
                                            "before" => 0,
                                            "after" => 0,
                                            "precision" => 11,
                                            "calendarmodel" =>
                                                "http://www.wikidata.org/entity/Q1985727",
                                        ],
                                        "type" => "time",
                                    ],
                                ],
                            ];
                            $urlClaim[
                                "qualifiers-order"
                            ][] = $retrievalDateProperty;
                        }

                        $updateData["claims"][$urlProperty][] = $urlClaim;
                        Log::info("Added URL source claim for person entity", [
                            "entity_id" => $entityId,
                            "url" => $url,
                            "title" => $source->getTitle() ?? "Untitled",
                        ]);
                    }
                }

                // For archive/book sources
                if (
                    $source->getType() === "archive" ||
                    $source->getType() === "book"
                ) {
                    $sourceEntityId = method_exists($source, "getEntityId")
                        ? $source->getEntityId()
                        : null;
                    $sourceTitle = $source->getTitle() ?? "Unknown source";

                    if (!empty($sourceEntityId)) {
                        // Create a described-by claim
                        $describedByClaim = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => $describedByProperty,
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => $sourceEntityId,
                                        "numeric-id" => intval(
                                            substr($sourceEntityId, 1),
                                        ),
                                    ],
                                    "type" => "wikibase-entityid",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ];

                        // Add inventory number if available
                        if (
                            method_exists($source, "getInventoryNumber") &&
                            !empty($source->getInventoryNumber())
                        ) {
                            $inventoryProperty = $mappingProvider->getPropertyId(
                                "INVENTORY_NUMBER",
                            );
                            $describedByClaim["qualifiers"] = [
                                $inventoryProperty => [
                                    [
                                        "snaktype" => "value",
                                        "property" => $inventoryProperty,
                                        "datavalue" => [
                                            "value" => $source->getInventoryNumber(),
                                            "type" => "string",
                                        ],
                                    ],
                                ],
                            ];
                            $describedByClaim["qualifiers-order"] = [
                                $inventoryProperty,
                            ];
                        }

                        $updateData["claims"][
                            $describedByProperty
                        ][] = $describedByClaim;
                        Log::info(
                            "Added described-by source claim for person entity",
                            [
                                "entity_id" => $entityId,
                                "source_entity_id" => $sourceEntityId,
                                "title" => $sourceTitle,
                            ],
                        );
                    } else {
                        Log::warning(
                            "Source has no entity ID, skipping described-by claim",
                            [
                                "source_type" => $source->getType(),
                                "source_title" => $sourceTitle,
                            ],
                        );
                    }
                }
            }

            // Remove empty claim arrays
            if (empty($updateData["claims"][$urlProperty])) {
                unset($updateData["claims"][$urlProperty]);
            }

            if (empty($updateData["claims"][$describedByProperty])) {
                unset($updateData["claims"][$describedByProperty]);
            }

            // Log the claims we're adding
            Log::info("Source claims prepared for person entity", [
                "entity_id" => $entityId,
                "url_claims" => !empty($updateData["claims"][$urlProperty])
                    ? count($updateData["claims"][$urlProperty])
                    : 0,
                "described_by_claims" => !empty(
                    $updateData["claims"][$describedByProperty]
                )
                    ? count($updateData["claims"][$describedByProperty])
                    : 0,
                "claims_data" => json_encode($updateData["claims"]),
            ]);

            // Update the entity with the source claims
            if (!empty($updateData["claims"])) {
                $result = $wikidataService->updateEntity(
                    $entityId,
                    $updateData,
                    $mappingProvider->getEntityTypeId(
                        "App\\Models\\Wikibase\\Person",
                    ),
                );

                if ($result) {
                    Log::info(
                        "Successfully added source claims to person entity",
                        [
                            "entity_id" => $entityId,
                            "claim_count" => count($updateData["claims"]),
                        ],
                    );
                } else {
                    Log::error("Failed to add source claims to person entity", [
                        "entity_id" => $entityId,
                        "error" => "WikibaseService returned false or null",
                    ]);
                }
            } else {
                Log::warning("No source claims to add to person entity", [
                    "entity_id" => $entityId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception while adding sources to person entity", [
                "error" => $e->getMessage(),
                "entity_id" => $entityId,
                "trace" => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Link this entity to its parent entity
     *
     * For victims and perpetrators, ensures they are always linked to the main event entity
     * rather than to each other, to maintain correct data relationships
     *
     * @param WikibaseService $wikidataService
     * @param string $entityId The newly created entity ID
     * @return void
     */
    protected function linkEntityToParent(
        WikibaseService $wikidataService,
        string $entityId,
    ) {
        // CRITICAL FIX: For any person entity (victim or perpetrator), ALWAYS use the main event ID
        // as the parent - this ensures the correct data structure in Wikidata
        if (
            $this->entityType === "Q5" && // Person entity type
            isset($GLOBALS["main_event_entity_id"])
        ) {
            // Only log if we're changing the parent
            if ($this->parentEntityId !== $GLOBALS["main_event_entity_id"]) {
                Log::info(
                    "OVERRIDE: Forcing main event as parent for person entity",
                    [
                        "entity_id" => $entityId,
                        "original_parent" => $this->parentEntityId,
                        "main_event_id" => $GLOBALS["main_event_entity_id"],
                        "relationship_type" => $this->relationshipType,
                        "entity_role" => $this->entityData["role"] ?? "not set",
                    ],
                );
            }

            // CRITICAL: Always use the main event as parent for all person entities
            // This is the key fix to ensure proper data relationships in Wikidata
            $this->parentEntityId = $GLOBALS["main_event_entity_id"];

            // Also update the significant_event_id to ensure proper back-links
            if (isset($this->entityData["significant_event_id"])) {
                $this->entityData["significant_event_id"] =
                    $GLOBALS["main_event_entity_id"];
            }

            // Make sure role and relationship_type are set correctly and consistently
            if ($this->relationshipType) {
                $this->entityData["role"] = $this->relationshipType;
                $this->entityData["relationship_type"] =
                    $this->relationshipType;

                // Log the relationship for debugging
                Log::info("Setting consistent role and relationship type", [
                    "entity_id" => $entityId,
                    "relationship_type" => $this->relationshipType,
                    "role" => $this->relationshipType,
                    "parent_id" => $this->parentEntityId,
                    "parent_is_main_event" =>
                        $this->parentEntityId ===
                        $GLOBALS["main_event_entity_id"]
                            ? "yes"
                            : "no",
                ]);
            }
        }
        try {
            // Check if we have a parent entity ID and relationship type
            if (empty($this->parentEntityId)) {
                Log::warning(
                    "Cannot link entity to parent - missing parent ID",
                    [
                        "entity_id" => $entityId,
                        "relationship" => $this->relationshipType ?? "unknown",
                    ],
                );
                return;
            }

            if (empty($this->relationshipType)) {
                Log::warning(
                    "Cannot link entity to parent - missing relationship type",
                    [
                        "entity_id" => $entityId,
                        "parent_id" => $this->parentEntityId,
                    ],
                );
                return;
            }

            // Double check that we're not using a placeholder parent ID
            if ($this->parentEntityId === "__MAIN_ENTITY_ID__") {
                // Try one more time to replace the placeholder with the correct main event ID
                if (isset($GLOBALS["main_event_entity_id"])) {
                    $this->parentEntityId = $GLOBALS["main_event_entity_id"];
                    Log::info("Fixed placeholder parent ID at linking time", [
                        "entity_id" => $entityId,
                        "main_event_id" => $this->parentEntityId,
                        "relationship" => $this->relationshipType,
                        "role" => $this->entityData["role"] ?? "not set",
                    ]);
                } elseif (isset($GLOBALS["last_created_entity_id"])) {
                    // Fallback to last created entity if main event ID is not available
                    $this->parentEntityId = $GLOBALS["last_created_entity_id"];
                    Log::warning(
                        "Using last_created_entity_id as fallback - main_event_entity_id not found",
                        [
                            "entity_id" => $entityId,
                            "last_created_id" => $this->parentEntityId,
                            "relationship" => $this->relationshipType,
                        ],
                    );
                } else {
                    Log::error(
                        "Cannot link to parent - parent ID is still a placeholder",
                        [
                            "entity_id" => $entityId,
                            "parent_id" => $this->parentEntityId,
                            "relationship" => $this->relationshipType,
                        ],
                    );
                    return;
                }
            }

            // Verify that we're linking to the correct parent type
            // Events (Q6983405) should be the parent for both victims and perpetrators
            if (
                $this->relationshipType === "perpetrator" ||
                $this->relationshipType === "victim"
            ) {
                // For person entities, check if we're linking to a person instead of an event
                // This would indicate an incorrect parent-child relationship
                $mappingProvider = app(
                    \App\Services\EntityMapping\EntityMappingProviderInterface::class,
                );

                // For person entities (victims/perpetrators), ALWAYS link to the main event entity
                // This is the critical fix for the parent-child relationship issue
                // All victims and perpetrators must link directly to the event entity,
                // not to each other, to maintain correct data relationships in Wikidata
                if (isset($GLOBALS["main_event_entity_id"])) {
                    // Only log a warning if we were about to use the wrong parent
                    if (
                        $GLOBALS["main_event_entity_id"] !==
                        $this->parentEntityId
                    ) {
                        Log::warning(
                            "FIXING RELATIONSHIP: Entity would be linked to wrong parent - correcting to main event",
                            [
                                "entity_id" => $entityId,
                                "entity_type" => $this->entityType,
                                "incorrect_parent_id" => $this->parentEntityId,
                                "correct_main_event_id" =>
                                    $GLOBALS["main_event_entity_id"],
                                "relationship" => $this->relationshipType,
                                "entity_role" =>
                                    $this->entityData["role"] ?? "not set",
                            ],
                        );
                    }

                    // CRITICAL FIX: Always use the main event ID for victims and perpetrators
                    $this->parentEntityId = $GLOBALS["main_event_entity_id"];

                    // Set the parent entity ID directly in the entity data as well
                    $this->entityData["parent_entity_id"] =
                        $GLOBALS["main_event_entity_id"];

                    // Ensure the significant_event property will use the main event
                    if (isset($this->entityData["significant_event_id"])) {
                        $this->entityData["significant_event_id"] =
                            $GLOBALS["main_event_entity_id"];
                    }

                    // Log the parent ID usage for transparency
                    Log::info("Using main event as parent for person entity", [
                        "entity_id" => $entityId,
                        "main_event_id" => $GLOBALS["main_event_entity_id"],
                        "relationship" => $this->relationshipType,
                        "entity_role" => $this->entityData["role"] ?? "not set",
                    ]);
                } else {
                    Log::warning(
                        "Cannot ensure correct parent - main_event_entity_id not available",
                        [
                            "entity_id" => $entityId,
                            "current_parent_id" => $this->parentEntityId,
                        ],
                    );
                }

                // Check if role matches relationship_type for consistency
                if (
                    isset($this->entityData["role"]) &&
                    $this->entityData["role"] !== $this->relationshipType
                ) {
                    Log::warning("Role and relationship_type mismatch", [
                        "entity_id" => $entityId,
                        "role" => $this->entityData["role"] ?? "not set",
                        "relationship_type" => $this->relationshipType,
                        "will_use" => $this->relationshipType, // We'll use the relationship_type for linking
                    ]);

                    // Fix the role to match the relationship_type
                    $this->entityData["role"] = $this->relationshipType;
                }

                // Log the parent ID and relationship for debugging
                Log::info("Verifying parent entity for person relationship", [
                    "entity_id" => $entityId,
                    "parent_id" => $this->parentEntityId,
                    "relationship" => $this->relationshipType,
                    "entity_data_role" =>
                        $this->entityData["role"] ?? "not set",
                    "entity_data_has_relationship_type" => isset(
                        $this->entityData["relationship_type"],
                    )
                        ? "yes"
                        : "no",
                    "entity_data_relationship_type" =>
                        $this->entityData["relationship_type"] ?? "not set",
                ]);
            }

            Log::info("Linking entity to parent", [
                "entity_id" => $entityId,
                "parent_id" => $this->parentEntityId,
                "relationship" => $this->relationshipType,
                "debug_backtrace" => debug_backtrace(
                    DEBUG_BACKTRACE_IGNORE_ARGS,
                    3,
                ),
            ]);

            // Different linking properties based on relationship type
            $linkProperty = null;
            $mappingProvider = app(
                \App\Services\EntityMapping\EntityMappingProviderInterface::class,
            );

            switch ($this->relationshipType) {
                case "source":
                    $linkProperty = $mappingProvider->getPropertyId(
                        "DESCRIBED_BY_SOURCE",
                    ); // Described by source
                    break;
                case "perpetrator":
                    $linkProperty = $mappingProvider->getPropertyId(
                        "PERPETRATOR",
                    ); // Has perpetrator
                    break;
                case "victim":
                    $linkProperty = $mappingProvider->getPropertyId("VICTIM"); // Has victim
                    break;
                case "book": // For backwards compatibility
                    $linkProperty = $mappingProvider->getPropertyId(
                        "DESCRIBED_BY_SOURCE",
                    ); // Treat books as sources
                    break;
                default:
                    Log::warning(
                        "Unknown relationship type for entity linking",
                        [
                            "relationship" => $this->relationshipType,
                            "entity_id" => $entityId,
                            "parent_id" => $this->parentEntityId,
                        ],
                    );
                    return; // Unknown relationship type
            }

            if ($linkProperty) {
                // Create the statement to add to the parent entity with proper structure
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

                // Add qualifiers if needed (e.g., page numbers for sources, locations for victims)
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
                // Add location qualifier for victims/perpetrators if available
                elseif (
                    ($this->relationshipType === "victim" ||
                        $this->relationshipType === "perpetrator") &&
                    !empty($this->entityData["location"]) &&
                    !empty($this->entityData["location"]["id"])
                ) {
                    $locationId = $this->entityData["location"]["id"];
                    $mappingProvider = app(
                        \App\Services\EntityMapping\EntityMappingProviderInterface::class,
                    );
                    $locationProperty = $mappingProvider->getPropertyId(
                        "LOCATION",
                    ); // Should map to P276

                    // Log the location data being used for qualifier
                    Log::info(
                        "Adding location qualifier to victim/perpetrator relationship",
                        [
                            "entity_type" => $this->relationshipType,
                            "location_id" => $locationId,
                            "location_property" => $locationProperty,
                            "parent_id" => $this->parentEntityId,
                            "entity_id" => $entityId,
                            "mapping_provider_class" => get_class(
                                $mappingProvider,
                            ),
                        ],
                    );

                    $statement["qualifiers"] = [
                        $locationProperty => [
                            [
                                "snaktype" => "value",
                                "property" => $locationProperty,
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => $locationId,
                                        "numeric-id" => intval(
                                            substr($locationId, 1),
                                        ),
                                    ],
                                    "type" => "wikibase-entityid",
                                ],
                            ],
                        ],
                    ];
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
                        $parentEntityType = $mappingProvider->getEntityTypeId(
                            "App\\Models\\Wikibase\\Event",
                        );
                        break;
                    case "source":
                    case "book":
                        // Parent could be any entity type, default to event but check ID
                        if (substr($this->parentEntityId, 0, 1) === "Q") {
                            // If we can't determine it, use a generic type that can accept sources
                            $parentEntityType = $mappingProvider->getEntityTypeId(
                                "App\\Models\\Wikibase\\Event",
                            );
                        }
                        break;
                }

                // Fallback if we couldn't determine the type
                if (!$parentEntityType) {
                    $parentEntityType = $mappingProvider->getEntityTypeId(
                        "App\\Models\\Wikibase\\Event",
                    ); // Default to event
                }

                // Log the statement we're about to add
                Log::info("Adding link statement to parent entity", [
                    "parent_id" => $this->parentEntityId,
                    "property" => $linkProperty,
                    "child_id" => $entityId,
                    "relationship" => $this->relationshipType,
                ]);

                // Ensure the parentEntityId is valid
                if (
                    empty($this->parentEntityId) ||
                    !preg_match('/^Q\d+$/', $this->parentEntityId)
                ) {
                    Log::error("Invalid parent entity ID format for linking", [
                        "parent_id" => $this->parentEntityId,
                        "child_id" => $entityId,
                        "relationship" => $this->relationshipType,
                    ]);
                    return;
                }

                // Prepare update data with claims
                $updateData = [
                    "claims" => [
                        $linkProperty => [$statement],
                    ],
                ];

                // Log what we're about to send
                Log::info(
                    "Preparing to update parent entity with link to child",
                    [
                        "parent_id" => $this->parentEntityId,
                        "child_id" => $entityId,
                        "property" => $linkProperty,
                        "update_data" => $updateData,
                    ],
                );

                $result = $wikidataService->updateEntity(
                    $this->parentEntityId,
                    $updateData,
                    $parentEntityType, // Use parent entity type
                );

                if ($result) {
                    Log::info("Successfully linked entity to its parent", [
                        "child_id" => $entityId,
                        "parent_id" => $this->parentEntityId,
                        "relationship" => $this->relationshipType,
                    ]);

                    // For victim or perpetrator entities, also add a significant event (P793) link back to the parent
                    if (
                        $this->relationshipType === "victim" ||
                        $this->relationshipType === "perpetrator"
                    ) {
                        // Create statement to add the main entity as a significant event (P793)
                        $backLinkStatement = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => "P793", // Significant event (Schlüsselevent)
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => $this->parentEntityId,
                                        "numeric-id" => intval(
                                            substr($this->parentEntityId, 1),
                                        ),
                                    ],
                                    "type" => "wikibase-entityid",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ];

                        // Ensure the entity ID is valid
                        if (
                            empty($entityId) ||
                            !preg_match('/^Q\d+$/', $entityId)
                        ) {
                            Log::error(
                                "Invalid entity ID format for back-link",
                                [
                                    "entity_id" => $entityId,
                                    "parent_id" => $this->parentEntityId,
                                    "relationship" => $this->relationshipType,
                                ],
                            );
                            return;
                        }

                        // Prepare back-link update data
                        $backLinkData = [
                            "claims" => [
                                "P793" => [$backLinkStatement],
                            ],
                        ];

                        // Log the back-link update we're about to perform
                        Log::info(
                            "Preparing to add significant event back-link to person entity",
                            [
                                "person_id" => $entityId,
                                "event_id" => $this->parentEntityId,
                                "update_data" => $backLinkData,
                            ],
                        );

                        // Add the P793 back-link (child entity points to parent as significant event)
                        $backLinkResult = $wikidataService->updateEntity(
                            $entityId,
                            $backLinkData,
                            $mappingProvider->getEntityTypeId(
                                "App\\Models\\Wikibase\\Person",
                            ), // Person entity type for victims/perpetrators
                        );

                        if ($backLinkResult) {
                            Log::info(
                                "Successfully added P793 significant event back-link",
                                [
                                    "person_id" => $entityId,
                                    "event_id" => $this->parentEntityId,
                                    "relationship" => $this->relationshipType,
                                ],
                            );

                            // Now that the back-link is added, add sources to the person entity
                            if (!empty($this->entityData["sources"])) {
                                Log::info(
                                    "Adding sources to newly created person entity",
                                    [
                                        "entity_id" => $entityId,
                                        "sources_count" => count(
                                            $this->entityData["sources"],
                                        ),
                                        "parent_id" => $this->parentEntityId,
                                        "first_source" => !empty(
                                            $this->entityData["sources"][0]
                                        )
                                            ? $this->entityData["sources"][0][
                                                    "title"
                                                ] ?? "Unknown"
                                            : "None",
                                    ],
                                );

                                // Create a Person object and add sources to it
                                $this->addSourcesUsingBaseEntity(
                                    $wikidataService,
                                    $entityId,
                                    $this->entityData["sources"],
                                );
                            } else {
                                Log::warning(
                                    "No sources available to add to person entity",
                                    [
                                        "entity_id" => $entityId,
                                        "parent_id" => $this->parentEntityId,
                                        "has_entity_data" => !empty(
                                            $this->entityData
                                        )
                                            ? "yes"
                                            : "no",
                                        "entity_data_keys" => !empty(
                                            $this->entityData
                                        )
                                            ? implode(
                                                ", ",
                                                array_keys($this->entityData),
                                            )
                                            : "none",
                                    ],
                                );
                            }
                        } else {
                            Log::error(
                                "Failed to add P793 significant event back-link",
                                [
                                    "person_id" => $entityId,
                                    "event_id" => $this->parentEntityId,
                                ],
                            );
                        }
                    }
                } else {
                    Log::error("Failed to link entity to its parent", [
                        "child_id" => $entityId,
                        "parent_id" => $this->parentEntityId,
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
        array $createdEntities,
    ): void {
        try {
            Log::info("Linking main entity to created children", [
                "main_entity_id" => $mainEntityId,
                "created_entities_count" => count($createdEntities),
            ]);

            if (empty($createdEntities)) {
                return;
            }

            // Get mapping provider for entity type information
            $mappingProvider = app(EntityMappingProviderInterface::class);
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
                        "property" => "P793",
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $mainEntityId,
                            ],
                            "type" => "wikibase-entityid",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ];

                // Call the WikibaseService to add this statement to the person entity
                $result = $wikidataService->updateEntity(
                    $entity["id"],
                    [
                        "claims" => [
                            "P793" => [$statement],
                        ],
                    ],
                    "Q5", // Q5 is the Person entity type
                );

                if ($result) {
                    $linkedEntities++;
                    Log::info(
                        "Successfully linked person entity to main entity",
                        [
                            "person_id" => $entity["id"],
                            "main_entity_id" => $mainEntityId,
                            "type" => $entity["type"],
                        ],
                    );
                } else {
                    Log::error("Failed to link person entity to main entity", [
                        "person_id" => $entity["id"],
                        "main_entity_id" => $mainEntityId,
                        "type" => $entity["type"],
                    ]);
                }
            }

            Log::info("Completed linking entities", [
                "main_entity_id" => $mainEntityId,
                "linked_entities" => $linkedEntities,
                "total_entities" => count($createdEntities),
            ]);
        } catch (\Exception $e) {
            Log::error("Exception while linking main entity to children", [
                "error" => $e->getMessage(),
                "main_entity_id" => $mainEntityId,
            ]);
        }
    }

    /**
     * Update an entity with formatted data using the formatter
     *
     * @param WikibaseFormatterModels $formatterModels
     * @param string $entityId The entity ID to update
     * @param array $entityData The entity data to format
     * @param string $type The entity type (defaults to Person)
     * @return void
     */
    protected function updateEntityWithFormattedData(
        WikibaseFormatterModels $formatterModels,
        string $entityId,
        array $entityData,
        string $type = "Q5",
    ): void {
        try {
            $formattedResult = $formatterModels->formatEntityData(
                $entityData,
                $type,
            );

            if (!empty($formattedResult)) {
                Log::info("Formatted entity data for update", [
                    "entity_id" => $entityId,
                    "type" => $type,
                ]);

                // Call update entity to apply the formatted data
                $wikidataService = app(WikibaseService::class);
                $wikidataService->updateEntity(
                    $entityId,
                    $formattedResult,
                    $type,
                );
            }
        } catch (\Exception $e) {
            Log::error("Error formatting entity data for update", [
                "error" => $e->getMessage(),
                "entity_id" => $entityId,
            ]);
        }
    }

    /**
     * Process relationships for an existing entity (that already has a Wikidata ID)
     *
     * @param WikibaseService $wikidataService
     * @return void
     */
    private function processExistingEntityRelationships(
        WikibaseService $wikidataService,
    ) {
        try {
            // Only process if we have a parent entity ID and relationship type
            if (
                empty($this->parentEntityId) ||
                $this->parentEntityId === "__MAIN_ENTITY_ID__" ||
                empty($this->relationshipType)
            ) {
                Log::info(
                    "Cannot process relationships for existing entity - missing parent info",
                    [
                        "wikidata_id" =>
                            $this->entityData["wikidata_id"] ?? null,
                        "parent_id" => $this->parentEntityId ?? null,
                        "relationship" => $this->relationshipType ?? null,
                    ],
                );
                return;
            }

            // Get the real parent ID if needed
            $realParentId = $this->parentEntityId;
            if (
                $this->parentEntityId === "__MAIN_ENTITY_ID__" &&
                isset($GLOBALS["main_event_entity_id"])
            ) {
                $realParentId = $GLOBALS["main_event_entity_id"];
            } elseif (
                $this->parentEntityId === "__MAIN_ENTITY_ID__" &&
                isset($GLOBALS["last_created_entity_id"])
            ) {
                $realParentId = $GLOBALS["last_created_entity_id"];
            }

            // Now we can add this existing entity as a relationship to the parent entity
            Log::info("Adding existing entity as relationship to parent", [
                "existing_entity_id" =>
                    $this->entityData["wikidata_id"] ?? null,
                "parent_id" => $realParentId,
                "relationship" => $this->relationshipType,
            ]);

            // Execute the linking process based on relationship type
            $this->linkEntityToParent($wikidataService);
        } catch (\Exception $e) {
            Log::error("Error processing relationships for existing entity", [
                "wikidata_id" => $this->entityData["wikidata_id"] ?? null,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
        }
    }
}

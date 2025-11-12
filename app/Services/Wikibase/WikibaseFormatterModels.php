<?php

namespace App\Services\Wikibase;

use App\Models\Wikibase\BaseEntity;
use App\Models\Wikibase\Book;
use App\Models\Wikibase\EntityFactory;
use App\Models\Wikibase\Person;
use App\Models\Wikibase\Source\SourceFactory;
use Illuminate\Support\Facades\Log;
use App\Services\EntityMapping\EntityMappingProviderInterface;
use App\Services\Wikibase\WikibaseService;

class WikibaseFormatterModels
{
    /**
     * Debug mode flag for testing
     *
     * @var bool
     */
    private $inDebugMode = false;

    /**
     * Entity mapping provider
     *
     * @var EntityMappingProviderInterface
     */
    private $mappingProvider;

    /**
     * Set debug mode on or off
     *
     * @param bool $mode True to enable debug mode, false to disable
     * @return self
     */
    public function setDebugMode(bool $mode): self
    {
        $this->inDebugMode = $mode;
        return $this;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->inDebugMode;
    }

    // Role Constants
    public const ROLE_VICTIM = "victim";
    public const ROLE_PERPETRATOR = "perpetrator";

    // Entity Type Constants
    public const ENTITY_TYPE_BOOK = "Q571";
    public const ENTITY_TYPE_EVENT = "Q1190554";
    public const ENTITY_TYPE_PERSON = "Q5";
    public const ENTITY_TYPE_ORGANIZATION = "Q43229";
    public const ENTITY_TYPE_STRUCTURE = "Q41176";

    // Wikidata Property Constants
    public const WIKIDATA_DESCRIBED_BY_SOURCE = "P1343";
    public const WIKIDATA_PERPETRATOR = "P1399";
    public const WIKIDATA_VICTIM = "P1196";
    public const WIKIDATA_INVENTORY_NUMBER = "P217";
    public const WIKIDATA_PAGE = "P304";
    public const WIKIDATA_LANGUAGE = "P407";
    public const WIKIDATA_RETRIEVAL_DATE = "P813";
    public const WIKIDATA_STATED_IN_URL = "P973";

    /**
     * Constructor
     *
     * @param EntityMappingProviderInterface $mappingProvider
     */
    public function __construct(EntityMappingProviderInterface $mappingProvider)
    {
        Log::debug("Creating new WikibaseFormatterModels instance");
        $this->mappingProvider = $mappingProvider;

        // If running in queue worker, automatically disable debug mode
        if ($this->isRunningInQueue()) {
            $this->inDebugMode = false;
            Log::debug("Debug mode automatically disabled in queue worker");
            // Force the global config to match
            config(["wikibase.debug_mode" => false]);
            return;
        }

        // Check if there's a global debug mode config setting and use it
        if (config()->has("wikibase.debug_mode")) {
            $this->inDebugMode = config("wikibase.debug_mode");
            if (!$this->inDebugMode) {
                Log::debug("Debug mode disabled via config setting");
            }
        }
    }

    /**
     * Determines if code is running in a queue worker
     *
     * @return bool
     */
    public function isRunningInQueue(): bool
    {
        // Check if app is running in console
        if (!app()->runningInConsole()) {
            return false;
        }

        // Check for queue-specific indicators
        if (defined("LARAVEL_QUEUE_WORKER")) {
            return true;
        }

        // Check for specific queue command in arguments
        if (
            isset($_SERVER["argv"][1]) &&
            in_array($_SERVER["argv"][1], ["queue:work", "queue:listen"])
        ) {
            return true;
        }

        // Check if we're in the job context by checking for the current job
        if (app()->bound("queue.worker") || app()->bound("queue.connection")) {
            return true;
        }

        return false;
    }

    /**
     * Formats the data for Wikibase based on the entity type using our entity models.
     *
     * @param array $data The source data from the request
     * @param string $type The entity type
     * @param bool $createEntities Flag whether new entities should be created
     * @return array Formatted data for Wikibase
     */
    public function formatEntityData(
        array $data,
        string $type,
        bool $createEntities = false,
    ): array {
        // If running in queue worker, automatically disable debug mode
        if ($this->isRunningInQueue()) {
            $this->inDebugMode = false;
            config(["wikibase.debug_mode" => false]);
            Log::debug(
                "Debug mode automatically disabled in queue worker for formatEntityData",
            );
        } elseif (config()->has("wikibase.debug_mode")) {
            // Only check global config if not running in queue worker
            $this->inDebugMode = config("wikibase.debug_mode");
        }

        // Debug input to formatEntityData only when needed
        if (config("app.debug")) {
            Log::debug("Processing entity data of type: " . $type);
            Log::debug(
                "Debug mode state: " . ($this->inDebugMode ? "true" : "false"),
            );
            Log::debug(
                "CreateEntities parameter: " .
                    ($createEntities ? "true" : "false"),
            );
            Log::debug(
                "Running in queue: " .
                    ($this->isRunningInQueue() ? "true" : "false"),
            );
        }

        // Get the instance-of property ID from the mapping provider
        $instanceOfProperty = $this->mappingProvider->getPropertyId(
            "INSTANCE_OF",
        );

        // Force debug mode to false in admin view to ensure we always get full formatted data
        // This ensures all data fields are processed even when viewing debug information
        if (request()->is("*/admin/*") || request()->is("*/admin")) {
            $originalDebugMode = $this->inDebugMode;
            $this->inDebugMode = false;
            Log::debug("Debug mode temporarily disabled for admin view");
        }

        // Only return placeholder data if in debug mode AND not in admin view
        if (
            $this->inDebugMode &&
            !(isset($originalDebugMode) && $originalDebugMode)
        ) {
            Log::debug("In debug mode - creating placeholder formatted data", [
                "type" => $type,
            ]);
            // Return properly structured placeholder data instead of proceeding with real processing
            return [
                "formattedData" => [
                    "type" => "item",
                    "labels" => [
                        "de" => [
                            "language" => "de",
                            "value" => $data["title"] ?? "",
                        ],
                    ],
                    "descriptions" => [
                        "de" => [
                            "language" => "de",
                            "value" => $data["description"] ?? "",
                        ],
                    ],
                    "aliases" => [],
                    "claims" => [
                        $instanceOfProperty => [
                            [
                                "mainsnak" => [
                                    "snaktype" => "value",
                                    "property" => $instanceOfProperty,
                                    "datavalue" => [
                                        "value" => [
                                            "entity-type" => "item",
                                            "id" => $type,
                                            "numeric-id" => intval(
                                                substr($type, 1),
                                            ),
                                        ],
                                        "type" => "wikibase-entityid",
                                    ],
                                ],
                                "type" => "statement",
                                "rank" => "normal",
                            ],
                        ],
                    ],
                    "sitelinks" => [],
                ],
                "updatedData" => $data,
            ];
        }

        // Create entity based on type using the EntityFactory
        $entity = EntityFactory::createFromArray($data, $type);
        // Get formatted data from the entity model
        $formattedData = $entity->getFormattedData();

        // Ensure all JSON fields are properly decoded
        $jsonFields = [
            "perpetrators",
            "victims",
            "sources",
            "selected_causes",
            "status_history",
            "created_entities",
        ];

        foreach ($jsonFields as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true) ?? [];
                Log::debug("Decoded JSON field: {$field}");
            }
        }

        // Process coordinates if available
        if (!empty($data["coordinates"]) && is_string($data["coordinates"])) {
            $data["coordinates"] = explode(", ", $data["coordinates"]);
            if (count($data["coordinates"]) === 2) {
                Log::debug("Processed coordinates into latitude and longitude");
            }
        }

        // Extract sources for related entities
        $sources = [];
        if (!empty($data["sources"])) {
            $sources = $data["sources"];
            Log::info("Found sources for related entities", [
                "count" => count($sources),
                "first_source" => !empty($sources[0])
                    ? $sources[0]["title"] ?? "Unknown"
                    : "None",
                "source_types" => array_map(function ($source) {
                    return $source["type"] ?? "unknown";
                }, $sources),
            ]);
        }

        // Add perpetrators if available
        if (!empty($data["perpetrators"])) {
            // Pass the sources from the main entity to related entities
            $sourcesToPass = !empty($data["sources"])
                ? $data["sources"]
                : $sources;
            $data["perpetrators"] = $this->processEntityRelations(
                $entity,
                $data["perpetrators"],
                self::ROLE_PERPETRATOR,
                $createEntities,
                $sourcesToPass,
            );
        }

        // Add victims if available
        if (!empty($data["victims"])) {
            // Pass the sources from the main entity to related entities
            $sourcesToPass = !empty($data["sources"])
                ? $data["sources"]
                : $sources;
            $data["victims"] = $this->processEntityRelations(
                $entity,
                $data["victims"],
                self::ROLE_VICTIM,
                $createEntities,
                $sourcesToPass,
            );
        }

        // Process selected causes if available
        if (!empty($data["selected_causes"])) {
            // Make sure entity has the causes set
            if (method_exists($entity, "setCauses")) {
                $entity->setCauses($data["selected_causes"]);
                Log::debug("Set causes on entity", [
                    count($data["selected_causes"]),
                ]);
            }
            // Get updated formatted data after setting causes
            $formattedData = $entity->getFormattedData();
        }

        // Add source information if available
        if (!empty($data["sources"])) {
            // Create source models
            $sources = SourceFactory::createMultipleFromArray($data["sources"]);

            // Add sources to the entity
            $this->addSourcesToEntityInternal(
                $entity,
                $sources,
                $data["wikidata_id"] ?? null,
            );

            // Get updated formatted data
            $formattedData = $entity->getFormattedData();
        }

        // Restore original debug mode if it was temporarily changed
        if (isset($originalDebugMode)) {
            $this->inDebugMode = $originalDebugMode;
            Log::debug(
                "Debug mode restored to original state: " .
                    ($this->inDebugMode ? "true" : "false"),
            );
        }

        // Remove type field from formatted data as it causes API errors
        if (isset($formattedData["type"])) {
            Log::info(
                "WikibaseFormatterModels: Removing 'type' field from formatted data for API compatibility",
            );
            unset($formattedData["type"]);
        }

        // Return result with formatted data and updated data
        return [
            "formattedData" => $formattedData,
            "updatedData" => $data,
        ];
    }

    /**
     * Process entity relations for both perpetrators and victims
     *
     * @param BaseEntity $entity The main entity
     * @param array $relatedEntities Array of related entities (perpetrators or victims)
     * @param string $role The role type (ROLE_PERPETRATOR or ROLE_VICTIM)
     * @param bool $createEntities Whether to create new entities
     * @return array Updated related entities array
     */
    protected function processEntityRelations(
        BaseEntity $entity,
        array $relatedEntities,
        string $role,
        bool $createEntities = false,
        array $sources = [],
    ): array {
        // If running in queue worker, automatically disable debug mode
        if ($this->isRunningInQueue()) {
            $this->inDebugMode = false;
            config(["wikibase.debug_mode" => false]);
            Log::debug(
                "Debug mode automatically disabled in queue worker for entity relations",
            );
        } elseif (config()->has("wikibase.debug_mode")) {
            // Only check config if not in queue worker
            $this->inDebugMode = config("wikibase.debug_mode");
        }

        // Log all relevant flags for better diagnostics
        Log::debug(
            "Processing entity relations with debug_mode=" .
                ($this->inDebugMode ? "true" : "false") .
                ", createEntities=" .
                ($createEntities ? "true" : "false") .
                ", inQueue=" .
                ($this->isRunningInQueue() ? "true" : "false"),
            [
                "role" => $role,
                "count" => count($relatedEntities),
            ],
        );

        // Skip if we're in debug mode
        if ($this->inDebugMode) {
            Log::debug("In debug mode - skipping entity relation processing", [
                "role" => $role,
            ]);
            return $relatedEntities;
        }

        if (empty($relatedEntities)) {
            return [];
        }

        // Make sure that $relatedEntities is an array
        if (is_string($relatedEntities)) {
            $relatedEntities = json_decode($relatedEntities, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Error parsing related entities as JSON", [
                    "entities" => $relatedEntities,
                    "error" => json_last_error_msg(),
                ]);
                return [];
            }
        }

        // Determine property based on the role
        $property =
            $role === self::ROLE_PERPETRATOR
                ? $this->mappingProvider->getPropertyId("PERPETRATOR")
                : $this->mappingProvider->getPropertyId("VICTIM");
        $roleName = $role === self::ROLE_PERPETRATOR ? "perpetrator" : "victim";

        // Process each related entity
        foreach ($relatedEntities as $index => $relatedEntity) {
            $entityId = null;
            $updatedEntity = $relatedEntity;

            // Ensure we have a name field derived from label if it exists
            if (
                !isset($updatedEntity["name"]) &&
                isset($relatedEntity["label"])
            ) {
                $updatedEntity["name"] = $relatedEntity["label"];
            }

            // Case 1: Entity already has a Wikidata ID
            if (
                isset($relatedEntity["id"]) &&
                is_string($relatedEntity["id"]) &&
                substr($relatedEntity["id"], 0, 1) === "Q"
            ) {
                $entityId = $relatedEntity["id"];

                // Add relation to the main entity using the ID
                $entity->addRelation($property, $entityId);

                // Update the entity in the result array
                $relatedEntities[$index] = $updatedEntity;
                continue;
            }

            // Case 2: Entity has a temporary ID (new entity to be created)
            elseif (
                (!empty($relatedEntity["temp_id"]) ||
                    !empty($relatedEntity["tempId"])) &&
                (!empty($relatedEntity["name"]) ||
                    !empty($relatedEntity["firstName"]) ||
                    !empty($relatedEntity["lastName"]))
            ) {
                // Create name from firstName and lastName if needed
                $name = $this->getEntityName($relatedEntity);

                // Prepare person data
                $personData = $this->preparePersonData(
                    $relatedEntity,
                    $entity,
                    $role,
                );

                // Include sources for the person entity, preserving any sources already in personData
                $existingSources = $personData["sources"] ?? [];
                $mergedSources = array_merge($existingSources, $sources);
                $personData["sources"] = $mergedSources;

                Log::info("Added sources to person entity data", [
                    "person" => $personData["name"] ?? "Unknown",
                    "existing_sources_count" => count($existingSources),
                    "parent_sources_count" => count($sources),
                    "total_sources_count" => count($mergedSources),
                    "first_source" => !empty($mergedSources[0])
                        ? $mergedSources[0]["title"] ?? "Unknown"
                        : "None",
                ]);

                // Check if there are additional sources in the related entity
                $additionalSources = $relatedEntity["sources"] ?? [];
                if (!empty($additionalSources)) {
                    // Merge with existing sources to avoid duplicates
                    $allSources = array_merge(
                        $personData["sources"] ?? [],
                        $additionalSources,
                    );
                    $personData["sources"] = $allSources;

                    Log::info("Added additional sources from related entity", [
                        "additional_sources_count" => count($additionalSources),
                        "merged_sources_count" => count($allSources),
                        "first_additional" => !empty($additionalSources[0])
                            ? $additionalSources[0]["title"] ?? "Unknown"
                            : "None",
                    ]);
                }

                // If _debugMode key is explicitly set to false, override debug mode locally
                $localDebugMode = $this->inDebugMode;
                if (
                    isset($relatedEntity["_debugMode"]) &&
                    $relatedEntity["_debugMode"] === false
                ) {
                    $localDebugMode = false;
                    Log::info(
                        "Debug mode overridden to false by _debugMode flag for: " .
                            $name,
                    );
                }

                // Create person in Wikidata if:
                // - createEntities is true, OR
                // Only create entities if:
                // - createEntities is true, OR
                // - _forceCreate flag is set, OR
                // - we're in a queue worker (which should always create entities)
                // BUT don't create if _skipCreate flag is set (this takes precedence)
                if (
                    !empty($relatedEntity["_skipCreate"]) === false &&
                    ($createEntities ||
                        !empty($relatedEntity["_forceCreate"]) ||
                        $this->isRunningInQueue())
                ) {
                    // If force creating, log it
                    if (!empty($relatedEntity["_forceCreate"])) {
                        Log::info(
                            "Force creating entity due to _forceCreate flag",
                            [
                                "name" => $name,
                                "role" => $role,
                            ],
                        );
                    } elseif ($this->isRunningInQueue()) {
                        Log::info(
                            "Creating entity because we're in queue worker",
                            [
                                "name" => $name,
                                "role" => $role,
                            ],
                        );
                    }

                    // Create person entity model
                    $personModel = EntityFactory::createPersonWithRole(
                        $personData,
                        $role,
                    );

                    // Explicitly add sources to the person model
                    if (!empty($sources) || !empty($personData["sources"])) {
                        // Combine any sources from person data with the parent entity sources
                        $allSources = array_merge(
                            $personData["sources"] ?? [],
                            $sources,
                        );
                        $sourceModels = SourceFactory::createMultipleFromArray(
                            $allSources,
                        );
                        $this->addSourcesToEntityInternal(
                            $personModel,
                            $sourceModels,
                        );
                        Log::info(
                            "Explicitly added sources to person model during creation",
                            [
                                "person_name" => $personModel->getName(),
                                "person_sources_count" => count(
                                    $personData["sources"] ?? [],
                                ),
                                "parent_sources_count" => count($sources),
                                "total_sources_count" => count($allSources),
                                "first_source" => !empty($allSources[0])
                                    ? $allSources[0]["title"] ?? "Unknown"
                                    : "None",
                                "sources_types" => array_map(function (
                                    $source,
                                ) {
                                    return $source["type"] ?? "unknown";
                                }, $allSources),
                                "has_url_claims" => isset(
                                    $personModel->getData()["claims"][
                                        $this->mappingProvider->getPropertyId(
                                            "STATED_IN_URL",
                                        )
                                    ],
                                ),
                                "has_described_by_claims" => isset(
                                    $personModel->getData()["claims"][
                                        $this->mappingProvider->getPropertyId(
                                            "DESCRIBED_BY_SOURCE",
                                        )
                                    ],
                                ),
                            ],
                        );
                    }

                    // Option 1: Use queue system for person entity creation
                    $useQueue = config("wikibase.use_queue", true);
                    $wikibaseService = app(WikibaseService::class);

                    if ($useQueue && $wikibaseService->hasValidCredentials()) {
                        // Add parent entity ID and role to the data for relationship tracking
                        // Get the parent ID - it could be in several places
                        $parentEntityId = null;

                        // 1. If we have entity data with ID, use that
                        if (isset($entity->getData()["id"])) {
                            $parentEntityId = $entity->getData()["id"];
                            Log::info(
                                "Using ID from entity data as parent ID",
                                [
                                    "parent_id" => $parentEntityId,
                                    "entity_type" => $entity->getEntityType(),
                                ],
                            );
                        } elseif (isset($this->entityData["id"])) {
                            $parentEntityId = $this->entityData["id"];
                            Log::info(
                                "Using ID from entity data as parent ID",
                                [
                                    "parent_id" => $parentEntityId,
                                    "entity_type" => $entity->getEntityType(),
                                ],
                            );
                        }
                        // 3. Check if this is being called from CreateWikidataEntity job - response might have the entity ID
                        elseif (isset($GLOBALS["last_created_entity_id"])) {
                            $parentEntityId =
                                $GLOBALS["last_created_entity_id"];
                            Log::info(
                                "Using ID from global variable as parent ID",
                                [
                                    "parent_id" => $parentEntityId,
                                    "entity_type" => $entity->getEntityType(),
                                ],
                            );
                        }
                        // 4. Last resort - log the problem but continue
                        else {
                            Log::warning(
                                "No parent ID found for victim/perpetrator creation",
                                [
                                    "entity_type" => $entity->getEntityType(),
                                    "entity_data_keys" =>
                                        $entity->getData() !== null
                                            ? array_keys($entity->getData())
                                            : [],
                                    "context_data_keys" =>
                                        $this->entityData !== null
                                            ? array_keys($this->entityData)
                                            : [],
                                    "is_running_in_queue" => $this->isRunningInQueue(),
                                ],
                            );
                        }

                        $personData["parent_entity_id"] = $parentEntityId;
                        $personData["relationship_type"] = $role;
                        $personData["role"] = $role;

                        // Set force create flag
                        $personData["_forceCreate"] = true;

                        // Queue the person entity creation with parent relationship
                        $queued = $wikibaseService->queueEntityCreation(
                            $personData,
                            $personModel->getEntityType(),
                            $parentEntityId,
                            $role,
                        );

                        if ($queued) {
                            Log::info(
                                "Queued {$roleName} entity creation with parent relationship",
                                [
                                    "name" => $name,
                                    "role" => $role,
                                    "parent_id" => $parentEntityId,
                                ],
                            );

                            // We can't set the ID yet, but mark it as pending
                            $updatedEntity["_pending"] = true;
                            $updatedEntity[
                                "parent_entity_id"
                            ] = $parentEntityId; // Ensure this is set
                            $updatedEntity["relationship_type"] = $role; // Ensure this is set
                            $relatedEntities[$index] = $updatedEntity;
                            continue;
                        }

                        // If queueing fails, fall back to direct creation
                        Log::warning(
                            "Failed to queue {$roleName} entity creation, falling back to direct creation",
                        );
                    }

                    // Option 2 (fallback): Create the entity directly
                    $entityId = $this->createPersonEntity(
                        $personModel,
                        $sources,
                    );

                    if ($entityId !== null) {
                        // Remove temporary ID fields
                        if (isset($updatedEntity["temp_id"])) {
                            unset($updatedEntity["temp_id"]);
                        }
                        if (isset($updatedEntity["tempId"])) {
                            unset($updatedEntity["tempId"]);
                        }

                        $updatedEntity["id"] = $entityId;
                        $updatedEntity["_wasCreated"] = true; // Flag that this entity was newly created

                        Log::info(
                            "New person created and linked as {$roleName}",
                            [
                                "name" => $name,
                                "temp_id/tempId" =>
                                    $relatedEntity["temp_id"] ??
                                    ($relatedEntity["tempId"] ?? "none"),
                                "wikidata_id" => $entityId,
                            ],
                        );

                        // Update the entity in the result array
                        $relatedEntities[$index] = $updatedEntity;

                        // Add relation to the main entity
                        $entity->addRelation($property, $entityId);
                    }
                } else {
                    // Only in explicit preview mode with createEntities=false, not because of debug mode
                    Log::info("Preview only: No {$roleName} created for", [
                        "name" => $name,
                        "temp_id/tempId" =>
                            $relatedEntity["temp_id"] ??
                            ($relatedEntity["tempId"] ?? "none"),
                        "reason" => "createEntities=false parameter",
                    ]);
                }
            }
            // Case 3: Other entities with just a name (no ID)
            elseif (
                !empty($relatedEntity["name"]) ||
                !empty($relatedEntity["firstName"]) ||
                !empty($relatedEntity["lastName"])
            ) {
                $name = $this->getEntityName($relatedEntity);

                // Prepare person data
                $personData = $this->preparePersonData(
                    $relatedEntity,
                    $entity,
                    $role,
                );

                // Get sources if available
                $sources = $relatedEntity["sources"] ?? [];

                // If _debugMode key is explicitly set to false, override debug mode locally
                $localDebugMode = $this->inDebugMode;
                if (
                    isset($relatedEntity["_debugMode"]) &&
                    $relatedEntity["_debugMode"] === false
                ) {
                    $localDebugMode = false;
                    Log::info(
                        "Debug mode overridden to false by _debugMode flag for: " .
                            $name,
                    );
                }

                // Create person in Wikidata if:
                // - createEntities is true, OR
                // - _forceCreate flag is set, OR
                // - we're in a queue worker (which should always create entities)
                // BUT don't create if _skipCreate flag is set (this takes precedence)
                if (
                    !empty($relatedEntity["_skipCreate"]) === false &&
                    ($createEntities ||
                        !empty($relatedEntity["_forceCreate"]) ||
                        $this->isRunningInQueue())
                ) {
                    // If force creating, log it
                    if (!empty($relatedEntity["_forceCreate"])) {
                        Log::info(
                            "Force creating entity due to _forceCreate flag",
                            [
                                "name" => $name,
                                "role" => $role,
                            ],
                        );
                    } elseif ($this->isRunningInQueue()) {
                        Log::info(
                            "Creating entity because we're in queue worker",
                            [
                                "name" => $name,
                                "role" => $role,
                            ],
                        );
                    }

                    // Create person entity model
                    $personModel = EntityFactory::createPersonWithRole(
                        $personData,
                        $role,
                    );

                    // Create the entity in Wikidata
                    $entityId = $this->createPersonEntity(
                        $personModel,
                        $sources,
                    );

                    if ($entityId !== null) {
                        $updatedEntity["id"] = $entityId;
                        $updatedEntity["_wasCreated"] = true; // Flag that this entity was newly created

                        Log::info(
                            "New person created and linked as {$roleName}",
                            [
                                "name" => $name,
                                "wikidata_id" => $entityId,
                            ],
                        );

                        // Update the entity in the result array
                        $relatedEntities[$index] = $updatedEntity;

                        // Add relation to the main entity
                        $entity->addRelation($property, $entityId);
                    }
                } else {
                    // Only in explicit preview mode with createEntities=false, not because of debug mode
                    Log::info("Preview only: No {$roleName} created for", [
                        "name" => $name,
                        "reason" => "createEntities=false parameter",
                    ]);
                }
            }
        }

        return $relatedEntities;
    }

    /**
     * Extract or create a name from a related entity array
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
     * Prepare person data from a related entity array
     *
     * @param array $entity The entity data
     * @param BaseEntity $parentEntity The parent entity
     * @param string $role The role (ROLE_PERPETRATOR or ROLE_VICTIM)
     * @return array Formatted person data
     */
    protected function preparePersonData(
        array $entity,
        BaseEntity $parentEntity,
        string $role,
    ): array {
        // Get the parent entity's label for descriptions
        $parentName = $parentEntity->getLabel("de") ?? "an event";

        // Create description based on role
        $description =
            $entity["description"] ??
            ($role === self::ROLE_PERPETRATOR
                ? "Perpetrator in connection with " . $parentName
                : "Victim in connection with " . $parentName);

        // Get name
        $name = $this->getEntityName($entity);

        // Ensure we have a title field explicitly set
        $title = $name; // This ensures the title is always set to the name

        // Get birth and death dates using standardized field names only
        $birthDate = $entity["birthDate"] ?? null;
        $deathDate = $entity["deathDate"] ?? null;

        // Return formatted person data
        return [
            "name" => $name,
            "title" => $title, // Explicitly include the title field
            "description" => $description,
            "birthDate" => $birthDate, // Standardized field name
            "deathDate" => $deathDate, // Standardized field name
            "occupation" => $entity["occupation"] ?? null,
            "nationality" => $entity["nationality"] ?? null,
            "isOrganization" => isset($entity["isOrganization"])
                ? Person::isOrganizationValue($entity["isOrganization"])
                : false,
            "gender" => $entity["gender"] ?? null,
            "place_of_birth" => $entity["place_of_birth"] ?? null,
            "place_of_death" => $entity["place_of_death"] ?? null,
            "place_of_detention" => $entity["place_of_detention"] ?? [],
            "place_of_residence" => $entity["place_of_residence"] ?? null,
            "birthName" => $entity["birthName"] ?? null,
            "employer" => $entity["employer"] ?? null,
            "key_event_person" => $entity["key_event_person"] ?? [],
            "role" => $role,
            "_add_role_claim" => true,
            "relationship" => $role,
            "sources" => $entity["sources"] ?? [], // Ensure sources from the person entity are included
            "imageUrl" => $entity["imageUrl"] ?? null,
            "imageFilename" =>
                $entity["imageFilename"] ??
                ($entity["image_filename"] ?? ($entity["image"] ?? null)),
        ];
    }

    /**
     * Creates a person entity in Wikibase.
     *
     * @param Person $person The person model
     * @param array $sources The sources to add
     * @return string|null The ID of the created entity or null if creation failed
     */
    protected function createPersonEntity(
        Person $person,
        array $sources = [],
    ): ?string {
        // IMPORTANT: Always force debug mode to false when creating entities
        // This prevents debug mode from blocking actual entity creation
        $originalDebugMode = $this->inDebugMode;
        $this->inDebugMode = false;

        // Ensure the global config is also set to false
        config(["wikibase.debug_mode" => false]);

        Log::info("Debug mode explicitly disabled for person entity creation", [
            "person_name" => $person->getName() ?? "Unknown",
            "sources_count" => count($sources),
            "has_person_sources" => !empty($person->getData()["sources"]),
            "person_sources_count" => !empty($person->getData()["sources"])
                ? count($person->getData()["sources"])
                : 0,
        ]);

        try {
            // Process sources and add them to the person entity
            if (!empty($sources)) {
                $sourceModels = SourceFactory::createMultipleFromArray(
                    $sources,
                );
                $this->addSourcesToEntityInternal($person, $sourceModels);
                Log::info(
                    "Added source information to person entity before creation",
                    [
                        "person_name" => $person->getName(),
                        "sources_count" => count($sources),
                        "first_source" => !empty($sources[0])
                            ? $sources[0]["title"] ?? "Unknown"
                            : "None",
                        "sources_types" => array_map(function ($source) {
                            return $source["type"] ?? "unknown";
                        }, $sources),
                        "has_url_claims" => isset(
                            $person->getData()["claims"][
                                $this->mappingProvider->getPropertyId(
                                    "STATED_IN_URL",
                                )
                            ],
                        ),
                        "has_described_by_claims" => isset(
                            $person->getData()["claims"][
                                $this->mappingProvider->getPropertyId(
                                    "DESCRIBED_BY_SOURCE",
                                )
                            ],
                        ),
                    ],
                );
            } elseif (!empty($person->getData()["sources"])) {
                // If sources are in the person data but not passed as parameter
                $personSources = $person->getData()["sources"];
                // Merge with any sources passed as parameter to ensure we have all sources
                $allSources = !empty($sources)
                    ? array_merge($personSources, $sources)
                    : $personSources;
                $sourceModels = SourceFactory::createMultipleFromArray(
                    $allSources,
                );
                $this->addSourcesToEntityInternal($person, $sourceModels);
                Log::info(
                    "Added source information from person data before creation",
                    [
                        "person_name" => $person->getName(),
                        "sources_count" => count($personSources),
                        "first_source" => !empty($personSources[0])
                            ? $personSources[0]["title"] ?? "Unknown"
                            : "None",
                    ],
                );
            } else {
                Log::warning("No sources provided for person entity", [
                    "person" => $person->getName() ?? "Unknown",
                ]);
            }

            // Get the formatted data for the WikibaseService
            $personData = $person->getFormattedData();

            // Get the WikibaseService instance
            $wikibaseService = app(WikibaseService::class);

            // Create entity data for the service
            $entityData = [
                "title" =>
                    $personData["labels"]["de"]["value"] ??
                    ($person->getName() ?? "Unnamed Person"),
                "description" =>
                    $personData["descriptions"]["de"]["value"] ??
                    "Person related to a historical event",
                "type" => $person->getEntityType(),
                "claims" => $personData["claims"] ?? [],
                "labels" => $personData["labels"] ?? [],
                "descriptions" => $personData["descriptions"] ?? [],
                "aliases" => $personData["aliases"] ?? [],
                "sitelinks" => $personData["sitelinks"] ?? [],
                // Add more data to ensure we have the entity information
                "original_name" => $person->getName(),
                "entity_role" => $person->getRole(),
                "debug_data" => [
                    "has_labels" => !empty($personData["labels"]),
                    "has_de_label" => !empty($personData["labels"]["de"]),
                    "label_value" =>
                        $personData["labels"]["de"]["value"] ?? "<missing>",
                ],
            ];

            // Check if we should use the queue system
            $useQueue = config("wikibase.use_queue", true);
            if ($useQueue) {
                $queued = $wikibaseService->queueEntityCreation(
                    $entityData,
                    $person->getEntityType(),
                );
                if ($queued) {
                    // For queued creation, we can't return a real ID yet
                    Log::info("Queued person entity creation", [
                        "name" => $entityData["title"] ?? "Unknown",
                    ]);

                    // Return null to indicate entity isn't available yet
                    return null;
                }
                // If queueing fails, fall back to synchronous creation
            }

            // Create the entity synchronously
            $response = $wikibaseService->createEntity(
                $entityData,
                $person->getEntityType(),
            );

            if ($response === null) {
                Log::error("Error creating a new person in Wikibase", [
                    "person" => $entityData["title"] ?? "Unknown",
                ]);

                return null;
            }

            // Extract Wikibase ID from the response
            $entityId = $response["entity"]["id"] ?? null;

            Log::info("New person created in Wikibase", [
                "person" => $entityData["title"] ?? "Unknown",
                "entity_id" => $entityId,
            ]);

            return $entityId;
        } catch (\Exception $e) {
            Log::error("Error creating a new person in Wikibase", [
                "person" => $person->getLabel("de") ?? "Unknown",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Public wrapper to add sources to an entity.
     * This allows direct access from controllers for testing purposes.
     *
     * @param BaseEntity $entity The entity to add sources to
     * @param array $sources Array of source models
     * @param string|null $parentEntityId Optional parent entity ID for linking sources
     */
    public function addSourcesToEntity(
        BaseEntity $entity,
        array $sources,
        ?string $parentEntityId = null,
    ): void {
        $this->addSourcesToEntityInternal($entity, $sources, $parentEntityId);
    }

    /**
     * Internal implementation to add sources to an entity.
     *
     * @param BaseEntity $entity The entity to add sources to
     * @param array $sources Array of source models
     * @param string|null $parentEntityId Optional parent entity ID for linking sources
     */
    protected function addSourcesToEntityInternal(
        BaseEntity $entity,
        array $sources,
        ?string $parentEntityId = null,
    ): void {
        if (empty($sources)) {
            return;
        }

        // Check for duplicate sources to avoid processing the same source multiple times
        $processedSourceHashes = [];
        $uniqueSources = [];

        foreach ($sources as $source) {
            // Create a hash key for this source to detect duplicates
            $sourceHash = "";
            if (method_exists($source, "getTitle")) {
                $sourceHash .= $source->getTitle();
            }
            if (method_exists($source, "getType")) {
                $sourceHash .= "-" . $source->getType();
            }
            if (method_exists($source, "getUrl")) {
                $sourceHash .= "-" . $source->getUrl();
            }

            $sourceHash = md5($sourceHash);

            // Skip if we've already processed this source
            if (in_array($sourceHash, $processedSourceHashes)) {
                Log::debug(
                    "Skipping duplicate source in addSourcesToEntityInternal",
                    [
                        "source_hash" => $sourceHash,
                        "source_title" => method_exists($source, "getTitle")
                            ? $source->getTitle()
                            : "unknown",
                    ],
                );
                continue;
            }

            // Add to processed list and unique sources
            $processedSourceHashes[] = $sourceHash;
            $uniqueSources[] = $source;
        }

        // Replace original sources with unique ones
        $sources = $uniqueSources;

        // Log how many duplicates were removed
        $originalCount = count($sources);
        if (count($uniqueSources) < $originalCount) {
            Log::info("Removed duplicate sources from processing", [
                "original_count" => $originalCount,
                "unique_count" => count($uniqueSources),
                "entity_type" => $entity->getEntityType(),
            ]);
        }

        // Get property IDs from mapping provider
        $describedBySourceProperty = $this->mappingProvider->getPropertyId(
            "DESCRIBED_BY_SOURCE",
        );
        $archiveProperty = $this->mappingProvider->getPropertyId("ARCHIVE");
        $inventoryNumberProperty = $this->mappingProvider->getPropertyId(
            "INVENTORY_NUMBER",
        );
        $pageProperty = $this->mappingProvider->getPropertyId("PAGE");
        $statedInUrlProperty = $this->mappingProvider->getPropertyId(
            "STATED_IN_URL",
        );
        $languageProperty = $this->mappingProvider->getPropertyId("LANGUAGE");
        $retrievalDateProperty = $this->mappingProvider->getPropertyId(
            "RETRIEVAL_DATE",
        );

        foreach ($sources as $source) {
            // Get source details for logging
            $sourceType = method_exists($source, "getType")
                ? $source->getType()
                : "unknown";
            $sourceTitle = method_exists($source, "getTitle")
                ? $source->getTitle()
                : "unknown";

            // 1. Add the source as reference to specific claims (e.g. P8031, P8032)
            $entity->addSource($source, $parentEntityId);

            // Skip adding standalone source claims for entity references (P8031, P8032)
            // to avoid duplicating entity reference claims when adding sources
            $entityData = $entity->getData();
            $perpetratorProperty = $this->mappingProvider->getPropertyId(
                "PERPETRATOR",
            );
            $victimProperty = $this->mappingProvider->getPropertyId("VICTIM");

            // If this source is also a perpetrator or victim entity, skip adding it as a standalone source
            // to prevent duplicating the entity reference
            if (
                method_exists($source, "getEntityId") &&
                !empty($source->getEntityId())
            ) {
                $sourceEntityId = $source->getEntityId();

                // Check if this entity is already listed as a perpetrator
                $isPerpetrator = false;
                if (isset($entityData["claims"][$perpetratorProperty])) {
                    foreach (
                        $entityData["claims"][$perpetratorProperty]
                        as $statement
                    ) {
                        if (
                            isset(
                                $statement["mainsnak"]["datavalue"]["value"][
                                    "id"
                                ],
                            ) &&
                            $statement["mainsnak"]["datavalue"]["value"][
                                "id"
                            ] === $sourceEntityId
                        ) {
                            $isPerpetrator = true;
                            Log::info(
                                "Skipping source addition for entity that is already a perpetrator",
                                [
                                    "entity_id" => $sourceEntityId,
                                ],
                            );
                            break;
                        }
                    }
                }

                // Check if this entity is already listed as a victim
                $isVictim = false;
                if (isset($entityData["claims"][$victimProperty])) {
                    foreach (
                        $entityData["claims"][$victimProperty]
                        as $statement
                    ) {
                        if (
                            isset(
                                $statement["mainsnak"]["datavalue"]["value"][
                                    "id"
                                ],
                            ) &&
                            $statement["mainsnak"]["datavalue"]["value"][
                                "id"
                            ] === $sourceEntityId
                        ) {
                            $isVictim = true;
                            Log::info(
                                "Skipping source addition for entity that is already a victim",
                                [
                                    "entity_id" => $sourceEntityId,
                                ],
                            );
                            break;
                        }
                    }
                }

                // If the source is already a perpetrator or victim, skip adding it as a standalone source
                if ($isPerpetrator || $isVictim) {
                    Log::info(
                        "Skipping standalone source claim for entity that is already a relation",
                        [
                            "entity_id" => $sourceEntityId,
                            "is_perpetrator" => $isPerpetrator,
                            "is_victim" => $isVictim,
                        ],
                    );
                    continue;
                }
            }

            // 2. Add the source as a standalone claim to the entity
            if (method_exists($source, "toClaim")) {
                $sourceClaim = $source->toClaim();

                // Skip if no claim data is provided
                if (empty($sourceClaim) || empty($sourceClaim["mainsnak"])) {
                    Log::warning(
                        "Source toClaim() returned empty or invalid claim data",
                        [
                            "source_type" => $sourceType,
                            "source_title" => $sourceTitle,
                        ],
                    );
                    continue;
                }

                // Get the property ID from the claim
                $propertyId = $sourceClaim["mainsnak"]["property"];

                // Make sure correct property is used for the source type
                if (
                    $sourceType === "archive" &&
                    $propertyId !== $describedBySourceProperty
                ) {
                    $propertyId = $describedBySourceProperty;
                    $sourceClaim["mainsnak"]["property"] = $propertyId;
                }

                // Get entity data to update
                $entityData = $entity->getData();

                // Initialize the claims array for this property if it doesn't exist
                if (!isset($entityData["claims"][$propertyId])) {
                    $entityData["claims"][$propertyId] = [];
                }

                // Log qualifier information for debugging
                $qualifierProperties = [];
                if (
                    isset($sourceClaim["qualifiers"]) &&
                    !empty($sourceClaim["qualifiers"])
                ) {
                    $qualifierProperties = array_keys(
                        $sourceClaim["qualifiers"],
                    );

                    Log::debug("Source claim contains qualifiers", [
                        "property" => $propertyId,
                        "qualifiers" => $qualifierProperties,
                        "source_type" => $sourceType,
                    ]);

                    // Check for specific qualifiers based on source type
                    if (
                        $sourceType === "book" &&
                        !in_array($pageProperty, $qualifierProperties)
                    ) {
                        Log::debug("Book source without page qualifier", [
                            "source_title" => $sourceTitle,
                        ]);
                    }
                    if (
                        $sourceType === "archive" &&
                        !in_array(
                            $inventoryNumberProperty,
                            $qualifierProperties,
                        )
                    ) {
                        Log::debug(
                            "Archive source without inventory number qualifier",
                            [
                                "source_title" => $sourceTitle,
                            ],
                        );
                    }
                    if ($sourceType === "url") {
                        if (
                            !in_array($languageProperty, $qualifierProperties)
                        ) {
                            Log::debug(
                                "URL source without language qualifier",
                                [
                                    "source_title" => $sourceTitle,
                                ],
                            );
                        }
                        if (
                            !in_array(
                                $retrievalDateProperty,
                                $qualifierProperties,
                            )
                        ) {
                            Log::debug(
                                "URL source without retrieval date qualifier",
                                [
                                    "source_title" => $sourceTitle,
                                ],
                            );
                        }
                    }
                }

                // Check if this exact claim already exists to avoid duplicates
                $isDuplicate = false;
                if (isset($entityData["claims"][$propertyId])) {
                    foreach (
                        $entityData["claims"][$propertyId]
                        as $existingClaim
                    ) {
                        // Compare the main datavalue to check for duplicates
                        if (
                            isset($existingClaim["mainsnak"]["datavalue"]) &&
                            isset($sourceClaim["mainsnak"]["datavalue"]) &&
                            $existingClaim["mainsnak"]["datavalue"] ==
                                $sourceClaim["mainsnak"]["datavalue"]
                        ) {
                            $isDuplicate = true;
                            Log::info("Skipping duplicate source claim", [
                                "property" => $propertyId,
                                "source_type" => $sourceType,
                                "source_title" => $sourceTitle,
                            ]);
                            break;
                        }
                    }
                }

                // Only add the claim if it's not a duplicate
                if (!$isDuplicate) {
                    $entityData["claims"][$propertyId][] = $sourceClaim;
                    Log::info("Added source claim to entity", [
                        "property" => $propertyId,
                        "source_type" => $sourceType,
                        "source_title" => $sourceTitle,
                    ]);
                }

                // Update the entity data
                $entity->setData($entityData);
            } else {
                Log::warning("Source does not have toClaim method", [
                    "source_type" => $sourceType,
                    "source_title" => $sourceTitle,
                ]);
            }
        }
    }

    /**
     * Formats a new Person entity for Wikibase using our Person model.
     *
     * @param array $personData The person's data
     * @param string|null $role The role (victim/perpetrator) or null if no specific role
     * @return array Formatted data for the Wikibase API
     */
    public function formatNewPersonEntity(
        array $personData,
        ?string $role = null,
    ): array {
        // Create a Person model with the appropriate role
        $person = EntityFactory::createPersonWithRole($personData, $role);

        // Return the formatted data
        return $person->getFormattedData();
    }

    /**
     * Creates a new Person entity in Wikibase using our Person model.
     *
     * @param array $personData The person's data
     * @param array $sources Optional: Source information for the person
     * @param string|null $role Optional: The role (victim/perpetrator) or null if no specific role
     * @return string|null The ID of the created person or null in case of error
     */
    public function createNewPersonEntity(
        array $personData,
        array $sources = [],
        ?string $role = null,
    ): ?string {
        // IMPORTANT: Always force debug mode to false when creating entities
        // This prevents debug mode from blocking actual entity creation
        $originalDebugMode = $this->inDebugMode;
        $this->inDebugMode = false;

        // Force the global config as well
        config(["wikibase.debug_mode" => false]);

        Log::info(
            "Debug mode explicitly disabled for new person entity creation",
        );

        // Create a Person model with the appropriate role
        $person = EntityFactory::createPersonWithRole($personData, $role);

        // Add sources to the person entity
        if (!empty($sources)) {
            $sourceModels = SourceFactory::createMultipleFromArray($sources);
            $this->addSourcesToEntityInternal($person, $sourceModels);
        }

        // Create the entity in Wikibase
        $result = $this->createPersonEntity($person, []);

        // Restore original debug mode
        $this->inDebugMode = $originalDebugMode;

        return $result;
    }

    /**
     * Formats a new Book entity for Wikibase using our Book model.
     *
     * @param array $bookData The book's data
     * @return array Formatted data for the Wikibase API
     */
    public function formatNewBookEntity(array $bookData): array
    {
        // Create a Book model
        $book = Book::fromArray($bookData);

        // Return the formatted data
        return $book->getFormattedData();
    }

    /**
     * Creates a new Book entity in Wikibase using our Book model.
     *
     * @param array $bookData The book's data
     * @param array $sources Optional: Source information for the book
     * @return string|null The ID of the created book or null in case of error
     */
    public function createNewBookEntity(
        array $bookData,
        array $sources = [],
    ): ?string {
        // IMPORTANT: Always force debug mode to false when creating entities
        // This prevents debug mode from blocking actual entity creation
        $originalDebugMode = $this->inDebugMode;
        $this->inDebugMode = false;

        // Create a Book model
        $book = Book::fromArray($bookData);

        // Clean up sources to ensure only appropriate ones are created
        $cleanedSources = $this->cleanupSourceData($sources);

        // Add sources to the book entity
        if (!empty($cleanedSources)) {
            $sourceModels = SourceFactory::createMultipleFromArray(
                $cleanedSources,
            );
            $this->addSourcesToEntityInternal($book, $sourceModels);
        }

        // Create the entity in Wikibase
        $result = $this->createBookEntity($book);

        // Restore original debug mode
        $this->inDebugMode = $originalDebugMode;

        return $result;
    }

    /**
     * Queues a new Book entity for creation in Wikibase.
     *
     * @param array $bookData The book's data
     * @param array $sources Optional: Source information for the book
     * @param string|null $parentEntityId Optional: ID of parent entity to link this book to when created
     * @return bool Whether the job was successfully queued
     */
    public function queueNewBookEntity(
        array $bookData,
        array $sources = [],
        ?string $parentEntityId = null,
    ): bool {
        // Check if there's a global config setting for debug mode
        if (config()->has("wikibase.debug_mode")) {
            $this->inDebugMode = config("wikibase.debug_mode");
        }

        // Skip if we're in debug mode
        if ($this->inDebugMode) {
            Log::debug("In debug mode - skipping book entity queuing", [
                "title" => $bookData["title"] ?? "Unknown",
            ]);
            return true; // Pretend success in debug mode
        }

        // Create a Book model
        $book = Book::fromArray($bookData);

        // Clean up sources to ensure only appropriate ones are created
        $cleanedSources = $this->cleanupSourceData($sources);

        // Add sources to the book entity
        if (!empty($cleanedSources)) {
            $sourceModels = SourceFactory::createMultipleFromArray(
                $cleanedSources,
            );
            $this->addSourcesToEntityInternal(
                $book,
                $sourceModels,
                $parentEntityId,
            );
        }

        // Get the WikibaseService instance
        $wikibaseService = app(WikibaseService::class);

        // Get the formatted data for the WikibaseService
        $bookData = $book->getFormattedData();

        // Create entity data for the service
        $entityData = [
            "title" => $bookData["labels"]["de"]["value"] ?? "Unnamed Book",
            "description" =>
                $bookData["descriptions"]["de"]["value"] ??
                "Book related to a historical event",
            "type" => $book->getEntityType(),
            "claims" => $bookData["claims"] ?? [],
            "labels" => $bookData["labels"] ?? [],
            "descriptions" => $bookData["descriptions"] ?? [],
            "aliases" => $bookData["aliases"] ?? [],
            "sitelinks" => $bookData["sitelinks"] ?? [],
            "parent_entity_id" => $parentEntityId,
        ];

        // Queue the entity creation
        $queued = $wikibaseService->queueEntityCreation(
            $entityData,
            $book->getEntityType(),
        );

        if ($queued) {
            Log::info("Queued book entity creation", [
                "title" => $entityData["title"] ?? "Unknown",
                "parent_id" => $parentEntityId,
            ]);
        } else {
            Log::error("Failed to queue book entity creation", [
                "title" => $entityData["title"] ?? "Unknown",
            ]);
        }

        return $queued;
    }

    /**
     * Format person entity data using the Person model
     *
     * Takes the given data array and creates a Person model from it.
     * The formatted claims from the Person model are then merged into
     * the provided formattedData array.
     *
     * @param array $formattedData The formatted data structure to update
     * @param array $data The original data to format
     * @return void
     */
    protected function formatPersonEntity(
        array &$formattedData,
        array $data,
    ): void {
        // Create a Person model from the data
        $person = \App\Models\Wikibase\Person::fromArray($data);

        // Get the formatted data from the Person model
        $personData = $person->getFormattedData();

        // Merge the claims from the Person model into the formatted data
        foreach ($personData["claims"] as $property => $claims) {
            $formattedData["claims"][$property] = $claims;
        }
    }

    /**
     * Format organization entity data using the Organization model
     *
     * Takes the given data array and creates an Organization model from it.
     * The formatted claims from the Organization model are then merged into
     * the provided formattedData array.
     *
     * @param array $formattedData The formatted data structure to update
     * @param array $data The original data to format
     * @return void
     */
    protected function formatOrganizationEntity(
        array &$formattedData,
        array $data,
    ): void {
        // Create an Organization model from the data
        $organization = \App\Models\Wikibase\Organization::fromArray($data);

        // Get the formatted data from the Organization model
        $organizationData = $organization->getFormattedData();

        // Merge the claims from the Organization model into the formatted data
        foreach ($organizationData["claims"] as $property => $claims) {
            $formattedData["claims"][$property] = $claims;
        }
    }

    /**
     * Handle calls to methods that don't exist in this class
     *
     * @param string $method The method name
     * @param array $arguments The method arguments
     * @return mixed Result from the method
     * @throws \BadMethodCallException If the method doesn't exist
     */
    public function __call($method, $arguments)
    {
        throw new \BadMethodCallException(
            "Method {$method} does not exist in " . __CLASS__,
        );
    }

    /**
     * Format structure entity data using the Structure model
     *
     * Takes the given data array and creates a Structure model from it.
     * The formatted claims from the Structure model are then merged into
     * the provided formattedData array.
     *
     * @param array $formattedData The formatted data structure to update
     * @param array $data The original data to format
     * @return void
     */
    protected function formatStructureEntity(
        array &$formattedData,
        array $data,
    ): void {
        // Create a Structure model from the data
        $structure = \App\Models\Wikibase\Structure::fromArray($data);

        // Get the formatted data from the Structure model
        $structureData = $structure->getFormattedData();

        // Merge the claims from the Structure model into the formatted data
        foreach ($structureData["claims"] as $property => $claims) {
            $formattedData["claims"][$property] = $claims;
        }
    }

    /**
     * Creates a book entity in Wikibase.
     *
     * @param Book $book The book model
     * @return string|null The ID of the created entity or null if creation failed
     */
    protected function createBookEntity(Book $book): ?string
    {
        // IMPORTANT: Always force debug mode to false when creating entities
        // This prevents debug mode from blocking actual entity creation
        $originalDebugMode = $this->inDebugMode;
        $this->inDebugMode = false;

        // Ensure the global config is also set to false
        config(["wikibase.debug_mode" => false]);

        Log::info("Debug mode explicitly disabled for book entity creation");

        try {
            // Get the formatted data for the WikibaseService
            $bookData = $book->getFormattedData();

            // Get the WikibaseService instance
            $wikibaseService = app(WikibaseService::class);

            // Create entity data for the service
            $entityData = [
                "title" => $bookData["labels"]["de"]["value"] ?? "Unnamed Book",
                "description" =>
                    $bookData["descriptions"]["de"]["value"] ??
                    "Book related to a historical event",
                "type" => $book->getEntityType(),
                "claims" => $bookData["claims"] ?? [],
                "labels" => $bookData["labels"] ?? [],
                "descriptions" => $bookData["descriptions"] ?? [],
                "aliases" => $bookData["aliases"] ?? [],
                "sitelinks" => $bookData["sitelinks"] ?? [],
            ];

            // Check if we should use the queue system
            $useQueue = config("wikibase.use_queue", true);
            if ($useQueue) {
                $queued = $wikibaseService->queueEntityCreation(
                    $entityData,
                    $book->getEntityType(),
                );
                if ($queued) {
                    // For queued creation, we can't return a real ID yet
                    Log::info("Queued book entity creation", [
                        "title" => $entityData["title"] ?? "Unknown",
                    ]);

                    // Return null to indicate entity isn't available yet
                    return null;
                }
                // If queueing fails, fall back to synchronous creation
            }

            // Create the entity synchronously
            $response = $wikibaseService->createEntity(
                $entityData,
                $book->getEntityType(),
            );

            if ($response === null) {
                Log::error("Error creating a new book in Wikibase", [
                    "title" => $entityData["title"] ?? "Unknown",
                ]);

                return null;
            }

            // Extract entity ID from the response
            $entityId = $response["entity"]["id"] ?? null;

            Log::info("New book created in Wikibase", [
                "title" => $entityData["title"] ?? "Unknown",
                "entity_id" => $entityId,
            ]);

            return $entityId;
        } catch (\Exception $e) {
            Log::error("Error creating a new book in Wikibase", [
                "title" => $book->getLabel("de") ?? "Unknown",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Cleans up source data by normalizing IDs and handling temporary IDs
     *
     * @param array $sources Array of source data to clean up
     * @return array Cleaned up source data
     */
    public function cleanupSourceData(array $sources): array
    {
        $cleanedSources = [];

        foreach ($sources as $source) {
            // Skip empty sources
            if (empty($source) || !isset($source["type"])) {
                continue;
            }

            // Skip URL and archive sources - these should never be created as entities
            if ($source["type"] === "url" || $source["type"] === "archive") {
                // Set a flag to indicate this should never be created as an entity
                $source["_skipCreate"] = true;
                Log::debug(
                    "Marked {$source["type"]} source to skip entity creation",
                    [
                        "title" => $source["title"] ?? "Unknown",
                    ],
                );
                $cleanedSources[] = $source;
                continue; // Skip further book-specific processing
            }

            // Process book sources specially
            if ($source["type"] === "book") {
                // If book has a valid entity ID, mark it to skip creation
                // In both Wikidata and FactGrid, entity IDs start with 'Q'
                if (
                    isset($source["id"]) &&
                    substr($source["id"], 0, 1) === "Q"
                ) {
                    // This is an existing book, just reference it, don't create
                    $source["_skipCreate"] = true;
                    Log::debug(
                        "Book with ID will be referenced but not created",
                        [
                            "title" => $source["title"] ?? "Unknown",
                            "id" => $source["id"],
                        ],
                    );
                }

                // If it's a manual book, always convert to temp_id and remove id
                if (isset($source["id"]) && $source["id"] === "manual") {
                    // If there's no temp_id, generate one
                    if (empty($source["temp_id"])) {
                        $source["temp_id"] = "temp_" . uniqid();
                        Log::debug("Converting manual book to temp_id", [
                            "title" => $source["title"] ?? "Unknown",
                            "temp_id" => $source["temp_id"],
                        ]);
                    }
                    // Always remove the manual ID
                    unset($source["id"]);
                }

                // Handle case where tempId is used instead of temp_id (frontend inconsistency)
                if (!empty($source["tempId"])) {
                    $source["temp_id"] = $source["tempId"];
                    unset($source["tempId"]);
                    Log::debug("Normalized tempId to temp_id", [
                        "title" => $source["title"] ?? "Unknown",
                        "temp_id" => $source["temp_id"],
                    ]);
                }

                // If it has both temp_id and any non-Wikibase id, remove the id
                if (
                    !empty($source["temp_id"]) &&
                    isset($source["id"]) &&
                    substr($source["id"], 0, 1) !== "Q"
                ) {
                    unset($source["id"]);
                    Log::debug("Removed non-Wikibase ID in favor of temp_id", [
                        "title" => $source["title"] ?? "Unknown",
                        "temp_id" => $source["temp_id"],
                    ]);
                }

                // Clean up authors if they exist
                if (
                    !empty($source["authors"]) &&
                    is_array($source["authors"])
                ) {
                    foreach ($source["authors"] as $index => $author) {
                        // Handle case where tempId is used instead of temp_id (frontend inconsistency)
                        if (!empty($author["tempId"])) {
                            $source["authors"][$index]["temp_id"] =
                                $author["tempId"];
                            unset($source["authors"][$index]["tempId"]);
                        }

                        // IMPORTANT: For all authors with temp_id, mark them to be processed as strings
                        // and NEVER create person entities for them
                        if (!empty($author["temp_id"])) {
                            // Flag to indicate this should be a string author, not an entity
                            $source["authors"][$index]["_useAsString"] = true;

                            // If author has both id and temp_id, and id is not a Wikibase ID,
                            // remove the id field to avoid confusion
                            if (
                                isset($author["id"]) &&
                                substr($author["id"], 0, 1) !== "Q"
                            ) {
                                unset($source["authors"][$index]["id"]);
                                Log::debug(
                                    "Removed non-Wikibase author ID in favor of temp_id",
                                    [
                                        "author" => "Unknown",
                                        "temp_id" => $author["temp_id"],
                                    ],
                                );
                            }
                        }
                    }
                }

                // Clean up single author format if it exists
                if (!empty($source["author"])) {
                    $author = $source["author"];

                    // Handle case where tempId is used instead of temp_id (frontend inconsistency)
                    if (!empty($author["tempId"])) {
                        $source["author"]["temp_id"] = $author["tempId"];
                        unset($source["author"]["tempId"]);
                    }

                    // IMPORTANT: For all authors with temp_id, mark them to be processed as strings
                    // and NEVER create person entities for them
                    if (!empty($author["temp_id"])) {
                        // Flag to indicate this should be a string author, not an entity
                        $source["author"]["_useAsString"] = true;

                        // If author has both id and temp_id, and id is not a Wikibase ID,
                        // remove the id field to avoid confusion
                        if (
                            isset($author["id"]) &&
                            substr($author["id"], 0, 1) !== "Q"
                        ) {
                            unset($source["author"]["id"]);
                            Log::debug(
                                "Removed non-Wikibase single author ID in favor of temp_id",
                                [
                                    "author" =>
                                        $author["label"] ??
                                        ($author["firstName"] ?? "") .
                                            " " .
                                            ($author["lastName"] ?? ""),
                                    "temp_id" => $author["temp_id"],
                                ],
                            );
                        }
                    }
                }
            }

            $cleanedSources[] = $source;
        }

        return $cleanedSources;
    }
}

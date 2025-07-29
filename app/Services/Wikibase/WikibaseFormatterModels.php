<?php

namespace App\Services\Wikibase;

use App\Models\Wikidata\BaseEntity;
use App\Models\Wikidata\Book;
use App\Models\Wikidata\EntityFactory;
use App\Models\Wikidata\Event;
use App\Models\Wikidata\Organization;
use App\Models\Wikidata\Person;
use App\Models\Wikidata\Structure;
use App\Models\Wikidata\Source\SourceFactory;
use Illuminate\Support\Facades\Log;
use App\Services\EntityMapping\EntityMappingProviderInterface;

class WikibaseFormatterModels
{
    // Role Constants
    public const ROLE_VICTIM = "victim";
    public const ROLE_PERPETRATOR = "perpetrator";
    
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
        bool $createEntities = false
    ): array {
        // If running in queue worker, automatically disable debug mode
        if ($this->isRunningInQueue()) {
            $this->inDebugMode = false;
            config(["wikibase.debug_mode" => false]);
            Log::debug(
                "Debug mode automatically disabled in queue worker for formatEntityData"
            );
        } elseif (config()->has("wikibase.debug_mode")) {
            // Only check global config if not running in queue worker
            $this->inDebugMode = config("wikibase.debug_mode");
        }

        // Debug input to formatEntityData only when needed
        if (config("app.debug")) {
            Log::debug("Processing entity data of type: " . $type);
            Log::debug(
                "Debug mode state: " . ($this->inDebugMode ? "true" : "false")
            );
            Log::debug(
                "CreateEntities parameter: " .
                    ($createEntities ? "true" : "false")
            );
            Log::debug(
                "Running in queue: " .
                    ($this->isRunningInQueue() ? "true" : "false")
            );
        }

        // Get the instance-of property ID from the mapping provider
        $instanceOfProperty = $this->mappingProvider->getPropertyId('INSTANCE_OF');

        // Check if we're in debug mode
        if ($this->inDebugMode) {
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
                                                substr($type, 1)
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

        // Add perpetrators if available
        if (!empty($data["perpetrators"])) {
            // Ensure perpetrators is an array
            if (is_string($data["perpetrators"])) {
                $data["perpetrators"] = json_decode($data["perpetrators"], true) ?? [];
            }
            
            $data["perpetrators"] = $this->processEntityRelations(
                $entity,
                $data["perpetrators"],
                self::ROLE_PERPETRATOR,
                $createEntities
            );
        }

        // Add victims if available
        if (!empty($data["victims"])) {
            // Ensure victims is an array
            if (is_string($data["victims"])) {
                $data["victims"] = json_decode($data["victims"], true) ?? [];
            }
            
            $data["victims"] = $this->processEntityRelations(
                $entity,
                $data["victims"],
                self::ROLE_VICTIM,
                $createEntities
            );
        }

        // Add source information if available
        if (!empty($data["sources"])) {
            // Ensure sources is an array
            if (is_string($data["sources"])) {
                $data["sources"] = json_decode($data["sources"], true) ?? [];
            }
            
            // Create source models
            $sources = SourceFactory::createMultipleFromArray($data["sources"]);

            // Add sources to the entity
            $this->addSourcesToEntityInternal(
                $entity,
                $sources,
                $data["wikidata_id"] ?? null
            );

            // Get updated formatted data
            $formattedData = $entity->getFormattedData();
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
        bool $createEntities = false
    ): array {
        // If running in queue worker, automatically disable debug mode
        if ($this->isRunningInQueue()) {
            $this->inDebugMode = false;
            config(["wikibase.debug_mode" => false]);
            Log::debug(
                "Debug mode automatically disabled in queue worker for entity relations"
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
            ]
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
        $property = $role === self::ROLE_PERPETRATOR
            ? $this->mappingProvider->getPropertyId('PERPETRATOR')
            : $this->mappingProvider->getPropertyId('VICTIM');
            
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
                    $role
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
                            $name
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
                            ]
                        );
                    } elseif ($this->isRunningInQueue()) {
                        Log::info(
                            "Creating entity because we're in queue worker",
                            [
                                "name" => $name,
                                "role" => $role,
                            ]
                        );
                    }

                    // TODO: Add logic to create entities if necessary
                    
                }
                
                // Update the entity in the result array
                $relatedEntities[$index] = $updatedEntity;
            }
        }

        return $relatedEntities;
    }

    /**
     * Get the name of an entity from the entity data
     *
     * @param array $entityData The entity data
     * @return string The entity name
     */
    protected function getEntityName(array $entityData): string
    {
        // If name is directly available, use it
        if (!empty($entityData["name"])) {
            return $entityData["name"];
        }

        // Otherwise, try to construct from firstName and lastName
        $firstName = $entityData["firstName"] ?? "";
        $lastName = $entityData["lastName"] ?? "";

        if (!empty($firstName) || !empty($lastName)) {
            return trim($firstName . " " . $lastName);
        }

        // If all else fails, use label or default to empty string
        return $entityData["label"] ?? "";
    }

    /**
     * Prepare person data for creating a new person entity
     *
     * @param array $data The source data
     * @param BaseEntity $parentEntity The parent entity
     * @param string $role The role of the person
     * @return array Prepared person data
     */
    protected function preparePersonData(
        array $data,
        BaseEntity $parentEntity,
        string $role
    ): array {
        // Get the name from data or construct from firstName and lastName
        $name = $this->getEntityName($data);

        // Prepare the basic data
        $personData = [
            "title" => $name,
            "description" => $data["description"] ?? "",
            "role" => $role,
        ];

        // Add any additional fields that are present
        if (!empty($data["birthDate"])) {
            $personData["birthDate"] = $data["birthDate"];
        }

        if (!empty($data["deathDate"])) {
            $personData["deathDate"] = $data["deathDate"];
        }

        if (!empty($data["gender"])) {
            $personData["gender"] = $data["gender"];
        }

        if (!empty($data["occupation"])) {
            $personData["occupation"] = $data["occupation"];
        }

        // Add temporary or permanent ID
        if (!empty($data["id"])) {
            $personData["id"] = $data["id"];
        } elseif (!empty($data["temp_id"])) {
            $personData["temp_id"] = $data["temp_id"];
        } elseif (!empty($data["tempId"])) {
            $personData["temp_id"] = $data["tempId"];
        }

        return $personData;
    }
}
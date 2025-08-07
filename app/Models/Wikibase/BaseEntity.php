<?php

namespace App\Models\Wikibase;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;
use App\Services\Wikibase\ImageHandler;

abstract class BaseEntity
{
    // Properties that should receive source references
    /**
     * Properties that should receive source references
     *
     * This list defines which properties will have source references added to them
     * when addSource is called. For example, if P8031 (perpetrator) is in this list,
     * then all perpetrator claims will get source references attached.
     */
    protected const SOURCE_REFERENCE_PROPERTIES = [
        "P1590", // Victim count
        "P8031", // Perpetrator
        "P8032", // Victim
        "P1120", // Number of deaths
        "P1561", // Number of missing persons
        "P5582", // Number of survivors
    ];

    // Entity data storage
    protected $data = [
        "labels" => [],
        "descriptions" => [],
        "aliases" => [],
        "claims" => [],
        "sitelinks" => [],
    ];

    // Entity type ID (e.g., Q-code in Wikidata)
    protected $entityType;

    // Entity label/title
    protected $title;

    // Entity description
    protected $description;

    // Entity mapping provider - making this property accessible to child classes
    protected $mappingProvider;

    /**
     * Constructor
     *
     * @param string $title The entity title
     * @param string $description The entity description
     * @param EntityMappingProviderInterface|null $mappingProvider Optional mapping provider (will resolve from container if not provided)
     */
    public function __construct(
        string $title,
        string $description = "",
        ?EntityMappingProviderInterface $mappingProvider = null,
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->mappingProvider =
            $mappingProvider ?? app(EntityMappingProviderInterface::class);
        $this->entityType = $this->mappingProvider->getEntityTypeId(
            get_class($this),
        );
        $this->initializeLabels();
    }

    /**
     * Initialize basic labels and descriptions
     */
    protected function initializeLabels(): void
    {
        // Don't initialize labels if we're updating an existing entity that already has an ID
        // This prevents overwriting existing labels
        if (
            isset($this->data["_is_update"]) &&
            $this->data["_is_update"] === true
        ) {
            Log::info("Skipping label initialization for entity update");
            return;
        }

        // Make sure title is not empty to ensure labels are set
        $title = !empty($this->title) ? $this->title : "Unnamed entity";

        // Set the German label (primary language)
        $this->data["labels"]["de"] = [
            "language" => "de",
            "value" => $title,
        ];

        // Also set English label for international accessibility
        $this->data["labels"]["en"] = [
            "language" => "en",
            "value" => $title,
        ];

        // Set description if available
        if (!empty($this->description)) {
            $this->data["descriptions"]["de"] = [
                "language" => "de",
                "value" => $this->description,
            ];

            // Also set English description
            $this->data["descriptions"]["en"] = [
                "language" => "en",
                "value" => $this->description,
            ];
        }

        // Log the initialization
        Log::info("Initialized entity labels and descriptions", [
            "title" => $title,
            "description" => $this->description ?? "None",
            "has_title" => !empty($this->title),
            "label_value" => $this->data["labels"]["de"]["value"],
        ]);
    }

    /**
     * Add the instance of claim (P31)
     */
    protected function addInstanceOfClaim(): void
    {
        $instanceOfProperty = $this->mappingProvider->getPropertyId(
            "INSTANCE_OF",
        );

        $this->data["claims"][$instanceOfProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $instanceOfProperty,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $this->entityType,
                            "numeric-id" => intval(
                                substr($this->entityType, 1),
                            ),
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];
    }

    /**
     * Add coordinates claim
     */
    public function addCoordinates($coordinates): void
    {
        if (empty($coordinates)) {
            return;
        }

        $parsedCoordinates = $this->parseCoordinates($coordinates);
        if ($parsedCoordinates === null) {
            return;
        }

        $coordinateProperty = $this->mappingProvider->getPropertyId(
            "COORDINATE_LOCATION",
        );

        $this->data["claims"][$coordinateProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $coordinateProperty,
                    "datavalue" => [
                        "value" => [
                            "latitude" => $parsedCoordinates["lat"],
                            "longitude" => $parsedCoordinates["lng"],
                            "precision" => 0.0001,
                            "globe" => "http://www.wikidata.org/entity/Q2",
                        ],
                        "type" => "globecoordinate",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];
    }

    /**
     * Add image claim
     */
    public function addImage(string $imageFilename): void
    {
        if (empty($imageFilename)) {
            Log::warning("Empty image filename provided to addImage");
            return;
        }

        Log::info("addImage called with filename", [
            "imageFilename" => $imageFilename,
            "entity_type" => $this->getEntityType(),
            "entity_label" => $this->getLabel() ?? "Unknown",
            "caller" =>
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1][
                    "function"
                ] ?? "unknown",
        ]);

        // Get the image property ID (P18 in Wikidata)
        $imageProperty = $this->mappingProvider->getPropertyId("IMAGE");
        if (empty($imageProperty)) {
            Log::error("Could not find IMAGE property ID", [
                "entity_type" => $this->getEntityType(),
                "entity_label" => $this->getLabel() ?? "Unknown",
            ]);
            return;
        }

        // Process the image filename using the ImageHandler utility class
        // Get a display version (with File: prefix) for storage
        $displayFilename = ImageHandler::prepareImageFilename(
            $imageFilename,
            true,
        );

        // Get a clean version (without File: prefix) for API submission
        $apiFilename = ImageHandler::prepareImageFilename(
            $imageFilename,
            false,
        );

        // Log the image addition
        Log::info("Adding image to entity", [
            "property" => $imageProperty,
            "display_filename" => $displayFilename,
            "api_filename" => $apiFilename,
            "entity_type" => $this->getEntityType(),
            "entity_label" => $this->getLabel() ?? "Unknown",
        ]);

        // Ensure claims array exists
        if (!isset($this->data["claims"])) {
            $this->data["claims"] = [];
        }

        // Add the image claim with proper formatting for API submission (no File: prefix)
        $this->data["claims"][$imageProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $imageProperty,
                    "datavalue" => [
                        "value" => $apiFilename,
                        "type" => "string",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        // Also add the image directly to the entity data for debugging/direct access
        // Use the API version (without File: prefix) as required by Wikidata
        $this->data["image"] = $apiFilename;

        // Add the image_filename field explicitly (without File: prefix) for UI compatibility
        $this->data["image_filename"] = $apiFilename;

        // Verify the image was added to the claims
        if (isset($this->data["claims"][$imageProperty])) {
            Log::info("Image claim successfully added to entity data", [
                "property" => $imageProperty,
                "api_filename" => $apiFilename,
                "display_filename" => $displayFilename,
                "claims_count" => count($this->data["claims"]),
                "has_image_claim" => isset(
                    $this->data["claims"][$imageProperty],
                )
                    ? "yes"
                    : "no",
                "claim_structure" => json_encode(
                    $this->data["claims"][$imageProperty],
                ),
            ]);
        } else {
            Log::error("Failed to add image claim to entity data", [
                "property" => $imageProperty,
                "api_filename" => $apiFilename,
                "display_filename" => $displayFilename,
                "claims_count" => count($this->data["claims"]),
                "claims_keys" => implode(
                    ", ",
                    array_keys($this->data["claims"]),
                ),
            ]);
        }
    }

    /**
     * Get the formatted data for Wikidata
     */
    public function getFormattedData(): array
    {
        // Ensure instance of claim is added
        $this->addInstanceOfClaim();

        return [
            "type" => "item",
            "labels" => $this->data["labels"],
            "descriptions" => $this->data["descriptions"],
            "aliases" => $this->data["aliases"],
            "claims" => $this->data["claims"],
            "sitelinks" => $this->data["sitelinks"],
        ];
    }

    /**
     * Formats a date into the format expected by Wikidata.
     *
     * @param string $date The date to format
     * @return array|null A formatted date for Wikidata or null in case of error
     */
    protected function formatDate(string $date): ?array
    {
        try {
            $dateTime = new \DateTime($date);
            $year = $dateTime->format("Y");
            $month = $dateTime->format("m");
            $day = $dateTime->format("d");

            return [
                "time" => "+{$year}-{$month}-{$day}T00:00:00Z",
                "timezone" => 0,
                "before" => 0,
                "after" => 0,
                "precision" => 11, // Day precision
                "calendarmodel" => "http://www.wikidata.org/entity/Q1985727", // Gregorian calendar
            ];
        } catch (\Exception $e) {
            Log::warning("Error formatting date", [
                "date" => $date,
                "error" => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Konvertiert verschiedene Koordinatenformate in ein einheitliches Format.
     *
     * @param mixed $coordinates Koordinaten in verschiedenen Formaten
     * @return array|null Array mit 'lat' und 'lng' oder null bei ungültigem Format
     */
    protected function parseCoordinates($coordinates)
    {
        if (empty($coordinates)) {
            return null;
        }

        // If already an array in the correct format
        if (
            is_array($coordinates) &&
            isset($coordinates["lat"]) &&
            isset($coordinates["lng"])
        ) {
            return [
                "lat" => (float) $coordinates["lat"],
                "lng" => (float) $coordinates["lng"],
            ];
        }

        if (is_string($coordinates)) {
            if (strpos($coordinates, "{") === 0) {
                $parsed = json_decode($coordinates, true);
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($parsed["lat"]) &&
                    isset($parsed["lng"])
                ) {
                    return [
                        "lat" => (float) $parsed["lat"],
                        "lng" => (float) $parsed["lng"],
                    ];
                }
            }

            // Try to parse as array
            if (strpos($coordinates, "[") === 0) {
                $parsed = json_decode($coordinates, true);
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($parsed[0]) &&
                    isset($parsed[1])
                ) {
                    return [
                        "lat" => (float) $parsed[0],
                        "lng" => (float) $parsed[1],
                    ];
                }
            }

            // Try to parse as comma-separated values
            $parts = explode(",", $coordinates);
            if (count($parts) >= 2) {
                return [
                    "lat" => (float) trim($parts[0]),
                    "lng" => (float) trim($parts[1]),
                ];
            }
        }

        // Fallback: Log error message and return null
        Log::warning("Invalid coordinate format", [
            "coordinates" => $coordinates,
        ]);
        return null;
    }

    /**
     * Add a qualifier to a statement.
     *
     * @param array &$statement Reference to the statement array
     * @param string $property The qualifier property ID
     * @param string $value The qualifier value (entity ID or text string)
     * @return void
     */
    protected function addQualifierToStatement(
        array &$statement,
        string $property,
        string $value,
    ): void {
        if (empty($value)) {
            return;
        }

        // Initialize qualifiers array if it doesn't exist
        if (!isset($statement["qualifiers"])) {
            $statement["qualifiers"] = [];
        }

        // Initialize specific property qualifiers array if it doesn't exist
        if (!isset($statement["qualifiers"][$property])) {
            $statement["qualifiers"][$property] = [];
        }

        // Determine if value is a Wikidata entity ID (starts with Q)
        $isEntityId = substr($value, 0, 1) === "Q";

        // Log the qualifier for debugging
        \Illuminate\Support\Facades\Log::info("Adding qualifier to statement", [
            "property" => $property,
            "value" => $value,
            "is_entity_id" => $isEntityId,
        ]);

        // Add the qualifier based on type
        if ($isEntityId) {
            // Entity ID qualifier (like Q12345)
            $statement["qualifiers"][$property][] = [
                "snaktype" => "value",
                "property" => $property,
                "datavalue" => [
                    "value" => [
                        "entity-type" => "item",
                        "id" => $value,
                        "numeric-id" => intval(substr($value, 1)),
                    ],
                    "type" => "wikibase-entityid",
                ],
            ];
        } else {
            // Text value qualifier
            $statement["qualifiers"][$property][] = [
                "snaktype" => "value",
                "property" => $property,
                "datavalue" => [
                    "value" => $value,
                    "type" => "string",
                ],
            ];
        }
    }

    /**
     * Get the underlying data array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the entity data array
     *
     * @param array $data The data to set
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Get the entity type
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get the entity label in the specified language
     *
     * @param string $language The language code (e.g., 'de', 'en')
     * @return string|null The label or null if not available
     */
    public function getLabel(string $language = "de"): ?string
    {
        return $this->data["labels"][$language]["value"] ??
            ($this->title ?? null);
    }

    /**
     * Add causes to the entity
     */
    public function addCauses(array $causes): void
    {
        if (empty($causes)) {
            return;
        }

        // Check if it's a JSON string that needs to be parsed
        if (is_string($causes)) {
            $causes = json_decode($causes, true);
        }

        if (!is_array($causes) || empty($causes)) {
            return;
        }

        $this->data["claims"]["P828"] = [];

        foreach ($causes as $cause) {
            if (
                is_array($cause) &&
                isset($cause["id"]) &&
                substr($cause["id"], 0, 1) === "Q"
            ) {
                $this->data["claims"]["P828"][] = [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => "P828",
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $cause["id"],
                                "numeric-id" => intval(substr($cause["id"], 1)),
                            ],
                            "type" => "wikibase-entityid",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ];
            }
        }
    }

    /**
     * Add a relation to another entity
     *
     * @param string $property The property ID for the relation
     * @param string $entityId The Wikidata entity ID to link to
     * @return void
     */
    public function addRelation(string $property, string $entityId): void
    {
        if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
            return;
        }

        if (!isset($this->data["claims"][$property])) {
            $this->data["claims"][$property] = [];
        }

        // Check if this relation already exists in the claims to avoid duplicates
        $isDuplicate = false;
        foreach ($this->data["claims"][$property] as $existingStatement) {
            if (
                isset(
                    $existingStatement["mainsnak"]["datavalue"]["value"]["id"],
                ) &&
                $existingStatement["mainsnak"]["datavalue"]["value"]["id"] ===
                    $entityId
            ) {
                $isDuplicate = true;

                Log::info("Skipping duplicate relation in addRelation method", [
                    "property" => $property,
                    "entity_id" => $entityId,
                ]);
                break;
            }
        }

        // Only add if not a duplicate
        if (!$isDuplicate) {
            $this->data["claims"][$property][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $property,
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

            Log::info("Added relation to entity", [
                "property" => $property,
                "entity_id" => $entityId,
            ]);
        }
    }

    /**
     * Add a string claim
     *
     * @param string $property The property ID for the string claim
     * @param string $value The string value
     * @return void
     */
    public function addStringClaim(string $property, string $value): void
    {
        if (empty($value)) {
            return;
        }

        if (!isset($this->data["claims"][$property])) {
            $this->data["claims"][$property] = [];
        }

        $this->data["claims"][$property][] = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $property,
                "datavalue" => [
                    "value" => $value,
                    "type" => "string",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];
    }

    /**
     * Add a source to the entity
     *
     * @param mixed $source The source object or data
     * @param string|null $parentEntityId Optional parent entity ID for linking sources
     * @return void
     */
    public function addSource($source, ?string $parentEntityId = null): void
    {
        if (empty($source)) {
            return;
        }

        // If the source has a getFormattedReference method, use it
        if (method_exists($source, "getFormattedReference")) {
            $reference = $source->getFormattedReference($parentEntityId);

            // Get the property of the source if it has a toClaim method
            $sourcePropertyId = null;
            if (method_exists($source, "toClaim")) {
                $sourceClaim = $source->toClaim();
                if (
                    !empty($sourceClaim) &&
                    !empty($sourceClaim["mainsnak"]) &&
                    !empty($sourceClaim["mainsnak"]["property"])
                ) {
                    $sourcePropertyId = $sourceClaim["mainsnak"]["property"];
                }
            }

            // Add the reference only to specific claims defined in SOURCE_REFERENCE_PROPERTIES
            foreach ($this->data["claims"] as $propertyId => &$statements) {
                // Skip properties that are not in our target list
                if (!in_array($propertyId, self::SOURCE_REFERENCE_PROPERTIES)) {
                    continue;
                }

                // Skip adding reference to the same property that the source itself uses
                // This prevents duplicate claims when a source is also a perpetrator or victim
                if ($propertyId === $sourcePropertyId) {
                    Log::debug(
                        "Skipping reference for property that matches source property",
                        [
                            "property" => $propertyId,
                            "source_property" => $sourcePropertyId,
                        ],
                    );
                    continue;
                }

                // Log that we're adding a source to this specific property
                if (
                    method_exists($source, "getType") &&
                    method_exists($source, "getTitle")
                ) {
                    Log::info("Adding source reference to targeted property", [
                        "property" => $propertyId,
                        "source_type" => $source->getType(),
                        "source_title" => $source->getTitle(),
                        "targeted_properties" => implode(
                            ", ",
                            self::SOURCE_REFERENCE_PROPERTIES,
                        ),
                    ]);
                }

                foreach ($statements as &$statement) {
                    if (!isset($statement["references"])) {
                        $statement["references"] = [];
                    }

                    // Check if this reference already exists for this statement
                    $referenceExists = false;
                    foreach ($statement["references"] as $existingRef) {
                        if ($existingRef == $reference) {
                            // Using == for array comparison
                            $referenceExists = true;
                            Log::debug(
                                "Skipping duplicate reference for property",
                                [
                                    "property" => $propertyId,
                                    "source_type" => $source->getType(),
                                ],
                            );
                            break;
                        }
                    }

                    if (!$referenceExists) {
                        $statement["references"][] = $reference;
                    }
                }
            }
        }
    }
}

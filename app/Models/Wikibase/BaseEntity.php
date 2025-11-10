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

        // Set only German label
        $this->data["labels"]["de"] = [
            "language" => "de",
            "value" => $title,
        ];

        // Set description if available (only in German)
        if (!empty($this->description)) {
            $this->data["descriptions"]["de"] = [
                "language" => "de",
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

        // Skip if property ID is empty
        if (empty($instanceOfProperty)) {
            return;
        }

        // Initialize the claims array if it doesn't exist yet
        if (!isset($this->data["claims"][$instanceOfProperty])) {
            $this->data["claims"][$instanceOfProperty] = [];
        }

        // Check if the base type is already in the claims
        $baseTypeExists = false;
        foreach ($this->data["claims"][$instanceOfProperty] as $claim) {
            if (
                isset($claim["mainsnak"]["datavalue"]["value"]["id"]) &&
                $claim["mainsnak"]["datavalue"]["value"]["id"] ===
                    $this->entityType
            ) {
                $baseTypeExists = true;
                break;
            }
        }

        // Only add the base entity type claim if it doesn't already exist
        if (!$baseTypeExists) {
            $this->data["claims"][$instanceOfProperty][] = [
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
            ];
        }
    }

    /**
     * Add coordinates to the entity
     *
     * @param array|string $coordinates Coordinates in format [lat, lng] or "lat, lng"
     * @param string|null $qualifier Optional qualifier for the coordinates
     */
    public function addCoordinates(
        $coordinates,
        ?string $qualifier = null,
    ): void {
        if (empty($coordinates)) {
            return;
        }

        // Handle different coordinate formats
        $lat = null;
        $lng = null;

        if (is_string($coordinates)) {
            // Format: "52.3744779, 9.7385532"
            $parts = explode(",", $coordinates);
            if (count($parts) === 2) {
                $latStr = trim($parts[0]);
                $lngStr = trim($parts[1]);

                // Clean up the values and ensure they're valid
                $latStr = preg_replace("/[^\d\.\-]/", "", $latStr);
                $lngStr = preg_replace("/[^\d\.\-]/", "", $lngStr);

                // Convert to float only if valid
                if (is_numeric($latStr) && is_numeric($lngStr)) {
                    $lat = (float) $latStr;
                    $lng = (float) $lngStr;

                    // Log the conversion for debugging
                    \Illuminate\Support\Facades\Log::info(
                        "Coordinates converted",
                        [
                            "original" => $coordinates,
                            "lat" => $lat,
                            "lng" => $lng,
                        ],
                    );
                }
            }
        } elseif (is_array($coordinates)) {
            if (isset($coordinates[0]) && isset($coordinates[1])) {
                // Format: [lat, lng]
                if (
                    is_numeric($coordinates[0]) &&
                    is_numeric($coordinates[1])
                ) {
                    $lat = (float) $coordinates[0];
                    $lng = (float) $coordinates[1];
                }
            } elseif (
                isset($coordinates["lat"]) &&
                isset($coordinates["lng"])
            ) {
                // Format: {lat: x, lng: y}
                if (
                    is_numeric($coordinates["lat"]) &&
                    is_numeric($coordinates["lng"])
                ) {
                    $lat = (float) $coordinates["lat"];
                    $lng = (float) $coordinates["lng"];
                }
            }
        }

        if ($lat === null || $lng === null) {
            \Illuminate\Support\Facades\Log::warning(
                "Invalid coordinates format",
                [
                    "coordinates" => $coordinates,
                ],
            );
            return;
        }

        $coordinatesProperty = $this->mappingProvider->getPropertyId(
            "COORDINATES",
        );
        if (empty($coordinatesProperty)) {
            \Illuminate\Support\Facades\Log::warning(
                "Coordinates property ID is empty, using P625 as fallback",
            );
            $coordinatesProperty = "P625"; // Fallback to standard Wikidata coordinates property
        }

        // Final validation to ensure coordinates are valid numbers
        if (!is_finite($lat) || !is_finite($lng)) {
            \Illuminate\Support\Facades\Log::error(
                "Coordinates failed final validation - not finite numbers",
                [
                    "lat" => $lat,
                    "lng" => $lng,
                ],
            );
            return;
        }

        // Ensure coordinates are within valid ranges
        $lat = max(-90, min(90, $lat)); // Latitude: -90 to 90
        $lng = max(-180, min(180, $lng)); // Longitude: -180 to 180

        $statement = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $coordinatesProperty,
                "datavalue" => [
                    "value" => [
                        "latitude" => $lat,
                        "longitude" => $lng,
                        "altitude" => null,
                        "precision" => 0.000001,
                        "globe" => "http://www.wikidata.org/entity/Q2",
                    ],
                    "type" => "globecoordinate",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Log the final coordinates being used
        \Illuminate\Support\Facades\Log::info(
            "Final coordinates for Wikidata",
            [
                "latitude" => $lat,
                "longitude" => $lng,
            ],
        );

        // Add qualifier if provided
        if (!empty($qualifier)) {
            $qualifierProperty = $this->mappingProvider->getPropertyId(
                "QUALIFIER_OF",
            );
            if (!empty($qualifierProperty)) {
                $this->addQualifierToStatement(
                    $statement,
                    $qualifierProperty,
                    $qualifier,
                );
            }
        }

        $this->data["claims"][$coordinatesProperty] = [$statement];
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
     * Format a date for Wikidata with different precision levels
     *
     * @param string $date The date string (YYYY, YYYY-MM, or YYYY-MM-DD format)
     * @param string|null $precision Optional precision parameter ('year', 'month', 'day')
     * @return array|null A formatted date for Wikidata or null in case of error
     */
    protected function formatDate(
        string $date,
        ?string $precision = null,
    ): ?array {
        try {
            // Determine precision based on date format if not explicitly provided
            if (!$precision) {
                if (preg_match('/^\d{4}$/', $date)) {
                    $precision = "year";
                } elseif (preg_match('/^\d{4}-\d{2}$/', $date)) {
                    $precision = "month";
                } else {
                    $precision = "day";
                }
            }

            // Normalize the date string based on precision
            switch ($precision) {
                case "year":
                    $year = $date;
                    $month = "00";
                    $day = "00";
                    $precisionValue = 9; // Year precision in Wikidata
                    break;
                case "month":
                    if (preg_match('/^(\d{4})-(\d{2})$/', $date, $matches)) {
                        $year = $matches[1];
                        $month = $matches[2];
                        $day = "00";
                    } else {
                        $dateTime = new \DateTime($date);
                        $year = $dateTime->format("Y");
                        $month = $dateTime->format("m");
                        $day = "00";
                    }
                    $precisionValue = 10; // Month precision in Wikidata
                    break;
                case "day":
                default:
                    $dateTime = new \DateTime($date);
                    $year = $dateTime->format("Y");
                    $month = $dateTime->format("m");
                    $day = $dateTime->format("d");
                    $precisionValue = 11; // Day precision in Wikidata
                    break;
            }

            return [
                "time" => "+{$year}-{$month}-{$day}T00:00:00Z",
                "timezone" => 0,
                "before" => 0,
                "after" => 0,
                "precision" => $precisionValue,
                "calendarmodel" => "http://www.wikidata.org/entity/Q1985727", // Gregorian calendar
            ];
        } catch (\Exception $e) {
            Log::warning("Error formatting date", [
                "date" => $date,
                "precision" => $precision,
                "error" => $e->getMessage(),
            ]);
            return null;
        }
    }

    // The parseCoordinates method has been removed as it's now handled
    // directly in the addCoordinates method

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
     * Set the entity's title/label
     *
     * @param string $title The entity title
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;

        // Update label in the data structure
        $this->data["labels"]["de"] = [
            "language" => "de",
            "value" => $title,
        ];
    }

    /**
     * Set the entity's description
     *
     * @param string $description The entity description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;

        // Update description in the data structure
        if (!empty($description)) {
            $this->data["descriptions"]["de"] = [
                "language" => "de",
                "value" => $description,
            ];
        }
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

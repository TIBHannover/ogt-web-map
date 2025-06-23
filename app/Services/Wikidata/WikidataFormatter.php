<?php

namespace App\Services\Wikidata;

use Illuminate\Support\Facades\Log;

class WikidataFormatter
{
    /*
     * Entity Type Constants
     */
    public const ENTITY_TYPE_PERSON = "Q5"; // Person
    public const ENTITY_TYPE_EVENT = "Q6983405"; // Event
    public const ENTITY_TYPE_ORGANIZATION = "Q43229"; // Organization
    public const ENTITY_TYPE_STRUCTURE = "Q811979"; // Architectural Structure

    /*
     * Role and Type Constants
     */
    public const ROLE_VICTIM = "victim";
    public const ROLE_PERPETRATOR = "perpetrator";
    public const WIKIDATA_ROLE_PROPERTY = "P2868"; // Subject has role
    public const WIKIDATA_VICTIM_NAZI = "Q2026714"; // Victim of Nazism
    public const WIKIDATA_PERPETRATOR_NAZI = "Q111080573"; // Perpetrator of Nazism
    public const WIKIDATA_GESTAPO_TERROR_PLACE = "Q106996250"; // Places of Gestapo terror

    /*
     * Qualifier Property Constants
     */
    public const WIKIDATA_SOURCING_CIRCUMSTANCES = "P1480"; // Sourcing circumstances
    public const WIKIDATA_CIRCA = "Q5727902"; // Circa
    public const WIKIDATA_UNGEFAEHR = "Q60070514"; // Ungefähr
    public const WIKIDATA_LESS_THAN = "Q52834024"; // Less than
    public const WIKIDATA_MORE_THAN = "Q47035128"; // More than

    /*
     * Common Property Constants
     */
    public const WIKIDATA_INSTANCE_OF = "P31"; // Instance of
    public const WIKIDATA_COUNTRY = "P17"; // Country
    public const WIKIDATA_COORDINATES = "P625"; // Coordinates
    public const WIKIDATA_CAUSE = "P828"; // Cause
    public const WIKIDATA_DESCRIBED_BY_SOURCE = "P1343"; // Described by source
    public const WIKIDATA_OFFICIAL_NAME = "P1448"; // Official name
    public const WIKIDATA_AUTHOR_STRING = "P2093"; // Author as string
    public const WIKIDATA_IMAGE = "P18"; // Image

    /**
     * Book and Author Property Constants
     */
    public const ENTITY_TYPE_BOOK = "Q7725634"; // Literary work
    public const WIKIDATA_TITLE = "P1476"; // Title
    public const WIKIDATA_AUTHOR = "P50"; // Author
    public const WIKIDATA_PUBLISHING_DATE = "P577"; // Publication date

    /**
     * Perpetrator and Victim Property Constants
     */
    public const WIKIDATA_PERPETRATOR = "P8031"; // has perpetrator (Participant has role perpetrator)
    public const WIKIDATA_VICTIM = "P8032"; // has victim (Manner of death/victim of)

    /**
     * Person Property Constants
     */
    public const WIKIDATA_DATE_OF_BIRTH = "P569"; // Date of birth
    public const WIKIDATA_DATE_OF_DEATH = "P570"; // Date of death
    public const WIKIDATA_OCCUPATION = "P106"; // Occupation
    public const WIKIDATA_NATIONALITY = "P27"; // Nationality
    public const WIKIDATA_GENDER = "P21"; // Gender
    public const WIKIDATA_PLACE_OF_DETENTION = "P2632"; // Place of detention
    public const WIKIDATA_PLACE_OF_BIRTH = "P19"; // Place of birth
    public const WIKIDATA_PLACE_OF_DEATH = "P20"; // Place of death

    /**
     * Event Property Constants
     */
    public const WIKIDATA_POINT_IN_TIME = "P585"; // Point in time
    public const WIKIDATA_START_TIME = "P580"; // Start date
    public const WIKIDATA_END_TIME = "P582"; // End date
    public const WIKIDATA_VICTIMS_COUNT = "P1590"; // Number of victims
    public const WIKIDATA_SURVIVORS_COUNT = "P1561"; // Number of survivors
    public const WIKIDATA_DEATHS_COUNT = "P1120"; // Number of deaths
    public const WIKIDATA_ARRESTS_COUNT = "P5582"; // Number of arrests

    /**
     * Organization and Structure Property Constants
     */
    public const WIKIDATA_INCEPTION = "P571"; // Foundation date
    public const WIKIDATA_DISSOLVED = "P576"; // Dissolution date/Demolition
    public const WIKIDATA_HEADQUARTERS = "P159"; // Headquarters
    public const WIKIDATA_ARCHITECT = "P84"; // Architect
    public const WIKIDATA_HEIGHT = "P2048"; // Height

    /**
     * Source/Reference Property Constants
     */
    public const WIKIDATA_STATED_IN = "P1343"; // Described in
    public const WIKIDATA_PAGE = "P304"; // Page
    public const WIKIDATA_STATED_IN_URL = "P973"; // Described in URL
    public const WIKIDATA_RETRIEVED_DATE = "P813"; // Access date
    public const WIKIDATA_ARCHIVE = "P485"; // Archive
    public const WIKIDATA_INVENTORY_NUMBER = "P217"; // Inventory number

    /**
     * The base URL for entity references
     */
    protected string $entityUrlBase;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Extract domain from API URL to create entity URL base
        $apiUrl = config("wikidata.api_url");
        $parsedUrl = parse_url($apiUrl);
        $domain = $parsedUrl["host"];
        $this->entityUrlBase = "https://{$domain}/entity/";
    }
    /*
     * Formats the data for Wikidata based on the entity type.
     *
     * @param array $data The source data from the request
     * @param string $type The entity type (Q5 = Person, Q6983405 = Event, Q43229 = Organization, Q811979 = Architectural Structure)
     * @param bool $createEntities Flag whether new entities should be created (only in jobs, not in preview)
     * @return array Formatted data for Wikidata
     */
    public function formatEntityData(
        array $data,
        string $type,
        bool $createEntities = false
    ): array {
        // Debug input to formatEntityData only when needed
        if (config("app.debug")) {
            Log::debug("Processing entity data of type: " . $type);
        }

        // Check if data already contains formatted claims
        // This is to preserve claims when data is passed through WikidataService::createEntity
        $existingClaims = null;
        if (
            isset($data["claims"]) &&
            is_array($data["claims"]) &&
            !empty($data["claims"])
        ) {
            $existingClaims = $data["claims"];
        }

        // Basic structure according to Wikibase documentation
        $formattedData = [
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
            "claims" => [],
            "sitelinks" => [],
        ];

        // Instance of (P31) - Create claim
        $formattedData["claims"][self::WIKIDATA_INSTANCE_OF] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_INSTANCE_OF,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $type,
                            "numeric-id" => intval(substr($type, 1)), // "Q123" -> 123
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        // Country (P17) - Germany under Nazi regime (Q7318)
        /*
        $formattedData["claims"][self::WIKIDATA_COUNTRY] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_COUNTRY,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => "Q7318",
                            "numeric-id" => 7318,
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];
        */

        // Add coordinates if available
        if (!empty($data["coordinates"])) {
            $coordinates = $this->parseCoordinates($data["coordinates"]);
            if ($coordinates !== null) {
                $formattedData["claims"][self::WIKIDATA_COORDINATES] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_COORDINATES,
                            "datavalue" => [
                                "value" => [
                                    "latitude" => $coordinates["lat"],
                                    "longitude" => $coordinates["lng"],
                                    "precision" => 0.0001,
                                    "globe" =>
                                        "http://www.wikidata.org/entity/Q2",
                                ],
                                "type" => "globecoordinate",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }

            // Array for updated perpetrator data
            $updatedPerpetrators = [];
        }

        // Add image if available (P18 - Image)
        if (!empty($data["image_filename"])) {
            $formattedData["claims"][self::WIKIDATA_IMAGE] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_IMAGE,
                        "datavalue" => [
                            "value" => $data["image_filename"],
                            "type" => "string",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }

        // Cause of persecution (causes array)
        if (!empty($data["selected_causes"])) {
            // Check if selected_causes contains JSON data of multiple causes
            $causes = is_string($data["selected_causes"])
                ? json_decode($data["selected_causes"], true)
                : $data["selected_causes"];

            if (
                is_array($causes) &&
                count($causes) > 0 &&
                isset($causes[0]["id"])
            ) {
                // It's a JSON array of causes
                $formattedData["claims"][self::WIKIDATA_CAUSE] = [];

                foreach ($causes as $cause) {
                    if (
                        is_array($cause) &&
                        isset($cause["id"]) &&
                        substr($cause["id"], 0, 1) === "Q"
                    ) {
                        // Add as Wikidata entity reference
                        $formattedData["claims"][self::WIKIDATA_CAUSE][] = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_CAUSE,
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => $cause["id"],
                                        "numeric-id" => intval(
                                            substr($cause["id"], 1)
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
            }
        }

        // Add perpetrators if available
        if (!empty($data["perpetrators"])) {
            $data["perpetrators"] = $this->addPerpetrators(
                $formattedData,
                $data["perpetrators"],
                $createEntities
            );
        }

        // Add victims if available
        if (!empty($data["victims"])) {
            $data["victims"] = $this->addVictims(
                $formattedData,
                $data["victims"],
                $createEntities
            );
        }

        // Type-specific formatting
        switch ($type) {
            case self::ENTITY_TYPE_PERSON: // Person
                $this->formatPersonEntity($formattedData, $data);
                break;
            case self::ENTITY_TYPE_EVENT: // Event
                $this->formatEventEntity($formattedData, $data);
                break;
            case self::ENTITY_TYPE_ORGANIZATION: // Organization
                $this->formatOrganizationEntity($formattedData, $data);
                break;
            case self::ENTITY_TYPE_STRUCTURE: // Architectural Structure
                $this->formatStructureEntity($formattedData, $data);
                break;
        }

        // Add source information if available
        if (!empty($data["sources"])) {
            $this->addSourcesAsClaims($formattedData, $data["sources"]);
        }

        // If we had existing claims, restore them all while preserving what we've added
        if ($existingClaims !== null) {
            // Add all claims from existingClaims that haven't been processed yet
            foreach ($existingClaims as $property => $propertyValues) {
                // If this is a new property we haven't processed yet, just add it
                if (
                    !in_array($property, array_keys($formattedData["claims"]))
                ) {
                    $formattedData["claims"][$property] = $propertyValues;
                }
                // If we already have this property (like P31), keep the existing claims
                // which are assumed to be more complete
                else {
                    $formattedData["claims"][$property] = $propertyValues;
                }
            }
        }

        // Return result with formatted data and updated perpetrators/victims
        return [
            "formattedData" => $formattedData,
            "updatedData" => $data,
        ];
    }

    /**
     * Konvertiert verschiedene Koordinatenformate in ein einheitliches Format.
     *
     * @param mixed $coordinates Koordinaten in verschiedenen Formaten
     * @return array|null Array mit 'lat' und 'lng' oder null bei ungu00fcltigem Format
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
     * Formats Person entities (Q5).
     *
     * @param array &$formattedData Reference to the formatted data structure
     * @param array $data Original data
     * @return array The updated data
     */
    protected function formatPersonEntity(
        array &$formattedData,
        array $data
    ): void {
        // Date of birth (P569)
        if (!empty($data["date_of_birth"])) {
            $birthDate = $this->formatDate($data["date_of_birth"]);
            if ($birthDate !== null) {
                $formattedData["claims"][self::WIKIDATA_DATE_OF_BIRTH] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_DATE_OF_BIRTH,
                            "datavalue" => [
                                "value" => $birthDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Date of death (P570)
        if (!empty($data["date_of_death"])) {
            $deathDate = $this->formatDate($data["date_of_death"]);
            if ($deathDate !== null) {
                $formattedData["claims"][self::WIKIDATA_DATE_OF_DEATH] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_DATE_OF_DEATH,
                            "datavalue" => [
                                "value" => $deathDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Occupation (P106) - Should be a Wikidata item, not a string
        if (!empty($data["occupation"])) {
            // We assume here that occupation is either a Wikidata ID or should be treated as a string
            // If it's a Wikidata ID (starts with Q), then treat it as an entity
            if (
                isset($data["occupation"]) &&
                is_string($data["occupation"]) &&
                substr($data["occupation"], 0, 1) === "Q"
            ) {
                $formattedData["claims"][self::WIKIDATA_OCCUPATION] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_OCCUPATION,
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["occupation"],
                                    "numeric-id" => intval(
                                        substr($data["occupation"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // Add as free text qualifier (P1343 - described by source)
                $formattedData["claims"][self::WIKIDATA_DESCRIBED_BY_SOURCE] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_DESCRIBED_BY_SOURCE,
                            "datavalue" => [
                                "value" => "Occupation: " . $data["occupation"],
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Nationality (P27) - Should be a Wikidata item, not a string
        if (!empty($data["nationality"])) {
            // We assume here that nationality is either a Wikidata ID or should be treated as a string
            // If it's a Wikidata ID (starts with Q), then treat it as an entity
            if (
                isset($data["nationality"]) &&
                is_string($data["nationality"]) &&
                substr($data["nationality"], 0, 1) === "Q"
            ) {
                $formattedData["claims"]["P27"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P27",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["nationality"],
                                    "numeric-id" => intval(
                                        substr($data["nationality"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // Add as free text qualifier (P1343 - described by source)
                $formattedData["claims"]["P1343"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P1343",
                            "datavalue" => [
                                "value" =>
                                    "Nationalität: " . $data["nationality"],
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }
    }

    /**
     * Formats Event entities (Q6983405).
     *
     * @param array &$formattedData Reference to the formatted data structure
     * @param array $data Original data
     */
    protected function formatEventEntity(
        array &$formattedData,
        array $data
    ): void {
        // Point in time (P585) or start and end time (P580, P582)
        if (!empty($data["time_point"])) {
            $timePoint = $this->formatDate($data["time_point"]);
            if ($timePoint !== null) {
                $formattedData["claims"][self::WIKIDATA_POINT_IN_TIME] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_POINT_IN_TIME,
                            "datavalue" => [
                                "value" => $timePoint,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
                
                // Add qualifier if provided
                if (!empty($data["time_point_qualifier"])) {
                    // Debug qualifier value
                    Log::debug("Adding time point qualifier", [
                        'qualifier' => $data["time_point_qualifier"],
                        'is_circa' => $data["time_point_qualifier"] === self::WIKIDATA_CIRCA,
                        'is_ungefaehr' => $data["time_point_qualifier"] === self::WIKIDATA_UNGEFAEHR
                    ]);
                    
                    $this->addQualifierToStatement(
                        $formattedData["claims"][self::WIKIDATA_POINT_IN_TIME][0],
                        self::WIKIDATA_SOURCING_CIRCUMSTANCES,
                        $data["time_point_qualifier"]
                    );
                }
            }
        } else {
            // Start time (P580)
            if (!empty($data["time_period_start"])) {
                $startTime = $this->formatDate($data["time_period_start"]);
                if ($startTime !== null) {
                    $formattedData["claims"][self::WIKIDATA_START_TIME] = [
                        [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_START_TIME,
                                "datavalue" => [
                                    "value" => $startTime,
                                    "type" => "time",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ],
                    ];
                    
                    // Add qualifier if provided
                    if (!empty($data["time_period_start_qualifier"])) {
                        // Debug qualifier value
                        Log::debug("Adding time period start qualifier", [
                            'qualifier' => $data["time_period_start_qualifier"],
                            'is_circa' => $data["time_period_start_qualifier"] === self::WIKIDATA_CIRCA,
                            'is_ungefaehr' => $data["time_period_start_qualifier"] === self::WIKIDATA_UNGEFAEHR
                        ]);
                        
                        $this->addQualifierToStatement(
                            $formattedData["claims"][self::WIKIDATA_START_TIME][0],
                            self::WIKIDATA_SOURCING_CIRCUMSTANCES,
                            $data["time_period_start_qualifier"]
                        );
                    }
                }
            }

            // End time (P582)
            if (!empty($data["time_period_end"])) {
                $endTime = $this->formatDate($data["time_period_end"]);
                if ($endTime !== null) {
                    $formattedData["claims"][self::WIKIDATA_END_TIME] = [
                        [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_END_TIME,
                                "datavalue" => [
                                    "value" => $endTime,
                                    "type" => "time",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ],
                    ];
                    
                    // Add qualifier if provided
                    if (!empty($data["time_period_end_qualifier"])) {
                        // Debug qualifier value
                        Log::debug("Adding time period end qualifier", [
                            'qualifier' => $data["time_period_end_qualifier"],
                            'is_circa' => $data["time_period_end_qualifier"] === self::WIKIDATA_CIRCA,
                            'is_ungefaehr' => $data["time_period_end_qualifier"] === self::WIKIDATA_UNGEFAEHR
                        ]);
                        
                        $this->addQualifierToStatement(
                            $formattedData["claims"][self::WIKIDATA_END_TIME][0],
                            self::WIKIDATA_SOURCING_CIRCUMSTANCES,
                            $data["time_period_end_qualifier"]
                        );
                    }
                }
            }
        }

        // Cause (P828) - Should be a Wikidata item, not a string
        if (!empty($data["cause"])) {
            // We assume here that cause is either a Wikidata ID or should be treated as a string
            if (
                isset($data["cause"]) &&
                is_string($data["cause"]) &&
                substr($data["cause"], 0, 1) === "Q"
            ) {
                // Add as Wikidata item
                $formattedData["claims"]["P828"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P828",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["cause"],
                                    "numeric-id" => intval(
                                        substr($data["cause"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // Add as string in the description (P2093 - "Author" as free text field)
                $formattedData["claims"]["P2093"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P2093",
                            "datavalue" => [
                                "value" => "Ursache: " . $data["cause"],
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Victim count (P1590) - Quantity type
        if (!empty($data["victim_count"])) {
            $formattedData["claims"][self::WIKIDATA_VICTIMS_COUNT] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_VICTIMS_COUNT,
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (int) $data["victim_count"],
                                "unit" => "1",
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];

            // Add qualifier if provided
            if (!empty($data["victim_count_qualifier"])) {
                $this->addQualifierToStatement(
                    $formattedData["claims"][self::WIKIDATA_VICTIMS_COUNT][0],
                    self::WIKIDATA_SOURCING_CIRCUMSTANCES,
                    $data["victim_count_qualifier"]
                );
            }
        }

        // Survivors (P1561)
        if (!empty($data["survivor_count"])) {
            $formattedData["claims"][self::WIKIDATA_SURVIVORS_COUNT] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_SURVIVORS_COUNT,
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (int) $data["survivor_count"],
                                "unit" => "1",
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];

            // Add qualifier if provided
            if (!empty($data["survivor_count_qualifier"])) {
                $this->addQualifierToStatement(
                    $formattedData["claims"][self::WIKIDATA_SURVIVORS_COUNT][0],
                    self::WIKIDATA_SOURCING_CIRCUMSTANCES,
                    $data["survivor_count_qualifier"]
                );
            }
        }

        // Deaths (P1120)
        if (!empty($data["death_count"])) {
            $formattedData["claims"][self::WIKIDATA_DEATHS_COUNT] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_DEATHS_COUNT,
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (int) $data["death_count"],
                                "unit" => "1",
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];

            // Add qualifier if provided
            if (!empty($data["death_count_qualifier"])) {
                $this->addQualifierToStatement(
                    $formattedData["claims"][self::WIKIDATA_DEATHS_COUNT][0],
                    self::WIKIDATA_SOURCING_CIRCUMSTANCES,
                    $data["death_count_qualifier"]
                );
            }
        }

        // Arrests (P5582)
        if (!empty($data["arrest_count"])) {
            $formattedData["claims"][self::WIKIDATA_ARRESTS_COUNT] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_ARRESTS_COUNT,
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (int) $data["arrest_count"],
                                "unit" => "1",
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];

            // Add qualifier if provided
            if (!empty($data["arrest_count_qualifier"])) {
                $this->addQualifierToStatement(
                    $formattedData["claims"][self::WIKIDATA_ARRESTS_COUNT][0],
                    self::WIKIDATA_SOURCING_CIRCUMSTANCES,
                    $data["arrest_count_qualifier"]
                );
            }
        }
    }

    /**
     * Formats Organization entities (Q43229).
     *
     * @param array &$formattedData Reference to the formatted data structure
     * @param array $data Original data
     */
    protected function formatOrganizationEntity(
        array &$formattedData,
        array $data
    ): void {
        // Foundation date (P571)
        if (!empty($data["inception"])) {
            $inception = $this->formatDate($data["inception"]);
            if ($inception !== null) {
                $formattedData["claims"]["P571"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P571",
                            "datavalue" => [
                                "value" => $inception,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Dissolution date (P576)
        if (!empty($data["dissolved_date"])) {
            $dissolvedDate = $this->formatDate($data["dissolved_date"]);
            if ($dissolvedDate !== null) {
                $formattedData["claims"]["P576"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P576",
                            "datavalue" => [
                                "value" => $dissolvedDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Headquarters (P159) - Should be a Wikidata item
        if (!empty($data["headquarters_location"])) {
            if (
                isset($data["headquarters_location"]) &&
                is_string($data["headquarters_location"]) &&
                substr($data["headquarters_location"], 0, 1) === "Q"
            ) {
                // Add as Wikidata item
                $formattedData["claims"]["P159"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P159",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["headquarters_location"],
                                    "numeric-id" => intval(
                                        substr(
                                            $data["headquarters_location"],
                                            1
                                        )
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // If it's a string, add as monolingual text (location description)
                $formattedData["claims"]["P1448"] = [
                    // P1448 is "official name"
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P1448",
                            "datavalue" => [
                                "value" => [
                                    "text" => $data["headquarters_location"],
                                    "language" => "de",
                                ],
                                "type" => "monolingualtext",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }
    }

    /**
     * Formats Architectural-Structure entities (Q811979).
     *
     * @param array &$formattedData Reference to the formatted data structure
     * @param array $data Original data
     */
    protected function formatStructureEntity(
        array &$formattedData,
        array $data
    ): void {
        // Construction date (P571)
        if (!empty($data["inception"])) {
            $inception = $this->formatDate($data["inception"]);
            if ($inception !== null) {
                $formattedData["claims"]["P571"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P571",
                            "datavalue" => [
                                "value" => $inception,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Demolition/Destruction date (P576)
        if (!empty($data["demolished_date"])) {
            $demolishedDate = $this->formatDate($data["demolished_date"]);
            if ($demolishedDate !== null) {
                $formattedData["claims"]["P576"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P576",
                            "datavalue" => [
                                "value" => $demolishedDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Architect (P84) - Should be a Wikidata item
        if (!empty($data["architect"])) {
            if (
                isset($data["architect"]) &&
                is_string($data["architect"]) &&
                substr($data["architect"], 0, 1) === "Q"
            ) {
                // Add as Wikidata item
                $formattedData["claims"]["P84"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P84",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["architect"],
                                    "numeric-id" => intval(
                                        substr($data["architect"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // If it's a string, add as architect's name
                $formattedData["claims"]["P2093"] = [
                    // P2093 is "Author as free text"
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P2093",
                            "datavalue" => [
                                "value" => $data["architect"],
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Height (P2048) - Quantity type
        if (!empty($data["height"])) {
            $formattedData["claims"]["P2048"] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => "P2048",
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (float) $data["height"],
                                "unit" =>
                                    "http://www.wikidata.org/entity/Q11573", // Meter
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }
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
     * Adds perpetrators to an entity.
     *
     * @param array &$formattedData Reference to the formatted data structure
     * @param array $perpetrators Array with perpetrator data
     * @param bool $createEntities Flag whether new entities should be created (only in jobs, not in preview)
     * @return array The updated perpetrator data
     */
    protected function addPerpetrators(
        array &$formattedData,
        array $perpetrators,
        bool $createEntities = false
    ): array {
        if (empty($perpetrators)) {
            return [];
        }

        // Ensure that $perpetrators is an array
        if (is_string($perpetrators)) {
            $perpetrators = json_decode($perpetrators, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Error parsing perpetrators as JSON", [
                    "perpetrators" => $perpetrators,
                    "error" => json_last_error_msg(),
                ]);
                return [];
            }
        }

        // Initialize the claims array for perpetrators if it doesn't exist yet
        if (!isset($formattedData["claims"][self::WIKIDATA_PERPETRATOR])) {
            $formattedData["claims"][self::WIKIDATA_PERPETRATOR] = [];
        }

        foreach ($perpetrators as $index => $perpetrator) {
            $perpetratorId = null;
            $updatedPerpetrator = $perpetrator;

            // Ensure we have a name field derived from label if it exists
            if (
                !isset($updatedPerpetrator["name"]) &&
                isset($perpetrator["label"])
            ) {
                $updatedPerpetrator["name"] = $perpetrator["label"];
            }

            // If the perpetrator already has a Wikidata ID (existing entry)
            if (
                isset($perpetrator["id"]) &&
                is_string($perpetrator["id"]) &&
                substr($perpetrator["id"], 0, 1) === "Q"
            ) {
                $perpetratorId = $perpetrator["id"];

                // If we have a valid Wikidata ID, add the perpetrator
                if ($perpetratorId !== null) {
                    $formattedData["claims"][self::WIKIDATA_PERPETRATOR][] = [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_PERPETRATOR,
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $perpetratorId,
                                    "numeric-id" => intval(
                                        substr($perpetratorId, 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ];

                    // Update the perpetrator in the array
                    $perpetrators[$index] = $updatedPerpetrator;
                    continue; // Skip the rest of the processing for this perpetrator
                }
            }
            // For temporary IDs or new entries
            elseif (
                (!empty($perpetrator["temp_id"]) ||
                    !empty($perpetrator["tempId"])) &&
                (!empty($perpetrator["name"]) ||
                    !empty($perpetrator["firstName"]) ||
                    !empty($perpetrator["lastName"]))
            ) {
                // Create name from firstName and lastName if available
                $name = !empty($perpetrator["name"])
                    ? $perpetrator["name"]
                    : (!empty($perpetrator["label"])
                        ? $perpetrator["label"]
                        : trim(
                            (isset($perpetrator["firstName"])
                                ? $perpetrator["firstName"]
                                : "") .
                                " " .
                                (isset($perpetrator["lastName"])
                                    ? $perpetrator["lastName"]
                                    : "")
                        ));

                // Convert birthDate/deathDate to date_of_birth/date_of_death
                $dateOfBirth =
                    $perpetrator["date_of_birth"] ??
                    ($perpetrator["birthDate"] ?? null);
                $dateOfDeath =
                    $perpetrator["date_of_death"] ??
                    ($perpetrator["deathDate"] ?? null);

                // Create a new person if data is available
                $personData = [
                    "name" => $name,
                    "description" =>
                        $perpetrator["description"] ??
                        "Perpetrator in connection with " .
                            ($formattedData["labels"]["de"]["value"] ??
                                "an event"),
                    "date_of_birth" => $dateOfBirth,
                    "date_of_death" => $dateOfDeath,
                    "occupation" => $perpetrator["occupation"] ?? null,
                    "nationality" => $perpetrator["nationality"] ?? null,
                    "isOrganization" => $this->isOrganizationValue(
                        $perpetrator["isOrganization"] ?? null
                    ),
                    "gender" => $perpetrator["gender"] ?? null,
                    "place_of_birth" => $perpetrator["place_of_birth"] ?? null,
                    "place_of_death" => $perpetrator["place_of_death"] ?? null,
                    "place_of_detention" =>
                        $perpetrator["place_of_detention"] ?? null,
                ];

                // Use source information if available
                $sources = $perpetrator["sources"] ?? [];

                // Create person in Wikidata and get ID - only if $createEntities=true
                if ($createEntities) {
                    $perpetratorId = $this->createNewPersonEntity(
                        $personData,
                        $sources,
                        self::ROLE_PERPETRATOR
                    );

                    if ($perpetratorId !== null) {
                        // Replace temp_id/tempId with real Wikidata ID
                        if (isset($updatedPerpetrator["temp_id"])) {
                            unset($updatedPerpetrator["temp_id"]);
                        }
                        if (isset($updatedPerpetrator["tempId"])) {
                            unset($updatedPerpetrator["tempId"]);
                        }
                        $updatedPerpetrator["id"] = $perpetratorId;
                        $updatedPerpetrator["_wasCreated"] = true; // Flag that this entity was newly created

                        Log::info(
                            "New person created and linked as perpetrator",
                            [
                                "name" =>
                                    $name ??
                                    ($perpetrator["name"] ?? "Unknown"),
                                "temp_id/tempId" =>
                                    $perpetrator["temp_id"] ??
                                    ($perpetrator["tempId"] ?? "none"),
                                "wikidata_id" => $perpetratorId,
                            ]
                        );

                        // Update the original perpetrator in the array
                        $perpetrators[$index] = $updatedPerpetrator;

                        // If we have a valid Wikidata ID, add the perpetrator
                        $formattedData["claims"][
                            self::WIKIDATA_PERPETRATOR
                        ][] = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_PERPETRATOR,
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => $perpetratorId,
                                        "numeric-id" => intval(
                                            substr($perpetratorId, 1)
                                        ),
                                    ],
                                    "type" => "wikibase-entityid",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ];
                    }
                } else {
                    // In preview mode, only create placeholder ID
                    Log::info("Preview only: No perpetrator created for", [
                        "name" => $name ?? ($perpetrator["name"] ?? "Unknown"),
                        "temp_id/tempId" =>
                            $perpetrator["temp_id"] ??
                            ($perpetrator["tempId"] ?? "none"),
                    ]);

                    // Add information about temporary persons as free text reference
                    $formattedData["claims"][self::WIKIDATA_AUTHOR_STRING][] = [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_AUTHOR_STRING,
                            "datavalue" => [
                                "value" =>
                                    "Perpetrator: " .
                                    ($name ?: "Unknown") .
                                    " (Will be created upon approval)",
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ];
                }
            }
            // Other entries without ID but with name
            elseif (
                !empty($perpetrator["name"]) ||
                !empty($perpetrator["firstName"]) ||
                !empty($perpetrator["lastName"])
            ) {
                // Add information about persons as free text reference
                $name = !empty($perpetrator["name"])
                    ? $perpetrator["name"]
                    : (!empty($perpetrator["label"])
                        ? $perpetrator["label"]
                        : trim(
                            (isset($perpetrator["firstName"])
                                ? $perpetrator["firstName"]
                                : "") .
                                " " .
                                (isset($perpetrator["lastName"])
                                    ? $perpetrator["lastName"]
                                    : "")
                        ));

                // Convert birthDate/deathDate to date_of_birth/date_of_death
                $dateOfBirth =
                    $perpetrator["date_of_birth"] ??
                    ($perpetrator["birthDate"] ?? null);
                $dateOfDeath =
                    $perpetrator["date_of_death"] ??
                    ($perpetrator["deathDate"] ?? null);

                // Create new person if data is available
                $personData = [
                    "name" => $name,
                    "description" =>
                        $perpetrator["description"] ??
                        "Perpetrator in connection with " .
                            ($formattedData["labels"]["de"]["value"] ??
                                "an event"),
                    "date_of_birth" => $dateOfBirth,
                    "date_of_death" => $dateOfDeath,
                    "occupation" => $perpetrator["occupation"] ?? null,
                    "nationality" => $perpetrator["nationality"] ?? null,
                    "isOrganization" => $this->isOrganizationValue(
                        $perpetrator["isOrganization"] ?? null
                    ),
                    "gender" => $perpetrator["gender"] ?? null,
                    "place_of_birth" => $perpetrator["place_of_birth"] ?? null,
                    "place_of_death" => $perpetrator["place_of_death"] ?? null,
                    "place_of_detention" =>
                        $perpetrator["place_of_detention"] ?? null,
                ];

                // Use source information if available
                $sources = $perpetrator["sources"] ?? [];

                // Create person in Wikidata and get ID - only if $createEntities=true
                if ($createEntities) {
                    $perpetratorId = $this->createNewPersonEntity(
                        $personData,
                        $sources,
                        self::ROLE_PERPETRATOR
                    );

                    if ($perpetratorId !== null) {
                        // Assign new ID to the perpetrator
                        $updatedPerpetrator["id"] = $perpetratorId;
                        $updatedPerpetrator["_wasCreated"] = true; // Flag that this entity was newly created

                        Log::info(
                            "New person created and linked as perpetrator",
                            [
                                "name" => $name,
                                "wikidata_id" => $perpetratorId,
                            ]
                        );

                        // Update the original perpetrator in the array
                        $perpetrators[$index] = $updatedPerpetrator;

                        // If we have a valid Wikidata ID, add the perpetrator
                        $formattedData["claims"][
                            self::WIKIDATA_PERPETRATOR
                        ][] = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_PERPETRATOR,
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => $perpetratorId,
                                        "numeric-id" => intval(
                                            substr($perpetratorId, 1)
                                        ),
                                    ],
                                    "type" => "wikibase-entityid",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ];
                    }
                } else {
                    // In preview mode, only create placeholder ID
                    Log::info("Preview only: No perpetrator created for", [
                        "name" => $name ?? ($perpetrator["name"] ?? "Unknown"),
                    ]);
                }

                // If no entry was created or we are in preview mode, add as free text reference
                if ($perpetratorId === null) {
                    $formattedData["claims"][self::WIKIDATA_AUTHOR_STRING][] = [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_AUTHOR_STRING,
                            "datavalue" => [
                                "value" =>
                                    "Täter/in: " .
                                    ($name ?: "Unbekannt") .
                                    " (Wird bei Genehmigung erstellt)",
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ];
                }
            }
        }

        return $perpetrators;
    }

    /**
     * Adds victims to an entity.
     *
     * @param array &$formattedData Reference to the formatted data structure
     * @param array $victims Array with victim data
     * @param bool $createEntities Flag whether new entities should be created (only in jobs, not in preview)
     * @return array The updated victim data
     */
    protected function addVictims(
        array &$formattedData,
        array $victims,
        bool $createEntities = false
    ): array {
        if (empty($victims)) {
            return [];
        }

        // Make sure that $victims is an array
        if (is_string($victims)) {
            $victims = json_decode($victims, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Error parsing victims as JSON", [
                    "victims" => $victims,
                    "error" => json_last_error_msg(),
                ]);
                return [];
            }
        }

        // Initialize the claims array for victims if it doesn't exist yet
        if (!isset($formattedData["claims"][self::WIKIDATA_VICTIM])) {
            $formattedData["claims"][self::WIKIDATA_VICTIM] = [];
        }

        foreach ($victims as $index => $victim) {
            $victimId = null;
            $updatedVictim = $victim;

            // Ensure we have a name field derived from label if it exists
            if (!isset($updatedVictim["name"]) && isset($victim["label"])) {
                $updatedVictim["name"] = $victim["label"];
            }

            // If the victim already has a Wikidata ID (existing entry)
            if (
                isset($victim["id"]) &&
                is_string($victim["id"]) &&
                substr($victim["id"], 0, 1) === "Q"
            ) {
                $victimId = $victim["id"];

                // If we have a valid Wikidata ID, add the victim
                if ($victimId !== null) {
                    $formattedData["claims"][self::WIKIDATA_VICTIM][] = [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_VICTIM,
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $victimId,
                                    "numeric-id" => intval(
                                        substr($victimId, 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ];

                    // Update the victim in the array
                    $victims[$index] = $updatedVictim;
                    continue; // Skip the rest of the processing for this victim
                }
            }
            // If the victim has a temp_id or tempId (new entry)
            elseif (
                (!empty($victim["temp_id"]) || !empty($victim["tempId"])) &&
                (!empty($victim["name"]) ||
                    !empty($victim["firstName"]) ||
                    !empty($victim["lastName"]))
            ) {
                // Create name from firstName and lastName if available
                $name = !empty($victim["name"])
                    ? $victim["name"]
                    : trim(
                        (isset($victim["firstName"])
                            ? $victim["firstName"]
                            : "") .
                            " " .
                            (isset($victim["lastName"])
                                ? $victim["lastName"]
                                : "")
                    );

                // Convert birthDate/deathDate to date_of_birth/date_of_death
                $dateOfBirth =
                    $victim["date_of_birth"] ?? ($victim["birthDate"] ?? null);
                $dateOfDeath =
                    $victim["date_of_death"] ?? ($victim["deathDate"] ?? null);

                // Create new person if data is available
                $personData = [
                    "name" => $name,
                    "description" =>
                        $victim["description"] ??
                        "Opfer im Zusammenhang mit " .
                            ($formattedData["labels"]["de"]["value"] ??
                                "einem Ereignis"),
                    "date_of_birth" => $dateOfBirth,
                    "date_of_death" => $dateOfDeath,
                    "occupation" => $victim["occupation"] ?? null,
                    "nationality" => $victim["nationality"] ?? null,
                    "isOrganization" => $this->isOrganizationValue(
                        $victim["isOrganization"] ?? null
                    ),
                    "gender" => $victim["gender"] ?? null,
                    "place_of_birth" => $victim["place_of_birth"] ?? null,
                    "place_of_death" => $victim["place_of_death"] ?? null,
                    "place_of_detention" =>
                        $victim["place_of_detention"] ?? null,
                ];

                // Use source information if available
                $sources = $victim["sources"] ?? [];

                // Create person in Wikidata and get ID - only if $createEntities=true
                $victimId = null;
                if ($createEntities) {
                    $victimId = $this->createNewPersonEntity(
                        $personData,
                        $sources,
                        self::ROLE_VICTIM
                    );

                    if ($victimId !== null) {
                        // Replace temp_id/tempId with real Wikidata ID
                        if (isset($updatedVictim["temp_id"])) {
                            unset($updatedVictim["temp_id"]);
                        }
                        if (isset($updatedVictim["tempId"])) {
                            unset($updatedVictim["tempId"]);
                        }
                        $updatedVictim["id"] = $victimId;
                        $updatedVictim["_wasCreated"] = true; // Flag that this entity was newly created

                        Log::info("New person created and linked as victim", [
                            "name" => $name ?? ($victim["name"] ?? "Unbekannt"),
                            "temp_id/tempId" =>
                                $victim["temp_id"] ??
                                ($victim["tempId"] ?? "none"),
                            "wikidata_id" => $victimId,
                        ]);

                        // Update the original victim in the array
                        $victims[$index] = $updatedVictim;
                    }
                } else {
                    // In preview mode, only create placeholder ID
                    Log::info("Preview only: No victim created for", [
                        "name" => $name ?? ($victim["name"] ?? "Unknown"),
                        "temp_id/tempId" =>
                            $victim["temp_id"] ?? ($victim["tempId"] ?? "none"),
                    ]);
                }
            }
            // Fallback for entries without ID but with name
            // Fallback if neither ID nor temp_id/tempId exists, but name or firstName/lastName is available
            elseif (
                !empty($victim["name"]) ||
                !empty($victim["firstName"]) ||
                !empty($victim["lastName"])
            ) {
                // Create name from firstName and lastName if available
                $name = !empty($victim["name"])
                    ? $victim["name"]
                    : trim(
                        (isset($victim["firstName"])
                            ? $victim["firstName"]
                            : "") .
                            " " .
                            (isset($victim["lastName"])
                                ? $victim["lastName"]
                                : "")
                    );

                // Konvertiere birthDate/deathDate zu date_of_birth/date_of_death
                $dateOfBirth =
                    $victim["date_of_birth"] ?? ($victim["birthDate"] ?? null);
                $dateOfDeath =
                    $victim["date_of_death"] ?? ($victim["deathDate"] ?? null);

                // Create new person if data is available
                $personData = [
                    "name" => $name,
                    "description" =>
                        $victim["description"] ??
                        "Opfer im Zusammenhang mit " .
                            ($formattedData["labels"]["de"]["value"] ??
                                "einem Ereignis"),
                    "date_of_birth" => $dateOfBirth,
                    "date_of_death" => $dateOfDeath,
                    "occupation" => $victim["occupation"] ?? null,
                    "nationality" => $victim["nationality"] ?? null,
                    "isOrganization" => $this->isOrganizationValue(
                        $victim["isOrganization"] ?? null
                    ),
                    "gender" => $victim["gender"] ?? null,
                    "place_of_birth" => $victim["place_of_birth"] ?? null,
                    "place_of_death" => $victim["place_of_death"] ?? null,
                    "place_of_detention" =>
                        $victim["place_of_detention"] ?? null,
                ];

                // Use source information from the main event if available
                $sources = $victim["sources"] ?? [];

                // Create person in Wikidata and get ID - only if $createEntities=true
                $victimId = null;
                if ($createEntities) {
                    $victimId = $this->createNewPersonEntity(
                        $personData,
                        $sources,
                        self::ROLE_VICTIM
                    );

                    if ($victimId !== null) {
                        // Assign new ID to the victim
                        $updatedVictim["id"] = $victimId;
                        $updatedVictim["_wasCreated"] = true; // Flag that this entity was newly created

                        Log::info("New person created and linked as victim", [
                            "name" => $name,
                            "wikidata_id" => $victimId,
                        ]);

                        // Update the original victim in the array
                        $victims[$index] = $updatedVictim;
                    }
                } else {
                    Log::info("Preview only: No victim created for", [
                        "name" => $name ?? ($victim["name"] ?? "Unknown"),
                    ]);
                }
            }

            // If we have a valid Wikidata ID, add the victim
            if ($victimId !== null) {
                $formattedData["claims"][self::WIKIDATA_VICTIM][] = [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_VICTIM,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $victimId,
                                "numeric-id" => intval(substr($victimId, 1)),
                            ],
                            "type" => "wikibase-entityid",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ];
            } else {
                // If no ID is available and could not be created, add as free text reference
                $formattedData["claims"][self::WIKIDATA_AUTHOR_STRING][] = [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_AUTHOR_STRING,
                        "datavalue" => [
                            "value" =>
                                "Opfer: " . ($victim["name"] ?? "Unbekannt"),
                            "type" => "string",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ];
            }
        }

        return $victims;
    }

    /**
     * Formats a new Person entity for Wikidata.
     *
     * @param array $personData The person's data
     * @param string|null $role The role (victim/perpetrator) or null if no specific role
     * @return array Formatted data for the Wikidata API
     */
    public function formatNewPersonEntity(
        array $personData,
        ?string $role = null
    ): array {
        // Basic structure according to Wikibase documentation
        $formattedData = [
            "type" => "item",
            "labels" => [
                "de" => [
                    "language" => "de",
                    "value" => $personData["name"] ?? "",
                ],
            ],
            "descriptions" => [
                "de" => [
                    "language" => "de",
                    "value" => $personData["description"] ?? "",
                ],
            ],
            "aliases" => [],
            "claims" => [],
            "sitelinks" => [],
        ];

        // Check if it's an organization
        $isOrganization = $this->isOrganizationValue($personData["isOrganization"] ?? null);

        // Instance of (P31) - either Person (Q5) or Organization (Q43229)
        $entityType = $isOrganization
            ? self::ENTITY_TYPE_ORGANIZATION
            : self::ENTITY_TYPE_PERSON;

        $formattedData["claims"][self::WIKIDATA_INSTANCE_OF] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_INSTANCE_OF,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $entityType,
                            "numeric-id" => intval(substr($entityType, 1)),
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        // For perpetrator organizations, additionally mark as "Places of Gestapo terror"
        if ($isOrganization && $role === self::ROLE_PERPETRATOR) {
            $formattedData["claims"][self::WIKIDATA_INSTANCE_OF][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_INSTANCE_OF,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => self::WIKIDATA_GESTAPO_TERROR_PLACE,
                            "numeric-id" => intval(
                                substr(self::WIKIDATA_GESTAPO_TERROR_PLACE, 1)
                            ),
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ];
        }

        // For persons (not organizations), mark as victim or perpetrator according to role
        //  Subjekt fungiert als (P2868)
        if (!$isOrganization && $role !== null) {
            $roleValue =
                $role === self::ROLE_VICTIM
                    ? self::WIKIDATA_VICTIM_NAZI
                    : self::WIKIDATA_PERPETRATOR_NAZI;

            // Set role property (P2868) to indicate victim or perpetrator status

            $formattedData["claims"][self::WIKIDATA_ROLE_PROPERTY] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_ROLE_PROPERTY,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $roleValue,
                                "numeric-id" => intval(substr($roleValue, 1)),
                            ],
                            "type" => "wikibase-entityid",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }

        // Date of birth (P569) - only for persons, not for organizations
        if (!$isOrganization && !empty($personData["date_of_birth"])) {
            $birthDate = $this->formatDate($personData["date_of_birth"]);
            if ($birthDate !== null) {
                $formattedData["claims"][self::WIKIDATA_DATE_OF_BIRTH] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_DATE_OF_BIRTH,
                            "datavalue" => [
                                "value" => $birthDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Date of death (P570) - only for persons, not for organizations
        if (!$isOrganization && !empty($personData["date_of_death"])) {
            $deathDate = $this->formatDate($personData["date_of_death"]);
            if ($deathDate !== null) {
                $formattedData["claims"][self::WIKIDATA_DATE_OF_DEATH] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_DATE_OF_DEATH,
                            "datavalue" => [
                                "value" => $deathDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Beruf (P106)
        if (
            !empty($personData["occupation"]) &&
            substr($personData["occupation"], 0, 1) === "Q"
        ) {
            $formattedData["claims"][self::WIKIDATA_OCCUPATION] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_OCCUPATION,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $personData["occupation"],
                                "numeric-id" => intval(
                                    substr($personData["occupation"], 1)
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

        // Nationality (P27)
        if (
            !empty($personData["nationality"]) &&
            substr($personData["nationality"], 0, 1) === "Q"
        ) {
            $formattedData["claims"][self::WIKIDATA_NATIONALITY] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_NATIONALITY,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $personData["nationality"],
                                "numeric-id" => intval(
                                    substr($personData["nationality"], 1)
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

        // Geschlecht (P21)
        if (
            !empty($personData["gender"]) &&
            substr($personData["gender"], 0, 1) === "Q"
        ) {
            $formattedData["claims"][self::WIKIDATA_GENDER] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_GENDER,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $personData["gender"],
                                "numeric-id" => intval(
                                    substr($personData["gender"], 1)
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

        // Haftort (P2632)
        if (
            !empty($personData["place_of_detention"]) &&
            substr($personData["place_of_detention"], 0, 1) === "Q"
        ) {
            $formattedData["claims"][self::WIKIDATA_PLACE_OF_DETENTION] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_PLACE_OF_DETENTION,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $personData["place_of_detention"],
                                "numeric-id" => intval(
                                    substr($personData["place_of_detention"], 1)
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

        // Geburtsort (P19)
        if (
            !empty($personData["place_of_birth"]) &&
            substr($personData["place_of_birth"], 0, 1) === "Q"
        ) {
            $formattedData["claims"][self::WIKIDATA_PLACE_OF_BIRTH] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_PLACE_OF_BIRTH,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $personData["place_of_birth"],
                                "numeric-id" => intval(
                                    substr($personData["place_of_birth"], 1)
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

        // Place of death (P20)
        if (
            !empty($personData["place_of_death"]) &&
            substr($personData["place_of_death"], 0, 1) === "Q"
        ) {
            $formattedData["claims"][self::WIKIDATA_PLACE_OF_DEATH] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_PLACE_OF_DEATH,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $personData["place_of_death"],
                                "numeric-id" => intval(
                                    substr($personData["place_of_death"], 1)
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

        return $formattedData;
    }

    /**
     * Creates a new Person entity in Wikidata and returns the Wikidata ID.
     *
     * @param array $personData The person's data
     * @param array $sources Optional: Source information for the person
     * @param string|null $role Optional: The role (victim/perpetrator) or null if no specific role
     * @return string|null The Wikidata ID of the created person or null in case of error
     */
    public function createNewPersonEntity(
        array $personData,
        array $sources = [],
        ?string $role = null
    ): ?string {
        try {
            $formattedData = $this->formatNewPersonEntity($personData, $role);

            if (!empty($sources)) {
                $this->addSourcesAsClaims($formattedData, $sources);
            }

            $wikidataService = app(
                \App\Services\Wikidata\WikidataService::class
            );

            $entityType =
                $this->isOrganizationValue($personData["isOrganization"])
                    ? self::ENTITY_TYPE_ORGANIZATION
                    : self::ENTITY_TYPE_PERSON;

            $entityData = [
                "title" => $personData["name"] ?? "Unnamed Person",
                "description" =>
                    $personData["description"] ??
                    "Person related to a historical event",
                "type" => $entityType,
                "claims" => $formattedData["claims"] ?? [],
                "labels" => $formattedData["labels"] ?? [],
                "descriptions" => $formattedData["descriptions"] ?? [],
                "aliases" => $formattedData["aliases"] ?? [],
                "sitelinks" => $formattedData["sitelinks"] ?? [],
            ];

            $response = $wikidataService->createEntity(
                $entityData,
                $entityType
            );

            if ($response === null) {
                Log::error("Error creating a new person in Wikidata", [
                    "person" => $personData["name"] ?? "Unknown",
                ]);
                return null;
            }

            // Wikidata-ID aus der Antwort extrahieren
            $wikidataId = $response["entity"]["id"] ?? null;

            Log::info("New person created in Wikidata", [
                "person" => $personData["name"] ?? "Unknown",
                "wikidata_id" => $wikidataId,
            ]);

            return $wikidataId;
        } catch (\Exception $e) {
            Log::error("Error creating a new person in Wikidata", [
                "person" => $personData["name"] ?? "Unknown",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Adds sources to an entity.
     *
     * @param array &$formattedData Reference to the formatted data structure
     * @param array $sources The source information
     */
    protected function addSourcesToEntity(
        array &$formattedData,
        array $sources
    ): void {
        if (empty($sources)) {
            return;
        }

        // Create references for all relevant claims
        foreach ($formattedData["claims"] as $propertyId => &$statements) {
            // Ignore certain properties that don't need sources
            // e.g., P31 (Instance of) or other basic properties
            if (in_array($propertyId, ["P31"])) {
                continue;
            }

            foreach ($statements as &$statement) {
                $statement["references"] = [];

                // Add each source as a reference
                foreach ($sources as $source) {
                    // Create reference based on the source type
                    $reference = $this->createReferenceFromSource($source);
                    if (!empty($reference)) {
                        $statement["references"][] = $reference;
                    }
                }
            }
        }
    }

    /**
     * Formats a new Book entity for Wikidata.
     *
     * @param array $bookData The book's data
     * @return array Formatted data for the Wikidata API
     */
    public function formatNewBookEntity(array $bookData): array
    {
        // Basic structure according to Wikibase documentation
        $formattedData = [
            "type" => "item",
            "labels" => [
                "de" => [
                    "language" => "de",
                    "value" => $bookData["title"] ?? "",
                ],
            ],
            "descriptions" => [
                "de" => [
                    "language" => "de",
                    "value" => $bookData["description"] ?? "",
                ],
            ],
            "aliases" => [],
            "claims" => [],
            "sitelinks" => [],
        ];

        // Instance of (P31) - Literary work (Q7725634)
        $formattedData["claims"][self::WIKIDATA_INSTANCE_OF] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_INSTANCE_OF,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => self::ENTITY_TYPE_BOOK,
                            "numeric-id" => intval(substr(self::ENTITY_TYPE_BOOK, 1)),
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        // Title (P1476)
        if (!empty($bookData["title"])) {
            $formattedData["claims"][self::WIKIDATA_TITLE] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_TITLE,
                        "datavalue" => [
                            "value" => [
                                "text" => $bookData["title"],
                                "language" => "de",
                            ],
                            "type" => "monolingualtext",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }

        // Author (P50) - requires a Wikidata entity
        if (!empty($bookData["author"]) && isset($bookData["author"]["id"])) {
            $authorId = $bookData["author"]["id"];

            // Only add if it's a valid Wikidata ID
            if (substr($authorId, 0, 1) === "Q") {
                $formattedData["claims"][self::WIKIDATA_AUTHOR] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_AUTHOR,
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $authorId,
                                    "numeric-id" => intval(substr($authorId, 1)),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
            // If author doesn't have a Wikidata ID but has a name, use author string property
            else if (isset($bookData["author"]["label"]) || 
                     (isset($bookData["author"]["firstName"]) || isset($bookData["author"]["lastName"]))) {
                $authorName = $bookData["author"]["label"] ?? 
                    trim((isset($bookData["author"]["firstName"]) ? $bookData["author"]["firstName"] : "") . " " .
                         (isset($bookData["author"]["lastName"]) ? $bookData["author"]["lastName"] : ""));
                
                $formattedData["claims"][self::WIKIDATA_AUTHOR_STRING] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_AUTHOR_STRING,
                            "datavalue" => [
                                "value" => $authorName,
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Publication date (P577) if available
        if (!empty($bookData["publishingDate"])) {
            $publishingDate = $this->formatDate($bookData["publishingDate"]);
            if ($publishingDate !== null) {
                $formattedData["claims"][self::WIKIDATA_PUBLISHING_DATE] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_PUBLISHING_DATE,
                            "datavalue" => [
                                "value" => $publishingDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        } else if (!empty($bookData["year"])) {
            // If we only have the year, format it as a date
            $year = $bookData["year"];
            $publishingDate = $this->formatDate("{$year}-01-01");
            if ($publishingDate !== null) {
                // Adjust the precision to year
                $publishingDate["precision"] = 9; // Year precision
                
                $formattedData["claims"][self::WIKIDATA_PUBLISHING_DATE] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_PUBLISHING_DATE,
                            "datavalue" => [
                                "value" => $publishingDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        return $formattedData;
    }

    /**
     * Creates a new Book entity in Wikidata and returns the Wikidata ID.
     * 
     * @param array $bookData The book's data
     * @param array $sources Optional: Source information for the book
     * @return string|null The Wikidata ID of the created book or null in case of error
     */
    public function createNewBookEntity(array $bookData, array $sources = []): ?string
    {
        try {
            $formattedData = $this->formatNewBookEntity($bookData);

            if (!empty($sources)) {
                $this->addSourcesAsClaims($formattedData, $sources);
            }

            $wikidataService = app(
                \App\Services\Wikidata\WikidataService::class
            );

            $entityData = [
                "title" => $bookData["title"] ?? "Unnamed Book",
                "description" => $bookData["description"] ?? "Literary work",
                "type" => self::ENTITY_TYPE_BOOK,
                "claims" => $formattedData["claims"] ?? [],
                "labels" => $formattedData["labels"] ?? [],
                "descriptions" => $formattedData["descriptions"] ?? [],
                "aliases" => $formattedData["aliases"] ?? [],
                "sitelinks" => $formattedData["sitelinks"] ?? [],
            ];

            $response = $wikidataService->createEntity(
                $entityData,
                self::ENTITY_TYPE_BOOK
            );

            if ($response === null) {
                Log::error("Error creating a new book in Wikidata", [
                    "book" => $bookData["title"] ?? "Unknown",
                ]);
                return null;
            }

            // Extract Wikidata ID from the response
            $wikidataId = $response["entity"]["id"] ?? null;

            Log::info("New book created in Wikidata", [
                "book" => $bookData["title"] ?? "Unknown",
                "wikidata_id" => $wikidataId,
            ]);

            // If the author has a temp_id, create the author entity and link it to the book
            if (!empty($bookData["author"]) && isset($bookData["author"]["temp_id"])) {
                $this->createAndLinkAuthor($bookData["author"], $wikidataId, $sources);
            }

            return $wikidataId;
        } catch (\Exception $e) {
            Log::error("Error creating a new book in Wikidata", [
                "book" => $bookData["title"] ?? "Unknown",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Creates a new author entity and links it to a book.
     * 
     * @param array $authorData The author's data
     * @param string $bookId The Wikidata ID of the book to link the author to
     * @param array $sources Optional: Source information 
     * @return string|null The Wikidata ID of the created author or null in case of error
     */
    protected function createAndLinkAuthor(array $authorData, string $bookId, array $sources = []): ?string
    {
        // We don't set any role for the author, just create the person entity
        $authorId = $this->createNewPersonEntity($authorData, $sources);
        
        if ($authorId !== null && $bookId !== null) {
            try {
                // Create a claim to link the author to the book
                $wikidataService = app(\App\Services\Wikidata\WikidataService::class);
                
                // Create a claim to add the author to the book
                $claim = [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_AUTHOR,
                        "datavalue" => [
                            "value" => [
                                "entity-type" => "item",
                                "id" => $authorId,
                                "numeric-id" => intval(substr($authorId, 1)),
                            ],
                            "type" => "wikibase-entityid",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ];
                
                $result = $wikidataService->addClaim($bookId, self::WIKIDATA_AUTHOR, $claim);
                
                if ($result) {
                    Log::info("Successfully linked author to book", [
                        "author_id" => $authorId,
                        "book_id" => $bookId,
                    ]);
                } else {
                    Log::error("Failed to link author to book", [
                        "author_id" => $authorId,
                        "book_id" => $bookId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Error linking author to book", [
                    "author_id" => $authorId,
                    "book_id" => $bookId,
                    "error" => $e->getMessage(),
                ]);
            }
        }
        
        return $authorId;
    }
    
    /**
     * Adds a qualifier to a statement.
     *
     * @param array &$statement Reference to the statement array
     * @param string $property The qualifier property ID
     * @param string $value The qualifier value (entity ID)
     * @return void
     */
    protected function addQualifierToStatement(
        array &$statement,
        string $property,
        string $value
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

        // Add the qualifier
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
    }

    /**
     * Helper method to determine if a value indicates an organization
     * 
     * @param mixed $value The value to check
     * @return bool True if the value indicates an organization
     */
    protected function isOrganizationValue($value): bool
    {
        if (!isset($value)) {
            return false;
        }
        
        // Boolean true
        if ($value === true) {
            return true;
        }
        
        // String values like "true", "1", "yes"
        if (is_string($value)) {
            $lowerValue = strtolower(trim($value));
            return $lowerValue === "true" || $lowerValue === "1" || $lowerValue === "yes";
        }
        
        // Numeric value 1
        if (is_numeric($value) && (int)$value === 1) {
            return true;
        }
        
        return false;
    }

    /**
     * Adds source information as independent claims.
     *
     * @param array &$formattedData Reference to the formatted data structure
     * @param array $sources The source information
     */
    protected function addSourcesAsClaims(
        array &$formattedData,
        array $sources
    ): void {
        if (empty($sources)) {
            return;
        }

        // Ensure that $sources is an array
        if (is_string($sources)) {
            $sources = json_decode($sources, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Error parsing source information as JSON", [
                    "sources" => $sources,
                    "error" => json_last_error_msg(),
                ]);
                return;
            }
        }

        foreach ($sources as $source) {
            if (empty($source) || !isset($source["type"])) {
                continue;
            }

            switch ($source["type"]) {
                case "url":
                    // Add URL to property P973 (URL)
                    if (!empty($source["url"])) {
                        if (
                            !isset(
                                $formattedData["claims"][
                                    self::WIKIDATA_STATED_IN_URL
                                ]
                            )
                        ) {
                            $formattedData["claims"][
                                self::WIKIDATA_STATED_IN_URL
                            ] = [];
                        }

                        $formattedData["claims"][
                            self::WIKIDATA_STATED_IN_URL
                        ][] = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_STATED_IN_URL,
                                "datavalue" => [
                                    "value" => $this->ensureUrlHasScheme(
                                        $source["url"]
                                    ),
                                    "type" => "string",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ];
                    }
                    break;

                case "book":
                case "archive":
                    // For manually created books with no Wikidata ID
                    if ($source["type"] === "book" && !empty($source["isManual"]) && $source["id"] === "manual") {
                        // Create a new book entity if it doesn't exist yet
                        $bookData = [
                            "title" => $source["title"] ?? "Unknown Book",
                            "description" => $source["description"] ?? "Literary work",
                            "author" => $source["author"] ?? null,
                            "year" => $source["year"] ?? null,
                            "publishingDate" => $source["publishingDate"] ?? null,
                        ];
                        
                        $newBookId = $this->createNewBookEntity($bookData);
                        if ($newBookId !== null) {
                            // Update the source with the new ID
                            $source["id"] = $newBookId;
                            Log::info("Created new book for source", [
                                "title" => $source["title"],
                                "id" => $newBookId,
                            ]);
                        }
                    }
                    
                    // Add book/archive to property P1343 (described by source)
                    if (
                        !empty($source["id"]) &&
                        substr($source["id"], 0, 1) === "Q"
                    ) {
                        if (
                            !isset(
                                $formattedData["claims"][
                                    self::WIKIDATA_STATED_IN
                                ]
                            )
                        ) {
                            $formattedData["claims"][
                                self::WIKIDATA_STATED_IN
                            ] = [];
                        }

                        $formattedData["claims"][self::WIKIDATA_STATED_IN][] = [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_STATED_IN,
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => $source["id"],
                                        "numeric-id" => intval(
                                            substr($source["id"], 1)
                                        ),
                                    ],
                                    "type" => "wikibase-entityid",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                            // If page information is available, add as qualifier
                            "qualifiers" => $this->createSourceQualifiers(
                                $source
                            ),
                        ];
                    }
                    break;
            }
        }
    }

    /**
     * Creates qualifiers for source information like page numbers
     *
     * @param array $source The source information
     * @return array The qualifiers for the source
     */
    protected function createSourceQualifiers(array $source): array
    {
        $qualifiers = [];

        // Page information
        if (!empty($source["pages"])) {
            $qualifiers[self::WIKIDATA_PAGE] = [
                [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_PAGE,
                    "datavalue" => [
                        "value" => $source["pages"],
                        "type" => "string",
                    ],
                ],
            ];
        }

        if (!empty($source["inventoryNumber"])) {
            $qualifiers[self::WIKIDATA_INVENTORY_NUMBER] = [
                [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_INVENTORY_NUMBER,
                    "datavalue" => [
                        "value" => $source["inventoryNumber"],
                        "type" => "string",
                    ],
                ],
            ];
        }

        // Additional qualifiers could be added here

        return $qualifiers;
    }

    /**
     * Ensures that a URL has a valid scheme (http:// or https://).
     *
     * @param string $url The URL to check
     * @return string The URL with scheme
     */
    protected function ensureUrlHasScheme(string $url): string
    {
        // Trim URL
        $url = trim($url);

        // Check if URL already has a scheme
        if (preg_match("#^https?://#i", $url)) {
            return $url;
        }

        // If no scheme is present, add https://
        return "https://" . $url;
    }

    /**
     * Creates a reference based on the source type.
     *
     * @param array $source The source information
     * @return array The formatted reference or empty array in case of error
     */
    protected function createReferenceFromSource(array $source): array
    {
        if (
            empty($source) ||
            !isset($source["type"]) ||
            !isset($source["title"])
        ) {
            return [];
        }

        $reference = [
            "hash" => "reference-" . md5(json_encode($source)),
            "snaks" => [],
        ];

        // Basic data for each reference: Titel (P1476)
        $reference["snaks"]["P1476"] = [
            [
                "snaktype" => "value",
                "property" => "P1476",
                "datavalue" => [
                    "value" => [
                        "text" => $source["title"],
                        "language" => "de",
                    ],
                    "type" => "monolingualtext",
                ],
            ],
        ];

        // Add specific data depending on source type
        switch ($source["type"]) {
            case "book":
                // for book sources
                if (isset($source["id"]) && $source["id"] !== "manual") {
                    // Wikidata ID reference (P248)
                    $reference["snaks"]["P248"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P248",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $source["id"],
                                    "numeric-id" => intval(
                                        substr($source["id"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                    ];
                }

                // Page reference (P304)
                if (isset($source["pages"])) {
                    $reference["snaks"]["P304"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P304",
                            "datavalue" => [
                                "value" => $source["pages"],
                                "type" => "string",
                            ],
                        ],
                    ];
                }
                break;

            case "url":
                // for URL sources
                if (isset($source["url"])) {
                    // URL of the reference (P854)
                    $reference["snaks"]["P854"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P854",
                            "datavalue" => [
                                "value" => $this->ensureUrlHasScheme(
                                    $source["url"]
                                ),
                                "type" => "string",
                            ],
                        ],
                    ];
                }

                // Access date (P813)
                if (isset($source["accessDate"])) {
                    $accessDate = $this->formatDate($source["accessDate"]);
                    if ($accessDate !== null) {
                        $reference["snaks"]["P813"] = [
                            [
                                "snaktype" => "value",
                                "property" => "P813",
                                "datavalue" => [
                                    "value" => $accessDate,
                                    "type" => "time",
                                ],
                            ],
                        ];
                    }
                }
                break;

            case "archive":
                // for archive sources
                if (isset($source["id"]) && $source["id"] !== "manual") {
                    // Archive reference (P485)
                    $reference["snaks"]["P485"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P485",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $source["id"],
                                    "numeric-id" => intval(
                                        substr($source["id"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                    ];
                }

                // Inventory number (P217)
                if (isset($source["inventoryNumber"])) {
                    $reference["snaks"]["P217"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P217",
                            "datavalue" => [
                                "value" => $source["inventoryNumber"],
                                "type" => "string",
                            ],
                        ],
                    ];
                }
                break;
        }

        // Add description as a note (P2093)
        if (isset($source["description"])) {
            $reference["snaks"]["P2093"] = [
                [
                    "snaktype" => "value",
                    "property" => "P2093",
                    "datavalue" => [
                        "value" => $source["description"],
                        "type" => "string",
                    ],
                ],
            ];
        }

        return $reference;
    }
}

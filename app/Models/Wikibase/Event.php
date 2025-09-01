<?php

namespace App\Models\Wikibase;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;

class Event extends BaseEntity
{
    // Additional event properties
    protected $startTime;
    protected $endTime;
    protected $pointInTime;
    protected $location;
    protected $victimCount;
    protected $survivorCount;
    protected $deathCount;
    protected $arrestCount;

    /**
     * Constructor
     *
     * @param string $title The event title
     * @param string $description The event description
     * @param EntityMappingProviderInterface|null $mappingProvider Optional mapping provider
     */
    public function __construct(
        string $title,
        string $description = "",
        ?EntityMappingProviderInterface $mappingProvider = null,
    ) {
        parent::__construct($title, $description, $mappingProvider);
    }

    /**
     * Create an Event model from array data
     */
    public function fromArray(array $data): self
    {
        $event = new self();
        $event->id = $data["id"] ?? null;

        if (!empty($data["time_point"])) {
            $event->setPointInTime(
                $data["time_point"],
                $data["time_point_qualifier"] ?? null,
            );
        } else {
            // Time Range
            if (!empty($data["time_period_start"])) {
                $event->setStartTime(
                    $data["time_period_start"],
                    $data["time_period_start_qualifier"] ?? null,
                );
            }

            if (!empty($data["time_period_end"])) {
                $event->setEndTime(
                    $data["time_period_end"],
                    $data["time_period_end_qualifier"] ?? null,
                );
            }
        }

        // Set location
        if (!empty($data["location"]) && !empty($data["location"]["id"])) {
            $event->setLocation($data["location"]["id"]);
        }

        // Set cause
        if (!empty($data["cause"]) && substr($data["cause"], 0, 1) === "Q") {
            $event->setCause($data["cause"]);
        } elseif (!empty($data["cause"])) {
            $event->setCauseAsText($data["cause"]);
        }

        // Set counts
        if (!empty($data["victim_count"])) {
            $event->setVictimCount(
                $data["victim_count"],
                $data["victim_count_qualifier"] ?? null,
            );
        }

        if (!empty($data["survivor_count"])) {
            $event->setSurvivorCount(
                $data["survivor_count"],
                $data["survivor_count_qualifier"] ?? null,
            );
        }

        if (!empty($data["death_count"])) {
            $event->setDeathCount(
                $data["death_count"],
                $data["death_count_qualifier"] ?? null,
            );
        }

        if (!empty($data["arrest_count"])) {
            $event->setArrestCount(
                $data["arrest_count"],
                $data["arrest_count_qualifier"] ?? null,
            );
        }

        // Set coordinates
        if (!empty($data["coordinates"])) {
            // Get the coordinates qualifier if it exists
            $coordsQualifier = $data["coordinates_qualifier"] ?? null;

            // Log the qualifier for debugging
            Log::info("Processing coordinates with qualifier", [
                "coordinates" => is_string($data["coordinates"])
                    ? $data["coordinates"]
                    : "complex-value",
                "has_qualifier" => !empty($coordsQualifier),
                "qualifier" => $coordsQualifier,
            ]);

            // Handle different coordinate formats
            if (is_string($data["coordinates"])) {
                // Try to parse coordinate string in format "lat, lng"
                if (
                    preg_match(
                        '/^\s*([0-9.\-]+)\s*,\s*([0-9.\-]+)\s*$/',
                        $data["coordinates"],
                        $matches,
                    )
                ) {
                    $event->addCoordinates(
                        [
                            "lat" => (float) $matches[1],
                            "lng" => (float) $matches[2],
                        ],
                        $coordsQualifier,
                    );
                } else {
                    // Try to decode JSON string
                    $decoded = json_decode($data["coordinates"], true);
                    if ($decoded) {
                        $event->addCoordinates($decoded, $coordsQualifier);
                    }
                }
            } else {
                // Already an array
                $event->addCoordinates($data["coordinates"], $coordsQualifier);
            }
        }

        // Set image
        if (!empty($data["image"])) {
            $event->addImage($data["image"]);
        }

        // Set causes (array)
        if (!empty($data["selected_causes"])) {
            // Ensure causes is passed as an array
            $causes = $data["selected_causes"];
            if (is_string($causes)) {
                $causes = json_decode($causes, true) ?: [];
            }
            Log::debug("Processing selected causes", [
                "count" => count($causes),
                "first_cause" => $causes[0] ?? null,
            ]);
            $event->addCauses($causes);
        }

        // Also try with selected_causes key if available
        if (empty($data["selected_causes"]) && !empty($data["cause"])) {
            if (substr($data["cause"], 0, 1) === "Q") {
                // If it's a Q-ID, add it directly
                $event->setCause($data["cause"]);
            }
        }

        // Add victims directly to claims if available
        if (!empty($data["victims"])) {
            $victims = $data["victims"];
            if (is_string($victims)) {
                $victims = json_decode($victims, true) ?: [];
            }
            foreach ($victims as $victim) {
                if (!empty($victim["id"])) {
                    // If it's an existing Wikidata ID (starts with Q), add it directly
                    if (substr($victim["id"], 0, 1) === "Q") {
                        $victimProperty = $event->mappingProvider->getPropertyId(
                            "VICTIM",
                        );
                        $event->addRelation($victimProperty, $victim["id"]);

                        Log::info("Added existing victim to event", [
                            "victim_id" => $victim["id"],
                            "victim_name" => $victim["name"] ?? "Unknown",
                        ]);
                    }
                    // For new victims (with temp_id), we'll log that they'll be added by CreateWikidataEntity
                    elseif (
                        substr($victim["id"], 0, 4) === "new_" ||
                        !empty($victim["temp_id"])
                    ) {
                        $tempId = $victim["id"] ?? $victim["temp_id"];
                        $victimName =
                            $victim["name"] ??
                            (!empty($victim["firstName"]) &&
                            !empty($victim["lastName"])
                                ? $victim["firstName"] .
                                    " " .
                                    $victim["lastName"]
                                : "Unknown");

                        Log::info(
                            "New victim will be created and linked through job chain",
                            [
                                "temp_id" => $tempId,
                                "victim_name" => $victimName,
                                "has_firstName" => !empty($victim["firstName"]),
                                "has_lastName" => !empty($victim["lastName"]),
                            ],
                        );
                    }
                }
            }
        }

        // Add perpetrators directly to claims if available
        if (!empty($data["perpetrators"])) {
            $perpetrators = $data["perpetrators"];
            if (is_string($perpetrators)) {
                $perpetrators = json_decode($perpetrators, true) ?: [];
            }
            foreach ($perpetrators as $perpetrator) {
                if (!empty($perpetrator["id"])) {
                    // If it's an existing Wikidata ID (starts with Q), add it directly
                    if (substr($perpetrator["id"], 0, 1) === "Q") {
                        $perpetratorProperty = $event->mappingProvider->getPropertyId(
                            "PERPETRATOR",
                        );

                        // Check if this perpetrator already exists in the claims to avoid duplicates
                        $isDuplicate = false;
                        if (
                            isset($event->data["claims"][$perpetratorProperty])
                        ) {
                            foreach (
                                $event->data["claims"][$perpetratorProperty]
                                as $existingStatement
                            ) {
                                if (
                                    isset(
                                        $existingStatement["mainsnak"][
                                            "datavalue"
                                        ]["value"]["id"],
                                    ) &&
                                    $existingStatement["mainsnak"]["datavalue"][
                                        "value"
                                    ]["id"] === $perpetrator["id"]
                                ) {
                                    $isDuplicate = true;
                                    Log::info(
                                        "Skipping duplicate perpetrator",
                                        [
                                            "perpetrator_id" =>
                                                $perpetrator["id"],
                                            "perpetrator_name" =>
                                                $perpetrator["name"] ??
                                                "Unknown",
                                        ],
                                    );
                                    break;
                                }
                            }
                        }

                        // Only add if not a duplicate
                        if (!$isDuplicate) {
                            $event->addRelation(
                                $perpetratorProperty,
                                $perpetrator["id"],
                            );

                            Log::info("Added existing perpetrator to event", [
                                "perpetrator_id" => $perpetrator["id"],
                                "perpetrator_name" =>
                                    $perpetrator["name"] ?? "Unknown",
                            ]);
                        }
                    }
                    // For new perpetrators (with temp_id), we'll log that they'll be added by CreateWikidataEntity
                    elseif (
                        substr($perpetrator["id"], 0, 4) === "new_" ||
                        !empty($perpetrator["temp_id"])
                    ) {
                        $tempId = $perpetrator["id"] ?? $perpetrator["temp_id"];
                        $perpetratorName =
                            $perpetrator["name"] ??
                            (!empty($perpetrator["firstName"]) &&
                            !empty($perpetrator["lastName"])
                                ? $perpetrator["firstName"] .
                                    " " .
                                    $perpetrator["lastName"]
                                : "Unknown");

                        Log::info(
                            "New perpetrator will be created and linked through job chain",
                            [
                                "temp_id" => $tempId,
                                "perpetrator_name" => $perpetratorName,
                                "has_firstName" => !empty(
                                    $perpetrator["firstName"]
                                ),
                                "has_lastName" => !empty(
                                    $perpetrator["lastName"]
                                ),
                            ],
                        );
                    }
                }
            }
        }

        return $event;
    }

    /**
     * Set the point in time
     */
    public function setPointInTime(
        string $date,
        ?string $qualifier = null,
    ): void {
        $this->pointInTime = $date;
        $formattedDate = $this->formatDate($date);

        if ($formattedDate) {
            $pointInTimeProperty = $this->mappingProvider->getPropertyId(
                "POINT_IN_TIME",
            );
            $qualifierProperty = $this->mappingProvider->getPropertyId(
                "SOURCING_CIRCUMSTANCES",
            );

            $statement = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $pointInTimeProperty, // Point in time
                    "datavalue" => [
                        "value" => $formattedDate,
                        "type" => "time",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ];

            // Add qualifier if provided
            if (!empty($qualifier)) {
                $this->addQualifierToStatement(
                    $statement,
                    $qualifierProperty,
                    $qualifier,
                );
            }

            $this->data["claims"][$pointInTimeProperty] = [$statement];
        }
    }

    /**
     * Set the start time
     */
    public function setStartTime(string $date, ?string $qualifier = null): void
    {
        $this->startTime = $date;
        $formattedDate = $this->formatDate($date);

        if ($formattedDate) {
            $startTimeProperty = $this->mappingProvider->getPropertyId(
                "START_TIME",
            );
            $qualifierProperty = $this->mappingProvider->getPropertyId(
                "SOURCING_CIRCUMSTANCES",
            );

            $statement = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $startTimeProperty, // Start time
                    "datavalue" => [
                        "value" => $formattedDate,
                        "type" => "time",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ];

            // Add qualifier if provided
            if (!empty($qualifier)) {
                $this->addQualifierToStatement(
                    $statement,
                    $qualifierProperty,
                    $qualifier,
                );
            }

            $this->data["claims"][$startTimeProperty] = [$statement];
        }
    }

    /**
     * Set the end time
     */
    public function setEndTime(string $date, ?string $qualifier = null): void
    {
        $this->endTime = $date;
        $formattedDate = $this->formatDate($date);

        if ($formattedDate) {
            $endTimeProperty = $this->mappingProvider->getPropertyId(
                "END_TIME",
            );
            $qualifierProperty = $this->mappingProvider->getPropertyId(
                "SOURCING_CIRCUMSTANCES",
            );

            $statement = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $endTimeProperty, // End time
                    "datavalue" => [
                        "value" => $formattedDate,
                        "type" => "time",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ];

            // Add qualifier if provided
            if (!empty($qualifier)) {
                $this->addQualifierToStatement(
                    $statement,
                    $qualifierProperty,
                    $qualifier,
                );
            }

            $this->data["claims"][$endTimeProperty] = [$statement];
        }
    }

    /**
     * Set the location
     */
    public function setLocation(string $locationId): void
    {
        $this->location = $locationId;

        if (empty($locationId) || substr($locationId, 0, 1) !== "Q") {
            return;
        }

        $locationProperty = $this->mappingProvider->getPropertyId("LOCATION");

        $this->data["claims"][$locationProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $locationProperty, // Location
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $locationId,
                            "numeric-id" => intval(substr($locationId, 1)),
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
     * Add coordinates to the event
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
                $lat = (float) trim($parts[0]);
                $lng = (float) trim($parts[1]);
            }
        } elseif (is_array($coordinates)) {
            if (isset($coordinates[0]) && isset($coordinates[1])) {
                // Format: [lat, lng]
                $lat = (float) $coordinates[0];
                $lng = (float) $coordinates[1];
            } elseif (
                isset($coordinates["lat"]) &&
                isset($coordinates["lng"])
            ) {
                // Format: {lat: x, lng: y}
                $lat = (float) $coordinates["lat"];
                $lng = (float) $coordinates["lng"];
            }
        }

        if ($lat === null || $lng === null) {
            Log::warning("Invalid coordinates format", [
                "coordinates" => $coordinates,
            ]);
            return;
        }

        $coordinatesProperty = $this->mappingProvider->getPropertyId(
            "COORDINATES",
        );
        if (empty($coordinatesProperty)) {
            Log::warning(
                "Coordinates property ID is empty, using P625 as fallback",
            );
            $coordinatesProperty = "P625"; // Fallback to standard Wikidata coordinates property
        }

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

        // Add qualifier if provided
        if (!empty($qualifier)) {
            $qualifierProperty = $this->mappingProvider->getPropertyId(
                "SOURCING_CIRCUMSTANCES",
            );
            $this->addQualifierToStatement(
                $statement,
                $qualifierProperty,
                $qualifier,
            );
        }

        $this->data["claims"][$coordinatesProperty] = [$statement];
    }

    /**
     * Set the cause
     */
    public function setCause(string $causeId): void
    {
        if (empty($causeId) || substr($causeId, 0, 1) !== "Q") {
            return;
        }

        $causeProperty = $this->mappingProvider->getPropertyId("HAS_CAUSE");

        $this->data["claims"][$causeProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $causeProperty, // Cause
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $causeId,
                            "numeric-id" => intval(substr($causeId, 1)),
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
     * Set cause as text
     */
    public function setCauseAsText(string $causeText): void
    {
        if (empty($causeText)) {
            return;
        }

        $authorStringProperty = $this->mappingProvider->getPropertyId(
            "AUTHOR_STRING",
        );

        $this->data["claims"][$authorStringProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $authorStringProperty, // Author string
                    "datavalue" => [
                        "value" => "Ursache: " . $causeText,
                        "type" => "string",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];
    }

    /**
     * Add multiple causes to the event
     *
     * @param array $causes Array of cause entities (with at least 'id' field)
     */
    public function addCauses(array $causes): void
    {
        if (empty($causes)) {
            return;
        }

        $causeProperty = $this->mappingProvider->getPropertyId("HAS_CAUSE");

        // Initialize claims array for this property if it doesn't exist
        if (!isset($this->data["claims"][$causeProperty])) {
            $this->data["claims"][$causeProperty] = [];
        }

        foreach ($causes as $cause) {
            if (!empty($cause["id"]) && substr($cause["id"], 0, 1) === "Q") {
                $this->data["claims"][$causeProperty][] = [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => $causeProperty,
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

                Log::debug("Added cause to event: {$cause["id"]}");
            }
        }
    }

    /**
     * Set the victim count
     */
    public function setVictimCount(int $count, ?string $qualifier = null): void
    {
        $this->victimCount = $count;

        $victimCountProperty = $this->mappingProvider->getPropertyId(
            "VICTIM_COUNT",
        );
        $qualifierProperty = $this->mappingProvider->getPropertyId(
            "SOURCING_CIRCUMSTANCES",
        );

        $statement = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $victimCountProperty, // Number of victims
                "datavalue" => [
                    "value" => [
                        "amount" => "+" . $count,
                        "unit" => "1",
                    ],
                    "type" => "quantity",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Add qualifier if provided
        if (!empty($qualifier)) {
            $this->addQualifierToStatement(
                $statement,
                $qualifierProperty,
                $qualifier,
            );
        }

        $this->data["claims"][$victimCountProperty] = [$statement];
    }

    /**
     * Set the survivor count
     */
    public function setSurvivorCount(
        int $count,
        ?string $qualifier = null,
    ): void {
        $this->survivorCount = $count;

        $survivorCountProperty = $this->mappingProvider->getPropertyId(
            "SURVIVOR_COUNT",
        );
        $qualifierProperty = $this->mappingProvider->getPropertyId(
            "SOURCING_CIRCUMSTANCES",
        );

        $statement = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $survivorCountProperty, // Number of survivors
                "datavalue" => [
                    "value" => [
                        "amount" => "+" . $count,
                        "unit" => "1",
                    ],
                    "type" => "quantity",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Add qualifier if provided
        if (!empty($qualifier)) {
            $this->addQualifierToStatement(
                $statement,
                $qualifierProperty,
                $qualifier,
            );
        }

        $this->data["claims"][$survivorCountProperty] = [$statement];
    }

    /**
     * Set the death count
     */
    public function setDeathCount(int $count, ?string $qualifier = null): void
    {
        $this->deathCount = $count;

        $deathCountProperty = $this->mappingProvider->getPropertyId(
            "DEATH_COUNT",
        );
        $qualifierProperty = $this->mappingProvider->getPropertyId(
            "SOURCING_CIRCUMSTANCES",
        );

        $statement = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $deathCountProperty, // Number of deaths
                "datavalue" => [
                    "value" => [
                        "amount" => "+" . $count,
                        "unit" => "1",
                    ],
                    "type" => "quantity",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Add qualifier if provided
        if (!empty($qualifier)) {
            $this->addQualifierToStatement(
                $statement,
                $qualifierProperty,
                $qualifier,
            );
        }

        $this->data["claims"][$deathCountProperty] = [$statement];
    }

    /**
     * Set the arrest count
     */
    public function setArrestCount(int $count, ?string $qualifier = null): void
    {
        $this->arrestCount = $count;

        $arrestCountProperty = $this->mappingProvider->getPropertyId(
            "ARREST_COUNT",
        );
        $qualifierProperty = $this->mappingProvider->getPropertyId(
            "SOURCING_CIRCUMSTANCES",
        );

        $statement = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $arrestCountProperty, // Number of arrests
                "datavalue" => [
                    "value" => [
                        "amount" => "+" . $count,
                        "unit" => "1",
                    ],
                    "type" => "quantity",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Add qualifier if provided
        if (!empty($qualifier)) {
            $this->addQualifierToStatement(
                $statement,
                $qualifierProperty,
                $qualifier,
            );
        }

        $this->data["claims"][$arrestCountProperty] = [$statement];
    }
}

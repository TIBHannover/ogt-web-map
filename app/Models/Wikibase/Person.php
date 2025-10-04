<?php

namespace App\Models\Wikibase;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;

class Person extends BaseEntity
{
    // Role constants (semantic identifiers, not dependent on specific knowledge base)
    const ROLE_VICTIM = "victim";
    const ROLE_PERPETRATOR = "perpetrator";

    // Additional person properties
    protected $gender;
    protected $birthDate;
    protected $deathDate;
    protected $placeOfBirth;
    protected $placeOfDeath;
    protected $placeOfDetention;
    protected $placeOfResidence;
    protected $occupation;
    protected $nationality;
    protected $role;
    protected $birthName;
    protected $employer;

    /**
     * Constructor
     *
     * @param string $title The person's name/title
     * @param string $description The person's description
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
     * Create a Person model from array data
     */
    public static function fromArray(array $data): self
    {
        // Log what data we received with more details
        Log::info("Creating Person model from array data", [
            "title" => $data["title"] ?? "Unknown",
            "name" => $data["name"] ?? "Not set",
            "has_name" => isset($data["name"]),
            "has_role" => isset($data["role"]),
            "has_relationship_type" => isset($data["relationship_type"]),
            "relationship_type" => $data["relationship_type"] ?? "not set",
            "has_significant_event" => isset($data["significant_event_id"]),
            "has_add_role_flag" => isset($data["_add_role_claim"]),
            "has_add_event_flag" => isset($data["_add_significant_event"]),
            "birth_date" => $data["birthDate"] ?? "Not set",
            "has_birth_date" => isset($data["birthDate"]),
            "death_date" => $data["deathDate"] ?? "Not set",
            "has_death_date" => isset($data["deathDate"]),
            "gender" => isset($data["gender"])
                ? json_encode($data["gender"])
                : "Not set",
            "place_of_birth" => isset($data["place_of_birth"])
                ? json_encode($data["place_of_birth"])
                : "Not set",
            "has_place_of_birth" => isset($data["place_of_birth"]),
            "place_of_death" => isset($data["place_of_death"])
                ? json_encode($data["place_of_death"])
                : "Not set",
            "has_place_of_death" => isset($data["place_of_death"]),
            "place_of_residence" => isset($data["place_of_residence"])
                ? json_encode($data["place_of_residence"])
                : "Not set",
            "has_place_of_residence" => isset($data["place_of_residence"]),
            "occupation" => isset($data["occupation"])
                ? json_encode($data["occupation"])
                : "Not set",
            "has_occupation" => isset($data["occupation"]),
            "birth_name" =>
                $data["birthName"] ?? ($data["birth_name"] ?? "Not set"),
            "has_birth_name" =>
                isset($data["birthName"]) || isset($data["birth_name"]),
            "image" => $data["image"] ?? ($data["imageFilename"] ?? "Not set"),
            "has_image" =>
                isset($data["image"]) || isset($data["imageFilename"]),
            "employer" => isset($data["employer"])
                ? json_encode($data["employer"])
                : "Not set",
            "has_employer" => isset($data["employer"]),
            "data_keys" => array_keys($data),
            "all_data" => $data,
        ]);

        // IMPORTANT: Title must be set here to ensure labels are created
        $title = "";
        if (!empty($data["title"])) {
            $title = $data["title"];
        } elseif (!empty($data["name"])) {
            $title = $data["name"];
        } elseif (!empty($data["firstName"]) || !empty($data["lastName"])) {
            // Create a name from first and last name if available
            $title = trim(
                ($data["firstName"] ?? "") . " " . ($data["lastName"] ?? ""),
            );
        }

        // If still no title, use a fallback based on role
        if (empty($title) && !empty($data["role"])) {
            $title =
                $data["role"] === "victim"
                    ? "Unknown victim"
                    : "Unknown perpetrator";
        } elseif (empty($title)) {
            // Default title if everything else fails
            $title = "Unnamed person";
        }

        // Make sure we have a description
        $description = "";
        if (!empty($data["description"])) {
            $description = $data["description"];
        }

        $person = new self($title, $description);

        // Set gender - handle both string and object formats
        if (!empty($data["gender"])) {
            if (
                is_string($data["gender"]) &&
                substr($data["gender"], 0, 1) === "Q"
            ) {
                // Direct Q-ID string
                $person->setGender($data["gender"]);
                Log::info("Set gender from string value", [
                    "gender_id" => $data["gender"],
                ]);
            } elseif (
                is_array($data["gender"]) &&
                !empty($data["gender"]["id"])
            ) {
                // Object with id field
                $person->setGender($data["gender"]["id"]);
                Log::info("Set gender from object value", [
                    "gender_id" => $data["gender"]["id"],
                ]);
            }
        }

        // Set birth date
        if (!empty($data["birthDate"])) {
            $person->setBirthDate($data["birthDate"]);
            Log::info("Set birth date", ["date" => $data["birthDate"]]);
        }

        // Set death date
        if (!empty($data["deathDate"])) {
            $person->setDeathDate($data["deathDate"]);
            Log::info("Set death date", ["date" => $data["deathDate"]]);
        }

        // Set place of birth - handle both string and object formats
        if (!empty($data["place_of_birth"])) {
            if (
                is_string($data["place_of_birth"]) &&
                substr($data["place_of_birth"], 0, 1) === "Q"
            ) {
                // Direct Q-ID string
                $person->setPlaceOfBirth($data["place_of_birth"]);
                Log::info("Set place of birth from string value", [
                    "place_id" => $data["place_of_birth"],
                ]);
            } elseif (
                is_array($data["place_of_birth"]) &&
                !empty($data["place_of_birth"]["id"])
            ) {
                // Object with id field
                $person->setPlaceOfBirth($data["place_of_birth"]["id"]);
                Log::info("Set place of birth from object value", [
                    "place_id" => $data["place_of_birth"]["id"],
                ]);
            }
        }

        // Set place of death - handle both string and object formats
        if (!empty($data["place_of_death"])) {
            if (
                is_string($data["place_of_death"]) &&
                substr($data["place_of_death"], 0, 1) === "Q"
            ) {
                // Direct Q-ID string
                $person->setPlaceOfDeath($data["place_of_death"]);
                Log::info("Set place of death from string value", [
                    "place_id" => $data["place_of_death"],
                ]);
            } elseif (
                is_array($data["place_of_death"]) &&
                !empty($data["place_of_death"]["id"])
            ) {
                // Object with id field
                $person->setPlaceOfDeath($data["place_of_death"]["id"]);
                Log::info("Set place of death from object value", [
                    "place_id" => $data["place_of_death"]["id"],
                ]);
            }
        }

        // Set place of detention - can be single value or array
        if (isset($data["place_of_detention"])) {
            if (
                is_string($data["place_of_detention"]) &&
                substr($data["place_of_detention"], 0, 1) === "Q"
            ) {
                // Direct Q-ID string
                $person->setPlaceOfDetention($data["place_of_detention"]);
                Log::info("Set place of detention from string value", [
                    "place_id" => $data["place_of_detention"],
                ]);
            } elseif (is_array($data["place_of_detention"])) {
                if (isset($data["place_of_detention"]["id"])) {
                    // Single object with id field
                    $person->setPlaceOfDetention(
                        $data["place_of_detention"]["id"],
                    );
                    Log::info("Set place of detention from object value", [
                        "place_id" => $data["place_of_detention"]["id"],
                    ]);
                } else {
                    // Array of IDs or objects
                    $placeIds = [];
                    foreach ($data["place_of_detention"] as $place) {
                        if (is_string($place) && substr($place, 0, 1) === "Q") {
                            $placeIds[] = $place;
                        } elseif (is_array($place) && isset($place["id"])) {
                            $placeIds[] = $place["id"];
                        }
                    }
                    if (!empty($placeIds)) {
                        $person->setPlaceOfDetention($placeIds);
                        Log::info("Set place of detention from array", [
                            "place_ids" => $placeIds,
                        ]);
                    }
                }
            }
        }

        // Set place of residence - handle both string and object formats
        if (!empty($data["place_of_residence"])) {
            if (
                is_string($data["place_of_residence"]) &&
                substr($data["place_of_residence"], 0, 1) === "Q"
            ) {
                // Direct Q-ID string
                $person->setPlaceOfResidence($data["place_of_residence"]);
                Log::info("Set place of residence from string value", [
                    "place_id" => $data["place_of_residence"],
                ]);
            } elseif (
                is_array($data["place_of_residence"]) &&
                !empty($data["place_of_residence"]["id"])
            ) {
                // Object with id field
                $person->setPlaceOfResidence($data["place_of_residence"]["id"]);
                Log::info("Set place of residence from object value", [
                    "place_id" => $data["place_of_residence"]["id"],
                ]);
            }
        }

        // Set nationality - handle both string and object formats
        if (!empty($data["nationality"])) {
            if (
                is_string($data["nationality"]) &&
                substr($data["nationality"], 0, 1) === "Q"
            ) {
                // Direct Q-ID string
                $person->setNationality($data["nationality"]);
                Log::info("Set nationality from string value", [
                    "nationality_id" => $data["nationality"],
                ]);
            } elseif (
                is_array($data["nationality"]) &&
                !empty($data["nationality"]["id"])
            ) {
                // Object with id field
                $person->setNationality($data["nationality"]["id"]);
                Log::info("Set nationality from object value", [
                    "nationality_id" => $data["nationality"]["id"],
                ]);
            }
        }

        // Set occupation - handle both string and object formats
        if (!empty($data["occupation"])) {
            if (
                is_string($data["occupation"]) &&
                substr($data["occupation"], 0, 1) === "Q"
            ) {
                // Direct Q-ID string
                $person->setOccupation($data["occupation"]);
                Log::info("Set occupation from string value", [
                    "occupation_id" => $data["occupation"],
                ]);
            } elseif (
                is_array($data["occupation"]) &&
                !empty($data["occupation"]["id"])
            ) {
                // Object with id field
                $person->setOccupation($data["occupation"]["id"]);
                Log::info("Set occupation from object value", [
                    "occupation_id" => $data["occupation"]["id"],
                ]);
            }
        }

        // Set birth name (P1477) - support both birthName and birth_name fields
        if (!empty($data["birthName"])) {
            $person->setBirthName($data["birthName"]);
            Log::info("Set birth name from birthName field", [
                "birthName" => $data["birthName"],
            ]);
        } elseif (!empty($data["birth_name"])) {
            $person->setBirthName($data["birth_name"]);
            Log::info("Set birth name from birth_name field", [
                "birth_name" => $data["birth_name"],
            ]);
        }

        // Set employer (P108) - typically for perpetrators
        if (!empty($data["employer"])) {
            if (
                is_string($data["employer"]) &&
                substr($data["employer"], 0, 1) === "Q"
            ) {
                // Direct Q-ID string
                $person->setEmployer($data["employer"]);
                Log::info("Set employer from string value", [
                    "employer_id" => $data["employer"],
                ]);
            } elseif (
                is_array($data["employer"]) &&
                !empty($data["employer"]["id"])
            ) {
                // Object with id field
                $person->setEmployer($data["employer"]["id"]);
                Log::info("Set employer from object value", [
                    "employer_id" => $data["employer"]["id"],
                ]);
            }
        }

        // Enhanced image handling - check all possible image fields
        $imageFilename = null;

        // Try the most specific imageFilename field first (from new UI)
        if (!empty($data["imageFilename"])) {
            $imageFilename = $data["imageFilename"];
            Log::info("Found imageFilename in person data", [
                "imageFilename" => $imageFilename,
            ]);
        }
        // Then try the direct image_filename field
        elseif (!empty($data["image_filename"])) {
            $imageFilename = $data["image_filename"];
            Log::info("Found image_filename in person data", [
                "image_filename" => $imageFilename,
            ]);
        }
        // Then try the legacy image field
        elseif (!empty($data["image"])) {
            $imageFilename = $data["image"];
            Log::info("Found legacy image field in person data", [
                "image" => $imageFilename,
            ]);
        }
        // Finally try the URL as a fallback and extract filename
        elseif (!empty($data["image_url"])) {
            $urlParts = explode("/", $data["image_url"]);
            $imageFilename = end($urlParts);
            Log::info("Extracted filename from image_url", [
                "image_url" => $data["image_url"],
                "extracted_filename" => $imageFilename,
            ]);
        }

        // If we found an image, add it to the person
        if ($imageFilename) {
            $person->addImage($imageFilename);
        }

        // Extract flags and data relevant for role setting
        // Reset newPersonLocal to default before setting values
        $hasRoleInData = !empty($data["role"]);
        $hasAddRoleFlag = !empty($data["_add_role_claim"]);
        // Check if it's a new entity with either "new" exactly or "new_" prefix
        $isNewEntity =
            (!empty($data["tempId"]) &&
                ($data["tempId"] === "new" ||
                    substr($data["tempId"], 0, 4) === "new_")) ||
            (!empty($data["id"]) &&
                ($data["id"] === "new" ||
                    substr($data["id"], 0, 4) === "new_"));
        $hasSignificantEvent = !empty($data["significant_event_id"]);
        $hasRelationshipFlag =
            !empty($data["relationship"]) || !empty($data["relationship_type"]);

        Log::info("Evaluating person role setting conditions", [
            "has_role_in_data" => $hasRoleInData,
            "has_add_role_flag" => $hasAddRoleFlag,
            "is_new_entity" => $isNewEntity,
            "has_significant_event" => $hasSignificantEvent,
            "has_relationship_flag" => $hasRelationshipFlag,
            "role_value" => $data["role"] ?? "not set",
            "relationship_value" =>
                $data["relationship"] ??
                ($data["relationship_type"] ?? "not set"),
            "person_name" => $person->getName(),
        ]);

        // Determine role from multiple possible sources
        $role = null;

        // 1. Explicit role in data
        if ($hasRoleInData) {
            $role = $data["role"];
            Log::info("Using explicit role from data", ["role" => $role]);
        }
        // 2. Relationship type (from job chain)
        elseif (!empty($data["relationship"])) {
            $role = $data["relationship"]; // 'victim' or 'perpetrator'
            Log::info("Using role from relationship field", ["role" => $role]);
        } elseif (!empty($data["relationship_type"])) {
            $role = $data["relationship_type"]; // 'victim' or 'perpetrator'
            Log::info("Using role from relationship_type field", [
                "role" => $role,
            ]);
        }
        // 3. Default to 'victim' if no explicit role
        else {
            $role = "victim";
            Log::info("Using default role", ["role" => $role]);
        }

        // Always set the role for new person entities (victims/perpetrators)
        // For consistency, normalize role names to lowercase
        if ($role) {
            $role = strtolower($role);
        }

        if (
            $hasRoleInData ||
            $hasAddRoleFlag ||
            $isNewEntity ||
            $hasSignificantEvent ||
            $hasRelationshipFlag
        ) {
            Log::info("Setting role for person entity", [
                "role" => $role,
                "person_name" => $person->getName(),
                "entity_data" => json_encode(array_keys($data)),
                "has_significant_event" => $hasSignificantEvent,
                "significant_event_id" =>
                    $data["significant_event_id"] ?? "None",
                "reason" => $hasRoleInData
                    ? "explicit_role"
                    : ($hasAddRoleFlag
                        ? "add_role_flag"
                        : ($isNewEntity
                            ? "new_entity"
                            : ($hasSignificantEvent
                                ? "has_significant_event"
                                : "has_relationship"))),
            ]);

            // Set the role on the person entity
            $person->setRole($role);
        } else {
            Log::warning("No role set for person - missing required data", [
                "has_role" => $hasRoleInData,
                "has_add_role_flag" => $hasAddRoleFlag,
                "is_new_entity" => $isNewEntity,
                "has_significant_event" => $hasSignificantEvent,
                "has_relationship_flag" => $hasRelationshipFlag,
                "person_name" => $person->getName(),
                "data_keys" => array_keys($data),
            ]);
        }

        // If a significant event is provided, add that claim
        // Make sure all new victims/perpetrators get linked to the main event
        if (
            !empty($data["significant_event_id"]) ||
            !empty($data["_add_significant_event"]) ||
            (!empty($data["tempId"]) && $data["tempId"] === "new") ||
            (!empty($data["id"]) && $data["id"] === "new")
        ) {
            $eventId = $data["significant_event_id"] ?? null;
            if ($eventId) {
                $person->addSignificantEvent($eventId);
                Log::info("Added significant event to person", [
                    "event_id" => $eventId,
                ]);
            }
        }

        return $person;
    }

    /**
     * Set the gender
     */
    public function setGender($gender): void
    {
        // Handle different formats of gender input
        $genderId = "";

        if (is_string($gender)) {
            $genderId = $gender;
        } elseif (is_array($gender) && isset($gender["id"])) {
            $genderId = $gender["id"];
        } elseif (is_object($gender) && isset($gender->id)) {
            $genderId = $gender->id;
        }

        $this->gender = $genderId;

        Log::info("Setting gender for person", [
            "input" => $gender,
            "processed_id" => $genderId,
        ]);

        if (empty($genderId) || substr($genderId, 0, 1) !== "Q") {
            return;
        }

        $genderProperty = $this->mappingProvider->getPropertyId("GENDER");

        $this->data["claims"][$genderProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $genderProperty, // Sex or gender
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $genderId,
                            "numeric-id" => intval(substr($genderId, 1)),
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
     * Set the birth date
     */
    public function setBirthDate(string $date): void
    {
        $this->birthDate = $date;
        $formattedDate = $this->formatDate($date);

        if ($formattedDate) {
            $birthDateProperty = $this->mappingProvider->getPropertyId(
                "BIRTH_DATE",
            );

            $this->data["claims"][$birthDateProperty] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => $birthDateProperty, // Date of birth
                        "datavalue" => [
                            "value" => $formattedDate,
                            "type" => "time",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }
    }

    /**
     * Set the death date
     */
    public function setDeathDate(string $date): void
    {
        $this->deathDate = $date;
        $formattedDate = $this->formatDate($date);

        if ($formattedDate) {
            $deathDateProperty = $this->mappingProvider->getPropertyId(
                "DEATH_DATE",
            );

            $this->data["claims"][$deathDateProperty] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => $deathDateProperty, // Date of death
                        "datavalue" => [
                            "value" => $formattedDate,
                            "type" => "time",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }
    }

    /**
     * Set the place of birth
     */
    public function setPlaceOfBirth(string $placeId): void
    {
        $this->placeOfBirth = $placeId;

        if (empty($placeId) || substr($placeId, 0, 1) !== "Q") {
            return;
        }

        $placeOfBirthProperty = $this->mappingProvider->getPropertyId(
            "PLACE_OF_BIRTH",
        );

        $this->data["claims"][$placeOfBirthProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $placeOfBirthProperty, // Place of birth
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $placeId,
                            "numeric-id" => intval(substr($placeId, 1)),
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
     * Set the place of death
     */
    public function setPlaceOfDeath(string $placeId): void
    {
        $this->placeOfDeath = $placeId;

        if (empty($placeId) || substr($placeId, 0, 1) !== "Q") {
            return;
        }

        $placeOfDeathProperty = $this->mappingProvider->getPropertyId(
            "PLACE_OF_DEATH",
        );

        $this->data["claims"][$placeOfDeathProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $placeOfDeathProperty, // Place of death
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $placeId,
                            "numeric-id" => intval(substr($placeId, 1)),
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
     * Set the place of residence
     *
     * @param string $placeId The Wikidata Q-number for the residence place
     * @return void
     */
    public function setPlaceOfResidence(string $placeId): void
    {
        $this->place_of_residence = $placeId;

        if (empty($placeId) || substr($placeId, 0, 1) !== "Q") {
            return;
        }

        $residenceProperty = $this->mappingProvider->getPropertyId(
            "PLACE_OF_RESIDENCE",
        ); // Should map to P551

        $this->data["claims"][$residenceProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $residenceProperty,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $placeId,
                            "numeric-id" => intval(substr($placeId, 1)),
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];
    }

    // Duplicate setPlaceOfResidence method removed - already defined at line 697

    /**
     * Set the place of detention
     *
     * @param string|array $placeIds The Wikidata Q-number(s) for the detention place(s)
     * @return void
     */
    public function setPlaceOfDetention($placeIds): void
    {
        $detentionProperty = $this->mappingProvider->getPropertyId(
            "PLACE_OF_DETENTION",
        ); // Should map to P2632

        // Initialize the array if it doesn't exist
        if (!isset($this->data["claims"][$detentionProperty])) {
            $this->data["claims"][$detentionProperty] = [];
        }

        // Handle both single ID and array of IDs
        $placeIdArray = is_array($placeIds) ? $placeIds : [$placeIds];

        foreach ($placeIdArray as $placeId) {
            if (empty($placeId) || substr($placeId, 0, 1) !== "Q") {
                Log::warning("Invalid place of detention ID provided", [
                    "id" => $placeId,
                ]);
                continue;
            }

            // Add the place of detention claim
            $this->data["claims"][$detentionProperty][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $detentionProperty,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $placeId,
                            "numeric-id" => intval(substr($placeId, 1)),
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ];

            Log::info("Added place of detention to person", [
                "property" => $detentionProperty,
                "place_id" => $placeId,
            ]);
        }
    }

    /**
     * Set the occupation
     */
    public function setOccupation(string $occupationId): void
    {
        $this->occupation = $occupationId;

        if (empty($occupationId) || substr($occupationId, 0, 1) !== "Q") {
            return;
        }

        $occupationProperty = $this->mappingProvider->getPropertyId(
            "OCCUPATION",
        );

        $this->data["claims"][$occupationProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $occupationProperty, // Occupation
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $occupationId,
                            "numeric-id" => intval(substr($occupationId, 1)),
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
     * Set the nationality (country of citizenship)
     *
     * @param string $nationalityId The Wikidata Q-number for the nationality
     * @return void
     */
    public function setNationality(string $nationalityId): void
    {
        if (empty($nationalityId) || substr($nationalityId, 0, 1) !== "Q") {
            Log::warning("Invalid nationality ID provided to setNationality", [
                "id" => $nationalityId,
            ]);
            return;
        }

        $nationalityProperty = $this->mappingProvider->getPropertyId(
            "COUNTRY_OF_CITIZENSHIP",
        ); // Should map to P27

        // If we don't already have nationality claims, initialize the array
        if (!isset($this->data["claims"][$nationalityProperty])) {
            $this->data["claims"][$nationalityProperty] = [];
        }

        // Add the nationality claim
        $this->data["claims"][$nationalityProperty][] = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $nationalityProperty,
                "datavalue" => [
                    "value" => [
                        "entity-type" => "item",
                        "id" => $nationalityId,
                        "numeric-id" => intval(substr($nationalityId, 1)),
                    ],
                    "type" => "wikibase-entityid",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        Log::info("Added nationality to person", [
            "property" => $nationalityProperty,
            "nationality_id" => $nationalityId,
        ]);
    }

    /**
     * Set the person's role (victim or perpetrator)
     *
     * @param string $role Either 'victim' or 'perpetrator'
     * @return void
     */
    public function setRole(string $role): void
    {
        $this->role = $role;

        // Map the role string to an entity ID using the mapping provider
        // Make sure we normalize the role name before checking
        $normalizedRole = strtolower($role);
        $roleId = null;

        switch ($normalizedRole) {
            case self::ROLE_VICTIM:
                $roleId = $this->mappingProvider->getEntityId("VICTIM"); // Should map to Q2026714 - Victim of Nazism
                break;
            case self::ROLE_PERPETRATOR:
                $roleId = $this->mappingProvider->getEntityId("PERPETRATOR"); // Should map to Q111080573 - Perpetrator of Nazism
                break;
            default:
                Log::warning("Unknown role provided to Person::setRole", [
                    "role" => $role,
                    "normalized_role" => $normalizedRole,
                ]);
                return;
        }

        // Log the role ID being used with more detailed information
        Log::info("Setting person role with entity ID", [
            "role" => $role,
            "normalized_role" => $normalizedRole,
            "roleId" => $roleId,
            "expected_victim_id" => "Q2026714",
            "expected_perpetrator_id" => "Q111080573",
            "person_name" => $this->title,
            "is_perpetrator" =>
                $normalizedRole === self::ROLE_PERPETRATOR ? "yes" : "no",
        ]);

        // Add the "subject has role" claim using the mapping provider
        $subjectHasRoleProperty = $this->mappingProvider->getPropertyId(
            "SUBJECT_HAS_ROLE",
        ); // Should map to P2868

        $this->data["claims"][$subjectHasRoleProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $subjectHasRoleProperty, // Subject has role
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $roleId,
                            "numeric-id" => intval(substr($roleId, 1)),
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        // Log what's being set
        Log::info("Role property added to person", [
            "property" => $subjectHasRoleProperty,
            "value" => $roleId,
            "role" => $role,
            "mapping_provider_class" => get_class($this->mappingProvider),
            "claims" => json_encode(
                $this->data["claims"][$subjectHasRoleProperty],
            ),
        ]);
    }

    /**
     * Add a significant event claim to link this person to an event
     *
     * @param string $eventId The Wikidata Q-number for the event
     * @return void
     */
    public function addSignificantEvent(string $eventId): void
    {
        if (empty($eventId) || substr($eventId, 0, 1) !== "Q") {
            Log::warning("Invalid event ID provided to addSignificantEvent", [
                "id" => $eventId,
            ]);
            return;
        }

        // Get the significant event property from the mapping provider
        $significantEventProperty = $this->mappingProvider->getPropertyId(
            "SIGNIFICANT_EVENT",
        ); // Should map to P793

        // Log what we're about to do
        Log::info("Adding significant event link to person", [
            "person_name" => $this->getName(),
            "event_id" => $eventId,
            "property" => $significantEventProperty,
            "person_role" => $this->role ?? "unknown",
            "mapping_provider_class" => get_class($this->mappingProvider),
        ]);

        // If we don't already have significant event claims, initialize the array
        if (!isset($this->data["claims"][$significantEventProperty])) {
            $this->data["claims"][$significantEventProperty] = [];
        }

        // Add the "significant event" claim
        $this->data["claims"][$significantEventProperty][] = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $significantEventProperty, // Significant event
                "datavalue" => [
                    "value" => [
                        "entity-type" => "item",
                        "id" => $eventId,
                        "numeric-id" => intval(substr($eventId, 1)),
                    ],
                    "type" => "wikibase-entityid",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Log the claim structure that was added
        Log::info("Added significant event claim to person", [
            "person_name" => $this->getName(),
            "event_id" => $eventId,
            "property" => $significantEventProperty,
            "claim_data" => json_encode(
                $this->data["claims"][$significantEventProperty],
            ),
        ]);
    }

    /**
     * Get the person's name
     *
     * @return string The name of the person
     */
    public function getName(): string
    {
        return $this->title ?? ($this->getLabel() ?? "");
    }

    /**
     * Get the person's role (victim/perpetrator)
     *
     * @return string|null The role or null if not set
     */
    public function getRole(): ?string
    {
        // If we already have the role property set, return that
        if (!empty($this->role)) {
            return $this->role;
        }

        // Get the property ID for subject has role
        $subjectHasRoleProperty = $this->mappingProvider->getPropertyId(
            "SUBJECT_HAS_ROLE",
        );

        // Get entity IDs for victim and perpetrator roles
        $victimId = $this->mappingProvider->getEntityId("VICTIM");
        $perpetratorId = $this->mappingProvider->getEntityId("PERPETRATOR");

        // Check for subject has role claims
        if (isset($this->data["claims"][$subjectHasRoleProperty])) {
            foreach ($this->data["claims"][$subjectHasRoleProperty] as $claim) {
                if (!isset($claim["mainsnak"]["datavalue"]["value"]["id"])) {
                    continue;
                }

                $roleId = $claim["mainsnak"]["datavalue"]["value"]["id"];

                // Check for victim roles
                if ($roleId === $victimId) {
                    return self::ROLE_VICTIM;
                }

                // Check for perpetrator roles
                if ($roleId === $perpetratorId) {
                    return self::ROLE_PERPETRATOR;
                }
            }
        }

        return null;
    }

    /**
     * Helper method to determine if a value indicates an organization
     *
     * @param mixed $value The value to check
     * @return bool True if the value indicates an organization
     */
    /**
     * Set the birth name
     *
     * @param string $birthName The birth name of the person
     * @return void
     */
    public function setBirthName(string $birthName): void
    {
        $this->birthName = $birthName;

        if (empty($birthName)) {
            return;
        }

        $birthNameProperty = $this->mappingProvider->getPropertyId(
            "BIRTH_NAME",
        ); // Should map to P1477

        $this->data["claims"][$birthNameProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $birthNameProperty,
                    "datavalue" => [
                        "value" => [
                            "text" => $birthName,
                            "language" => "de",
                        ],
                        "type" => "monolingualtext",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        Log::info("Added birth name claim to person", [
            "property" => $birthNameProperty,
            "value" => $birthName,
        ]);
    }

    /**
     * Set the employer
     *
     * @param string $employerId The Wikidata Q-number for the employer
     * @return void
     */
    public function setEmployer(string $employerId): void
    {
        $this->employer = $employerId;

        if (empty($employerId) || substr($employerId, 0, 1) !== "Q") {
            Log::warning("Invalid employer ID provided to setEmployer", [
                "id" => $employerId,
            ]);
            return;
        }

        $employerProperty = $this->mappingProvider->getPropertyId("EMPLOYER"); // Should map to P108

        $this->data["claims"][$employerProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $employerProperty,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $employerId,
                            "numeric-id" => intval(substr($employerId, 1)),
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        Log::info("Added employer claim to person", [
            "property" => $employerProperty,
            "employer_id" => $employerId,
        ]);
    }

    public static function isOrganizationValue($value): bool
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
            return $lowerValue === "true" ||
                $lowerValue === "1" ||
                $lowerValue === "yes";
        }

        // Numeric value 1
        if (is_numeric($value) && (int) $value === 1) {
            return true;
        }

        return false;
    }
}

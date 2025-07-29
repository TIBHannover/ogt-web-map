<?php

namespace App\Models\Wikidata;

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
    protected $occupation;
    protected $role;

    /**
     * Constructor
     * 
     * @param string $title The person's name/title
     * @param string $description The person's description
     * @param EntityMappingProviderInterface|null $mappingProvider Optional mapping provider
     */
    public function __construct(string $title, string $description = "", ?EntityMappingProviderInterface $mappingProvider = null)
    {
        parent::__construct($title, $description, $mappingProvider);
    }

    /**
     * Create a Person model from array data
     */
    public static function fromArray(array $data): self
    {
        // Log what data we received
        Log::info("Creating Person model from array data", [
            "title" => $data["title"] ?? "Unknown",
            "name" => $data["name"] ?? "Not set",
            "has_name" => isset($data["name"]),
            "has_role" => isset($data["role"]),
            "has_significant_event" => isset($data["significant_event_id"]),
            "has_add_role_flag" => isset($data["_add_role_claim"]),
            "has_add_event_flag" => isset($data["_add_significant_event"]),
            "birth_date" => $data["birthDate"] ?? "Not set",
            "has_birth_date" => isset($data["birthDate"]),
            "death_date" => $data["deathDate"] ?? "Not set",
            "has_death_date" => isset($data["deathDate"]),
            "data_keys" => array_keys($data),
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

        // Set gender
        if (!empty($data["gender"]) && !empty($data["gender"]["id"])) {
            $person->setGender($data["gender"]["id"]);
        }

        // Set birth date
        if (!empty($data["birthDate"])) {
            $person->setBirthDate($data["birthDate"]);
        }

        // Set death date
        if (!empty($data["deathDate"])) {
            $person->setDeathDate($data["deathDate"]);
        }

        // Set place of birth
        if (
            !empty($data["place_of_birth"]) &&
            !empty($data["place_of_birth"]["id"])
        ) {
            $person->setPlaceOfBirth($data["place_of_birth"]["id"]);
        }

        // Set place of death
        if (
            !empty($data["place_of_death"]) &&
            !empty($data["place_of_death"]["id"])
        ) {
            $person->setPlaceOfDeath($data["place_of_death"]["id"]);
        }

        // Set occupation
        if (!empty($data["occupation"]) && !empty($data["occupation"]["id"])) {
            $person->setOccupation($data["occupation"]["id"]);
        }

        // Set image
        if (!empty($data["image"])) {
            $person->addImage($data["image"]);
        }

        // Set role (victim/perpetrator) for all persons in the victim/perpetrator flow
        // This ensures victims always get the victim role claim and perpetrators always get the perpetrator role claim
        if (
            !empty($data["role"]) ||
            !empty($data["_add_role_claim"]) ||
            (!empty($data["tempId"]) && $data["tempId"] === "new") ||
            (!empty($data["id"]) && $data["id"] === "new")
        ) {
            $role = $data["role"] ?? "victim";
            $person->setRole($role);
            Log::info("Set role for person", ["role" => $role]);
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
    public function setGender(string $genderId): void
    {
        $this->gender = $genderId;

        if (empty($genderId) || substr($genderId, 0, 1) !== "Q") {
            return;
        }

        $genderProperty = $this->mappingProvider->getPropertyId('GENDER');
        
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
            $birthDateProperty = $this->mappingProvider->getPropertyId('BIRTH_DATE');
            
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
            $deathDateProperty = $this->mappingProvider->getPropertyId('DEATH_DATE');
            
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

        $placeOfBirthProperty = $this->mappingProvider->getPropertyId('PLACE_OF_BIRTH');
        
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

        $placeOfDeathProperty = $this->mappingProvider->getPropertyId('PLACE_OF_DEATH');
        
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
     * Set the occupation
     */
    public function setOccupation(string $occupationId): void
    {
        $this->occupation = $occupationId;

        if (empty($occupationId) || substr($occupationId, 0, 1) !== "Q") {
            return;
        }

        $occupationProperty = $this->mappingProvider->getPropertyId('OCCUPATION');
        
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
     * Set the person's role (victim or perpetrator)
     *
     * @param string $role Either 'victim' or 'perpetrator'
     * @return void
     */
    public function setRole(string $role): void
    {
        $this->role = $role;

        // Map the role string to an entity ID using the mapping provider
        $roleId = null;
        switch (strtolower($role)) {
            case self::ROLE_VICTIM:
                $roleId = $this->mappingProvider->getEntityId('VICTIM');
                break;
            case self::ROLE_PERPETRATOR:
                $roleId = $this->mappingProvider->getEntityId('PERPETRATOR');
                break;
            default:
                Log::warning("Unknown role provided to Person::setRole", [
                    "role" => $role,
                ]);
                return;
        }

        // Add the "subject has role" claim using the mapping provider
        $subjectHasRoleProperty = $this->mappingProvider->getPropertyId('SUBJECT_HAS_ROLE');
        
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

        $significantEventProperty = $this->mappingProvider->getPropertyId('SIGNIFICANT_EVENT');
        
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
        $subjectHasRoleProperty = $this->mappingProvider->getPropertyId('SUBJECT_HAS_ROLE');
        
        // Get entity IDs for victim and perpetrator roles
        $victimId = $this->mappingProvider->getEntityId('VICTIM');
        $perpetratorId = $this->mappingProvider->getEntityId('PERPETRATOR');

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

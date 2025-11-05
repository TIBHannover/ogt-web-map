<?php

namespace App\Models\Wikibase;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Represents a GestapoPlace entity for Wikidata (Q106996250)
 */
class GestapoPlace extends BaseEntity
{
    /**
     * The place's founding date property (P571)
     *
     * @var string|null
     */
    protected $foundingDate;

    /**
     * Qualifier for the founding date
     *
     * @var string|null
     */
    protected $foundingDateQualifier;

    /**
     * The place's dissolution date property (P576)
     *
     * @var string|null
     */
    protected $dissolutionDate;

    /**
     * Qualifier for the dissolution date
     *
     * @var string|null
     */
    protected $dissolutionDateQualifier;

    /**
     * The place's address property (P6375)
     *
     * @var string|null
     */
    protected $address;

    /**
     * The key event property (P793)
     *
     * @var string|null
     */
    protected $keyEvent;

    /**
     * The prisoner count property (P5630)
     *
     * @var string|null
     */
    protected $prisonerCount;

    /**
     * Qualifier for the prisoner count
     *
     * @var string|null
     */
    protected $prisonerCountQualifier;

    /**
     * The parent organization property (P749)
     *
     * @var string|null
     */
    protected $parentOrganization;

    /**
     * The child organization property (P355)
     *
     * @var string|null
     */
    protected $childOrganization;

    /**
     * The predecessor organization property (P1365)
     *
     * @var string|null
     */
    protected $predecessorOrganization;

    /**
     * The successor organization property (P1366)
     *
     * @var string|null
     */
    protected $successorOrganization;

    /**
     * The director property (P1037)
     *
     * @var string|null
     */
    protected $director;

    /**
     * The employees count property (P1128)
     *
     * @var string|null
     */
    protected $employees;

    /**
     * The entity subtype (specific type of Gestapo place)
     *
     * @var string|null
     */
    protected $entitySubtype;

    /**
     * Create a new GestapoPlace instance from an array of data
     *
     * @param array $data The source data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $title = $data["title"] ?? "";
        $description = $data["description"] ?? "";
        $mappingProvider = app(EntityMappingProviderInterface::class);
        $gestapoPlace = new self($title, $description, $mappingProvider);

        // Set coordinates if available
        if (!empty($data["coordinates"])) {
            $gestapoPlace->addCoordinates(
                $data["coordinates"],
                $data["coordinates_qualifier"] ?? null,
            );
        }

        // Set entity subtype (specific type of Gestapo place)
        if (isset($data["entity_subtype"])) {
            $gestapoPlace->setEntitySubtype($data["entity_subtype"]);
        }

        // Set specific gestapo place properties
        if (isset($data["founding_date"])) {
            $gestapoPlace->setFoundingDate(
                $data["founding_date"],
                $data["founding_date_qualifier"] ?? null,
            );
        }

        if (isset($data["dissolution_date"])) {
            $gestapoPlace->setDissolutionDate(
                $data["dissolution_date"],
                $data["dissolution_date_qualifier"] ?? null,
            );
        }

        if (isset($data["address"])) {
            $gestapoPlace->setAddress($data["address"]);
        }

        if (isset($data["key_event"])) {
            $gestapoPlace->setKeyEvent($data["key_event"]);
        }

        if (isset($data["prisoner_count"])) {
            $gestapoPlace->setPrisonerCount(
                $data["prisoner_count"],
                $data["prisoner_count_qualifier"] ?? null,
            );
        }

        if (isset($data["parent_organization"])) {
            $gestapoPlace->setParentOrganization($data["parent_organization"]);
        }

        if (isset($data["child_organization"])) {
            $gestapoPlace->setChildOrganization($data["child_organization"]);
        }

        if (isset($data["predecessor_organization"])) {
            $gestapoPlace->setPredecessorOrganization(
                $data["predecessor_organization"],
            );
        }

        if (isset($data["successor_organization"])) {
            $gestapoPlace->setSuccessorOrganization(
                $data["successor_organization"],
            );
        }

        if (isset($data["director"])) {
            $gestapoPlace->setDirector($data["director"]);
        }

        if (isset($data["employees"])) {
            $gestapoPlace->setEmployees($data["employees"]);
        }

        // Set image if available
        if (!empty($data["image"]) && !empty($data["image"]["filename"])) {
            $gestapoPlace->setImage($data["image"]["filename"]);
        }

        return $gestapoPlace;
    }

    /**
     * Set the entity subtype (specific type of Gestapo place)
     * This will be added as an additional P31 value alongside Q106996250
     *
     * @param string $entitySubtype The Wikidata entity ID for the specific type
     * @return void
     */
    public function setEntitySubtype(?string $entitySubtype): void
    {
        $this->entitySubtype = $entitySubtype;

        // Skip if empty or not a valid Q-ID
        if (empty($entitySubtype) || substr($entitySubtype, 0, 1) !== "Q") {
            return;
        }

        $instanceOfProperty = $this->mappingProvider->getPropertyId(
            "INSTANCE_OF",
        );

        // Ensure the base type (Q106996250) is set
        $this->addInstanceOfClaim();

        // Add the specific subtype as an additional P31 claim
        if (!isset($this->data["claims"][$instanceOfProperty])) {
            $this->data["claims"][$instanceOfProperty] = [];
        }

        // Add the subtype as an additional P31 claim
        $this->data["claims"][$instanceOfProperty][] = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $instanceOfProperty,
                "datavalue" => [
                    "value" => [
                        "entity-type" => "item",
                        "id" => $entitySubtype,
                        "numeric-id" => intval(substr($entitySubtype, 1)),
                    ],
                    "type" => "wikibase-entityid",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];
    }

    /**
     * Set the founding date property (P571)
     *
     * @param string $date The founding date in a supported format
     * @param string|null $qualifier Optional qualifier for the date
     * @return void
     */
    public function setFoundingDate(
        ?string $date,
        ?string $qualifier = null,
    ): void {
        $this->foundingDate = $date;
        $this->foundingDateQualifier = $qualifier;

        if (empty($date)) {
            return;
        }

        $foundingDateProperty = $this->mappingProvider->getPropertyId(
            "FOUNDING_DATE",
        );

        $precision = 11; // Default to day precision
        $formattedDate = $date;

        // Determine date precision based on format
        if (preg_match('/^\d{4}$/', $date)) {
            // Year only format: 2023
            $precision = 9;
            $formattedDate = $date . "-00-00T00:00:00Z";
        } elseif (preg_match('/^\d{4}-\d{2}$/', $date)) {
            // Year-month format: 2023-01
            $precision = 10;
            $formattedDate = $date . "-00T00:00:00Z";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            // Full date format: 2023-01-01
            $formattedDate = $date . "T00:00:00Z";
        }

        $dateStatement = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $foundingDateProperty,
                "datavalue" => [
                    "value" => [
                        "time" => "+" . $formattedDate,
                        "timezone" => 0,
                        "before" => 0,
                        "after" => 0,
                        "precision" => $precision,
                        "calendarmodel" =>
                            "http://www.wikidata.org/entity/Q1985727", // Gregorian calendar
                    ],
                    "type" => "time",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Add qualifier if provided
        if (!empty($qualifier)) {
            $qualifierProperty = $this->mappingProvider->getPropertyId(
                "QUALIFIER_OF",
            );

            if ($qualifierProperty) {
                $dateStatement["qualifiers"] = [
                    $qualifierProperty => [
                        [
                            "snaktype" => "value",
                            "property" => $qualifierProperty,
                            "datavalue" => [
                                "value" => $qualifier,
                                "type" => "string",
                            ],
                        ],
                    ],
                ];
            }
        }

        $this->data["claims"][$foundingDateProperty] = [$dateStatement];
    }

    /**
     * Set the dissolution date property (P576)
     *
     * @param string $date The dissolution date in a supported format
     * @param string|null $qualifier Optional qualifier for the date
     * @return void
     */
    public function setDissolutionDate(
        ?string $date,
        ?string $qualifier = null,
    ): void {
        $this->dissolutionDate = $date;
        $this->dissolutionDateQualifier = $qualifier;

        if (empty($date)) {
            return;
        }

        $dissolutionDateProperty = $this->mappingProvider->getPropertyId(
            "DISSOLUTION_DATE",
        );

        $precision = 11; // Default to day precision
        $formattedDate = $date;

        // Determine date precision based on format
        if (preg_match('/^\d{4}$/', $date)) {
            // Year only format: 2023
            $precision = 9;
            $formattedDate = $date . "-00-00T00:00:00Z";
        } elseif (preg_match('/^\d{4}-\d{2}$/', $date)) {
            // Year-month format: 2023-01
            $precision = 10;
            $formattedDate = $date . "-00T00:00:00Z";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            // Full date format: 2023-01-01
            $formattedDate = $date . "T00:00:00Z";
        }

        $dateStatement = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $dissolutionDateProperty,
                "datavalue" => [
                    "value" => [
                        "time" => "+" . $formattedDate,
                        "timezone" => 0,
                        "before" => 0,
                        "after" => 0,
                        "precision" => $precision,
                        "calendarmodel" =>
                            "http://www.wikidata.org/entity/Q1985727", // Gregorian calendar
                    ],
                    "type" => "time",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Add qualifier if provided
        if (!empty($qualifier)) {
            $qualifierProperty = $this->mappingProvider->getPropertyId(
                "QUALIFIER_OF",
            );

            if ($qualifierProperty) {
                $dateStatement["qualifiers"] = [
                    $qualifierProperty => [
                        [
                            "snaktype" => "value",
                            "property" => $qualifierProperty,
                            "datavalue" => [
                                "value" => $qualifier,
                                "type" => "string",
                            ],
                        ],
                    ],
                ];
            }
        }

        $this->data["claims"][$dissolutionDateProperty] = [$dateStatement];
    }

    /**
     * Set the address property (P6375)
     *
     * @param string $address The street address
     * @return void
     */
    public function setAddress(?string $address): void
    {
        $this->address = $address;

        if (empty($address)) {
            return;
        }

        $addressProperty = $this->mappingProvider->getPropertyId("ADDRESS");

        $this->data["claims"][$addressProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $addressProperty,
                    "datavalue" => [
                        "value" => [
                            "text" => $address,
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

    /**
     * Set the key event property (P793)
     *
     * @param string $entityId The Wikidata entity ID of the key event
     * @return void
     */
    public function setKeyEvent($entityIds): void
    {
        $this->keyEvent = $entityIds;

        // Handle empty input or non-arrays
        if (empty($entityIds)) {
            return;
        }

        // Convert single string to array if needed
        $entityIdArray = is_array($entityIds) ? $entityIds : [$entityIds];

        $keyEventProperty = $this->mappingProvider->getPropertyId(
            "SIGNIFICANT_EVENT",
        );

        // Initialize the claims array if not already set
        if (!isset($this->data["claims"][$keyEventProperty])) {
            $this->data["claims"][$keyEventProperty] = [];
        } else {
            // Clear existing claims for this property
            $this->data["claims"][$keyEventProperty] = [];
        }

        // Add each entity ID as a separate claim
        foreach ($entityIdArray as $entityId) {
            // Skip if not a valid Q-ID
            if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
                continue;
            }

            $this->data["claims"][$keyEventProperty][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $keyEventProperty,
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
        }
    }

    /**
     * Set the prisoner count property (P5630)
     *
     * @param string $count The prisoner count
     * @param string|null $qualifier Optional qualifier for the count
     * @return void
     */
    public function setPrisonerCount(
        ?string $count,
        ?string $qualifier = null,
    ): void {
        $this->prisonerCount = $count;
        $this->prisonerCountQualifier = $qualifier;

        if (empty($count) || !is_numeric($count)) {
            return;
        }

        // Convert to integer to ensure valid format
        $numericCount = (int) $count;

        // Format properly for Wikidata - ensure positive integers have a plus sign
        $formattedAmount =
            $numericCount >= 0 ? "+$numericCount" : "$numericCount";

        $prisonerCountProperty = $this->mappingProvider->getPropertyId(
            "PRISONER_COUNT",
        );

        $countStatement = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $prisonerCountProperty,
                "datavalue" => [
                    "value" => [
                        "amount" => $formattedAmount,
                        "unit" => "1",
                    ],
                    "type" => "quantity",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];

        // Add qualifier if provided (P1480 - sourcing circumstances)
        if (!empty($qualifier)) {
            $qualifierProperty = $this->mappingProvider->getPropertyId(
                "SOURCING_CIRCUMSTANCES",
            );

            if ($qualifierProperty) {
                $countStatement["qualifiers"] = [
                    $qualifierProperty => [
                        [
                            "snaktype" => "value",
                            "property" => $qualifierProperty,
                            "datavalue" => [
                                "value" => $qualifier,
                                "type" => "string",
                            ],
                        ],
                    ],
                ];
            }
        }

        $this->data["claims"][$prisonerCountProperty] = [$countStatement];
    }

    /**
     * Set the parent organization property (P749)
     *
     * @param string $entityId The Wikidata entity ID of the parent organization
     * @return void
     */
    public function setParentOrganization($entityIds): void
    {
        $this->parentOrganization = $entityIds;

        // Handle empty input
        if (empty($entityIds)) {
            return;
        }

        // Convert single string to array if needed
        $entityIdArray = is_array($entityIds) ? $entityIds : [$entityIds];

        $parentOrgProperty = $this->mappingProvider->getPropertyId(
            "PARENT_ORGANIZATION",
        );

        // Initialize the claims array if not already set
        if (!isset($this->data["claims"][$parentOrgProperty])) {
            $this->data["claims"][$parentOrgProperty] = [];
        } else {
            // Clear existing claims for this property
            $this->data["claims"][$parentOrgProperty] = [];
        }

        // Add each entity ID as a separate claim
        foreach ($entityIdArray as $entityId) {
            // Skip if not a valid Q-ID
            if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
                continue;
            }

            $this->data["claims"][$parentOrgProperty][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $parentOrgProperty,
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
        }
    }

    /**
     * Set the child organization property (P355)
     *
     * @param array|string $entityIds The Wikidata entity IDs of child organizations
     * @return void
     */
    public function setChildOrganization($entityIds): void
    {
        $this->childOrganization = $entityIds;

        // Handle empty input
        if (empty($entityIds)) {
            return;
        }

        // Convert single string to array if needed
        $entityIdArray = is_array($entityIds) ? $entityIds : [$entityIds];

        $childOrgProperty = $this->mappingProvider->getPropertyId(
            "CHILD_ORGANIZATION",
        );

        // Initialize the claims array if not already set
        if (!isset($this->data["claims"][$childOrgProperty])) {
            $this->data["claims"][$childOrgProperty] = [];
        } else {
            // Clear existing claims for this property
            $this->data["claims"][$childOrgProperty] = [];
        }

        // Add each entity ID as a separate claim
        foreach ($entityIdArray as $entityId) {
            // Skip if not a valid Q-ID
            if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
                continue;
            }

            $this->data["claims"][$childOrgProperty][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $childOrgProperty,
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
        }
    }

    /**
     * Set the predecessor organization property (P1365)
     *
     * @param array|string $entityIds The Wikidata entity IDs of predecessor organizations
     * @return void
     */
    public function setPredecessorOrganization($entityIds): void
    {
        $this->predecessorOrganization = $entityIds;

        // Handle empty input
        if (empty($entityIds)) {
            return;
        }

        // Convert single string to array if needed
        $entityIdArray = is_array($entityIds) ? $entityIds : [$entityIds];

        $predecessorOrgProperty = $this->mappingProvider->getPropertyId(
            "PREDECESSOR",
        );

        // Initialize the claims array if not already set
        if (!isset($this->data["claims"][$predecessorOrgProperty])) {
            $this->data["claims"][$predecessorOrgProperty] = [];
        } else {
            // Clear existing claims for this property
            $this->data["claims"][$predecessorOrgProperty] = [];
        }

        // Add each entity ID as a separate claim
        foreach ($entityIdArray as $entityId) {
            // Skip if not a valid Q-ID
            if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
                continue;
            }

            $this->data["claims"][$predecessorOrgProperty][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $predecessorOrgProperty,
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
        }
    }

    /**
     * Set the successor organization property (P1366)
     *
     * @param string $entityId The Wikidata entity ID of the successor organization
     * @return void
     */
    public function setSuccessorOrganization($entityIds): void
    {
        $this->successorOrganization = $entityIds;

        // Handle empty input
        if (empty($entityIds)) {
            return;
        }

        // Convert single string to array if needed
        $entityIdArray = is_array($entityIds) ? $entityIds : [$entityIds];

        $successorOrgProperty = $this->mappingProvider->getPropertyId(
            "SUCCESSOR",
        );

        // Initialize the claims array if not already set
        if (!isset($this->data["claims"][$successorOrgProperty])) {
            $this->data["claims"][$successorOrgProperty] = [];
        } else {
            // Clear existing claims for this property
            $this->data["claims"][$successorOrgProperty] = [];
        }

        // Add each entity ID as a separate claim
        foreach ($entityIdArray as $entityId) {
            // Skip if not a valid Q-ID
            if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
                continue;
            }

            $this->data["claims"][$successorOrgProperty][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $successorOrgProperty,
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
        }
    }

    /**
     * Set the director property (P1037)
     *
     * @param string $entityId The Wikidata entity ID of the director
     * @return void
     */
    public function setDirector($entityIds): void
    {
        $this->director = $entityIds;

        // Handle empty input
        if (empty($entityIds)) {
            return;
        }

        // Convert single string to array if needed
        $entityIdArray = is_array($entityIds) ? $entityIds : [$entityIds];

        $directorProperty = $this->mappingProvider->getPropertyId("DIRECTOR");

        // Initialize the claims array if not already set
        if (!isset($this->data["claims"][$directorProperty])) {
            $this->data["claims"][$directorProperty] = [];
        } else {
            // Clear existing claims for this property
            $this->data["claims"][$directorProperty] = [];
        }

        // Add each entity ID as a separate claim
        foreach ($entityIdArray as $entityId) {
            // Skip if not a valid Q-ID
            if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
                continue;
            }

            $this->data["claims"][$directorProperty][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $directorProperty,
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
        }
    }

    /**
     * Set the employees property
     *
     * @param array|string $entityIds The Wikidata entity IDs of employees
     * @return void
     */
    public function setEmployees($entityIds): void
    {
        $this->employees = $entityIds;

        // Handle empty input
        if (empty($entityIds)) {
            return;
        }

        // Convert single string to array if needed
        $entityIdArray = is_array($entityIds) ? $entityIds : [$entityIds];

        $employeesProperty = $this->mappingProvider->getPropertyId("EMPLOYEES");

        // Initialize the claims array if not already set
        if (!isset($this->data["claims"][$employeesProperty])) {
            $this->data["claims"][$employeesProperty] = [];
        } else {
            // Clear existing claims for this property
            $this->data["claims"][$employeesProperty] = [];
        }

        // Add each entity ID as a separate claim
        foreach ($entityIdArray as $entityId) {
            // Skip if not a valid Q-ID
            if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
                continue;
            }

            $this->data["claims"][$employeesProperty][] = [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $employeesProperty,
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
        }
    }

    /**
     * Set the image for this entity (P18)
     *
     * @param string $filename The image filename
     * @return void
     */
    public function setImage($filename): void
    {
        if (empty($filename)) {
            return;
        }

        // Use the BaseEntity implementation
        $this->addImage($filename);
    }
}

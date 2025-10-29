<?php

namespace App\Models\Wikibase;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Represents a Memorial/Erinnerungsort entity for Wikidata
 */
class Memorial extends BaseEntity
{
    /**
     * The memorial's commemorates property (P547)
     *
     * @var string|null
     */
    protected $commemorates;

    /**
     * The memorial's operator property (P137)
     *
     * @var string|null
     */
    protected $operator;

    /**
     * The memorial's opening date property (P1619)
     *
     * @var string|null
     */
    protected $openingDate;

    /**
     * Qualifier for the opening date
     *
     * @var string|null
     */
    protected $openingDateQualifier;

    /**
     * The memorial's address property (P6375)
     *
     * @var string|null
     */
    protected $address;

    /**
     * The memorial's website property (P856)
     *
     * @var string|null
     */
    protected $website;

    /**
     * Create a new Memorial instance from an array of data
     *
     * @param array $data The source data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $mappingProvider = app(EntityMappingProviderInterface::class);
        $memorial = new self($mappingProvider);

        // Set basic properties
        $memorial->setTitle($data["title"] ?? "");
        $memorial->setDescription($data["description"] ?? "");

        // Set coordinates if available
        if (!empty($data["coordinates"])) {
            if (is_string($data["coordinates"])) {
                // Parse from string format (e.g. "52.5200, 13.4050")
                $parts = explode(",", $data["coordinates"]);
                if (count($parts) === 2) {
                    $latitude = trim($parts[0]);
                    $longitude = trim($parts[1]);
                    $memorial->setCoordinates(
                        $latitude,
                        $longitude,
                        $data["coordinates_qualifier"] ?? null,
                    );
                }
            } elseif (is_array($data["coordinates"])) {
                // Array format
                if (
                    isset($data["coordinates"]["latitude"]) &&
                    isset($data["coordinates"]["longitude"])
                ) {
                    $memorial->setCoordinates(
                        $data["coordinates"]["latitude"],
                        $data["coordinates"]["longitude"],
                        $data["coordinates_qualifier"] ?? null,
                    );
                } elseif (count($data["coordinates"]) >= 2) {
                    $memorial->setCoordinates(
                        $data["coordinates"][0],
                        $data["coordinates"][1],
                        $data["coordinates_qualifier"] ?? null,
                    );
                }
            }
        }

        // Set specific memorial properties
        if (!empty($data["commemorates"])) {
            $memorial->setCommemorates($data["commemorates"]);
        }

        if (!empty($data["operator"])) {
            $memorial->setOperator($data["operator"]);
        }

        if (!empty($data["opening_date"])) {
            $memorial->setOpeningDate(
                $data["opening_date"],
                $data["opening_date_qualifier"] ?? null,
            );
        }

        if (!empty($data["address"])) {
            $memorial->setAddress($data["address"]);
        }

        if (!empty($data["website"])) {
            $memorial->setWebsite($data["website"]);
        }

        // Set image if available
        if (!empty($data["image"]) && !empty($data["image"]["filename"])) {
            $memorial->setImage($data["image"]["filename"]);
        }

        return $memorial;
    }

    /**
     * Set the commemorates property (P547)
     *
     * @param string $entityId The Wikidata entity ID that this memorial commemorates
     * @return void
     */
    public function setCommemorates(string $entityId): void
    {
        $this->commemorates = $entityId;

        // Skip if empty or not a valid Q-ID
        if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
            return;
        }

        $commemoratesProperty = $this->mappingProvider->getPropertyId(
            "COMMEMORATES",
        );

        $this->data["claims"][$commemoratesProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $commemoratesProperty,
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
            ],
        ];
    }

    /**
     * Set the operator property (P137)
     *
     * @param string $entityId The Wikidata entity ID of the operator
     * @return void
     */
    public function setOperator(string $entityId): void
    {
        $this->operator = $entityId;

        // Skip if empty or not a valid Q-ID
        if (empty($entityId) || substr($entityId, 0, 1) !== "Q") {
            return;
        }

        $operatorProperty = $this->mappingProvider->getPropertyId("OPERATOR");

        $this->data["claims"][$operatorProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $operatorProperty,
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
            ],
        ];
    }

    /**
     * Set the opening date property (P1619)
     *
     * @param string $date The opening date in a supported format
     * @param string|null $qualifier Optional qualifier for the date
     * @return void
     */
    public function setOpeningDate(
        string $date,
        ?string $qualifier = null,
    ): void {
        $this->openingDate = $date;
        $this->openingDateQualifier = $qualifier;

        if (empty($date)) {
            return;
        }

        $openingDateProperty = $this->mappingProvider->getPropertyId(
            "OPENING_DATE",
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
                "property" => $openingDateProperty,
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

        $this->data["claims"][$openingDateProperty] = [$dateStatement];
    }

    /**
     * Set the address property (P6375)
     *
     * @param string $address The street address
     * @return void
     */
    public function setAddress(string $address): void
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
     * Set the website property (P856)
     *
     * @param string $website The URL of the official website
     * @return void
     */
    public function setWebsite(string $website): void
    {
        $this->website = $website;

        if (empty($website)) {
            return;
        }

        // Ensure URL has a protocol
        if (strpos($website, "http") !== 0) {
            $website = "https://" . $website;
        }

        $websiteProperty = $this->mappingProvider->getPropertyId("WEBSITE");

        $this->data["claims"][$websiteProperty] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $websiteProperty,
                    "datavalue" => [
                        "value" => $website,
                        "type" => "string",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];
    }
}

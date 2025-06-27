<?php

namespace App\Models\Wikidata;

use Illuminate\Support\Facades\Log;

abstract class BaseEntity
{
    // Entity data storage
    protected $data = [
        'labels' => [],
        'descriptions' => [],
        'aliases' => [],
        'claims' => [],
        'sitelinks' => []
    ];
    
    // Wikidata entity type (Q-code)
    protected $entityType;
    
    // Entity label/title
    protected $title;
    
    // Entity description
    protected $description;

    /**
     * Constructor
     */
    public function __construct(string $title, string $description = '')
    {
        $this->title = $title;
        $this->description = $description;
        $this->initializeLabels();
    }
    
    /**
     * Initialize basic labels and descriptions
     */
    protected function initializeLabels(): void
    {
        // Don't initialize labels if we're updating an existing entity that already has an ID
        // This prevents overwriting existing labels
        if (isset($this->data['_is_update']) && $this->data['_is_update'] === true) {
            Log::info("Skipping label initialization for entity update");
            return;
        }

        // Make sure title is not empty to ensure labels are set
        $title = !empty($this->title) ? $this->title : 'Unnamed entity';
        
        // Set the German label (primary language)
        $this->data['labels']['de'] = [
            'language' => 'de',
            'value' => $title
        ];
        
        // Also set English label for international accessibility
        $this->data['labels']['en'] = [
            'language' => 'en',
            'value' => $title
        ];
        
        // Set description if available
        if (!empty($this->description)) {
            $this->data['descriptions']['de'] = [
                'language' => 'de',
                'value' => $this->description
            ];
            
            // Also set English description
            $this->data['descriptions']['en'] = [
                'language' => 'en',
                'value' => $this->description
            ];
        }
        
        // Log the initialization
        Log::info("Initialized entity labels and descriptions", [
            "title" => $title,
            "description" => $this->description ?? 'None',
            "has_title" => !empty($this->title),
            "label_value" => $this->data['labels']['de']['value']
        ]);
    }
    
    /**
     * Add the instance of claim (P31)
     */
    protected function addInstanceOfClaim(): void
    {
        $this->data['claims']['P31'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P31',
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => $this->entityType,
                            'numeric-id' => intval(substr($this->entityType, 1))
                        ],
                        'type' => 'wikibase-entityid'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ]
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
        
        $this->data['claims']['P625'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P625',
                    'datavalue' => [
                        'value' => [
                            'latitude' => $parsedCoordinates['lat'],
                            'longitude' => $parsedCoordinates['lng'],
                            'precision' => 0.0001,
                            'globe' => 'http://www.wikidata.org/entity/Q2'
                        ],
                        'type' => 'globecoordinate'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ]
        ];
    }
    
    /**
     * Add image claim
     */
    public function addImage(string $imageFilename): void
    {
        if (empty($imageFilename)) {
            return;
        }
        
        $this->data['claims']['P18'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P18',
                    'datavalue' => [
                        'value' => $imageFilename,
                        'type' => 'string'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ]
        ];
    }
    
    /**
     * Get the formatted data for Wikidata
     */
    public function getFormattedData(): array
    {
        // Ensure instance of claim is added
        $this->addInstanceOfClaim();
        
        return [
            'type' => 'item',
            'labels' => $this->data['labels'],
            'descriptions' => $this->data['descriptions'],
            'aliases' => $this->data['aliases'],
            'claims' => $this->data['claims'],
            'sitelinks' => $this->data['sitelinks']
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
            $year = $dateTime->format('Y');
            $month = $dateTime->format('m');
            $day = $dateTime->format('d');

            return [
                'time' => "+{$year}-{$month}-{$day}T00:00:00Z",
                'timezone' => 0,
                'before' => 0,
                'after' => 0,
                'precision' => 11, // Day precision
                'calendarmodel' => 'http://www.wikidata.org/entity/Q1985727' // Gregorian calendar
            ];
        } catch (\Exception $e) {
            Log::warning('Error formatting date', [
                'date' => $date,
                'error' => $e->getMessage()
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
        if (is_array($coordinates) && isset($coordinates['lat']) && isset($coordinates['lng'])) {
            return [
                'lat' => (float)$coordinates['lat'],
                'lng' => (float)$coordinates['lng']
            ];
        }

        if (is_string($coordinates)) {
            if (strpos($coordinates, '{') === 0) {
                $parsed = json_decode($coordinates, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($parsed['lat']) && isset($parsed['lng'])) {
                    return [
                        'lat' => (float)$parsed['lat'],
                        'lng' => (float)$parsed['lng']
                    ];
                }
            }

            // Try to parse as array
            if (strpos($coordinates, '[') === 0) {
                $parsed = json_decode($coordinates, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($parsed[0]) && isset($parsed[1])) {
                    return [
                        'lat' => (float)$parsed[0],
                        'lng' => (float)$parsed[1]
                    ];
                }
            }

            // Try to parse as comma-separated values
            $parts = explode(',', $coordinates);
            if (count($parts) >= 2) {
                return [
                    'lat' => (float)trim($parts[0]),
                    'lng' => (float)trim($parts[1])
                ];
            }
        }

        // Fallback: Log error message and return null
        Log::warning('Invalid coordinate format', [
            'coordinates' => $coordinates
        ]);
        return null;
    }
    
    /**
     * Add a qualifier to a statement.
     *
     * @param array &$statement Reference to the statement array
     * @param string $property The qualifier property ID
     * @param string $value The qualifier value (entity ID)
     * @return void
     */
    protected function addQualifierToStatement(array &$statement, string $property, string $value): void
    {
        if (empty($value)) {
            return;
        }

        // Initialize qualifiers array if it doesn't exist
        if (!isset($statement['qualifiers'])) {
            $statement['qualifiers'] = [];
        }

        // Initialize specific property qualifiers array if it doesn't exist
        if (!isset($statement['qualifiers'][$property])) {
            $statement['qualifiers'][$property] = [];
        }

        // Add the qualifier
        $statement['qualifiers'][$property][] = [
            'snaktype' => 'value',
            'property' => $property,
            'datavalue' => [
                'value' => [
                    'entity-type' => 'item',
                    'id' => $value,
                    'numeric-id' => intval(substr($value, 1))
                ],
                'type' => 'wikibase-entityid'
            ]
        ];
    }
    
    /**
     * Get the underlying data array
     */
    public function getData(): array
    {
        return $this->data;
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
    public function getLabel(string $language = 'de'): ?string
    {
        return $this->data['labels'][$language]['value'] ?? $this->title ?? null;
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
        
        $this->data['claims']['P828'] = [];
        
        foreach ($causes as $cause) {
            if (is_array($cause) && isset($cause['id']) && substr($cause['id'], 0, 1) === 'Q') {
                $this->data['claims']['P828'][] = [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P828',
                        'datavalue' => [
                            'value' => [
                                'entity-type' => 'item',
                                'id' => $cause['id'],
                                'numeric-id' => intval(substr($cause['id'], 1))
                            ],
                            'type' => 'wikibase-entityid'
                        ]
                    ],
                    'type' => 'statement',
                    'rank' => 'normal'
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
        if (empty($entityId) || substr($entityId, 0, 1) !== 'Q') {
            return;
        }

        if (!isset($this->data['claims'][$property])) {
            $this->data['claims'][$property] = [];
        }

        $this->data['claims'][$property][] = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => $property,
                'datavalue' => [
                    'value' => [
                        'entity-type' => 'item',
                        'id' => $entityId,
                        'numeric-id' => intval(substr($entityId, 1))
                    ],
                    'type' => 'wikibase-entityid'
                ]
            ],
            'type' => 'statement',
            'rank' => 'normal'
        ];
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

        if (!isset($this->data['claims'][$property])) {
            $this->data['claims'][$property] = [];
        }

        $this->data['claims'][$property][] = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => $property,
                'datavalue' => [
                    'value' => $value,
                    'type' => 'string'
                ]
            ],
            'type' => 'statement',
            'rank' => 'normal'
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
        if (method_exists($source, 'getFormattedReference')) {
            $reference = $source->getFormattedReference($parentEntityId);
            
            // Add the reference to all claims
            foreach ($this->data['claims'] as $propertyId => &$statements) {
                foreach ($statements as &$statement) {
                    if (!isset($statement['references'])) {
                        $statement['references'] = [];
                    }
                    $statement['references'][] = $reference;
                }
            }
        }
    }
}
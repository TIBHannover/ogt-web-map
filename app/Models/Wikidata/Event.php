<?php

namespace App\Models\Wikidata;

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
    public function __construct(string $title, string $description = '', ?EntityMappingProviderInterface $mappingProvider = null)
    {
        parent::__construct($title, $description, $mappingProvider);
    }
    
    /**
     * Create an Event model from array data
     */
    public static function fromArray(array $data): self
    {
        $event = new self($data['title'] ?? '', $data['description'] ?? '');
        
        // Set point in time if available
        if (!empty($data['time_point'])) {
            $event->setPointInTime($data['time_point'], $data['time_point_qualifier'] ?? null);
        } else {
            // Or set start and end time
            if (!empty($data['time_period_start'])) {
                $event->setStartTime($data['time_period_start'], $data['time_period_start_qualifier'] ?? null);
            }
            
            if (!empty($data['time_period_end'])) {
                $event->setEndTime($data['time_period_end'], $data['time_period_end_qualifier'] ?? null);
            }
        }
        
        // Set location
        if (!empty($data['location']) && !empty($data['location']['id'])) {
            $event->setLocation($data['location']['id']);
        }
        
        // Set cause
        if (!empty($data['cause']) && substr($data['cause'], 0, 1) === 'Q') {
            $event->setCause($data['cause']);
        } elseif (!empty($data['cause'])) {
            $event->setCauseAsText($data['cause']);
        }
        
        // Set counts
        if (!empty($data['victim_count'])) {
            $event->setVictimCount($data['victim_count'], $data['victim_count_qualifier'] ?? null);
        }
        
        if (!empty($data['survivor_count'])) {
            $event->setSurvivorCount($data['survivor_count'], $data['survivor_count_qualifier'] ?? null);
        }
        
        if (!empty($data['death_count'])) {
            $event->setDeathCount($data['death_count'], $data['death_count_qualifier'] ?? null);
        }
        
        if (!empty($data['arrest_count'])) {
            $event->setArrestCount($data['arrest_count'], $data['arrest_count_qualifier'] ?? null);
        }
        
        // Set coordinates
        if (!empty($data['coordinates'])) {
            $event->addCoordinates($data['coordinates']);
        }
        
        // Set image
        if (!empty($data['image'])) {
            $event->addImage($data['image']);
        }
        
        // Set causes (array)
        if (!empty($data['selected_causes'])) {
            // Ensure causes is passed as an array
            $causes = $data['selected_causes'];
            if (is_string($causes)) {
                $causes = json_decode($causes, true) ?: [];
            }
            $event->addCauses($causes);
        }
        
        return $event;
    }
    
    /**
     * Set the point in time
     */
    public function setPointInTime(string $date, ?string $qualifier = null): void
    {
        $this->pointInTime = $date;
        $formattedDate = $this->formatDate($date);
        
        if ($formattedDate) {
            $pointInTimeProperty = $this->mappingProvider->getPropertyId('POINT_IN_TIME');
            $qualifierProperty = $this->mappingProvider->getPropertyId('SOURCING_CIRCUMSTANCES');
            
            $statement = [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => $pointInTimeProperty, // Point in time
                    'datavalue' => [
                        'value' => $formattedDate,
                        'type' => 'time'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ];
            
            // Add qualifier if provided
            if (!empty($qualifier)) {
                $this->addQualifierToStatement($statement, $qualifierProperty, $qualifier);
            }
            
            $this->data['claims'][$pointInTimeProperty] = [$statement];
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
            $startTimeProperty = $this->mappingProvider->getPropertyId('START_TIME');
            $qualifierProperty = $this->mappingProvider->getPropertyId('SOURCING_CIRCUMSTANCES');
            
            $statement = [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => $startTimeProperty, // Start time
                    'datavalue' => [
                        'value' => $formattedDate,
                        'type' => 'time'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ];
            
            // Add qualifier if provided
            if (!empty($qualifier)) {
                $this->addQualifierToStatement($statement, $qualifierProperty, $qualifier);
            }
            
            $this->data['claims'][$startTimeProperty] = [$statement];
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
            $endTimeProperty = $this->mappingProvider->getPropertyId('END_TIME');
            $qualifierProperty = $this->mappingProvider->getPropertyId('SOURCING_CIRCUMSTANCES');
            
            $statement = [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => $endTimeProperty, // End time
                    'datavalue' => [
                        'value' => $formattedDate,
                        'type' => 'time'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ];
            
            // Add qualifier if provided
            if (!empty($qualifier)) {
                $this->addQualifierToStatement($statement, $qualifierProperty, $qualifier);
            }
            
            $this->data['claims'][$endTimeProperty] = [$statement];
        }
    }
    
    /**
     * Set the location
     */
    public function setLocation(string $locationId): void
    {
        $this->location = $locationId;
        
        if (empty($locationId) || substr($locationId, 0, 1) !== 'Q') {
            return;
        }
        
        $locationProperty = $this->mappingProvider->getPropertyId('LOCATION');
        
        $this->data['claims'][$locationProperty] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => $locationProperty, // Location
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => $locationId,
                            'numeric-id' => intval(substr($locationId, 1))
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
     * Set the cause
     */
    public function setCause(string $causeId): void
    {
        if (empty($causeId) || substr($causeId, 0, 1) !== 'Q') {
            return;
        }
        
        $causeProperty = $this->mappingProvider->getPropertyId('HAS_CAUSE');
        
        $this->data['claims'][$causeProperty] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => $causeProperty, // Cause
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => $causeId,
                            'numeric-id' => intval(substr($causeId, 1))
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
     * Set cause as text
     */
    public function setCauseAsText(string $causeText): void
    {
        if (empty($causeText)) {
            return;
        }
        
        $authorStringProperty = $this->mappingProvider->getPropertyId('AUTHOR_STRING');
        
        $this->data['claims'][$authorStringProperty] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => $authorStringProperty, // Author string
                    'datavalue' => [
                        'value' => 'Ursache: ' . $causeText,
                        'type' => 'string'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ]
        ];
    }
    
    /**
     * Set the victim count
     */
    public function setVictimCount(int $count, ?string $qualifier = null): void
    {
        $this->victimCount = $count;
        
        $victimCountProperty = $this->mappingProvider->getPropertyId('VICTIM_COUNT');
        $qualifierProperty = $this->mappingProvider->getPropertyId('SOURCING_CIRCUMSTANCES');
        
        $statement = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => $victimCountProperty, // Number of victims
                'datavalue' => [
                    'value' => [
                        'amount' => '+' . $count,
                        'unit' => '1'
                    ],
                    'type' => 'quantity'
                ]
            ],
            'type' => 'statement',
            'rank' => 'normal'
        ];
        
        // Add qualifier if provided
        if (!empty($qualifier)) {
            $this->addQualifierToStatement($statement, $qualifierProperty, $qualifier);
        }
        
        $this->data['claims'][$victimCountProperty] = [$statement];
    }
    
    /**
     * Set the survivor count
     */
    public function setSurvivorCount(int $count, ?string $qualifier = null): void
    {
        $this->survivorCount = $count;
        
        $survivorCountProperty = $this->mappingProvider->getPropertyId('SURVIVOR_COUNT');
        $qualifierProperty = $this->mappingProvider->getPropertyId('SOURCING_CIRCUMSTANCES');
        
        $statement = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => $survivorCountProperty, // Number of survivors
                'datavalue' => [
                    'value' => [
                        'amount' => '+' . $count,
                        'unit' => '1'
                    ],
                    'type' => 'quantity'
                ]
            ],
            'type' => 'statement',
            'rank' => 'normal'
        ];
        
        // Add qualifier if provided
        if (!empty($qualifier)) {
            $this->addQualifierToStatement($statement, $qualifierProperty, $qualifier);
        }
        
        $this->data['claims'][$survivorCountProperty] = [$statement];
    }
    
    /**
     * Set the death count
     */
    public function setDeathCount(int $count, ?string $qualifier = null): void
    {
        $this->deathCount = $count;
        
        $deathCountProperty = $this->mappingProvider->getPropertyId('DEATH_COUNT');
        $qualifierProperty = $this->mappingProvider->getPropertyId('SOURCING_CIRCUMSTANCES');
        
        $statement = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => $deathCountProperty, // Number of deaths
                'datavalue' => [
                    'value' => [
                        'amount' => '+' . $count,
                        'unit' => '1'
                    ],
                    'type' => 'quantity'
                ]
            ],
            'type' => 'statement',
            'rank' => 'normal'
        ];
        
        // Add qualifier if provided
        if (!empty($qualifier)) {
            $this->addQualifierToStatement($statement, $qualifierProperty, $qualifier);
        }
        
        $this->data['claims'][$deathCountProperty] = [$statement];
    }
    
    /**
     * Set the arrest count
     */
    public function setArrestCount(int $count, ?string $qualifier = null): void
    {
        $this->arrestCount = $count;
        
        $arrestCountProperty = $this->mappingProvider->getPropertyId('ARREST_COUNT');
        $qualifierProperty = $this->mappingProvider->getPropertyId('SOURCING_CIRCUMSTANCES');
        
        $statement = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => $arrestCountProperty, // Number of arrests
                'datavalue' => [
                    'value' => [
                        'amount' => '+' . $count,
                        'unit' => '1'
                    ],
                    'type' => 'quantity'
                ]
            ],
            'type' => 'statement',
            'rank' => 'normal'
        ];
        
        // Add qualifier if provided
        if (!empty($qualifier)) {
            $this->addQualifierToStatement($statement, $qualifierProperty, $qualifier);
        }
        
        $this->data['claims'][$arrestCountProperty] = [$statement];
    }
}
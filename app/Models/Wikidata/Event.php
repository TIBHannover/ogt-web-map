<?php

namespace App\Models\Wikidata;

use Illuminate\Support\Facades\Log;

class Event extends BaseEntity
{
    // Wikidata entity type for Event (Q6983405 = event)
    protected $entityType = 'Q6983405';
    
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
     */
    public function __construct(string $title, string $description = '')
    {
        parent::__construct($title, $description);
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
            $statement = [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P585', // Point in time
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
                $this->addQualifierToStatement($statement, 'P1480', $qualifier);
            }
            
            $this->data['claims']['P585'] = [$statement];
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
            $statement = [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P580', // Start time
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
                $this->addQualifierToStatement($statement, 'P1480', $qualifier);
            }
            
            $this->data['claims']['P580'] = [$statement];
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
            $statement = [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P582', // End time
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
                $this->addQualifierToStatement($statement, 'P1480', $qualifier);
            }
            
            $this->data['claims']['P582'] = [$statement];
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
        
        $this->data['claims']['P276'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P276', // Location
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
        
        $this->data['claims']['P828'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P828', // Cause
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
        
        $this->data['claims']['P2093'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P2093', // Author string
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
        
        $statement = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => 'P1590', // Number of victims
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
            $this->addQualifierToStatement($statement, 'P1480', $qualifier);
        }
        
        $this->data['claims']['P1590'] = [$statement];
    }
    
    /**
     * Set the survivor count
     */
    public function setSurvivorCount(int $count, ?string $qualifier = null): void
    {
        $this->survivorCount = $count;
        
        $statement = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => 'P1561', // Number of survivors
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
            $this->addQualifierToStatement($statement, 'P1480', $qualifier);
        }
        
        $this->data['claims']['P1561'] = [$statement];
    }
    
    /**
     * Set the death count
     */
    public function setDeathCount(int $count, ?string $qualifier = null): void
    {
        $this->deathCount = $count;
        
        $statement = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => 'P1120', // Number of deaths
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
            $this->addQualifierToStatement($statement, 'P1480', $qualifier);
        }
        
        $this->data['claims']['P1120'] = [$statement];
    }
    
    /**
     * Set the arrest count
     */
    public function setArrestCount(int $count, ?string $qualifier = null): void
    {
        $this->arrestCount = $count;
        
        $statement = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => 'P5582', // Number of arrests
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
            $this->addQualifierToStatement($statement, 'P1480', $qualifier);
        }
        
        $this->data['claims']['P5582'] = [$statement];
    }
}
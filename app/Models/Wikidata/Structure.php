<?php

namespace App\Models\Wikidata;

use Illuminate\Support\Facades\Log;

class Structure extends BaseEntity
{
    // Wikidata entity type for Structure (Q811979 = architectural structure)
    protected $entityType = 'Q811979';
    
    // Additional structure properties
    protected $inception;
    protected $demolishedDate;
    protected $architect;
    protected $height;
    
    /**
     * Constructor
     */
    public function __construct(string $title, string $description = '')
    {
        parent::__construct($title, $description);
    }
    
    /**
     * Create a Structure model from array data
     */
    public static function fromArray(array $data): self
    {
        $structure = new self($data['title'] ?? '', $data['description'] ?? '');
        
        // Set construction date
        if (!empty($data['inception'])) {
            $structure->setInception($data['inception']);
        }
        
        // Set demolished date
        if (!empty($data['demolished_date'])) {
            $structure->setDemolishedDate($data['demolished_date']);
        }
        
        // Set architect
        if (!empty($data['architect']) && substr($data['architect'], 0, 1) === 'Q') {
            $structure->setArchitect($data['architect']);
        } elseif (!empty($data['architect'])) {
            $structure->setArchitectAsText($data['architect']);
        }
        
        // Set height
        if (!empty($data['height'])) {
            $structure->setHeight($data['height']);
        }
        
        // Set coordinates
        if (!empty($data['coordinates'])) {
            $structure->addCoordinates($data['coordinates']);
        }
        
        // Set image
        if (!empty($data['image'])) {
            $structure->addImage($data['image']);
        }
        
        return $structure;
    }
    
    /**
     * Set the inception (construction date)
     */
    public function setInception(string $date): void
    {
        $this->inception = $date;
        $formattedDate = $this->formatDate($date);
        
        if ($formattedDate) {
            $this->data['claims']['P571'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P571', // Inception
                        'datavalue' => [
                            'value' => $formattedDate,
                            'type' => 'time'
                        ]
                    ],
                    'type' => 'statement',
                    'rank' => 'normal'
                ]
            ];
        }
    }
    
    /**
     * Set the demolished date
     */
    public function setDemolishedDate(string $date): void
    {
        $this->demolishedDate = $date;
        $formattedDate = $this->formatDate($date);
        
        if ($formattedDate) {
            $this->data['claims']['P576'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P576', // Dissolved, abolished or demolished
                        'datavalue' => [
                            'value' => $formattedDate,
                            'type' => 'time'
                        ]
                    ],
                    'type' => 'statement',
                    'rank' => 'normal'
                ]
            ];
        }
    }
    
    /**
     * Set the architect
     */
    public function setArchitect(string $architectId): void
    {
        $this->architect = $architectId;
        
        if (empty($architectId) || substr($architectId, 0, 1) !== 'Q') {
            return;
        }
        
        $this->data['claims']['P84'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P84', // Architect
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => $architectId,
                            'numeric-id' => intval(substr($architectId, 1))
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
     * Set architect as text (when no Wikidata ID is available)
     */
    public function setArchitectAsText(string $architectName): void
    {
        if (empty($architectName)) {
            return;
        }
        
        $this->data['claims']['P2093'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P2093', // Author string
                    'datavalue' => [
                        'value' => $architectName,
                        'type' => 'string'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ]
        ];
    }
    
    /**
     * Set the height
     */
    public function setHeight(float $height): void
    {
        $this->height = $height;
        
        $this->data['claims']['P2048'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P2048', // Height
                    'datavalue' => [
                        'value' => [
                            'amount' => '+' . (float)$height,
                            'unit' => 'http://www.wikidata.org/entity/Q11573' // Meter
                        ],
                        'type' => 'quantity'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ]
        ];
    }
}
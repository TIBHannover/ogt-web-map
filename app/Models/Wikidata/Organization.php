<?php

namespace App\Models\Wikidata;

use Illuminate\Support\Facades\Log;

class Organization extends BaseEntity
{
    // Wikidata entity type for Organization (Q43229 = organization)
    protected $entityType = 'Q43229';
    
    // Additional organization properties
    protected $inception;
    protected $dissolvedDate;
    protected $headquarters;
    
    /**
     * Constructor
     */
    public function __construct(string $title, string $description = '')
    {
        parent::__construct($title, $description);
    }
    
    /**
     * Create an Organization model from array data
     */
    public static function fromArray(array $data): self
    {
        $organization = new self($data['title'] ?? '', $data['description'] ?? '');
        
        // Set inception date
        if (!empty($data['inception'])) {
            $organization->setInception($data['inception']);
        }
        
        // Set dissolution date
        if (!empty($data['dissolved_date'])) {
            $organization->setDissolvedDate($data['dissolved_date']);
        }
        
        // Set headquarters
        if (!empty($data['headquarters_location']) && !empty($data['headquarters_location']['id'])) {
            $organization->setHeadquarters($data['headquarters_location']['id']);
        } elseif (!empty($data['headquarters_location']) && is_string($data['headquarters_location'])) {
            $organization->setHeadquartersAsText($data['headquarters_location']);
        }
        
        // Set coordinates
        if (!empty($data['coordinates'])) {
            $organization->addCoordinates($data['coordinates']);
        }
        
        // Set image
        if (!empty($data['image'])) {
            $organization->addImage($data['image']);
        }
        
        return $organization;
    }
    
    /**
     * Set the inception (foundation date)
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
     * Set the dissolved date
     */
    public function setDissolvedDate(string $date): void
    {
        $this->dissolvedDate = $date;
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
     * Set the headquarters location
     */
    public function setHeadquarters(string $locationId): void
    {
        $this->headquarters = $locationId;
        
        if (empty($locationId) || substr($locationId, 0, 1) !== 'Q') {
            return;
        }
        
        $this->data['claims']['P159'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P159', // Headquarters location
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
     * Set headquarters as text (when no Wikidata ID is available)
     */
    public function setHeadquartersAsText(string $locationName): void
    {
        if (empty($locationName)) {
            return;
        }
        
        $this->data['claims']['P1448'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P1448', // Official name
                    'datavalue' => [
                        'value' => [
                            'text' => $locationName,
                            'language' => 'de'
                        ],
                        'type' => 'monolingualtext'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ]
        ];
    }
}
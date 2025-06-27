<?php

namespace App\Models\Wikidata\Source;

use App\Services\Wikidata\WikidataFormatterModels;

class ArchiveSource extends BaseSource
{
    protected $type = 'archive';
    protected $id;
    protected $inventoryNumber;
    
    /**
     * Constructor with optional data
     */
    public function __construct(string $title, string $description = '', array $data = [])
    {
        parent::__construct($title, $description);
        
        // Set additional properties if provided
        if (!empty($data['id'])) {
            $this->id = $data['id'];
        }
        
        if (!empty($data['inventoryNumber'])) {
            $this->inventoryNumber = $data['inventoryNumber'];
        }
    }
    
    /**
     * Generate formatted reference data
     */
    public function toReference(): array
    {
        $reference = [
            'hash' => 'reference-' . md5($this->title . ($this->id ?? '')),
            'snaks' => []
        ];
        
        // Basic data: Title (P1476)
        $reference['snaks']['P1476'] = [
            [
                'snaktype' => 'value',
                'property' => 'P1476',
                'datavalue' => [
                    'value' => [
                        'text' => $this->title,
                        'language' => 'de'
                    ],
                    'type' => 'monolingualtext'
                ]
            ]
        ];
        
        // For archive sources with a valid Wikidata ID
        if (!empty($this->id) && substr($this->id, 0, 1) === 'Q') {
            // Archive reference (P485)
            $reference['snaks']['P485'] = [
                [
                    'snaktype' => 'value',
                    'property' => 'P485',
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => $this->id,
                            'numeric-id' => intval(substr($this->id, 1))
                        ],
                        'type' => 'wikibase-entityid'
                    ]
                ]
            ];
        }
        
        // Inventory number (P217)
        if (!empty($this->inventoryNumber)) {
            $reference['snaks']['P217'] = [
                [
                    'snaktype' => 'value',
                    'property' => 'P217',
                    'datavalue' => [
                        'value' => $this->inventoryNumber,
                        'type' => 'string'
                    ]
                ]
            ];
        }
        
        // Add description as a note (P2093)
        if (!empty($this->description)) {
            $reference['snaks']['P2093'] = [
                [
                    'snaktype' => 'value',
                    'property' => 'P2093',
                    'datavalue' => [
                        'value' => $this->description,
                        'type' => 'string'
                    ]
                ]
            ];
        }
        
        return $reference;
    }
    
    /**
     * Generate source claim for entity
     */
    public function toClaim(): array
    {
        // Only create claims for archives with valid Wikidata IDs
        if (empty($this->id) || substr($this->id, 0, 1) !== 'Q') {
            return [];
        }
        
        $claim = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => WikidataFormatterModels::WIKIDATA_ARCHIVE,
                'datavalue' => [
                    'value' => [
                        'entity-type' => 'item',
                        'id' => $this->id,
                        'numeric-id' => intval(substr($this->id, 1))
                    ],
                    'type' => 'wikibase-entityid'
                ]
            ],
            'type' => 'statement',
            'rank' => 'normal'
        ];
        
        // Add qualifiers
        $claim['qualifiers'] = $this->createQualifiers();
        
        return $claim;
    }
    
    /**
     * Create qualifiers for the claim
     */
    protected function createQualifiers(): array
    {
        $qualifiers = [];
        
        // Inventory number
        if (!empty($this->inventoryNumber)) {
            $qualifiers[WikidataFormatterModels::WIKIDATA_INVENTORY_NUMBER] = [
                [
                    'snaktype' => 'value',
                    'property' => WikidataFormatterModels::WIKIDATA_INVENTORY_NUMBER,
                    'datavalue' => [
                        'value' => $this->inventoryNumber,
                        'type' => 'string'
                    ]
                ]
            ];
        }
        
        return $qualifiers;
    }
    
    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['title'] ?? '',
            $data['description'] ?? '',
            $data
        );
    }
}
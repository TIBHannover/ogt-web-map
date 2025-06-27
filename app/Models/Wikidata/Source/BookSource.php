<?php

namespace App\Models\Wikidata\Source;

use App\Services\Wikidata\WikidataFormatterModels;

class BookSource extends BaseSource
{
    protected $type = 'book';
    protected $id;
    protected $authors = [];
    protected $publishingDate;
    protected $year;
    protected $pages;
    
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
        
        if (!empty($data['authors'])) {
            $this->authors = $data['authors'];
        } elseif (!empty($data['author'])) {
            $this->authors = [$data['author']];
        }
        
        if (!empty($data['publishingDate'])) {
            $this->publishingDate = $data['publishingDate'];
        }
        
        if (!empty($data['year'])) {
            $this->year = $data['year'];
        }
        
        if (!empty($data['pages'])) {
            $this->pages = $data['pages'];
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
        
        // For book sources with a valid Wikidata ID
        if (!empty($this->id) && substr($this->id, 0, 1) === 'Q') {
            // Wikidata ID reference (P248)
            $reference['snaks']['P248'] = [
                [
                    'snaktype' => 'value',
                    'property' => 'P248',
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
        } else {
            // Add a note that this is a temporary source pending creation
            $reference['snaks']['P2093'][] = [
                'snaktype' => 'value',
                'property' => 'P2093',
                'datavalue' => [
                    'value' => 'Temporary source: ' . $this->title . ' (ID will be assigned upon approval)',
                    'type' => 'string'
                ]
            ];
        }
        
        // Page reference (P304)
        if (!empty($this->pages)) {
            $reference['snaks']['P304'] = [
                [
                    'snaktype' => 'value',
                    'property' => 'P304',
                    'datavalue' => [
                        'value' => $this->pages,
                        'type' => 'string'
                    ]
                ]
            ];
        }
        
        // Add description as a note (P2093)
        if (!empty($this->description)) {
            $reference['snaks']['P2093'][] = [
                'snaktype' => 'value',
                'property' => 'P2093',
                'datavalue' => [
                    'value' => $this->description,
                    'type' => 'string'
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
        // Only create claims for books with valid Wikidata IDs
        if (empty($this->id) || substr($this->id, 0, 1) !== 'Q') {
            return [];
        }
        
        $claim = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => WikidataFormatterModels::WIKIDATA_DESCRIBED_BY_SOURCE,
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
        
        // Page information
        if (!empty($this->pages)) {
            $qualifiers[WikidataFormatterModels::WIKIDATA_PAGE] = [
                [
                    'snaktype' => 'value',
                    'property' => WikidataFormatterModels::WIKIDATA_PAGE,
                    'datavalue' => [
                        'value' => $this->pages,
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
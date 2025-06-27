<?php

namespace App\Models\Wikidata\Source;

use App\Services\Wikidata\WikidataFormatterModels;

class UrlSource extends BaseSource
{
    protected $type = 'url';
    protected $url;
    protected $accessDate;
    
    /**
     * Constructor with optional data
     */
    public function __construct(string $title, string $url, string $description = '', array $data = [])
    {
        parent::__construct($title, $description);
        $this->url = $this->ensureUrlHasScheme($url);
        
        // Set additional properties if provided
        if (!empty($data['accessDate'])) {
            $this->accessDate = $data['accessDate'];
        }
    }
    
    /**
     * Generate formatted reference data
     */
    public function toReference(): array
    {
        $reference = [
            'hash' => 'reference-' . md5($this->url),
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
        
        // URL of the reference (P854)
        $reference['snaks']['P854'] = [
            [
                'snaktype' => 'value',
                'property' => 'P854',
                'datavalue' => [
                    'value' => $this->url,
                    'type' => 'string'
                ]
            ]
        ];
        
        // Access date (P813)
        if (!empty($this->accessDate)) {
            $accessDate = $this->formatDate($this->accessDate);
            if ($accessDate !== null) {
                $reference['snaks']['P813'] = [
                    [
                        'snaktype' => 'value',
                        'property' => 'P813',
                        'datavalue' => [
                            'value' => $accessDate,
                            'type' => 'time'
                        ]
                    ]
                ];
            }
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
        $claim = [
            'mainsnak' => [
                'snaktype' => 'value',
                'property' => WikidataFormatterModels::WIKIDATA_STATED_IN_URL,
                'datavalue' => [
                    'value' => $this->url,
                    'type' => 'string'
                ]
            ],
            'type' => 'statement',
            'rank' => 'normal'
        ];
        
        return $claim;
    }
    
    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['url'])) {
            throw new \InvalidArgumentException('URL is required for UrlSource');
        }
        
        return new self(
            $data['title'] ?? '',
            $data['url'],
            $data['description'] ?? '',
            $data
        );
    }
}
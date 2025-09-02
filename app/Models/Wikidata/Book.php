<?php

namespace App\Models\Wikidata;

use Illuminate\Support\Facades\Log;

class Book extends BaseEntity
{
    // Wikidata entity type for Book (Q7725634 = literary work)
    protected $entityType = 'Q7725634';
    
    // Additional book properties
    protected $title;
    protected $authors = [];
    protected $publishingDate;
    protected $year;
    
    /**
     * Constructor
     */
    public function __construct(string $title, string $description = '')
    {
        parent::__construct($title, $description);
        $this->title = $title;
    }
    
    /**
     * Add a source to the entity, overriding the base method to handle book sources specially
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
        
        // Add source as reference using parent method
        parent::addSource($source, $parentEntityId);
        
        // If it's a book source with an ID, also add it as a described-by-source claim
        if ($source instanceof \App\Models\Wikidata\Source\BookSource) {
            $sourceClaim = $source->toClaim();
            if (!empty($sourceClaim)) {
                if (!isset($this->data['claims']['P1343'])) {
                    $this->data['claims']['P1343'] = [];
                }
                $this->data['claims']['P1343'][] = $sourceClaim;
            }
        }
    }
    
    /**
     * Create a Book model from array data
     */
    public static function fromArray(array $data): self
    {
        $book = new self($data['title'] ?? '', $data['description'] ?? 'Literary work');
        
        // Set title
        if (!empty($data['title'])) {
            $book->setTitle($data['title']);
        }
        
        // Set authors - support both old (single author) and new (multiple authors) formats
        if (!empty($data['authors']) && is_array($data['authors'])) {
            foreach ($data['authors'] as $author) {
                $book->addAuthor($author);
            }
        } elseif (!empty($data['author'])) {
            $book->addAuthor($data['author']);
        }
        
        // Set publishing date
        if (!empty($data['publishingDate'])) {
            $book->setPublishingDate($data['publishingDate']);
        } elseif (!empty($data['year'])) {
            $book->setPublishingYear($data['year']);
        }
        
        return $book;
    }
    
    /**
     * Set the title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
        
        $this->data['claims']['P1476'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P1476', // Title
                    'datavalue' => [
                        'value' => [
                            'text' => $title,
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
    
    /**
     * Add an author
     */
    public function addAuthor(array $author): void
    {
        // Add to internal authors list
        $this->authors[] = $author;
        
        // For authors with a valid Wikidata ID
        if (isset($author['id']) && is_string($author['id']) && substr($author['id'], 0, 1) === 'Q') {
            // Initialize P50 claim array if it doesn't exist yet
            if (!isset($this->data['claims']['P50'])) {
                $this->data['claims']['P50'] = [];
            }
            
            // Add as Wikidata entity reference
            $this->data['claims']['P50'][] = [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P50', // Author
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => $author['id'],
                            'numeric-id' => intval(substr($author['id'], 1))
                        ],
                        'type' => 'wikibase-entityid'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ];
        } else {
            // For ALL other authors, use string representation
            // Get the author name
            $authorName = $author['label'] ?? $author['name'] ?? 
                trim((isset($author['firstName']) ? $author['firstName'] : '') . ' ' . 
                     (isset($author['lastName']) ? $author['lastName'] : ''));
            
            // Only add if we have a valid name
            if (!empty($authorName)) {
                // Initialize P2093 claim array if it doesn't exist yet
                if (!isset($this->data['claims']['P2093'])) {
                    $this->data['claims']['P2093'] = [];
                }
                
                $this->data['claims']['P2093'][] = [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P2093', // Author string
                        'datavalue' => [
                            'value' => $authorName,
                            'type' => 'string'
                        ]
                    ],
                    'type' => 'statement',
                    'rank' => 'normal'
                ];
            }
        }
    }
    
    /**
     * Set the publishing date
     */
    public function setPublishingDate(string $date): void
    {
        $this->publishingDate = $date;
        $formattedDate = $this->formatDate($date);
        
        if ($formattedDate) {
            $this->data['claims']['P577'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P577', // Publication date
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
     * Set the publishing year
     */
    public function setPublishingYear(string $year): void
    {
        $this->year = $year;
        
        try {
            // Format as date with year precision
            $date = new \DateTime("{$year}-01-01");
            $formattedDate = [
                'time' => "+{$year}-00-00T00:00:00Z",
                'timezone' => 0,
                'before' => 0,
                'after' => 0,
                'precision' => 9, // Year precision
                'calendarmodel' => 'http://www.wikidata.org/entity/Q1985727' // Gregorian calendar
            ];
            
            $this->data['claims']['P577'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P577', // Publication date
                        'datavalue' => [
                            'value' => $formattedDate,
                            'type' => 'time'
                        ]
                    ],
                    'type' => 'statement',
                    'rank' => 'normal'
                ]
            ];
        } catch (\Exception $e) {
            Log::warning('Error formatting year as date', [
                'year' => $year,
                'error' => $e->getMessage()
            ]);
        }
    }
}
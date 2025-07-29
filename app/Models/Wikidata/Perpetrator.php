<?php

namespace App\Models\Wikidata;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;

class Perpetrator extends Person
{
    /**
     * Constructor
     * 
     * @param string $title The perpetrator's name/title
     * @param string $description The perpetrator's description
     * @param EntityMappingProviderInterface|null $mappingProvider Optional mapping provider
     */
    public function __construct(string $title, string $description = '', ?EntityMappingProviderInterface $mappingProvider = null)
    {
        parent::__construct($title, $description, $mappingProvider);
        
        // Explicitly set the role property 
        $this->role = self::ROLE_PERPETRATOR;
        
        // Add the perpetrator role to this person
        $this->addPerpetratorRole();
    }
    
    /**
     * Create a Perpetrator model from array data
     */
    public static function fromArray(array $data): Person
    {
        // Log the input data for debugging
        Log::info("Creating Perpetrator from array data", [
            "has_title" => !empty($data['title']),
            "has_name" => !empty($data['name']),
            "has_description" => !empty($data['description']),
            "has_role" => !empty($data['role']),
            "has_add_role_claim" => !empty($data['_add_role_claim']),
            "data_keys" => array_keys($data)
        ]);
        
        // First create a regular person
        $person = parent::fromArray($data);
        
        // Now convert it to a perpetrator by creating a new perpetrator with the same data
        $perpetrator = new Perpetrator(
            $data['title'] ?? $data['name'] ?? '', 
            $data['description'] ?? '',
            $person->mappingProvider // Pass along the mapping provider
        );
        
        // Copy ALL data from the person object to ensure no properties are lost
        $perpetrator->data = $person->getData();
        
        // Add the perpetrator role - this adds the specialized perpetrator role claim
        $perpetrator->addPerpetratorRole();
        
        // Also ensure significant event is added if specified
        if (!empty($data['significant_event_id'])) {
            $perpetrator->addSignificantEvent($data['significant_event_id']);
            Log::info("Added significant event to perpetrator entity", [
                "event_id" => $data['significant_event_id']
            ]);
        }
        
        // Log the final data being returned
        Log::info("Completed Perpetrator entity creation", [
            "title" => $perpetrator->title,
            "has_label" => !empty($perpetrator->data['labels']),
            "has_role_claim" => !empty($perpetrator->data['claims'][$perpetrator->mappingProvider->getPropertyId('SUBJECT_HAS_ROLE')]),
            "claim_count" => count($perpetrator->data['claims'])
        ]);
        
        return $perpetrator;
    }
    
    /**
     * Add the perpetrator role to this person
     */
    protected function addPerpetratorRole(): void
    {
        $subjectHasRoleProperty = $this->mappingProvider->getPropertyId('SUBJECT_HAS_ROLE');
        $perpetratorId = $this->mappingProvider->getEntityId('PERPETRATOR');
        
        // Add "perpetrator of Nazism" to subject has role
        $this->data['claims'][$subjectHasRoleProperty] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => $subjectHasRoleProperty, // Subject has role
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => $perpetratorId, // Perpetrator of Nazism
                            'numeric-id' => intval(substr($perpetratorId, 1))
                        ],
                        'type' => 'wikibase-entityid'
                    ]
                ],
                'type' => 'statement',
                'rank' => 'normal'
            ]
        ];
    }
}
<?php

namespace App\Models\Wikidata;

use Illuminate\Support\Facades\Log;

class Victim extends Person
{
    /**
     * Constructor
     */
    public function __construct(string $title, string $description = '')
    {
        parent::__construct($title, $description);
        
        // Explicitly set the role property 
        $this->role = self::ROLE_VICTIM;
        
        // Add the victim role to this person
        $this->addVictimRole();
    }
    
    /**
     * Create a Victim model from array data
     */
    public static function fromArray(array $data): Person
    {
        // Log the input data for debugging
        Log::info("Creating Victim from array data", [
            "has_title" => !empty($data['title']),
            "has_name" => !empty($data['name']),
            "has_description" => !empty($data['description']),
            "has_role" => !empty($data['role']),
            "has_add_role_claim" => !empty($data['_add_role_claim']),
            "data_keys" => array_keys($data)
        ]);
        
        // First create a regular person
        $person = parent::fromArray($data);
        
        // Now convert it to a victim by creating a new victim with the same data
        $victim = new Victim($data['title'] ?? $data['name'] ?? '', $data['description'] ?? '');
        
        // Copy ALL data from the person object to ensure no properties are lost
        $victim->data = $person->getData();
        
        // Add the victim role - this adds the specialized victim role claim
        $victim->addVictimRole();
        
        // Also ensure significant event is added if specified
        if (!empty($data['significant_event_id'])) {
            $victim->addSignificantEvent($data['significant_event_id']);
            Log::info("Added significant event to victim entity", [
                "event_id" => $data['significant_event_id']
            ]);
        }
        
        // Log the final data being returned
        Log::info("Completed Victim entity creation", [
            "title" => $victim->title,
            "has_label" => !empty($victim->data['labels']),
            "has_role_claim" => !empty($victim->data['claims']['P2868']),
            "claim_count" => count($victim->data['claims'])
        ]);
        
        return $victim;
    }
    
    /**
     * Add the victim role to this person
     */
    protected function addVictimRole(): void
    {
        // Add "victim of Nazism" (Q2026714) to subject has role (P2868)
        $this->data['claims']['P2868'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P2868', // Subject has role
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => 'Q2026714', // Victim of Nazism
                            'numeric-id' => 2026714
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
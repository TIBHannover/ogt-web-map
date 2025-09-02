<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WikidataEntityCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The created Wikidata entity ID
     *
     * @var string
     */
    public $entityId;
    
    /**
     * The entity type (Q-code)
     *
     * @var string
     */
    public $entityType;
    
    /**
     * The parent entity ID (if any)
     *
     * @var string|null
     */
    public $parentEntityId;
    
    /**
     * The relationship type to the parent entity
     *
     * @var string|null
     */
    public $relationshipType;
    
    /**
     * Associated entities that were created
     *
     * @var array
     */
    public $createdEntities;
    
    /**
     * Original request data
     *
     * @var array
     */
    public $requestData;

    /**
     * Create a new event instance.
     *
     * @param string $entityId
     * @param string $entityType
     * @param array $createdEntities
     * @param string|null $parentEntityId
     * @param string|null $relationshipType
     * @param array $requestData
     * @return void
     */
    public function __construct(
        string $entityId,
        string $entityType,
        array $createdEntities = [],
        ?string $parentEntityId = null,
        ?string $relationshipType = null,
        array $requestData = []
    ) {
        $this->entityId = $entityId;
        $this->entityType = $entityType;
        $this->createdEntities = $createdEntities;
        $this->parentEntityId = $parentEntityId;
        $this->relationshipType = $relationshipType;
        $this->requestData = $requestData;
    }
}
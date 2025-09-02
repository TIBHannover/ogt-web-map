<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WikidataChildEntityCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The created entity ID
     *
     * @var string
     */
    public $entityId;

    /**
     * The entity type
     *
     * @var string
     */
    public $entityType;

    /**
     * The parent entity ID
     *
     * @var string
     */
    public $parentEntityId;

    /**
     * The relationship type to the parent entity
     *
     * @var string
     */
    public $relationshipType;

    /**
     * The original entity data
     *
     * @var array
     */
    public $entityData;

    /**
     * Create a new event instance.
     *
     * @param string $entityId The Wikidata entity ID
     * @param string $entityType The entity type
     * @param string $parentEntityId The parent entity ID
     * @param string $relationshipType The relationship type to the parent
     * @param array $entityData The original entity data
     * @return void
     */
    public function __construct(
        string $entityId,
        string $entityType,
        string $parentEntityId,
        string $relationshipType,
        array $entityData
    ) {
        $this->entityId = $entityId;
        $this->entityType = $entityType;
        $this->parentEntityId = $parentEntityId;
        $this->relationshipType = $relationshipType;
        $this->entityData = $entityData;
    }
}
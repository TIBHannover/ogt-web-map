<?php

namespace App\Models\Wikidata;

use App\Services\Wikidata\WikidataFormatterModels;

class EntityFactory
{
    /**
     * Create an entity model from array data based on the entity type
     *
     * @param array $data The entity data
     * @param string $type The entity type (Q-code)
     * @return BaseEntity An entity model
     * @throws \InvalidArgumentException if the entity type is not supported
     */
    public static function createFromArray(array $data, string $type): BaseEntity
    {
        switch ($type) {
            case WikidataFormatterModels::ENTITY_TYPE_PERSON:
                return Person::fromArray($data);
                
            case WikidataFormatterModels::ENTITY_TYPE_EVENT:
                return Event::fromArray($data);
                
            case WikidataFormatterModels::ENTITY_TYPE_ORGANIZATION:
                return Organization::fromArray($data);
                
            case WikidataFormatterModels::ENTITY_TYPE_STRUCTURE:
                return Structure::fromArray($data);
                
            case WikidataFormatterModels::ENTITY_TYPE_BOOK:
                return Book::fromArray($data);
                
            default:
                throw new \InvalidArgumentException("Unsupported entity type: {$type}");
        }
    }
    
    /**
     * Create a person entity with the specified role
     *
     * @param array $data The person data
     * @param string|null $role The role (victim/perpetrator) or null for a generic person
     * @return Person|Victim|Perpetrator A person entity model
     */
    public static function createPersonWithRole(array $data, ?string $role = null): Person
    {
        switch ($role) {
            case WikidataFormatterModels::ROLE_VICTIM:
                return Victim::fromArray($data);
                
            case WikidataFormatterModels::ROLE_PERPETRATOR:
                return Perpetrator::fromArray($data);
                
            default:
                return Person::fromArray($data);
        }
    }
}
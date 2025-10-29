<?php

namespace App\Models\Wikibase;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use Illuminate\Support\Facades\Log;

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
    public static function createFromArray(
        array $data,
        string $type,
    ): BaseEntity {
        // Get type ID from the mapping provider
        $mappingProvider = app(EntityMappingProviderInterface::class);

        switch ($type) {
            case $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Person",
            ):
                return Person::fromArray($data);

            case $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Event",
            ):
                return Event::fromArray($data);

            case $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Organization",
            ):
                return Organization::fromArray($data);

            case $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Structure",
            ):
                return Structure::fromArray($data);

            case $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Book",
            ):
                return Book::fromArray($data);

            case "Q5003624": // Memorial place
                return Memorial::fromArray($data);

            default:
                throw new \InvalidArgumentException(
                    "Unsupported entity type: {$type}",
                );
        }
    }

    /**
     * Create a person entity with the specified role
     *
     * @param array $data The person data
     * @param string|null $role The role (victim/perpetrator) or null for a generic person
     * @return Person|Victim|Perpetrator A person entity model
     */
    public static function createPersonWithRole(
        array $data,
        ?string $role = null,
    ): Person {
        $mappingProvider = app(EntityMappingProviderInterface::class);

        Log::info("Creating person with role via EntityFactory", [
            "role" => $role ?? "null",
            "has_title" => !empty($data["title"]),
            "has_name" => !empty($data["name"]),
            "has_temp_id" => !empty($data["tempId"]) ? $data["tempId"] : "none",
            "has_role_in_data" => !empty($data["role"]),
        ]);

        switch ($role) {
            case "victim":
                Log::info("Creating specialized Victim entity");
                return Victim::fromArray($data);

            case "perpetrator":
                Log::info("Creating specialized Perpetrator entity");
                return Perpetrator::fromArray($data);

            default:
                Log::info(
                    "Creating generic Person entity (role not specified or recognized)",
                );
                return Person::fromArray($data);
        }
    }
}

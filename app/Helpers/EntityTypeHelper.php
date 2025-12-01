<?php

namespace App\Helpers;

use App\Services\EntityMapping\EntityMappingProviderInterface;

class EntityTypeHelper
{
    /**
     * Get the display label for an entity type
     *
     * @param string $type The entity type code
     * @return string The human-readable label
     */
    public static function getEntityTypeLabel($type)
    {
        $mappingProvider = app(EntityMappingProviderInterface::class);

        $types = [
            $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Book",
            ) => "Literarisches Werk",
            $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Person",
            ) => "Person",
            $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Event",
            ) => "Ereignis",
            $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Organization",
            ) => "Organisation",
            $mappingProvider->getEntityTypeId(
                "App\\Models\\Wikibase\\Structure",
            ) => "Architektonisches Struktur",
            "Q5003624" => "Erinnerungsort",
            "Q106996250" => "Ort des Gestapoterrors",
            "book" => "Buch",
            "perpetrator" => "Täter*in",
            "victim" => "Opfer",
        ];

        return $types[$type] ?? $type;
    }
}

<?php

namespace App\Helpers;

use App\Services\Wikidata\WikidataFormatterModels;

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
        $types = [
            WikidataFormatterModels::ENTITY_TYPE_BOOK => 'Literarisches Werk',
            WikidataFormatterModels::ENTITY_TYPE_PERSON => 'Person',
            WikidataFormatterModels::ENTITY_TYPE_EVENT => 'Ereignis',
            WikidataFormatterModels::ENTITY_TYPE_ORGANIZATION => 'Organisation',
            WikidataFormatterModels::ENTITY_TYPE_STRUCTURE => 'Architektonisches Struktur',
            'Q5003624' => 'Erinnerungsort',
            'Q108047567' => 'Staatspolizeistelle',
            'Q108047541' => 'Staatspolizeiaußenstelle',
            'Q277565' => 'Arbeitserziehungslager',
            'Q40357' => 'Gefängnis',
            'book' => 'Buch',
            WikidataFormatterModels::ROLE_PERPETRATOR => 'Täter*in',
            WikidataFormatterModels::ROLE_VICTIM => 'Opfer'
        ];

        return $types[$type] ?? $type;
    }
}
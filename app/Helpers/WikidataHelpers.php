<?php

namespace App\Helpers;

use App\Http\Controllers\WikidataPropertyController;

/**
 * Format a Wikidata property from an entity for display
 *
 * @param array $entity The entity containing properties
 * @param string $propertyId The property ID to format
 * @param string|null $defaultLabel Default label to use if not found
 * @return array|null Formatted property data
 */
function formatWikidataProperty($entity, $propertyId, $defaultLabel = null) {
    $label = WikidataPropertyController::translatePropertyLabel($propertyId);

    if (!isset($entity[$propertyId]) || empty($entity[$propertyId])) {
        return null;
    }

    $property = $entity[$propertyId];

    if (!is_array($property)) {
        return [
            'label' => $label ?: ucfirst(str_replace('_', ' ', $propertyId)),
            'value' => $property,
            'id' => null
        ];
    }

    if (isset($property['id'])) {
        return [
            'label' => $label ?: ucfirst(str_replace('_', ' ', $propertyId)),
            'value' => $property['label'] ?? $property['id'],
            'id' => $property['id']
        ];
    }

    return null;
}

/**
 * Format a Wikidata property ID as a clickable link
 *
 * @param string $property The property to format (e.g. P31, Q42)
 * @param string $baseUrl Optional base URL for Wikidata entities
 * @return string Formatted HTML for the property
 */
function formatWikidataPropertyLink($property, $baseUrl = 'https://www.wikidata.org/entity/') {
    if (empty($property)) {
        return '';
    }
    
    // Check if the property is a valid Wikidata property (starts with P or Q)
    if (preg_match('/^[PQ]\d+$/', $property)) {
        $url = $baseUrl . $property;
        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $property . '</a>';
    }
    
    // Return the original value if it's not a valid Wikidata property
    return $property;
}
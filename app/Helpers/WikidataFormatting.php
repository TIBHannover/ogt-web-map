<?php

namespace App\Helpers;

use App\Http\Controllers\WikidataPropertyController as WDPropertyController;
use Illuminate\Support\Facades\Log;

/**
 * Format a Wikidata entity ID with its label as a clickable link
 *
 * @param string $id The entity ID (e.g. Q12345)
 * @param string $label The entity label to display
 * @param string $baseUrl Optional base URL for Wikidata entities
 * @return string Formatted HTML link to the entity
 */
function formatWikidataLink(
    $id,
    $label,
    $baseUrl = "https://www.wikidata.org/entity/",
) {
    if (empty($id)) {
        return $label ?? "";
    }

    // Check if the ID is a valid Wikidata ID (starts with Q or P)
    if (preg_match('/^[PQ]\d+$/', $id)) {
        $url = $baseUrl . $id;
        return '<a href="' .
            $url .
            '" target="_blank" rel="noopener noreferrer">' .
            htmlspecialchars($label) .
            " <small>(" .
            $id .
            ")</small></a>";
    }

    // Return the label if ID is not a valid Wikidata ID
    return htmlspecialchars($label);
}

/**
 * Format a Wikidata property from an entity object for display
 *
 * @param array $entity The entity containing properties
 * @param string $propertyId The property ID to format
 * @param string|null $defaultLabel Default label to use if not found
 * @return array|null Formatted property data
 */
function formatEntityProperty($entity, $propertyId, $defaultLabel = null)
{
    $label = WDPropertyController::translatePropertyLabel($propertyId);

    if (!isset($entity[$propertyId]) || empty($entity[$propertyId])) {
        return null;
    }

    $property = $entity[$propertyId];

    if (!is_array($property)) {
        return [
            "label" => $label ?: ucfirst(str_replace("_", " ", $propertyId)),
            "value" => $property,
            "id" => null,
        ];
    }

    if (isset($property["id"])) {
        return [
            "label" => $label ?: ucfirst(str_replace("_", " ", $propertyId)),
            "value" => $property["label"] ?? $property["id"],
            "id" => $property["id"],
        ];
    }

    return null;
}

/**
 * Get and format entity data for a Wikidata ID
 *
 * @param string $entityId The Wikidata entity ID
 * @return string Formatted HTML link or plain text
 */
function formatWikidataEntity($entityId)
{
    if (!$entityId) {
        return "";
    }

    try {
        if (!is_string($entityId) || !preg_match('/^[QP]\d+$/', $entityId)) {
            // If not a valid Wikidata ID, just return it as is
            return htmlspecialchars($entityId);
        }

        // Get entity data from Wikidata
        $entityData = WDPropertyController::getEntityData($entityId);
        $label = $entityId; // Default to the ID if no label is found

        if ($entityData && isset($entityData["label"])) {
            $label = $entityData["label"];
        }

        return formatWikidataLink($entityId, $label);
    } catch (\Exception $e) {
        Log::error("Error formatting Wikidata entity", [
            "entityId" => $entityId,
            "error" => $e->getMessage(),
        ]);

        // Return just the ID if there was an error
        return htmlspecialchars($entityId);
    }
}

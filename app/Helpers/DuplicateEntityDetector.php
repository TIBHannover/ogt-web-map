<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * Helper class for detecting and handling duplicate Wikidata entities
 */
class DuplicateEntityDetector
{
    /**
     * Check if the error response indicates that the entity already exists in Wikidata
     *
     * @param array|null $response The API response
     * @return bool Whether the error is due to a duplicate entity
     */
    public static function isDuplicateEntity(?array $response): bool
    {
        if (empty($response) || !isset($response['error']) || !isset($response['error']['info'])) {
            return false;
        }

        $errorInfo = $response['error']['info'];
        return (
            strpos($errorInfo, 'already has label') !== false &&
            strpos($errorInfo, 'using the same description text') !== false
        );
    }

    /**
     * Extract the existing entity ID from the Wikidata error message
     *
     * @param array|null $response The API response
     * @return string|null The extracted entity ID or null if not found
     */
    public static function extractExistingEntityId(?array $response): ?string
    {
        if (empty($response) || !isset($response['error']) || !isset($response['error']['info'])) {
            return null;
        }

        $errorInfo = $response['error']['info'];
        if (preg_match('/\[\[Q\d+\|?(Q\d+)?\]\]/', $errorInfo, $matches)) {
            // Extract the Q ID from the match
            $id = isset($matches[1]) && !empty($matches[1]) ? $matches[1] : substr($matches[0], 2, -2);
            return $id;
        }

        return null;
    }

    /**
     * Create a user-friendly error message for duplicate entity errors
     *
     * @param array|null $response The API response
     * @return string A user-friendly error message
     */
    public static function createDuplicateEntityMessage(?array $response): string
    {
        $entityId = self::extractExistingEntityId($response);

        $message = "Entity already exists in Wikidata";
        if ($entityId) {
            $message .= " with ID: " . $entityId . ". You can view it at https://www.wikidata.org/wiki/" . $entityId;
        }

        return $message;
    }

    /**
     * Log information about the duplicate entity
     *
     * @param array|null $response The API response
     * @param string $title The entity title (if available)
     * @param string $type The entity type (if available)
     * @param int|null $requestId The request ID (if available)
     * @return void
     */
    public static function logDuplicateEntityInfo(
        ?array $response,
        string $title = "Unknown",
        string $type = "Unknown",
        ?int $requestId = null
    ): void {
        $existingEntityId = self::extractExistingEntityId($response);

        Log::info("Duplicate entity detected", [
            "existing_entity_id" => $existingEntityId,
            "entity_data" => [
                "type" => $type,
                "title" => $title,
            ],
            "request_id" => $requestId,
        ]);
    }
}

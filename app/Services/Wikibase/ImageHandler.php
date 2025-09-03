<?php

namespace App\Services\Wikibase;

use Illuminate\Support\Facades\Log;

/**
 * Class ImageHandler
 * Handles image processing for Wikidata entity creation and updates
 */
class ImageHandler
{
    /**
     * Prepare an image filename for Wikidata API submission
     *
     * @param string|null $imageFilename The raw image filename
     * @param bool $forDisplay Whether this is for display (keeps File: prefix) or API submission (removes it)
     * @return string|null The processed image filename
     */
    public static function prepareImageFilename(?string $imageFilename, bool $forDisplay = false): ?string
    {
        if (empty($imageFilename)) {
            return null;
        }

        // Clean up the image filename - remove any URL components or path information
        if (preg_match("/[\/\\\]/", $imageFilename)) {
            $parts = preg_split("/[\/\\\]/", $imageFilename);
            $imageFilename = end($parts);
            Log::debug("Extracted filename from path", [
                "original" => $imageFilename,
                "extracted" => $imageFilename,
            ]);
        }

        // For display/storage: ensure the File: prefix is present
        if ($forDisplay) {
            if (strpos($imageFilename, "File:") !== 0 && strpos($imageFilename, "file:") !== 0) {
                $imageFilename = "File:" . $imageFilename;
            }
            return $imageFilename;
        }

        // For API submission: remove the File: prefix as Wikidata API doesn't accept it
        return str_replace(["File:", "file:"], "", $imageFilename);
    }

    /**
     * Extract image filename from entity data, checking multiple possible locations
     *
     * @param array $entityData The entity data array
     * @return string|null The image filename or null if not found
     */
    public static function extractImageFilename(array $entityData): ?string
    {
        // Try the most specific imageFilename field first (from new UI)
        if (!empty($entityData["imageFilename"])) {
            $imageFilename = $entityData["imageFilename"];
            Log::info("Found imageFilename in entity data", [
                "imageFilename" => $imageFilename,
            ]);
            return $imageFilename;
        }

        // Then try the direct image_filename field
        if (!empty($entityData["image_filename"])) {
            $imageFilename = $entityData["image_filename"];
            Log::info("Found image_filename in entity data", [
                "image_filename" => $imageFilename,
            ]);
            return $imageFilename;
        }

        // Then try the legacy image field
        if (!empty($entityData["image"])) {
            $imageFilename = $entityData["image"];
            Log::info("Found legacy image field in entity data", [
                "image" => $imageFilename,
            ]);
            return $imageFilename;
        }

        // Finally try the URL as a fallback and extract filename
        if (!empty($entityData["image_url"])) {
            $urlParts = explode("/", $entityData["image_url"]);
            $imageFilename = end($urlParts);
            Log::info("Extracted filename from image_url", [
                "image_url" => $entityData["image_url"],
                "extracted_filename" => $imageFilename,
            ]);
            return $imageFilename;
        }

        return null;
    }

    /**
     * Add an image claim to the formatted entity data for Wikidata submission
     *
     * @param array $formattedData The formatted entity data
     * @param string $imageFilename The raw image filename
     * @param string $imagePropertyId The property ID for images (usually P18)
     * @return array The updated formatted data
     */
    public static function addImageClaim(array $formattedData, string $imageFilename, string $imagePropertyId): array
    {
        // Ensure claims array exists
        if (empty($formattedData["claims"])) {
            $formattedData["claims"] = [];
        }

        // Remove File: prefix for API submission
        $processedFilename = self::prepareImageFilename($imageFilename, false);

        // Create properly formatted image claim
        $formattedData["claims"][$imagePropertyId] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => $imagePropertyId,
                    "datavalue" => [
                        "value" => $processedFilename,
                        "type" => "string",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        // Also store the raw image value in the image field
        $formattedData["image"] = $processedFilename;

        Log::info("Added image claim to formatted data", [
            "image" => $processedFilename,
            "property" => $imagePropertyId,
            "claim_structure" => json_encode($formattedData["claims"][$imagePropertyId]),
        ]);

        return $formattedData;
    }
}

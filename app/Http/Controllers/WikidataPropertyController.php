<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WikidataPropertyController extends Controller
{
    /**
     * Get entity label by Q number
     *
     * @param string $qNumber The Wikidata Q number (e.g. Q1715)
     * @param string $language The language code (default: 'de')
     * @return array The entity data with label and description
     */
    /**
     * Static version of getByQNumber for direct usage without instantiating the controller
     *
     * @param string $qNumber The Wikidata Q number
     * @param string $language The preferred language
     * @return array The entity data with label and description
     */
    public static function getEntityData($qNumber, $language = "de")
    {
        // Create a controller instance and delegate to instance method
        $controller = new self();
        return $controller->getByQNumber($qNumber, $language);
    }

    public function getByQNumber($qNumber, $language = "de")
    {
        try {
            $client = new Client();
            $url = "https://www.wikidata.org/w/api.php";

            // Log the request we're making
            Log::info("Fetching Wikidata entity", [
                "qNumber" => $qNumber,
                "language" => $language,
            ]);

            $response = $client->request("GET", $url, [
                "query" => [
                    "action" => "wbgetentities",
                    "ids" => $qNumber,
                    "languages" => "{$language}|en", // Try both German and English as fallback
                    "format" => "json",
                    "origin" => "*", // Required for CORS
                ],
                "headers" => [
                    "User-Agent" =>
                        "OGT-API/1.0 (https://www.gestapo-terror-orte.de/; admin@gestapo-terror-orte.de)",
                ],
                "timeout" => 10,
                "connect_timeout" => 5,
            ]);

            $data = json_decode($response->getBody(), true);

            // Log response success
            Log::debug("Wikidata API response received", [
                "qNumber" => $qNumber,
                "hasEntity" => isset($data["entities"][$qNumber]),
            ]);

            if (isset($data["entities"][$qNumber])) {
                $entity = $data["entities"][$qNumber];

                // Try to get label in preferred language, then English, then any available language
                $label = $qNumber;
                if (isset($entity["labels"][$language]["value"])) {
                    $label = $entity["labels"][$language]["value"];
                } elseif (isset($entity["labels"]["en"]["value"])) {
                    $label = $entity["labels"]["en"]["value"];
                } elseif (!empty($entity["labels"])) {
                    // Get first available label if preferred languages not found
                    $firstLang = array_key_first($entity["labels"]);
                    $label = $entity["labels"][$firstLang]["value"] ?? $qNumber;
                }

                // Same approach for description
                $description = null;
                if (isset($entity["descriptions"][$language]["value"])) {
                    $description = $entity["descriptions"][$language]["value"];
                } elseif (isset($entity["descriptions"]["en"]["value"])) {
                    $description = $entity["descriptions"]["en"]["value"];
                } elseif (!empty($entity["descriptions"])) {
                    $firstLang = array_key_first($entity["descriptions"]);
                    $description =
                        $entity["descriptions"][$firstLang]["value"] ?? null;
                }

                return [
                    "id" => $qNumber,
                    "label" => $label,
                    "description" => $description,
                ];
            }

            return [
                "id" => $qNumber,
                "label" => $qNumber,
                "description" => null,
            ];
        } catch (\Exception $e) {
            Log::error(
                "Error fetching Wikidata entity {$qNumber}: " .
                    $e->getMessage(),
            );
            return [
                "id" => $qNumber,
                "label" => $qNumber,
                "description" => null,
            ];
        }
    }

    /**
     * Wandelt eine Q-Nummer in ein lesbares Label um (mit Cache)
     *
     * @param string $qNumber Die Wikidata Q-Nummer
     * @param string $language Sprachcode (Standard: 'de')
     * @return string Das Label oder die Q-Nummer, falls nicht gefunden
     */
    public static function getQLabel($qNumber, $language = "de")
    {
        // Prüfen ob es eine Q-Nummer ist
        if (!$qNumber || !preg_match('/^Q\d+$/', $qNumber)) {
            return $qNumber;
        }

        // Cache-Key erstellen - using a common cache key regardless of language
        // to ensure we get at least some label
        $cacheKey = "wikidata_label_{$qNumber}";

        // Aus Cache abrufen, wenn vorhanden
        if (Cache::has($cacheKey)) {
            $label = Cache::get($cacheKey);
            Log::debug("Retrieved label from cache", [
                "qNumber" => $qNumber,
                "label" => $label,
            ]);
            return $label;
        }

        try {
            // Use static method for convenience
            $data = self::getEntityData($qNumber, $language);

            $label = $data["label"] ?? $qNumber;

            // Log successful label retrieval
            if ($label !== $qNumber) {
                Log::info(
                    "Successfully retrieved label for {$qNumber}: {$label}",
                );
            } else {
                Log::warning(
                    "Could not retrieve proper label for {$qNumber}, using Q number as fallback",
                );
            }

            // Im Cache speichern (für 7 Tage)
            Cache::put($cacheKey, $label, 60 * 24 * 7);

            return $label;
        } catch (\Exception $e) {
            Log::error(
                "Fehler beim Abrufen des Labels für {$qNumber}: " .
                    $e->getMessage(),
                [
                    "qNumber" => $qNumber,
                    "trace" => $e->getTraceAsString(),
                ],
            );

            // Store the error in cache to avoid repeated failed requests
            // but with a shorter expiration (1 hour)
            Cache::put($cacheKey, $qNumber, 60);

            return "{$qNumber}"; // Return as string to ensure it's displayed properly
        }
    }

    /**
     * Translate property ID to human-readable German label
     *
     * @param string $propertyId The property ID (e.g. 'place_of_birth')
     * @return string The translated label
     */
    public static function translatePropertyLabel($propertyId)
    {
        $translations = [
            "place_of_birth" => "Geburtsort",
            "place_of_death" => "Sterbeort",
            "place_of_detention" => "Haftort",
            "nationality" => "Staatsangehörigkeit",
            "gender" => "Geschlecht",
            "birthDate" => "Geburtsdatum",
            "deathDate" => "Sterbedatum",
            "date_of_birth" => "Geburtsdatum",
            "birth" => "Geburtsdatum",
            "date_of_death" => "Sterbedatum",
            "death" => "Sterbedatum",
            "FirstName" => "Vorname",
            "LastName" => "Nachname",
            // Weitere Übersetzungen hier hinzufügen
        ];

        return $translations[$propertyId] ??
            ucfirst(str_replace("_", " ", $propertyId));
    }

    /**
     * Helper to get a property with translated label and human-readable value
     *
     * @param array $data The entity data containing the property
     * @param string $propertyId The property ID
     * @param bool $isWikidataId Whether the value is a Wikidata ID
     * @return array|null The formatted property or null if not present
     */
    public static function getFormattedProperty(
        $data,
        $propertyId,
        $isWikidataId = true,
    ) {
        if (!isset($data[$propertyId]) || empty($data[$propertyId])) {
            return null;
        }

        $property = $data[$propertyId];
        $label = self::translatePropertyLabel($propertyId);

        if (is_array($property) && isset($property["id"]) && $isWikidataId) {
            // Property is a Wikidata entity
            return [
                "id" => $property["id"],
                "label" => $label,
                "value" => self::getQLabel($property["id"]),
            ];
        }

        // Property is a simple value
        return [
            "label" => $label,
            "value" => $property,
        ];
    }
}

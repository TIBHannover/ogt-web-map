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
    public function getByQNumber($qNumber, $language = "de")
    {
        try {
            $client = new Client();
            $url = "https://www.wikidata.org/w/api.php";

            $response = $client->request("GET", $url, [
                "query" => [
                    "action" => "wbgetentities",
                    "ids" => $qNumber,
                    "languages" => $language,
                    "format" => "json",
                ],
                "timeout" => 10,
                "connect_timeout" => 5,
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data["entities"][$qNumber])) {
                $entity = $data["entities"][$qNumber];

                return [
                    "id" => $qNumber,
                    "label" =>
                        $entity["labels"][$language]["value"] ?? $qNumber,
                    "description" =>
                        $entity["descriptions"][$language]["value"] ?? null,
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

        // Cache-Key erstellen
        $cacheKey = "wikidata_label_{$qNumber}_{$language}";

        // Aus Cache abrufen, wenn vorhanden
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Neue Instanz des Controllers erstellen für den API-Aufruf
            $controller = new self();
            $data = $controller->getByQNumber($qNumber, $language);

            $label = $data["label"] ?? $qNumber;

            // Im Cache speichern (für 7 Tage)
            Cache::put($cacheKey, $label, 60 * 24 * 7);

            return $label;
        } catch (\Exception $e) {
            Log::error(
                "Fehler beim Abrufen des Labels für {$qNumber}: " .
                    $e->getMessage(),
            );
            return $qNumber;
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

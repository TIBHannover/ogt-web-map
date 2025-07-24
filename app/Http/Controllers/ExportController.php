<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Clients\WikidataClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    protected $wikidataClient;

    public function __construct(WikidataClient $wikidataClient)
    {
        $this->wikidataClient = $wikidataClient;
    }

    /**
     * Export minimal places data as CSV (only specific types)
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportPlacesMinimal($output, $separator = ",")
    {
        // Write header row for places (same as regular places export)
        fputcsv(
            $output,
            [
                "ID",
                "Bezeichnung",
                "Beschreibung",
                "Typ",
                "Koordinaten",
                "Adresse",
                "Gründungsdatum",
                "Auflösungsdatum",
                "Webseite",
                "Betreiber",
                "Opfer",
                "Täter",
                "Bedeutende Ereignisse",
                "nachgeordnete Organisationen",
                "nachgeordnete Organisationen",
                "nachgeordnete Organisationen",
                "nachgeordnete Organisationen",
                "nachgeordnete Organisationen",
                "nachgeordnete Organisationen",
                "nachgeordnete Organisationen",
            ],
            $separator,
        );

        // Get places data from Wikidata
        $placesData = $this->wikidataClient->queryPlaces();

        if (!empty($placesData["results"]["bindings"])) {
            $places = $this->wikidataClient->mergeItemsData(
                $placesData["results"]["bindings"],
            );

            // Define allowed place types
            $allowedTypes = [
                "Arbeitserziehungslager",
                "Gefängnis",
                "Erweitertes Polizeigefängnis",
            ];

            // Write data rows, filtering by place type
            foreach ($places as $place) {
                if (!isset($place["instances"])) {
                    continue; // Skip if no type information
                }

                $isAllowedType = false;
                foreach ($place["instances"] as $instance) {
                    if (
                        isset($instance["value"]) &&
                        in_array($instance["value"], $allowedTypes)
                    ) {
                        $isAllowedType = true;
                        break;
                    }
                }

                // Skip if not one of the allowed types
                if (!$isAllowedType) {
                    continue;
                }

                $row = [
                    $place["id"] ?? "",
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractFirstDate($place["inceptionDates"] ?? []),
                    $this->extractFirstDate($place["dissolvedDates"] ?? []),
                    $this->extractFirstValue($place["officialWebsite"] ?? []),
                    $this->extractMultipleValues($place["operators"] ?? []),
                    $this->extractMultipleValues($place["victims"] ?? []),
                    $this->extractMultipleValues($place["perpetrators"] ?? []),
                    $this->extractMultipleValues(
                        $place["significantEvents"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                ];

                fputcsv($output, $row, $separator, $separator);
            }

            // Add a blank row between sections
            fputcsv($output, [""], $separator, $separator);
        }
    }

    /**
     * Export places data as CSV without Nazi crime events
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportPlacesWithoutEvents($output, $separator = ",")
    {
        // Write header row for places (same as regular places export)
        fputcsv(
            $output,
            [
                "ID",
                "Bezeichnung",
                "Beschreibung",
                "Typ",
                "Koordinaten",
                "Adresse",
                "Gründungsdatum",
                "Auflösungsdatum",
                "Webseite",
                "Betreiber",
                "Opfer",
                "Täter",
                "Bedeutende Ereignisse",
                "nachgeordnete Organisationen",
            ],
            $separator,
        );

        // Get places data from Wikidata
        $placesData = $this->wikidataClient->queryPlaces();

        if (!empty($placesData["results"]["bindings"])) {
            $places = $this->wikidataClient->mergeItemsData(
                $placesData["results"]["bindings"],
            );

            // Write data rows, filtering out Nazi crime events (Q6983405)
            foreach ($places as $place) {
                // Skip if this is a Nazi crime event
                if (isset($place["instances"])) {
                    $isNaziCrime = false;
                    foreach ($place["instances"] as $instance) {
                        if (
                            isset($instance["id"]) &&
                            $instance["id"] === "Q6983405"
                        ) {
                            $isNaziCrime = true;
                            break;
                        }
                    }
                    if ($isNaziCrime) {
                        continue; // Skip this place as it's a Nazi crime event
                    }
                }

                $row = [
                    $place["id"] ?? "",
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractFirstDate($place["inceptionDates"] ?? []),
                    $this->extractFirstDate($place["dissolvedDates"] ?? []),
                    $this->extractFirstValue($place["officialWebsite"] ?? []),
                    $this->extractMultipleValues($place["operators"] ?? []),
                    $this->extractMultipleValues($place["victims"] ?? []),
                    $this->extractMultipleValues($place["perpetrators"] ?? []),
                    $this->extractMultipleValues(
                        $place["significantEvents"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                ];

                fputcsv($output, $row, $separator);
            }

            // Add a blank row between sections
            fputcsv($output, [""], $separator);
        }
    }

    /**
     * Export places data as CSV filtered by specific place type
     *
     * @param resource $output The output stream
     * @param string $placeType The type of place to filter by
     * @return void
     */
    protected function exportPlacesByType($output, $placeType, $separator = ",")
    {
        // Write header row for places (same as regular places export)
        fputcsv(
            $output,
            [
                "ID",
                "Bezeichnung",
                "Beschreibung",
                "Typ",
                "Koordinaten",
                "Adresse",
                "Gründungsdatum",
                "Auflösungsdatum",
                "Webseite",
                "Betreiber",
                "Opfer",
                "Täter",
                "Bedeutende Ereignisse",
                "nachgeordnete Organisationen",
            ],
            $separator,
        );

        // Debug info row for diagnosing issues
        if ($placeType === "Erinnerungsort") {
            fputcsv(
                $output,
                [
                    "DEBUG INFO",
                    "Searching for type: " . $placeType,
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                ],
                $separator,
            );
        }

        // Get places data from Wikidata
        $placesData = $this->wikidataClient->queryPlaces();

        if (!empty($placesData["results"]["bindings"])) {
            $places = $this->wikidataClient->mergeItemsData(
                $placesData["results"]["bindings"],
            );

            // Track if we found any matches
            $foundMatch = false;

            // Track all place types for debugging
            $allTypes = [];

            // Write data rows, filtering by place type
            foreach ($places as $place) {
                if (!isset($place["instances"])) {
                    continue; // Skip if no type information
                }

                // For Erinnerungsorte debugging, collect all types
                if ($placeType === "Erinnerungsort") {
                    foreach ($place["instances"] as $instance) {
                        if (
                            isset($instance["value"]) &&
                            !in_array($instance["value"], $allTypes)
                        ) {
                            $allTypes[] = $instance["value"];
                        }
                    }
                }

                $isTargetType = false;
                foreach ($place["instances"] as $instance) {
                    if (
                        isset($instance["value"]) &&
                        $instance["value"] === $placeType
                    ) {
                        $isTargetType = true;
                        $foundMatch = true;
                        break;
                    }
                }

                // Skip if not the target type
                if (!$isTargetType) {
                    continue;
                }

                $row = [
                    $place["id"] ?? "",
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractFirstDate($place["inceptionDates"] ?? []),
                    $this->extractFirstDate($place["dissolvedDates"] ?? []),
                    $this->extractFirstValue($place["officialWebsite"] ?? []),
                    $this->extractMultipleValues($place["operators"] ?? []),
                    $this->extractMultipleValues($place["victims"] ?? []),
                    $this->extractMultipleValues($place["perpetrators"] ?? []),
                    $this->extractMultipleValues(
                        $place["significantEvents"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                ];

                fputcsv($output, $row, $separator);
            }

            // Add debug info if no matches found
            if ($placeType === "Erinnerungsort" && !$foundMatch) {
                fputcsv(
                    $output,
                    [
                        "DEBUG INFO",
                        "No matches found for type: " . $placeType,
                        "Available types: " . implode(", ", $allTypes),
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ],
                    $separator,
                );
            }

            // Add a blank row between sections
            fputcsv($output, [""], $separator);
        }
    }

    /**
     * Export Ereignisse (events) data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportEvents($output, $separator = ",")
    {
        fputcsv(
            $output,
            [
                "ID",
                "Bezeichnung",
                "Beschreibung",
                "Typ",
                "Koordinaten",
                "Adresse",
                "Gründungsdatum",
                "Auflösungsdatum",
                "Webseite",
                "Betreiber",
                "Geschädigte",
                "Täter*Innen",
                "Bedeutende Ereignisse",
                "nachgeordnete Organisationen",
            ],
            $separator,
        );

        // Get places data from Wikidata
        $placesData = $this->wikidataClient->queryPlaces();

        if (!empty($placesData["results"]["bindings"])) {
            $places = $this->wikidataClient->mergeItemsData(
                $placesData["results"]["bindings"],
            );

            foreach ($places as $place) {
                if (isset($place["instances"])) {
                    $isNaziCrime = false;
                    foreach ($place["instances"] as $instance) {
                        if (
                            isset($instance["id"]) &&
                            $instance["id"] === "Q6983405"
                        ) {
                            $isNaziCrime = true;
                            break;
                        }
                    }
                    if (!$isNaziCrime) {
                        continue;
                    }
                } else {
                    continue;
                }

                $row = [
                    $place["id"] ?? "",
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractFirstDate($place["inceptionDates"] ?? []),
                    $this->extractFirstDate($place["dissolvedDates"] ?? []),
                    $this->extractFirstValue($place["officialWebsite"] ?? []),
                    $this->extractMultipleValues($place["operators"] ?? []),
                    $this->extractMultipleValues($place["victims"] ?? []),
                    $this->extractMultipleValues($place["perpetrators"] ?? []),
                    $this->extractMultipleValues(
                        $place["significantEvents"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                ];

                fputcsv($output, $row, $separator);
            }

            // Add a blank row between sections
            fputcsv($output, [""], $separator);
        }
    }

    /**
     * Export Staatspolizeileitstellen data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportStaatspolizeileitstellen($output, $separator = ",")
    {
        $this->exportPlacesByType(
            $output,
            "Staatspolizeileitstelle",
            $separator,
            $separator,
        );
    }

    /**
     * Export Staatspolizeistellen data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportStaatspolizeistellen($output, $separator = ",")
    {
        $this->exportPlacesByType(
            $output,
            "Staatspolizeistelle",
            $separator,
            $separator,
        );
    }

    /**
     * Export Außendienstellen data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportAussendienststellen($output, $separator = ",")
    {
        $this->exportPlacesByType(
            $output,
            "Außendienststelle",
            $separator,
            $separator,
        );
    }

    /**
     * Export Gefängnisse data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportGefaengnisse($output, $separator = ",")
    {
        $this->exportPlacesByType($output, "Gefängnis", $separator);
    }

    /**
     * Export Erweiterte Polizeigefu00e4ngnisse data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportErweitertesPolizeigefaengnis(
        $output,
        $separator = ",",
    ) {
        $this->exportPlacesByType(
            $output,
            "Erweitertes Polizeigefängnis",
            $separator,
        );
    }

    /**
     * Export Arbeitserziehungslager data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportArbeitserziehungslager($output, $separator = ",")
    {
        $this->exportPlacesByType(
            $output,
            "Arbeitserziehungslager",
            $separator,
        );
    }

    /**
     * Export Erinnerungsorte data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportErinnerungsorte($output, $separator = ",")
    {
        // Try with an alternative spelling that might be in the data
        $this->exportPlacesByType($output, "Gedenkstätte", $separator);
    }

    /**
     * Show the export interface
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view("admin.export.index");
    }

    /**
     * Export data as CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function export(Request $request)
    {
        $type = $request->input("type", "all");
        $format = $request->input("format", "csv");

        // If Excel format is requested, use the ExcelExportController
        if ($format === "excel") {
            return app(ExcelExportController::class)->exportExcel($request);
        }

        // For CSV format, continue with the current implementation
        $filename = "wikidata_export_" . date("Y-m-d_His") . ".csv";
        $separator = ",";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => 'attachment; filename="' . $filename . '"',
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        ];

        // Create the CSV with a callback that will write content
        return Response::stream(
            function () use ($type, $separator) {
                $output = fopen("php://output", "w");

                // Add UTF-8 BOM to fix Excel encoding issues
                fputs($output, "\xEF\xBB\xBF");

                if ($type == "places" || $type == "all") {
                    $this->exportPlaces($output, $separator);
                } elseif ($type == "places_no_events") {
                    $this->exportPlacesWithoutEvents($output, $separator);
                } elseif ($type == "places_minimal") {
                    $this->exportPlacesMinimal($output, $separator);
                } elseif ($type == "staatspolizeileitstellen") {
                    $this->exportStaatspolizeileitstellen($output, $separator);
                } elseif ($type == "staatspolizeistellen") {
                    $this->exportStaatspolizeistellen($output, $separator);
                } elseif ($type == "aussendienststellen") {
                    $this->exportAussendienststellen($output, $separator);
                } elseif ($type == "gefaengnisse") {
                    $this->exportGefaengnisse($output, $separator);
                } elseif ($type == "erweitertes_polizeigefaengnis") {
                    $this->exportErweitertesPolizeigefaengnis(
                        $output,
                        $separator,
                    );
                } elseif ($type == "arbeitserziehungslager") {
                    $this->exportArbeitserziehungslager($output, $separator);
                } elseif ($type == "ereignisse") {
                    $this->exportEvents($output, $separator);
                } elseif ($type == "erinnerungsorte") {
                    $this->exportErinnerungsorte($output, $separator);
                }

                if ($type == "persons" || $type == "all") {
                    $this->exportPersons($output, $separator);
                } elseif ($type == "persons_perpetrators") {
                    $this->exportPersonsPerpetrators($output, $separator);
                } elseif ($type == "persons_victims") {
                    $this->exportPersonsVictims($output, $separator);
                }

                fclose($output);
            },
            200,
            $headers,
        );
    }

    /**
     * Export places data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportPlaces($output, $separator = ",")
    {
        // Write header row for places
        fputcsv(
            $output,
            [
                "ID",
                "Bezeichnung",
                "Beschreibung",
                "Typ",
                "Koordinaten",
                "Adresse",
                "Gründungsdatum",
                "Auflösungsdatum",
                "Webseite",
                "Betreiber",
                "Opfer",
                "Täter",
                "Bedeutende Ereignisse",
                "nachgeordnete Organisationen",
            ],
            $separator,
        );

        // Get places data from Wikidata
        $placesData = $this->wikidataClient->queryPlaces();

        if (!empty($placesData["results"]["bindings"])) {
            $places = $this->wikidataClient->mergeItemsData(
                $placesData["results"]["bindings"],
            );

            // Write data rows
            foreach ($places as $place) {
                $row = [
                    $place["id"] ?? "",
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractFirstDate($place["inceptionDates"] ?? []),
                    $this->extractFirstDate($place["dissolvedDates"] ?? []),
                    $this->extractFirstValue($place["officialWebsite"] ?? []),
                    $this->extractMultipleValues($place["operators"] ?? []),
                    $this->extractMultipleValues($place["victims"] ?? []),
                    $this->extractMultipleValues($place["perpetrators"] ?? []),
                    $this->extractMultipleValues(
                        $place["significantEvents"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                ];

                fputcsv($output, $row, $separator);
            }

            // Add a blank row between sections
            fputcsv($output, [""], $separator);
        }
    }

    /**
     * Combine lat and lng coordinates into a single string
     *
     * @param array $coordinatesData
     * @return string
     */
    protected function combineCoordinates(array $coordinatesData): string
    {
        if (empty($coordinatesData)) {
            return "";
        }

        $coordinates = [];
        foreach ($coordinatesData as $item) {
            if (
                isset($item["value"]) &&
                is_array($item["value"]) &&
                isset($item["value"]["lat"]) &&
                isset($item["value"]["lng"])
            ) {
                // Format as latitude, longitude - more common for spreadsheet use
                $coordinates[] =
                    $item["value"]["lat"] . ", " . $item["value"]["lng"];
            }
        }

        // Join multiple coordinates with semicolons to keep them in a single column
        return implode("; ", $coordinates);
    }

    /**
     * Export persons data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportPersons($output, $separator = ",")
    {
        // Write header row for persons
        fputcsv(
            $output,
            [
                "ID",
                "Name",
                "Beschreibung",
                "Geschlecht",
                "Vorname",
                "Nachname",
                "Geburtsdatum",
                "Sterbedatum",
                "Geburtsort",
                "Sterbeort",
                "Staatsangehu00f6rigkeit",
                "Rollen",
                "Arbeitgeber",
                "Haftorte",
            ],
            $separator,
        );

        // Get persons data from Wikidata
        $personsData = $this->wikidataClient->queryPersons();

        if (!empty($personsData["results"]["bindings"])) {
            $persons = $this->wikidataClient->mergeItemsData(
                $personsData["results"]["bindings"],
            );

            // First, try to group persons by subject roles (P2868)
            $processedPersons = [];

            // Process each person directly without relying on grouping
            foreach ($persons as $personId => $person) {
                // Determine person type based on subjectRoles if available
                $personType = "Unknown";
                $subjectRoles = $this->extractMultipleValues(
                    $person["subjectRoles"] ?? [],
                );

                // Process every person regardless of grouping
                $row = [
                    $person["id"] ?? "",
                    $person["label"] ?? "",
                    $person["description"] ?? "",
                    $this->extractFirstValue($person["gender"] ?? []),
                    $this->extractFirstValue($person["givenName"] ?? []),
                    $this->extractFirstValue($person["familyName"] ?? []),
                    $this->extractFirstDate($person["dateOfBirth"] ?? []),
                    $this->extractFirstDate($person["dateOfDeath"] ?? []),
                    $this->extractFirstValue($person["placeOfBirth"] ?? []),
                    $this->extractFirstValue($person["placeOfDeath"] ?? []),
                    $this->extractMultipleValues($person["citizenships"] ?? []),
                    $subjectRoles,
                    $this->extractMultipleValues($person["employers"] ?? []),
                    $this->extractMultipleValues(
                        $person["detentionPlaces"] ?? [],
                    ),
                ];

                fputcsv($output, $row, $separator);
                $processedPersons[] = $personId;
            }
        }
    }

    /**
     * Extract multiple values from a property array and join them
     *
     * @param array $propertyData
     * @return string
     */
    protected function extractMultipleValues(array $propertyData): string
    {
        if (empty($propertyData)) {
            return "";
        }

        $values = [];
        foreach ($propertyData as $item) {
            if (isset($item["value"]) && !empty($item["value"])) {
                if (is_array($item["value"])) {
                    // Handle special cases like coordinates
                    if (
                        isset($item["value"]["lat"]) &&
                        isset($item["value"]["lng"])
                    ) {
                        $values[] =
                            $item["value"]["lat"] . "," . $item["value"]["lng"];
                    }
                } else {
                    // Clean up any commas to prevent CSV field confusion
                    $values[] = str_replace(",", ";", $item["value"]);
                }
            }
        }

        // Use semicolons as separator within a single cell to prevent CSV field confusion
        return implode("; ", $values);
    }

    /**
     * Export perpetrator persons data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportPersonsPerpetrators($output, $separator = ",")
    {
        // Write header row for persons (same as regular persons export)
        fputcsv(
            $output,
            [
                "ID",
                "Name",
                "Beschreibung",
                "Geschlecht",
                "Vorname",
                "Nachname",
                "Geburtsdatum",
                "Sterbedatum",
                "Geburtsort",
                "Sterbeort",
                "Staatsangehörigkeit",
                "Rollen",
                "Arbeitgeber",
                "Haftorte",
            ],
            $separator,
        );

        // Get persons data from Wikidata
        $personsData = $this->wikidataClient->queryPersons();

        if (!empty($personsData["results"]["bindings"])) {
            $persons = $this->wikidataClient->mergeItemsData(
                $personsData["results"]["bindings"],
            );

            // Process each person and check if they are a perpetrator
            foreach ($persons as $personId => $person) {
                $isPerpetrator = false;

                // Check subject roles for perpetrator indicators
                if (isset($person["subjectRoles"])) {
                    foreach ($person["subjectRoles"] as $role) {
                        // Check for perpetrator role ID (Q111080573)
                        if (
                            isset($role["id"]) &&
                            $role["id"] === "Q111080573"
                        ) {
                            $isPerpetrator = true;
                            break;
                        }

                        // Check for perpetrator role in role description
                        if (
                            isset($role["value"]) &&
                            (stripos($role["value"], "perpetrator") !== false ||
                                stripos($role["value"], "täter") !== false)
                        ) {
                            $isPerpetrator = true;
                            break;
                        }
                    }
                }

                // Skip if not a perpetrator
                if (!$isPerpetrator) {
                    continue;
                }

                $row = [
                    $person["id"] ?? "",
                    $person["label"] ?? "",
                    $person["description"] ?? "",
                    $this->extractFirstValue($person["gender"] ?? []),
                    $this->extractFirstValue($person["givenName"] ?? []),
                    $this->extractFirstValue($person["familyName"] ?? []),
                    $this->extractFirstDate($person["dateOfBirth"] ?? []),
                    $this->extractFirstDate($person["dateOfDeath"] ?? []),
                    $this->extractFirstValue($person["placeOfBirth"] ?? []),
                    $this->extractFirstValue($person["placeOfDeath"] ?? []),
                    $this->extractMultipleValues($person["citizenships"] ?? []),
                    $this->extractMultipleValues($person["subjectRoles"] ?? []),
                    $this->extractMultipleValues($person["employers"] ?? []),
                    $this->extractMultipleValues(
                        $person["detentionPlaces"] ?? [],
                    ),
                ];

                fputcsv($output, $row, $separator);
            }

            // Add a blank row between sections
            fputcsv($output, [""], $separator);
        }
    }

    /**
     * Export victim persons data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportPersonsVictims($output, $separator = ",")
    {
        // Write header row for persons (same as regular persons export)
        fputcsv(
            $output,
            [
                "ID",
                "Name",
                "Beschreibung",
                "Geschlecht",
                "Vorname",
                "Nachname",
                "Geburtsdatum",
                "Sterbedatum",
                "Geburtsort",
                "Sterbeort",
                "Staatsangehu00f6rigkeit",
                "Rollen",
                "Arbeitgeber",
                "Haftorte",
            ],
            $separator,
        );

        // Get persons data from Wikidata
        $personsData = $this->wikidataClient->queryPersons();

        if (!empty($personsData["results"]["bindings"])) {
            $persons = $this->wikidataClient->mergeItemsData(
                $personsData["results"]["bindings"],
            );

            // Process each person and check if they are a victim
            foreach ($persons as $personId => $person) {
                $isVictim = false;

                // Check subject roles for victim indicators
                if (isset($person["subjectRoles"])) {
                    foreach ($person["subjectRoles"] as $role) {
                        // Check for victim role IDs (any of the victim IDs)
                        if (isset($role["id"])) {
                            $victimIds = [
                                "Q2026714",
                                "Q2744762",
                                "Q5883980",
                                "Q12409870",
                                "Q111510085",
                                "Q111510497",
                                "Q113980000",
                            ];
                            if (in_array($role["id"], $victimIds)) {
                                $isVictim = true;
                                break;
                            }
                        }

                        // Check for victim role in role description
                        if (
                            isset($role["value"]) &&
                            (stripos($role["value"], "victim") !== false ||
                                stripos($role["value"], "survivor") !== false ||
                                stripos($role["value"], "opfer") !== false)
                        ) {
                            $isVictim = true;
                            break;
                        }
                    }
                }

                // Additional check: look for victim indicators in person description
                if (!$isVictim && isset($person["description"])) {
                    $description = strtolower($person["description"]);
                    if (
                        strpos($description, "victim") !== false ||
                        strpos($description, "survivor") !== false ||
                        strpos($description, "opfer") !== false
                    ) {
                        $isVictim = true;
                    }
                }

                // Skip if not a victim
                if (!$isVictim) {
                    continue;
                }

                $row = [
                    $person["id"] ?? "",
                    $person["label"] ?? "",
                    $person["description"] ?? "",
                    $this->extractFirstValue($person["gender"] ?? []),
                    $this->extractFirstValue($person["givenName"] ?? []),
                    $this->extractFirstValue($person["familyName"] ?? []),
                    $this->extractFirstDate($person["dateOfBirth"] ?? []),
                    $this->extractFirstDate($person["dateOfDeath"] ?? []),
                    $this->extractFirstValue($person["placeOfBirth"] ?? []),
                    $this->extractFirstValue($person["placeOfDeath"] ?? []),
                    $this->extractMultipleValues($person["citizenships"] ?? []),
                    $this->extractMultipleValues($person["subjectRoles"] ?? []),
                    $this->extractMultipleValues($person["employers"] ?? []),
                    $this->extractMultipleValues(
                        $person["detentionPlaces"] ?? [],
                    ),
                ];

                fputcsv($output, $row, $separator);
            }

            // Add a blank row between sections
            fputcsv($output, [""], $separator);
        }
    }

    /**
     * Extract the first value from a property array
     *
     * @param array $propertyData
     * @return string
     */
    protected function extractFirstValue(array $propertyData): string
    {
        if (empty($propertyData)) {
            return "";
        }

        $firstItem = reset($propertyData);
        return $firstItem["value"] ?? "";
    }

    /**
     * Extract the first date value from a property array
     *
     * @param array $dateData
     * @return string
     */
    protected function extractFirstDate(array $dateData): string
    {
        if (empty($dateData)) {
            return "";
        }

        $firstItem = reset($dateData);
        $value = $firstItem["value"] ?? "";

        // If it's a full ISO date, format it nicely
        if (preg_match("/^\d{4}-\d{2}-\d{2}/", $value)) {
            return date("d.m.Y", strtotime($value));
        }

        return $value;
    }

    /**
     * Extract type values with 'Ort des Gestapoterrors' filtered out
     *
     * @param array $instancesData
     * @return string
     */
    protected function extractFilteredTypes(array $instancesData): string
    {
        if (empty($instancesData)) {
            return "";
        }

        $values = [];
        foreach ($instancesData as $item) {
            if (isset($item["value"]) && !empty($item["value"])) {
                // Skip "Ort des Gestapoterrors" type
                if ($item["value"] !== "Ort des Gestapoterrors") {
                    $values[] = $item["value"];
                }
            }
        }

        return implode("; ", $values);
    }
}

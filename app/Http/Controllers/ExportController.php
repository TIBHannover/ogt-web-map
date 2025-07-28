<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Clients\WikidataClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    protected $wikidataClient;

    /**
     * Cache for places data
     */
    protected $placesCache = null;

    /**
     * Cache for persons data
     */
    protected $personsCache = null;

    public function __construct(WikidataClient $wikidataClient)
    {
        $this->wikidataClient = $wikidataClient;
    }

    /**
     * Preprocess data for better performance
     */
    protected function preprocessData() {
        if ($this->placesCache === null) {
            $placesData = $this->wikidataClient->queryPlaces();
            if (!empty($placesData["results"]["bindings"])) {
                $this->placesCache = $this->wikidataClient->mergeItemsData(
                    $placesData["results"]["bindings"],
                );
            } else {
                $this->placesCache = [];
            }
        }
        
        if ($this->personsCache === null) {
            $personsData = $this->wikidataClient->queryPersons();
            if (!empty($personsData["results"]["bindings"])) {
                $this->personsCache = $this->wikidataClient->mergeItemsData(
                    $personsData["results"]["bindings"],
                );
            } else {
                $this->personsCache = [];
            }
        }
    }

    /**
     * Utility method to write CSV data with proper UTF-8 encoding
     * 
     * @param resource $handle File handle to write to
     * @param array $fields Array of fields to write
     * @param string $delimiter Delimiter character (default: semicolon)
     * @return void
     */
    protected function writeUtf8CsvLine($handle, $fields, $delimiter = ';')
    {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure = '"';
        $enclosure_esc = preg_quote($enclosure, '/');
        
        $output = '';
        foreach ($fields as $field) {
            // Ensure field is string
            $field = (string)$field;
            
            // Escape enclosure characters within the field
            $field = str_replace($enclosure, $enclosure . $enclosure, $field);
            
            // If the field contains the delimiter, enclosure, or newlines, enclose it
            if (preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field)) {
                $field = $enclosure . $field . $enclosure;
            }
            
            $output .= $field . $delimiter;
        }
        
        // Remove trailing delimiter and add a newline
        fputs($handle, substr($output, 0, -1) . "\n");
    }
    
    /**
     * Add a blank line to the CSV file
     * 
     * @param resource $handle File handle to write to
     * @param string $delimiter Delimiter character
     * @return void
     */
    protected function writeBlankLine($handle, $delimiter = ';')
    {
        fputs($handle, "\n");
    }

    /**
     * Export minimal places data as CSV (only specific types)
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportPlacesMinimal($output, $separator = ",", $csvWriter = null)
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
                "Leiter",
                "Personal",
                "Geschädigte",
                "Inhaftierte",
                "Erinnerungsorte",
                "nachgeordnete Organisationen",
            ],
            $separator,
        );

        // Get places data from cache
        $this->preprocessData();
        $places = $this->placesCache;

        if (!empty($places)) {

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

                $placeId = $place["id"] ?? "";
                $row = [
                    $placeId,
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractDateFromMultipleProperties(
                        $place["inceptionDates"] ?? [],  // P571 (inception)
                        $place["startTime"] ?? [],      // P580 (start time)
                        $place["pointInTime"] ?? [],    // P585 (point in time)
                        $place["openingDate"] ?? []     // P1619 (time period)
                    ),
                    // $this->extractFirstDate($place["dissolvedDates"] ?? []),  // Excluded as requested
                    $this->extractFirstValue($place["officialWebsite"] ?? []),
                    $this->extractDirectors($place["directors"] ?? []),
                    $this->getStaffForPlace($placeId),
                    $this->getVictimsForPlace($placeId),
                    $this->getPrisonersForPlace($placeId),
                    $this->getMemorialsForPlace($placeId),
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
    protected function exportPlacesWithoutEvents($output, $separator = ",", $csvWriter = null)
    {
        // Write header row for places (same as regular places export)
        $headerRow = [
            "ID",
            "Bezeichnung",
            "Beschreibung",
            "Typ",
            "Koordinaten",
            "Adresse",
            "Gründungsdatum",
            // "Auflösungsdatum",   // Excluded as requested
            "Webseite",
            "Leiter",
            "Personal",
            "Geschädigte",
            "Inhaftierte",
            "Erinnerungsorte",
            "nachgeordnete Organisationen",
        ];
        
        if ($csvWriter) {
            $csvWriter($output, $headerRow, $separator);
        } else {
            $this->writeUtf8CsvLine($output, $headerRow, $separator);
        }

        // Get places data from cache
        $this->preprocessData();
        $places = $this->placesCache;

        if (!empty($places)) {

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

                $placeId = $place["id"] ?? "";
                $row = [
                    $placeId,
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractDateFromMultipleProperties(
                        $place["inceptionDates"] ?? [],  // P571 (inception)
                        $place["startTime"] ?? [],      // P580 (start time)
                        $place["pointInTime"] ?? [],    // P585 (point in time)
                        $place["openingDate"] ?? []     // P1619 (time period)
                    ),
                    $this->extractFirstDate($place["dissolvedDates"] ?? []),
                    $this->extractFirstValue($place["officialWebsite"] ?? []),
                    $this->extractDirectors($place["directors"] ?? []),
                    $this->getStaffForPlace($placeId),
                    $this->getVictimsForPlace($placeId),
                    $this->getPrisonersForPlace($placeId),
                    $this->getMemorialsForPlace($placeId),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                ];

                // Write the data row using the appropriate writer
                if ($csvWriter) {
                    $csvWriter($output, $row, $separator);
                } else {
                    $this->writeUtf8CsvLine($output, $row, $separator);
                }
            }

            // Add a blank row between sections
            if ($csvWriter) {
                $csvWriter($output, [""], $separator);
            } else {
                $this->writeBlankLine($output, $separator);
            }
        }
    }

    /**
     * Export places data as CSV filtered by specific place type
     *
     * @param resource $output The output stream
     * @param string $placeType The type of place to filter by
     * @return void
     */
    protected function exportPlacesByType($output, $placeType, $separator = ",", $csvWriter = null)
    {
        // Write header row for places (same as regular places export)
        $headerRow = [
            "ID",
            "Bezeichnung",
            "Beschreibung",
            "Typ",
            "Koordinaten",
            "Adresse",
            "Gründungsdatum",
            "Auflösungsdatum",
            "Webseite",
            "Leiter",
            "Personal",
            "Geschädigte",
            "Inhaftierte",
            "Erinnerungsorte",
            "nachgeordnete Organisationen",
        ];
        
        if ($csvWriter) {
            $csvWriter($output, $headerRow, $separator);
        } else {
            $this->writeUtf8CsvLine($output, $headerRow, $separator);
        }

        // Debug info row for diagnosing issues
        if ($placeType === "Erinnerungsort") {
            $debugRow = [
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
            ];
            
            if ($csvWriter) {
                $csvWriter($output, $debugRow, $separator);
            } else {
                $this->writeUtf8CsvLine($output, $debugRow, $separator);
            }
        }

        // Get places data from cache
        $this->preprocessData();
        $places = $this->placesCache;

        if (!empty($places)) {
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

                $placeId = $place["id"] ?? "";
                $row = [
                    $placeId,
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
                    $this->extractDirectors($place["directors"] ?? []),
                    $this->getStaffForPlace($placeId),
                    $this->getVictimsForPlace($placeId),
                    $this->getPrisonersForPlace($placeId),
                    $this->getMemorialsForPlace($placeId),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                    $this->extractMultipleValues(
                        $place["subsidiaries"] ?? [],
                    ),
                ];

                // Write the data row using the appropriate writer
                if ($csvWriter) {
                    $csvWriter($output, $row, $separator);
                } else {
                    $this->writeUtf8CsvLine($output, $row, $separator);
                }
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
            if ($csvWriter) {
                $csvWriter($output, [""], $separator);
            } else {
                $this->writeBlankLine($output, $separator);
            }
        }
    }

    /**
     * Export Ereignisse (events) data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportEvents($output, $separator = ",", $csvWriter = null)
    {
        // Define header row with only the specified columns
        $headerRow = [
            "ID",
            "Bezeichnung",
            "Beschreibung",
            "Typ",
            "Koordinaten",
            "Adresse",
            "Startdatum",   // P571 (inception)
            "Enddatum",     // Renamed from Auflösungsdatum
            "Geschädigte",  // P8032
            "Täter*Innen",  // P8031
        ];
        
        // Write header using appropriate writer
        if ($csvWriter) {
            $csvWriter($output, $headerRow, $separator);
        } else {
            fputcsv($output, $headerRow, $separator);
        }

        // Get places data from cache
        $this->preprocessData();
        $places = $this->placesCache;

        if (!empty($places)) {
            foreach ($places as $place) {
                // Filter for Nazi crimes (Q6983405)
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

                $placeId = $place["id"] ?? "";
                
                // Get direct victims and perpetrators from Wikidata properties
                $victims = isset($place["victims"]) 
                    ? $this->extractDirectVictims($place["victims"]) 
                    : "";
                    
                $perpetrators = isset($place["perpetrators"]) 
                    ? $this->extractDirectPerpetrators($place["perpetrators"]) 
                    : "";
                
                $row = [
                    $placeId,
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractDateFromMultipleProperties(
                        $place["inceptionDates"] ?? [],  // P571 (inception)
                        $place["startTime"] ?? [],      // P580 (start time)
                        $place["pointInTime"] ?? [],    // P585 (point in time)
                        $place["openingDate"] ?? []     // P1619 (time period)
                    ),  // Startdatum
                    $this->extractFirstDate($place["endTime"] ?? []),  // Enddatum (P582)
                    $victims,       // Direct victims from P8032
                    $perpetrators,  // Direct perpetrators from P8031
                ];

                // Write row using appropriate writer
                if ($csvWriter) {
                    $csvWriter($output, $row, $separator);
                } else {
                    fputcsv($output, $row, $separator);
                }
            }

            // Add a blank row between sections
            if ($csvWriter) {
                $csvWriter($output, [""], $separator);
            } else {
                fputcsv($output, [""], $separator);
            }
        }
    }

    /**
     * Export Staatspolizeileitstellen data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportStaatspolizeileitstellen($output, $separator = ",", $csvWriter = null)
    {
        $this->exportPlacesByType(
            $output,
            "Staatspolizeileitstelle",
            $separator,
            $csvWriter
        );
    }

    /**
     * Export Staatspolizeistellen data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportStaatspolizeistellen($output, $separator = ",", $csvWriter = null)
    {
        $this->exportPlacesByType(
            $output,
            "Staatspolizeistelle",
            $separator,
            $csvWriter
        );
    }

    /**
     * Export Außendienstellen data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportAussendienststellen($output, $separator = ",", $csvWriter = null)
    {
        $this->exportPlacesByType(
            $output,
            "Außendienststelle",
            $separator,
            $csvWriter
        );
    }

    /**
     * Export Gefängnisse data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportGefaengnisse($output, $separator = ",", $csvWriter = null)
    {
        $this->exportPlacesByType($output, "Gefängnis", $separator, $csvWriter);
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
        $csvWriter = null
    ) {
        $this->exportPlacesByType(
            $output,
            "Erweitertes Polizeigefängnis",
            $separator,
            $csvWriter
        );
    }

    /**
     * Export Arbeitserziehungslager data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportArbeitserziehungslager($output, $separator = ",", $csvWriter = null)
    {
        $this->exportPlacesByType(
            $output,
            "Arbeitserziehungslager",
            $separator,
            $csvWriter
        );
    }

    /**
     * Export Erinnerungsorte data as CSV
     *
     * @param resource $output The output stream
     * @return void
     */
    protected function exportErinnerungsorte($output, $separator = ",", $csvWriter = null)
    {
        // Write header row for places - exclude unwanted columns
        $headerRow = [
            "ID",
            "Bezeichnung",
            "Beschreibung",
            "Typ",
            "Koordinaten",
            "Adresse",
            "Gründungsdatum",
            // "Auflösungsdatum",   // Excluded as requested
            "Webseite",
            // "Leiter",           // Excluded as requested
            // "Personal",         // Excluded as requested
            // "Geschädigte",      // Excluded as requested
            // "Inhaftierte",      // Excluded as requested
            // "Erinnerungsorte",  // Excluded as requested
            // "nachgeordnete Organisationen", // Excluded as requested
        ];
        
        if ($csvWriter) {
            $csvWriter($output, $headerRow, $separator);
        } else {
            $this->writeUtf8CsvLine($output, $headerRow, $separator);
        }

        // Get places data from cache
        $this->preprocessData();
        $places = $this->placesCache;

        if (!empty($places)) {
            // Track all place types for debugging
            $allTypes = [];
            $foundMatch = false;

            // Write data rows, filtering for memorial places
            foreach ($places as $place) {
                if (!isset($place["instances"])) {
                    continue; // Skip if no type information
                }

                // Collect all types for debugging
                foreach ($place["instances"] as $instance) {
                    if (
                        isset($instance["value"]) &&
                        !in_array($instance["value"], $allTypes)
                    ) {
                        $allTypes[] = $instance["value"];
                    }
                }

                // Check if this is a memorial (Gedenkstätte) 
                $isMemorial = false;
                foreach ($place["instances"] as $instance) {
                    if (
                        isset($instance["value"]) &&
                        $instance["value"] === "Gedenkstätte"
                    ) {
                        $isMemorial = true;
                        $foundMatch = true;
                        break;
                    }
                }

                // Skip if not a memorial
                if (!$isMemorial) {
                    continue;
                }

                $placeId = $place["id"] ?? "";
                $row = [
                    $placeId,
                    $place["label"] ?? "",
                    $place["description"] ?? "",
                    $this->extractFilteredTypes($place["instances"] ?? []),
                    $this->combineCoordinates($place["coordinates"] ?? []),
                    $this->extractMultipleValues(
                        $place["streetAddresses"] ?? [],
                    ),
                    $this->extractDateFromMultipleProperties(
                        $place["inceptionDates"] ?? [],  // P571 (inception)
                        $place["startTime"] ?? [],      // P580 (start time)
                        $place["pointInTime"] ?? [],    // P585 (point in time)
                        $place["openingDate"] ?? []     // P1619 (time period)
                    ),
                    // $this->extractFirstDate($place["dissolvedDates"] ?? []), // Excluded as requested
                    $this->extractFirstValue($place["officialWebsite"] ?? []),
                    // $this->extractDirectors($place["directors"] ?? []),       // Excluded as requested
                    // $this->getStaffForPlace($placeId),                       // Excluded as requested
                    // $this->getVictimsForPlace($placeId),                     // Excluded as requested
                    // $this->getPrisonersForPlace($placeId),                   // Excluded as requested
                    // $this->getMemorialsForPlace($placeId),                   // Excluded as requested
                    // $this->extractMultipleValues($place["subsidiaries"] ?? []), // Excluded as requested
                ];

                // Write the data row using the appropriate writer
                if ($csvWriter) {
                    $csvWriter($output, $row, $separator);
                } else {
                    $this->writeUtf8CsvLine($output, $row, $separator);
                }
            }

            // Add debug info if no matches found
            if (!$foundMatch) {
                fputcsv(
                    $output,
                    [
                        "DEBUG INFO",
                        "No matches found for type: Gedenkstätte",
                        "Available types: " . implode(", ", $allTypes),
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
            if ($csvWriter) {
                $csvWriter($output, [""], $separator);
            } else {
                $this->writeBlankLine($output, $separator);
            }
        }
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

        // Preprocess data to improve performance
        $this->preprocessData();

        // If Excel format is requested, use the ExcelExportController
        if ($format === "excel") {
            return app(ExcelExportController::class)->exportExcel($request);
        }

        // For CSV format, continue with the current implementation
        $filename = "wikidata_export_" . date("Y-m-d_His") . ".csv";
        $separator = ";"; // Using semicolon as separator for better Excel compatibility with German texts

        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => 'attachment; filename="' . $filename . '"',
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        ];

        // Create the CSV with a callback that will write content
        return Response::stream(
            function () use ($type, $separator) {
                // Open the output with UTF-8 encoding
                $output = fopen("php://output", "w");

                // Add UTF-8 BOM to fix Excel encoding issues
                fputs($output, "\xEF\xBB\xBF");

                // Helper function to write UTF-8 CSV lines properly
                $fputs_csv_utf8 = function($handle, $fields, $delimiter = ';') {
                    $delimiter_esc = preg_quote($delimiter, '/');
                    $enclosure = '"';
                    $enclosure_esc = preg_quote($enclosure, '/');
                    
                    $output = '';
                    foreach ($fields as $field) {
                        // Escape enclosure characters within the field
                        $field = str_replace($enclosure, $enclosure . $enclosure, $field);
                        
                        // If the field contains the delimiter, enclosure, or newlines, enclose it
                        if (preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field)) {
                            $field = $enclosure . $field . $enclosure;
                        }
                        
                        $output .= $field . $delimiter;
                    }
                    
                    // Remove trailing delimiter and add a newline
                    fputs($handle, substr($output, 0, -1) . "\n");
                };

                if ($type == "places" || $type == "all") {
                    $this->exportPlaces($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "places_no_events") {
                    $this->exportPlacesWithoutEvents($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "places_minimal") {
                    $this->exportPlacesMinimal($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "staatspolizeileitstellen") {
                    $this->exportStaatspolizeileitstellen($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "staatspolizeistellen") {
                    $this->exportStaatspolizeistellen($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "aussendienststellen") {
                    $this->exportAussendienststellen($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "gefaengnisse") {
                    $this->exportGefaengnisse($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "erweitertes_polizeigefaengnis") {
                    $this->exportErweitertesPolizeigefaengnis(
                        $output,
                        $separator,
                        $fputs_csv_utf8
                    );
                } elseif ($type == "arbeitserziehungslager") {
                    $this->exportArbeitserziehungslager($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "ereignisse") {
                    $this->exportEvents($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "erinnerungsorte") {
                    $this->exportErinnerungsorte($output, $separator, $fputs_csv_utf8);
                }

                if ($type == "persons" || $type == "all") {
                    $this->exportPersons($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "persons_perpetrators") {
                    $this->exportPersonsPerpetrators($output, $separator, $fputs_csv_utf8);
                } elseif ($type == "persons_victims") {
                    $this->exportPersonsVictims($output, $separator, $fputs_csv_utf8);
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
    protected function exportPlaces($output, $separator = ",", $csvWriter = null)
    {
        // Write header row for places
        $headerRow = [
            "ID",
            "Bezeichnung",
            "Beschreibung",
            "Typ",
            "Koordinaten",
            "Adresse",
            "Gründungsdatum",
            "Auflösungsdatum",
            "Webseite",
            "Leiter",
            "Personal",
            "Geschädigte",
            "Inhaftierte",
            "Erinnerungsorte",
            "nachgeordnete Organisationen",
        ];
        
        if ($csvWriter) {
            $csvWriter($output, $headerRow, $separator);
        } else {
            $this->writeUtf8CsvLine($output, $headerRow, $separator);
        }

        // Get places data from Wikidata
        $placesData = $this->wikidataClient->queryPlaces();

        if (!empty($placesData["results"]["bindings"])) {
            $places = $this->wikidataClient->mergeItemsData(
                $placesData["results"]["bindings"],
            );

            // Write data rows
            foreach ($places as $place) {
                $placeId = $place["id"] ?? "";
                $row = [
                    $placeId,
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
                    $this->extractDirectors($place["directors"] ?? []),
                    $this->getStaffForPlace($placeId),
                    $this->getVictimsForPlace($placeId),
                    $this->getPrisonersForPlace($placeId),
                    $this->getMemorialsForPlace($placeId),
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
    protected function exportPersons($output, $separator = ",", $csvWriter = null)
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

        // Get persons data from cache
        $this->preprocessData();
        $persons = $this->personsCache;

        if (!empty($persons)) {

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
    protected function exportPersonsPerpetrators($output, $separator = ",", $csvWriter = null)
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

        // Get persons data from cache
        $this->preprocessData();
        $persons = $this->personsCache;

        if (!empty($persons)) {

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
    protected function exportPersonsVictims($output, $separator = ",", $csvWriter = null)
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

        // Get persons data from cache
        $this->preprocessData();
        $persons = $this->personsCache;

        if (!empty($persons)) {

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

    /**
     * Extract director names from directors property
     *
     * @param array $directorsData
     * @return string
     */
    protected function extractDirectors(array $directorsData): string
    {
        if (empty($directorsData)) {
            return "";
        }

        $directors = [];
        foreach ($directorsData as $director) {
            if (isset($director["value"]) && !empty($director["value"])) {
                $directors[] = $director["value"];
            }
        }

        return implode("; ", $directors);
    }

    /**
     * Get staff information for a place
     *
     * @param string $placeId
     * @return string
     */
    protected function getStaffForPlace(string $placeId): string
    {
        $this->preprocessData();
        
        $staff = [];
        
        foreach ($this->personsCache as $perpetrator) {
            if (isset($perpetrator["employers"])) {
                foreach ($perpetrator["employers"] as $employer) {
                    if (isset($employer["id"]) && $employer["id"] === $placeId) {
                        $staff[] = $perpetrator["label"];
                        break;
                    }
                }
            }
        }
        
        return implode("; ", $staff);
    }
    
    /**
     * Get victims information for a place
     *
     * @param string $placeId
     * @return string
     */
    protected function getVictimsForPlace(string $placeId): string
    {
        $this->preprocessData();
        
        $victims = [];
        
        // Get victims directly linked to this place
        foreach ($this->personsCache as $person) {
            // Check for victim role
            $isVictim = false;
            if (isset($person["subjectRoles"])) {
                foreach ($person["subjectRoles"] as $role) {
                    // Check victim role IDs or descriptions
                    if (isset($role["id"])) {
                        $victimIds = [
                            "Q2026714", "Q2744762", "Q5883980", 
                            "Q12409870", "Q111510085", "Q111510497", 
                            "Q113980000"
                        ];
                        if (in_array($role["id"], $victimIds)) {
                            $isVictim = true;
                            break;
                        }
                    }
                    
                    if (isset($role["value"])) {
                        $roleValue = strtolower($role["value"]);
                        if (strpos($roleValue, "victim") !== false || 
                            strpos($roleValue, "survivor") !== false || 
                            strpos($roleValue, "opfer") !== false) {
                            $isVictim = true;
                            break;
                        }
                    }
                }
            }
            
            // Check if they're connected to this place
            if ($isVictim) {
                $isConnectedToPlace = false;
                
                // Check detention places
                if (isset($person["detentionPlaces"])) {
                    foreach ($person["detentionPlaces"] as $detentionPlace) {
                        if (isset($detentionPlace["id"]) && $detentionPlace["id"] === $placeId) {
                            $isConnectedToPlace = true;
                            break;
                        }
                    }
                }
                
                if ($isConnectedToPlace) {
                    $victims[] = $person["label"];
                }
            }
        }
        
        return implode("; ", $victims);
    }
    
    /**
     * Get prisoners information for a place
     *
     * @param string $placeId
     * @return string
     */
    protected function getPrisonersForPlace(string $placeId): string
    {
        $this->preprocessData();
        
        $prisoners = [];
        
        foreach ($this->personsCache as $person) {
            if (isset($person["detentionPlaces"])) {
                foreach ($person["detentionPlaces"] as $detentionPlace) {
                    if (isset($detentionPlace["id"]) && $detentionPlace["id"] === $placeId) {
                        $prisoners[] = $person["label"];
                        break;
                    }
                }
            }
        }
        
        return implode("; ", $prisoners);
    }
    
    /**
     * Get memorials information for a place
     *
     * @param string $placeId
     * @return string
     */
    protected function getMemorialsForPlace(string $placeId): string
    {
        $this->preprocessData();
        
        $memorials = [];
        
        foreach ($this->placesCache as $place) {
            $isMemorial = false;
            if (isset($place["instances"])) {
                foreach ($place["instances"] as $instance) {
                    if (isset($instance["value"]) && 
                        (strpos(strtolower($instance["value"]), "gedenkstätte") !== false || 
                         strpos(strtolower($instance["value"]), "memorial") !== false || 
                         strpos(strtolower($instance["value"]), "erinnerungsor") !== false)) {
                        $isMemorial = true;
                        break;
                    }
                }
            }
            
            if ($isMemorial && isset($place["commemorates"])) {
                foreach ($place["commemorates"] as $commemorated) {
                    if (isset($commemorated["id"]) && $commemorated["id"] === $placeId) {
                        $memorials[] = $place["label"];
                        break;
                    }
                }
            }
        }
        
        return implode("; ", $memorials);
    }

    /**
     * Extract perpetrators directly from P8031 property for events
     *
     * @param array $perpetratorsData The P8031 property data
     * @return string Formatted list of perpetrators
     */
    protected function extractDirectPerpetrators(array $perpetratorsData): string
    {
        $perpetrators = [];
        
        // Extract value from each statement
        foreach ($perpetratorsData as $statementId => $statement) {
            if (isset($statement['value'])) {
                $perpetrators[] = $statement['value'];
            }
        }
        
        return implode("; ", array_unique($perpetrators));
    }

    /**
     * Extract victims directly from P8032 property for events
     *
     * @param array $victimsData The P8032 property data
     * @return string Formatted list of victims
     */
    protected function extractDirectVictims(array $victimsData): string
    {
        $victims = [];
        
        // Extract value from each statement
        foreach ($victimsData as $statementId => $statement) {
            if (isset($statement['value'])) {
                $victims[] = $statement['value'];
            }
        }
        
        return implode("; ", array_unique($victims));
    }

    /**
     * Extract date from multiple possible date properties
     * 
     * @param array $inceptionDates P571 (inception) dates if available
     * @param array $startTimeDates P580 (start time) dates if available
     * @param array $pointInTimeDates P585 (point in time) dates if available
     * @param array $openingDates P1619 (time period/opening date) dates if available
     * @return string The earliest date from any of these properties
     */
    protected function extractDateFromMultipleProperties(
        array $inceptionDates = [], 
        array $startTimeDates = [], 
        array $pointInTimeDates = [],
        array $openingDates = []
    ): string
    {
        $allDates = [];
        
        // Extract dates from each property array
        if (!empty($inceptionDates)) {
            $allDates[] = $this->extractFirstDate($inceptionDates);
        }
        
        if (!empty($startTimeDates)) {
            $allDates[] = $this->extractFirstDate($startTimeDates);
        }
        
        if (!empty($pointInTimeDates)) {
            $allDates[] = $this->extractFirstDate($pointInTimeDates);
        }
        
        if (!empty($openingDates)) {
            $allDates[] = $this->extractFirstDate($openingDates);
        }
        
        // Filter out empty strings
        $allDates = array_filter($allDates, function($date) {
            return !empty($date);
        });
        
        // If no valid dates found, return empty string
        if (empty($allDates)) {
            return "";
        }
        
        // Sort dates chronologically and return the earliest one
        sort($allDates);
        return $allDates[0];
    }
}

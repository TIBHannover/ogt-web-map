<?php

namespace App\Http\Clients;

use Illuminate\Support\Facades\Log;

/**
 * FactGrid client for SPARQL queries
 */
class FactgridClient
{
    /**
     * Groups of FactGrid Q-Ids of person roles.
     */
    const PERSON_GROUPS_IDS = [
        "perpetrators" => [
            "Q1784661", // https://www.wikidata.org/wiki/Q111080573     perpetrator of the Nazi regime
        ],
        "victims" => [
            "Q1784663", // https://www.wikidata.org/wiki/Q2026714       victim of the Nazi regime
        ],
    ];

    /**
     * Groups of Factgrid Q-Ids of places
     */
    const PLACE_GROUPS_IDS = [
        "statePoliceOffices" => [
            "Q1784670", // Grenzpolizeiposten
            "Q1784671", // Grenzpolizeikommissariat
        ],
        "statePoliceHeadquarters" => [
            "Q1784668", // Staatspolizeistelle
            "Q1784673", // Staatspolizeileitstelle
        ],
        "prisons" => ["Q23223"], // Haftstätte
        "extPolicePrisons" => ["Q1784665"], // Erweitertes Polizeigefängnis
        "laborEducationCamps" => ["Q978663"], // Arbeitserziehungslager
        "fieldOffices" => ["Q1784669"], // Außendienststelle
        "events" => [
            "Q1784964", // Tag der Befreiung
            "Q1784963", // Novemberpogrome
            "Q1784962", // Bücherverbrennung
        ],
        "memorials" => ["Q1784672"], // Gedenkstätte
    ];

    /**
     * FactGrid property ID to semantic name mapping
     * These are used to transform property IDs like "P189" into meaningful names like "images"
     * Mapping based on: https://docs.google.com/document/d/1rBLt0j2ndhoRQ38FdrtiVoLVwCub8Uv7ALdXBUZ_LCM/edit
     */
    const PROPERTY_LABEL_OF_ID = [
        // Instance and Type Properties
        "P2" => "instances", // Instance of (Wikidata P31)

        // Image and Media Properties
        "P189" => "images", // Images (Wikidata P18)

        // Location Properties
        "P48" => "coordinates", // Coordinate location (Wikidata P625)
        "P47" => "locations", // Location (Wikidata P276)
        "P82" => "placeOfBirth", // Place of birth (Wikidata P19)
        "P168" => "placeOfDeath", // Place of death (Wikidata P20)
        "P153" => "streetAddresses", // Street addresses (Wikidata P6375)

        // Time Properties
        "P49" => "startTime", // Start time (Wikidata P580)
        "P50" => "endTime", // End time (Wikidata P582)
        "P106" => "pointInTime", // Point in time (Wikidata P585)
        "P77" => "dateOfBirth", // Date of birth (Wikidata P569)
        "P38" => "dateOfDeath", // Date of death (Wikidata P570)
        // "P?" => "inceptionDates", // Inception dates (Wikidata P571) - No mapping yet
        // "P?" => "dissolvedDates", // Dissolved dates (Wikidata P576) - No mapping yet
        // "P?" => "openingDate", // Opening date (Wikidata P1619) - No mapping yet

        // Person Properties
        "P154" => "gender", // Gender (Wikidata P21)
        "P616" => "citizenships", // Citizenships (Wikidata P27)
        "P247" => "familyName", // Family name (Wikidata P734)
        "P248" => "givenName", // Given name (Wikidata P735)

        // Organization Properties
        "P319" => "parentOrganizations", // Parent organization (Wikidata P749)
        "P419" => "subsidiaries", // Has subsidiaries (Wikidata P355)
        "P7" => "replacedBys", // Replaced by (Wikidata P1366)
        // "P?" => "replaces", // Replaces (Wikidata P1365) - No mapping yet

        // Role and Relationship Properties
        "P277" => "subjectRoles", // Subject has role (Wikidata P2868)
        "P315" => "employers", // Employer (Wikidata P108)
        "P14" => "directors", // Director (Wikidata P1037)
        "P216" => "detentionPlaces", // Place of detention (Wikidata P2632)
        "P550" => "significantEvents", // Significant event (Wikidata P793)

        // Event Specific Properties
        "P178" => "perpetrators", // Perpetrator (Wikidata P8031 - first mapping)
        "P66" => "perpetrators", // Perpetrator (Wikidata P8031 - second mapping)
        "P751" => "victims", // Victim (Wikidata P8032)
        "P464" => "hasCause", // Has cause (Wikidata P828)
        // "P?" => "numberOfCasualties", // Number of casualties (Wikidata P1590) - No mapping yet
        // "P?" => "numberOfSurvivors", // Number of survivors (Wikidata P1561) - No mapping yet
        // "P?" => "numberOfDeaths", // Number of deaths (Wikidata P1120) - No mapping yet
        // "P?" => "numberOfArrests", // Number of arrests (Wikidata P5582) - No mapping yet
        // "P?" => "employeeCounts", // Employee counts (Wikidata P1128) - No mapping yet
        // "P?" => "prisonerCounts", // Prisoner counts (Wikidata P5630) - No mapping yet

        // Memorial Site Properties
        "P534" => "operators", // Operators (Wikidata P137)
        "P280" => "hasUses", // Has use (Wikidata P366)
        "P547" => "commemorates", // Commemorates (Wikidata P547)

        // Source Properties
        "P129" => "describedBySources", // Described by source (Wikidata P1343)
        "P146" => "describedByUrlSources", // Described by URL (Wikidata P973)
        "P156" => "officialWebsite", // Official website (Wikidata P856)
    ];

    /**
     * FactGrid's qualifiers of queried locations and associated qualifier labels.
     * Mapping based on: https://docs.google.com/document/d/1rBLt0j2ndhoRQ38FdrtiVoLVwCub8Uv7ALdXBUZ_LCM/edit
     */
    const QUALIFIER_LABEL_OF_ID = [
        // Basic Qualifiers
        "P54" => "pages", // Similar to Wikidata P304 (pages)
        "P49" => "startTime", // Similar to Wikidata P580 (start time)
        "P50" => "endTime", // Similar to Wikidata P582 (end time)
        "P106" => "pointInTime", // Similar to Wikidata P585 (point in time)
        "P48" => "coordinate", // Similar to Wikidata P625 (coordinate)
        "P1243" => "mediaLegend", // Similar to Wikidata P2096 (media legend)

        // Date Range Qualifiers
        "P1124" => "earliestDate", // Similar to Wikidata P1319 (earliest date)
        "P1126" => "latestDate", // Similar to Wikidata P1326 (latest date)
        "P1123" => "earliestEndDate", // Similar to Wikidata P8554 (earliest end date)
        "P1125" => "latestStartDate", // Similar to Wikidata P8555 (latest start date)

        // Other Qualifiers
        "P155" => "sourcingCircumstance", // Similar to Wikidata P1480 (sourcing circumstance)
        "P153" => "streetAddress", // Similar to Wikidata P6375 (street address)
        "P10" => "inventoryNumber", // Similar to Wikidata P217 (inventory number)
    ];

    /**
     * Mappings for place subgroups
     */
    const PLACE_SUBGROUP_MAPPINGS = [
        // No specific mappings for FactGrid yet
    ];

    /**
     * Get places of Gestapo terror from FactGrid.
     *
     * @return array The query response
     */
    public function queryPlaces(): array
    {
        Log::debug("FactgridClient::queryPlaces - Starting method");

        // Simple combined query that reliably fetches items, instance types, and coordinates
        // We confirmed this query pattern works correctly in tests
        $query = '
SELECT
  ?item
  ?itemLabel
  ?itemDescription
  ?property
  ?statement
  ?propertyValue
  ?propertyValueLabel
  ?propertyTimePrecision
  ?qualifier
  ?qualifierValue
  ?qualifierValueLabel
  ?qualifierTimePrecision
  ?coordinates
WHERE {
  {
    ?item p:P2 ?itemStatement.
    ?itemStatement ps:P2 wd:Q1784677.
  }
  UNION
  {
    ?item wdt:P550 ?location.
    ?location wdt:P2 wd:Q1784677.
  }
  UNION
  {
    ?item wdt:P2 wd:Q6983405;
          wdt:P277 ?location.
    ?location wdt:P2 wd:Q1784677.
  }
  FILTER(EXISTS { ?item wdt:P48 ?coordinates. })
  ?item wdt:P48 ?coordinates.
  ?property wikibase:claim ?claim.
  ?item ?claim ?statement.
  {
    ?property wikibase:propertyType ?propertyType.
    FILTER(?property IN(
      wd:P189, wd:P2, wd:P534, wd:P47, wd:P419, wd:P8, wd:P280, wd:P9, wd:P550, wd:P48,
      wd:P319, wd:P156, wd:P315, wd:P7, wd:P7, wd:P137, wd:P146, wd:P1164, wd:P550, wd:P534,
      wd:P153, wd:P49, wd:P50, wd:P1164, wd:P153, wd:P156, wd:P155, wd:P280, wd:P49, wd:P50,
      wd:P1164, wd:P319, wd:P419, wd:P7, wd:P7, wd:P1164, wd:P315, wd:P14, wd:P247, wd:P248,
      wd:P8, wd:P9, wd:P95, wd:P47, wd:P47
    ))
    FILTER(?propertyType != wikibase:Time)
    ?property wikibase:statementProperty ?ps.
    ?statement ?ps ?propertyValue.
  }
  UNION
  {
    ?property wikibase:statementValue ?psv.
    FILTER(?property IN(wd:P49, wd:P50, wd:P49, wd:P50, wd:P106, wd:P49))
    ?statement ?psv ?propertyValueNode.
    ?propertyValueNode wikibase:timeValue ?propertyValue;
      wikibase:timePrecision ?propertyTimePrecision.
  }
  OPTIONAL {
    {
      ?qualifier wikibase:propertyType ?qualifierType.
      FILTER(?qualifier IN(wd:P54, wd:P10, wd:P48, wd:P155, wd:P55, wd:P153))
      FILTER(?qualifierType != wikibase:Time)
      ?qualifier wikibase:qualifier ?pq.
      ?statement ?pq ?qualifierValue.
    }
    UNION
    {
      ?qualifier wikibase:qualifierValue ?pqv.
      FILTER(?qualifier IN(wd:P49, wd:P50, wd:P106, wd:P218, wd:P219, wd:P432, wd:P432))
      ?statement ?pqv ?qualifierValueNode.
      ?qualifierValueNode wikibase:timeValue ?qualifierValue;
        wikibase:timePrecision ?qualifierTimePrecision.
    }
  }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
}
ORDER BY (?item) (?property) (?statement)';

        $response = $this->requestFactgrid($query);

        // Save the response for debugging
        file_put_contents(
            storage_path("logs/factgrid_response.json"),
            json_encode($response, JSON_PRETTY_PRINT),
        );

        // Check for valid response structure and filter out empty bindings
        if (
            isset($response["results"]["bindings"]) &&
            is_array($response["results"]["bindings"])
        ) {
            // Filter the bindings to remove any invalid ones
            $filteredBindings = array_filter(
                $response["results"]["bindings"],
                function ($binding) {
                    return !empty($binding) &&
                        isset($binding["item"]) &&
                        isset($binding["item"]["value"]);
                },
            );

            // Replace the original bindings with filtered ones
            $response["results"]["bindings"] = array_values($filteredBindings);

            $bindingsCount = count($response["results"]["bindings"]);
            Log::info(
                "Received FactGrid response with {$bindingsCount} valid bindings",
            );

            // All data is already included in the main query
            // No need for additional property enrichment with this approach

            // Save the filtered response for debugging
            file_put_contents(
                storage_path("logs/factgrid_filtered_response.json"),
                json_encode($response, JSON_PRETTY_PRINT),
            );
        } else {
            Log::warning(
                "Unexpected or empty response structure from FactGrid",
                [
                    "responseStructure" => isset($response)
                        ? json_encode(array_keys($response))
                        : "null",
                ],
            );
        }

        return $response;
    }

    /**
     * Create a SPARQL query for direct testing
     *
     * This method is for diagnostic purposes
     */
    public function createSimpleTestQuery(): string
    {
        return "
SELECT DISTINCT ?item ?itemLabel
WHERE {
  ?item wdt:P2 wd:Q1784677 .
  SERVICE wikibase:label { bd:serviceParam wikibase:language \"de,en\" . }
}
LIMIT 25";
    }

    /**
     * Query persons from FactGrid
     */
    public function queryPersons(array $groupIds = []): array
    {
        // Query for persons
        $groupIdsFilter = "";

        if (!empty($groupIds)) {
            $groupIdsValues = implode(
                " ",
                array_map(function ($id) {
                    return "wd:$id";
                }, $groupIds),
            );

            $groupIdsFilter = "VALUES ?role { $groupIdsValues }
      ?item wdt:P2 ?role .";
        }

        $query = "
SELECT ?item ?itemLabel ?itemDescription ?birthdate ?deathdate ?birthplace ?birthplaceLabel ?deathplace ?deathplaceLabel ?gender ?genderLabel ?role ?roleLabel
WHERE {
  $groupIdsFilter

  # Birth date (optional)
  OPTIONAL {
    ?item wdt:P569 ?birthdate .
  }

  # Death date (optional)
  OPTIONAL {
    ?item wdt:P570 ?deathdate .
  }

  # Birth place (optional)
  OPTIONAL {
    ?item wdt:P19 ?birthplace .
  }

  # Death place (optional)
  OPTIONAL {
    ?item wdt:P20 ?deathplace .
  }

  # Gender (optional)
  OPTIONAL {
    ?item wdt:P21 ?gender .
  }

  # Role/type (optional)
  OPTIONAL {
    ?item wdt:P2 ?role .
  }

  # Labels
  SERVICE wikibase:label {
    bd:serviceParam wikibase:language 'de,en' .
  }
}
LIMIT 1000";

        return $this->requestFactgrid($query);
    }

    /**
     * Send a SPARQL request to FactGrid
     */
    public function requestFactgrid(string $query): array
    {
        $query = preg_replace("/\s\s+/", " ", trim($query));
        // Simplify the query by removing any excess whitespace
        $query = preg_replace("/\s+/", " ", trim($query));

        // Encode the query for use in a URL
        $encodedQuery = urlencode($query);
        $endpoint = "https://database.factgrid.de/sparql";

        Log::info("Sending FactGrid SPARQL query to: {$endpoint}");
        Log::debug("SPARQL Query: " . $query);

        // Save the query for debugging
        file_put_contents(storage_path("logs/last_factgrid_query.txt"), $query);

        // Primary strategy: Direct SPARQL endpoint with GET request
        try {
            // Format parameter must be in the URL
            $url = "{$endpoint}?query={$encodedQuery}&format=json";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "Content-Type: application/x-www-form-urlencoded",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            Log::info("FactGrid SPARQL response", [
                "status" => $httpCode,
                "contentType" => $contentType,
                "error" => $error,
                "responseLength" => strlen($responseBody),
            ]);

            // Save the raw response for debugging
            file_put_contents(
                storage_path("logs/last_factgrid_response.txt"),
                $responseBody,
            );
            file_put_contents(
                storage_path("logs/last_factgrid_response_info.txt"),
                "HTTP Code: $httpCode\nContent-Type: $contentType\nError: $error\n",
            );

            if ($httpCode === 200 && $responseBody) {
                // Try parsing as JSON
                $jsonData = json_decode($responseBody, true);
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($jsonData["results"]["bindings"])
                ) {
                    // Filter out any empty bindings and log them
                    if (!empty($jsonData["results"]["bindings"])) {
                        $filteredBindings = array_filter(
                            $jsonData["results"]["bindings"],
                            function ($binding) {
                                $isValid =
                                    !empty($binding) &&
                                    isset($binding["item"]) &&
                                    isset($binding["item"]["value"]);

                                if (!$isValid && !empty($binding)) {
                                    Log::warning(
                                        "Filtered out invalid binding",
                                        ["binding" => json_encode($binding)],
                                    );
                                }

                                return $isValid;
                            },
                        );

                        // Check if we filtered out any bindings
                        if (
                            count($filteredBindings) <
                            count($jsonData["results"]["bindings"])
                        ) {
                            Log::warning(
                                "Removed " .
                                    (count($jsonData["results"]["bindings"]) -
                                        count($filteredBindings)) .
                                    " empty or invalid bindings from SPARQL response",
                            );
                            // Replace with filtered bindings
                            $jsonData["results"]["bindings"] = array_values(
                                $filteredBindings,
                            );
                        }
                    }

                    $bindingsCount = count($jsonData["results"]["bindings"]);
                    Log::info("Successfully received JSON response", [
                        "binding_count" => $bindingsCount,
                        "sample_binding" =>
                            $bindingsCount > 0 &&
                            !empty($jsonData["results"]["bindings"][0])
                                ? json_encode(
                                    array_slice(
                                        array_keys(
                                            $jsonData["results"]["bindings"][0],
                                        ),
                                        0,
                                        3,
                                    ),
                                )
                                : "none",
                    ]);

                    // Save the parsed JSON for debugging
                    file_put_contents(
                        storage_path("logs/last_factgrid_parsed.json"),
                        json_encode($jsonData, JSON_PRETTY_PRINT),
                    );

                    // Log additional details about the bindings
                    if (
                        $bindingsCount > 0 &&
                        !empty($jsonData["results"]["bindings"][0])
                    ) {
                        $firstBinding = $jsonData["results"]["bindings"][0];
                        Log::debug("First binding details", [
                            "full_binding" => json_encode($firstBinding),
                            "has_item" => isset($firstBinding["item"]),
                            "has_property" => isset($firstBinding["property"]),
                            "has_property_value" => isset(
                                $firstBinding["propertyValue"],
                            ),
                        ]);
                    }
                    return $jsonData;
                } else {
                    Log::warning("Response is not valid SPARQL JSON", [
                        "error" => json_last_error_msg(),
                        "responsePreview" => substr($responseBody, 0, 200),
                    ]);
                }
            } else {
                Log::warning("Invalid response from FactGrid SPARQL endpoint", [
                    "httpCode" => $httpCode,
                    "error" => $error,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception during FactGrid request", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
        }

        // Fallback strategy: direct GET request
        try {
            $url =
                "https://database.factgrid.de/query?format=json&query=" .
                $encodedQuery;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if (
                $httpCode === 200 &&
                $responseBody &&
                $this->isJsonResponse($contentType)
            ) {
                $jsonData = json_decode($responseBody, true);
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($jsonData["results"]["bindings"])
                ) {
                    Log::info(
                        "Fallback approach succeeded with valid JSON response",
                    );
                    return $jsonData;
                }
            }
        } catch (\Exception $e) {
            Log::error("Fallback strategy exception", [
                "error" => $e->getMessage(),
            ]);
        }

        // Create a properly structured empty result if all strategies failed
        $emptyResult = [
            "head" => [
                "vars" => ["item", "itemLabel", "itemDescription"],
            ],
            "results" => [
                "bindings" => [],
            ],
        ];

        Log::warning(
            "All strategies failed - returning empty result structure",
        );
        return $emptyResult;
    }

    /**
     * Check if the content type is a JSON response
     */
    public function isJsonResponse(string $contentType): bool
    {
        $jsonContentTypes = [
            "application/json",
            "application/sparql-results+json",
        ];

        foreach ($jsonContentTypes as $type) {
            if (strpos($contentType, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge items data
     *
     * @param array $data The raw data from SPARQL query
     * @return array The merged data
     */
    public function mergeItemsData(array $data): array
    {
        $items = [];

        Log::debug("Starting mergeItemsData with " . count($data) . " rows");

        // If empty data, return empty items
        if (empty($data)) {
            Log::warning("Empty data passed to mergeItemsData");
            return $items;
        }

        // Filter out any empty bindings or bindings missing required fields
        $data = array_filter($data, function ($row) {
            return !empty($row) &&
                is_array($row) &&
                isset($row["item"]) &&
                isset($row["item"]["value"]);
        });

        // Re-index array after filtering
        $data = array_values($data);

        // If we have no data after filtering, return empty items
        if (empty($data)) {
            Log::warning("No valid data after filtering in mergeItemsData");
            return $items;
        }

        // Log the first valid binding for debugging
        if (count($data) > 0) {
            Log::info("First valid binding in mergeItemsData", [
                "item_value" => $data[0]["item"]["value"],
                "has_label" => isset($data[0]["itemLabel"]),
                "binding_keys" => implode(", ", array_keys($data[0])),
            ]);
        }

        // First check if we have proper structure
        if (!is_array($data) || !isset($data[0]) || !is_array($data[0])) {
            Log::warning("Data passed to mergeItemsData has wrong structure", [
                "data_type" => gettype($data),
                "first_item_type" => isset($data[0])
                    ? gettype($data[0])
                    : "not set",
            ]);
            return $items;
        }

        foreach ($data as $index => $row) {
            // Debug log for the current row structure
            Log::debug(
                "Processing row #{$index} with keys: " .
                    json_encode(array_keys($row)),
            );

            if (!isset($row["item"]) || !isset($row["item"]["value"])) {
                Log::warning("Row #{$index} is missing item value", [
                    "row" => json_encode($row),
                ]);
                continue;
            }

            $itemId = $row["item"]["value"];
            $baseUrl = "https://database.factgrid.de/entity/";
            $qid = str_replace($baseUrl, "", $itemId);

            if (!isset($items[$itemId])) {
                $items[$itemId] = [
                    "id" => $qid,
                    "qid" => $qid,
                    "label" =>
                        isset($row["itemLabel"]) &&
                        isset($row["itemLabel"]["value"])
                            ? $row["itemLabel"]["value"]
                            : "Unnamed Item",
                    "description" =>
                        isset($row["itemDescription"]) &&
                        isset($row["itemDescription"]["value"])
                            ? $row["itemDescription"]["value"]
                            : "No description available",
                ];

                Log::info("Created new item: {$qid}", [
                    "label" => $items[$itemId]["label"],
                ]);
            }

            // Process instance values from the query results
            // Make sure we have necessary data structures
            if (!isset($items[$itemId]["coordinates"])) {
                $items[$itemId]["coordinates"] = [];
            }
            if (!isset($items[$itemId]["instances"])) {
                $items[$itemId]["instances"] = [];
            }
            if (!isset($items[$itemId]["properties"])) {
                $items[$itemId]["properties"] = [];
            }

            // Process instance values from the query results
            if (
                isset($row["property"]) &&
                isset($row["property"]["value"]) &&
                strpos($row["property"]["value"], "P2") !== false &&
                isset($row["propertyValue"]) &&
                isset($row["propertyValue"]["value"])
            ) {
                $instanceValue = $row["propertyValue"]["value"];
                // Extract the QID from the instance value for better logging
                $instanceQid = basename($instanceValue);
                $instanceLabel = $instanceQid; // Default to QID

                // Get label if available
                if (
                    isset($row["propertyValueLabel"]) &&
                    isset($row["propertyValueLabel"]["value"])
                ) {
                    $instanceLabel = $row["propertyValueLabel"]["value"];
                }

                // Skip if the instance is the main query entity (e.g., Q1784677)
                if (strpos($instanceValue, "Q1784677") !== false) {
                    continue;
                }

                // Make sure we have the instances field
                if (!isset($items[$itemId]["instances"])) {
                    $items[$itemId]["instances"] = [];
                }

                // Generate a unique ID for this instance statement
                $instanceStatementId =
                    $qid . "-" . substr(md5($instanceValue), 0, 16);

                // Check for duplicates before adding
                $isDuplicate = false;
                foreach ($items[$itemId]["instances"] as $key => $existing) {
                    if (
                        isset($existing["value"]) &&
                        $existing["value"] === $instanceLabel
                    ) {
                        $isDuplicate = true;
                        break;
                    }
                }

                if (!$isDuplicate) {
                    $items[$itemId]["instances"][$instanceStatementId] = [
                        "value" => $instanceLabel,
                        "id" => $instanceQid,
                    ];

                    Log::info(
                        "Added instance type: " .
                            $instanceQid .
                            " (" .
                            $instanceLabel .
                            ") to item " .
                            $qid,
                    );
                }
            }

            // Make sure we have the coordinates field
            if (!isset($items[$itemId]["coordinates"])) {
                $items[$itemId]["coordinates"] = [];
            }

            // Process coordinates if available
            if (
                isset($row["coordinates"]) &&
                isset($row["coordinates"]["value"])
            ) {
                $coordValue = $row["coordinates"]["value"];
                $parsedCoordinates = $this->parseCoordinates($coordValue);

                if ($parsedCoordinates) {
                    // Generate a unique statement ID for the coordinate
                    $statementId =
                        $qid .
                        "-" .
                        strtoupper(substr(md5($coordValue), 0, 12));

                    // Store coordinates in Wikidata-compatible format (if not already present)
                    if (!isset($items[$itemId]["coordinates"][$statementId])) {
                        $items[$itemId]["coordinates"][$statementId] = [
                            "value" => [
                                "lat" => (string) $parsedCoordinates["lat"],
                                "lng" => (string) $parsedCoordinates["lng"],
                            ],
                        ];

                        Log::info(
                            "Added coordinates for {$qid}: lat={$parsedCoordinates["lat"]}, lng={$parsedCoordinates["lng"]}",
                        );
                    }

                    // This is now handled by the coordinates field directly
                } else {
                    Log::warning(
                        "Failed to parse coordinates for {$qid}: {$coordValue}",
                    );
                }
            }

            // Process other properties
            if (
                isset($row["property"]) &&
                isset($row["property"]["value"]) &&
                isset($row["propertyValue"]) &&
                isset($row["propertyValue"]["value"])
            ) {
                // Extract the property P-ID from the URI
                $propertyUri = $row["property"]["value"];
                $propertyId = basename($propertyUri);

                // Skip P2 (instance of) as we handle it separately
                if ($propertyId === "P2") {
                    continue;
                }

                // Skip P48 (coordinates) as we handle it separately
                if ($propertyId === "P48") {
                    continue;
                }

                // Get the property value
                $propertyValue = $row["propertyValue"]["value"];
                $propertyValueLabel = isset($row["propertyValueLabel"])
                    ? $row["propertyValueLabel"]["value"]
                    : basename($propertyValue);

                // Qid has already been extracted above

                // Get the property's semantic name
                $semanticKey = isset(self::PROPERTY_LABEL_OF_ID[$propertyId])
                    ? self::PROPERTY_LABEL_OF_ID[$propertyId]
                    : "properties";

                // Initialize property array if not set
                if ($semanticKey === "properties") {
                    // Keep using the old format for properties we don't have mappings for
                    $propKey = "wdt:" . $propertyId;
                    if (!isset($items[$itemId]["properties"][$propKey])) {
                        $items[$itemId]["properties"][$propKey] = [];
                    }

                    // Add property value if not duplicate
                    $isDuplicate = false;
                    foreach (
                        $items[$itemId]["properties"][$propKey]
                        as $existingProp
                    ) {
                        if ($existingProp["value"] === $propertyValue) {
                            $isDuplicate = true;
                            break;
                        }
                    }

                    if (!$isDuplicate) {
                        // Extract the Qid from the property value if it's a URI
                        $valueQid = $propertyValue;
                        if (
                            strpos(
                                $propertyValue,
                                "https://database.factgrid.de/entity/",
                            ) === 0
                        ) {
                            $valueQid = str_replace(
                                "https://database.factgrid.de/entity/",
                                "",
                                $propertyValue,
                            );
                        }

                        $statementEntry = [
                            "value" => $propertyValue,
                            "label" => $propertyValueLabel,
                            "qid" => $valueQid,
                        ];

                        // Process qualifier if available
                        if (
                            isset($row["qualifier"]) &&
                            isset($row["qualifier"]["value"]) &&
                            isset($row["qualifierValue"]) &&
                            isset($row["qualifierValue"]["value"])
                        ) {
                            $qualifierId = basename($row["qualifier"]["value"]);
                            $qualifierValue = $row["qualifierValue"]["value"];
                            $qualifierLabel = isset(
                                self::QUALIFIER_LABEL_OF_ID[$qualifierId],
                            )
                                ? self::QUALIFIER_LABEL_OF_ID[$qualifierId]
                                : "qualifier_" . $qualifierId;

                            $statementEntry[$qualifierLabel] = [
                                "value" => $qualifierValue,
                            ];
                        }

                        $items[$itemId]["properties"][
                            $propKey
                        ][] = $statementEntry;
                    }
                } else {
                    // This is a property with semantic meaning that we want to map
                    if (!isset($items[$itemId][$semanticKey])) {
                        $items[$itemId][$semanticKey] = [];
                    }

                    // Generate a unique ID for this property statement
                    $statementId =
                        $qid . "-" . substr(md5($propertyValue), 0, 16);

                    // Extract the Qid from the property value if it's a URI
                    $valueQid = $propertyValue;
                    if (
                        strpos(
                            $propertyValue,
                            "https://database.factgrid.de/entity/",
                        ) === 0
                    ) {
                        $valueQid = str_replace(
                            "https://database.factgrid.de/entity/",
                            "",
                            $propertyValue,
                        );
                    }

                    // Check for duplicates before adding
                    $isDuplicate = false;
                    foreach (
                        $items[$itemId][$semanticKey]
                        as $key => $existingProp
                    ) {
                        if (
                            isset($existingProp["value"]) &&
                            $existingProp["value"] === $propertyValueLabel
                        ) {
                            $isDuplicate = true;
                            break;
                        }
                    }

                    if (!$isDuplicate) {
                        // Make sure we initialize this semantic property if it doesn't exist
                        if (!isset($items[$itemId][$semanticKey])) {
                            $items[$itemId][$semanticKey] = [];
                        }

                        $statementEntry = [
                            "value" => $propertyValueLabel,
                            "id" => $valueQid,
                        ];

                        // Process qualifier if available
                        if (
                            isset($row["qualifier"]) &&
                            isset($row["qualifier"]["value"]) &&
                            isset($row["qualifierValue"]) &&
                            isset($row["qualifierValue"]["value"])
                        ) {
                            $qualifierId = basename($row["qualifier"]["value"]);
                            $qualifierValue = $row["qualifierValue"]["value"];
                            $qualifierLabel = isset(
                                self::QUALIFIER_LABEL_OF_ID[$qualifierId],
                            )
                                ? self::QUALIFIER_LABEL_OF_ID[$qualifierId]
                                : "qualifier_" . $qualifierId;

                            $statementEntry[$qualifierLabel] = [
                                "value" => $qualifierValue,
                            ];

                            // If this is a date qualifier, add precision info
                            if (
                                in_array($qualifierLabel, [
                                    "startTime",
                                    "endTime",
                                    "pointInTime",
                                    "earliestDate",
                                    "latestDate",
                                ])
                            ) {
                                if (
                                    preg_match(
                                        "/^\d{4}-\d{2}-\d{2}/",
                                        $qualifierValue,
                                    )
                                ) {
                                    $statementEntry[$qualifierLabel][
                                        "datePrecision"
                                    ] = "11"; // Day precision
                                } elseif (
                                    preg_match(
                                        "/^\d{4}-\d{2}/",
                                        $qualifierValue,
                                    )
                                ) {
                                    $statementEntry[$qualifierLabel][
                                        "datePrecision"
                                    ] = "10"; // Month precision
                                } elseif (
                                    preg_match("/^\d{4}/", $qualifierValue)
                                ) {
                                    $statementEntry[$qualifierLabel][
                                        "datePrecision"
                                    ] = "9"; // Year precision
                                }
                            }
                        }

                        $items[$itemId][$semanticKey][
                            $statementId
                        ] = $statementEntry;
                    }
                }

                Log::debug(
                    "Added property {$propertyId} to item {$qid}: {$propertyValueLabel}",
                );
            }

            // Special case handling is no longer needed as coordinates
            // are directly included in the main query results

            // Check if property is P2 (instance of) when processing property values
            if (
                isset($row["property"]) &&
                isset($row["property"]["value"]) &&
                strpos($row["property"]["value"], "P2") !== false &&
                isset($row["propertyValue"]) &&
                isset($row["propertyValue"]["value"])
            ) {
                // This is an instance value, process it here instead of instanceValue direct field
                $instanceValue = $row["propertyValue"]["value"];
                // Extract the QID from the instance value for better logging
                $instanceQid = basename($instanceValue);
                $instanceLabel = $instanceQid; // Default to QID

                // Get label if available
                if (
                    isset($row["propertyValueLabel"]) &&
                    isset($row["propertyValueLabel"]["value"])
                ) {
                    $instanceLabel = $row["propertyValueLabel"]["value"];
                }

                // Add only if not duplicate and not the main type (Q1784677)
                if (strpos($instanceValue, "Q1784677") === false) {
                    // Check for duplicates before adding
                    $isDuplicate = false;
                    foreach (
                        $items[$itemId]["properties"]["wdt:P2"]
                        as $existing
                    ) {
                        if ($existing["value"] === $instanceValue) {
                            $isDuplicate = true;
                            break;
                        }
                    }

                    if (!$isDuplicate) {
                        $items[$itemId]["properties"]["wdt:P2"][] = [
                            "value" => $instanceValue,
                            "label" => $instanceLabel,
                            "qid" => $instanceQid,
                        ];
                    }
                }
            }

            // Process other non-P2 and non-P48 properties
            if (
                isset($row["property"]) &&
                isset($row["property"]["value"]) &&
                strpos($row["property"]["value"], "P2") === false &&
                strpos($row["property"]["value"], "P48") === false &&
                isset($row["propertyValue"]) &&
                isset($row["propertyValue"]["value"])
            ) {
                $propertyUri = $row["property"]["value"];
                $propertyId = basename($propertyUri);
                $propKey = "wdt:" . $propertyId;

                // Initialize property array if not set
                if (!isset($items[$itemId]["properties"][$propKey])) {
                    $items[$itemId]["properties"][$propKey] = [];
                }

                $propertyValue = $row["propertyValue"]["value"];
                $propertyValueLabel = isset($row["propertyValueLabel"])
                    ? $row["propertyValueLabel"]["value"]
                    : basename($propertyValue);

                // Add property value if not duplicate
                $isDuplicate = false;
                foreach (
                    $items[$itemId]["properties"][$propKey]
                    as $existingProp
                ) {
                    if ($existingProp["value"] === $propertyValue) {
                        $isDuplicate = true;
                        break;
                    }
                }

                if (!$isDuplicate) {
                    $items[$itemId]["properties"][$propKey][] = [
                        "value" => $propertyValue,
                        "label" => $propertyValueLabel,
                        "qid" => basename($propertyValue),
                    ];

                    Log::debug(
                        "Added property {$propertyId} to item {$qid}: {$propertyValueLabel}",
                    );
                }
            }

            // Check for direct instanceValue (for backward compatibility)
            if (
                isset($row["instanceValue"]) &&
                isset($row["instanceValue"]["value"])
            ) {
                $instanceValue = $row["instanceValue"]["value"];
                // Extract the QID from the instance value for better logging
                $instanceQid = basename($instanceValue);
                $instanceLabel = $instanceQid; // Default to QID

                // Get label if available
                if (
                    isset($row["instanceValueLabel"]) &&
                    isset($row["instanceValueLabel"]["value"])
                ) {
                    $instanceLabel = $row["instanceValueLabel"]["value"];
                }

                // Make sure we have the instances field
                if (!isset($items[$itemId]["instances"])) {
                    $items[$itemId]["instances"] = [];
                }

                // Generate a unique statement ID
                $statementId = $qid . "-" . substr(md5($instanceValue), 0, 16);

                // Check for duplicates before adding
                $isDuplicate = false;
                foreach ($items[$itemId]["instances"] as $key => $existing) {
                    if (
                        isset($existing["value"]) &&
                        $existing["value"] === $instanceLabel
                    ) {
                        $isDuplicate = true;
                        break;
                    }
                }

                if (!$isDuplicate) {
                    $items[$itemId]["instances"][$statementId] = [
                        "value" => $instanceLabel,
                        "id" => $instanceQid,
                    ];

                    Log::info(
                        "Added instance type: " .
                            $instanceQid .
                            " (" .
                            $instanceLabel .
                            ") to item " .
                            $qid,
                    );
                }
            } else {
                // If no instance value but we have an item, add a default instance type
                if (empty($items[$itemId]["properties"]["wdt:P2"])) {
                    $defaultInstanceValue =
                        "https://database.factgrid.de/entity/Q1784677";
                    $defaultInstanceQid = "Q1784677";
                    $defaultInstanceLabel = "Ort des Gestapoterrors";

                    $items[$itemId]["properties"]["wdt:P2"][] = [
                        "value" => $defaultInstanceValue,
                        "label" => $defaultInstanceLabel,
                        "qid" => $defaultInstanceQid,
                    ];

                    Log::info("Added default instance type to item {$qid}");
                }
            }
        }

        Log::info("Completed mergeItemsData with " . count($items) . " items");

        // Check for items with coordinates
        $itemsWithCoordinates = array_filter($items, function ($item) {
            return !empty($item["coordinates"]);
        });

        Log::debug(
            "Items with coordinates: " .
                count($itemsWithCoordinates) .
                "/" .
                count($items),
        );

        // Log the first item for debugging
        if (!empty($items)) {
            $firstItem = reset($items);
            Log::debug("First merged item:", [
                "qid" => $firstItem["qid"],
                "label" => $firstItem["label"],
                "instance_types" => count(
                    $firstItem["properties"]["wdt:P2"] ?? [],
                ),
                "has_coordinates" => !empty($firstItem["coordinates"]),
                "coordinates" => !empty($firstItem["coordinates"])
                    ? json_encode($firstItem["coordinates"])
                    : "none",
            ]);
        }

        return $items;
    }

    /**
     * Group items by property
     *
     * @param array $items The items to group
     * @param array $groupConfigs The group configurations
     * @param string $propertyId The property ID to group by
     * @return array The grouped items
     */
    public function groupItemsByProperty(
        array $items,
        array $groupConfigs,
        string $propertyId,
    ): array {
        $groups = [];

        // Initialize groups
        foreach (array_keys($groupConfigs) as $groupKey) {
            $groups[$groupKey] = [];
        }

        Log::info(
            "Grouping by property {$propertyId} with " .
                count($items) .
                " items",
        );

        // If no items, return empty groups
        if (empty($items)) {
            Log::warning("No items to group");
            return $groups;
        }

        // Track ungrouped items to potentially add them based on labels
        $ungroupedItems = [];

        foreach ($items as $itemId => $item) {
            $placed = false;
            $itemQid = $item["qid"] ?? basename($itemId);

            // Skip if the item doesn't have the specified property
            if (
                !isset($item["properties"][$propertyId]) ||
                empty($item["properties"][$propertyId])
            ) {
                Log::warning(
                    "Item {$itemQid} has no properties for {$propertyId}",
                );
                $ungroupedItems[$itemId] = $item;
                continue;
            }

            // Get all values of the property
            $propertyValues = $item["properties"][$propertyId];
            foreach ($propertyValues as $propValueObj) {
                // Extract QID from URI
                $instanceQid = isset($propValueObj["qid"])
                    ? $propValueObj["qid"]
                    : basename($propValueObj["value"]);

                // Skip the generic Gestapo terror location type for grouping
                if ($instanceQid === "Q1784677") {
                    continue;
                }

                // Check each group to see if this QID is in its list
                foreach ($groupConfigs as $groupKey => $qids) {
                    if (in_array($instanceQid, $qids)) {
                        // Create a copy of the item with its coordinates
                        $groups[$groupKey][] = $item;
                        $placed = true;

                        // Log coordinate information for debugging
                        $hasCoords = !empty($item["coordinates"]);
                        Log::info(
                            "Placed item {$itemQid} in group {$groupKey} based on type {$instanceQid}. Has coordinates: " .
                                ($hasCoords ? "YES" : "NO") .
                                ($hasCoords
                                    ? " (" .
                                        json_encode(
                                            array_keys($item["coordinates"]),
                                        ) .
                                        ")"
                                    : ""),
                        );

                        break 2; // Once placed, move to next item
                    }
                }
            }

            if (!$placed) {
                Log::warning(
                    "Could not place item {$itemQid} in any group based on types",
                );
                $ungroupedItems[$itemId] = $item;
            }
        }

        // Try to group remaining items based on label matching
        if (!empty($ungroupedItems)) {
            Log::info(
                "Attempting to group " .
                    count($ungroupedItems) .
                    " remaining items by label",
            );

            foreach ($ungroupedItems as $itemId => $item) {
                $itemQid = $item["qid"] ?? basename($itemId);
                $label = $item["label"] ?? "";

                // Skip items with no label
                if (empty($label)) {
                    continue;
                }

                // Some keyword-based grouping
                $label = strtolower($label);

                if (strpos($label, "staatspolizeistelle") !== false) {
                    $groups["statePoliceHeadquarters"][] = $item;
                    Log::info(
                        "Placed item {$itemQid} in statePoliceHeadquarters based on label",
                    );
                } elseif (strpos($label, "staatspolizeileitstelle") !== false) {
                    $groups["statePoliceHeadquarters"][] = $item;
                    Log::info(
                        "Placed item {$itemQid} in statePoliceHeadquarters based on label",
                    );
                } elseif (strpos($label, "arbeitserziehungslager") !== false) {
                    $groups["laborEducationCamps"][] = $item;
                    Log::info(
                        "Placed item {$itemQid} in laborEducationCamps based on label",
                    );
                } elseif (
                    strpos($label, "polizeigefängnis") !== false ||
                    strpos($label, "polizeigefangnis") !== false
                ) {
                    $groups["extPolicePrisons"][] = $item;
                    Log::info(
                        "Placed item {$itemQid} in extPolicePrisons based on label",
                    );
                } elseif (
                    strpos($label, "gefängnis") !== false ||
                    strpos($label, "gefangnis") !== false
                ) {
                    $groups["prisons"][] = $item;
                    Log::info(
                        "Placed item {$itemQid} in prisons based on label",
                    );
                } elseif (
                    strpos($label, "grenzpolizeiposten") !== false ||
                    strpos($label, "grenzpolizei") !== false
                ) {
                    $groups["statePoliceOffices"][] = $item;
                    Log::info(
                        "Placed item {$itemQid} in statePoliceOffices based on label",
                    );
                } elseif (
                    strpos($label, "gedenkstätte") !== false ||
                    strpos($label, "gedenkstatte") !== false
                ) {
                    $groups["memorials"][] = $item;
                    Log::info(
                        "Placed item {$itemQid} in memorials based on label",
                    );
                }
                // Default to statePoliceOffices if we can't place it otherwise
                else {
                    $groups["statePoliceOffices"][] = $item;
                    Log::info(
                        "Placed item {$itemQid} in statePoliceOffices as fallback",
                    );
                }
            }
        }

        Log::info("Grouped items by property", [
            "itemCount" => count($items),
            "groupCounts" => array_map("count", $groups),
        ]);

        // Output counts for each group
        foreach ($groups as $groupKey => $groupItems) {
            Log::info(
                "Group '{$groupKey}' contains " . count($groupItems) . " items",
            );
        }

        return $groups;
    }

    /**
     * This method was previously used to enrich items with additional properties
     * It's been replaced by the direct query approach that provides all required data
     * Keeping the method signature for backward compatibility
     *
     * @param array $bindings The bindings to enrich
     * @return void
     */
    private function enrichWithProperties(array &$bindings): void
    {
        Log::debug(
            "Property enrichment method called but not needed with direct query approach",
        );
        // No implementation needed as all data is included in the main query
    }

    /**
     * Apply subgroup mappings to the groups
     *
     * @param array $groups The grouped items
     * @param array $subgroupMappings The subgroup mappings
     * @param string $partOfPropertyId The part-of property ID
     * @return array The subgrouped items
     */
    public function subgroupItems(
        array $groups,
        array $subgroupMappings,
        string $partOfPropertyId,
    ): array {
        // FactGrid doesn't currently have specific subgroups
        return $groups;
    }

    // Method removed - we now handle the bindings directly in queryPlaces() and mergeItemsData()

    /**
     * Search titles in FactGrid
     */

    /**
     * Parse coordinate string into lat/lng object
     *
     * FactGrid stores coordinates in various formats. This method attempts to parse them
     * into a standardized { lat: x, lng: y } format for use in mapping.
     *
     * @param mixed $coordinateString The coordinate string from FactGrid
     * @return array|null Array with lat/lng keys or null if parsing fails
     */
    /**
     * Parse coordinate string into lat/lng object
     *
     * FactGrid stores coordinates in various formats. This method attempts to parse them
     * into a standardized { lat: x, lng: y } format for use in mapping.
     *
     * @param mixed $coordinateString The coordinate string from FactGrid
     * @return array|null Array with lat/lng keys or null if parsing fails
     */
    public function parseCoordinates($coordinateString): ?array
    {
        // If already an array, return as is
        if (is_array($coordinateString)) {
            return $coordinateString;
        }

        // If not a string, return null
        if (!is_string($coordinateString)) {
            Log::warning(
                "Expected string for coordinates but got " .
                    gettype($coordinateString),
            );
            return null;
        }

        // Log the coordinate string we're trying to parse for debugging
        Log::debug("Parsing coordinate string: " . $coordinateString);

        // Remove any surrounding whitespace
        $coordinateString = trim($coordinateString);

        // Try to parse as a Point format - common in FactGrid
        if (
            preg_match(
                "/Point\((-?[0-9.]+) (-?[0-9.]+)\)/i",
                $coordinateString,
                $matches,
            )
        ) {
            // Point format typically has longitude first, then latitude
            return [
                "lat" => (float) $matches[2],
                "lng" => (float) $matches[1],
            ];
        }

        // Try to parse as simple "lat,lng" or "lat lng" format
        if (
            preg_match(
                '/^(-?[0-9.]+)[,\s]+(-?[0-9.]+)$/',
                $coordinateString,
                $matches,
            )
        ) {
            return [
                "lat" => (float) $matches[1],
                "lng" => (float) $matches[2],
            ];
        }

        // Try to parse as GeoJSON-like format
        if (
            preg_match(
                "/\[\s*(-?[0-9.]+)\s*,\s*(-?[0-9.]+)\s*\]/",
                $coordinateString,
                $matches,
            )
        ) {
            // GeoJSON uses [longitude, latitude] order
            return [
                "lat" => (float) $matches[2],
                "lng" => (float) $matches[1],
            ];
        }

        // Check if it's a GeoPoint WKT format (common in some systems)
        if (
            preg_match(
                '/^POINT\s*\(\s*(-?[0-9.]+)\s+(-?[0-9.]+)\s*\)$/i',
                $coordinateString,
                $matches,
            )
        ) {
            return [
                "lat" => (float) $matches[2],
                "lng" => (float) $matches[1],
            ];
        }

        // If no pattern matches, log the issue and return null
        Log::warning("Could not parse coordinate string: {$coordinateString}");
        return null;
    }

    public function searchTitles(string $q): array
    {
        $query = "
SELECT ?item ?itemLabel ?itemDescription WHERE {
  ?item rdfs:label ?label.
  FILTER(CONTAINS(LCASE(?label), LCASE(\"$q\"))).
  SERVICE wikibase:label { bd:serviceParam wikibase:language \"de,en\". }
}
LIMIT 25";
        return $this->requestFactgrid($query);
    }
}

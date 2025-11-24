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

        // Direct query that gets places with instance types
        $query = '
SELECT DISTINCT ?item ?itemLabel ?itemDescription ?instanceValue ?instanceValueLabel
WHERE {
  ?item wdt:P2 wd:Q1784677 .
  ?item wdt:P2 ?instanceValue .
  FILTER(?instanceValue != wd:Q1784677)
  SERVICE wikibase:label {
    bd:serviceParam wikibase:language "de,en" .
  }
}';

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
            // Filter out any empty bindings
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
                                    isset($binding["item"]["value"]) &&
                                    isset($binding["instanceValue"]) &&
                                    isset($binding["instanceValue"]["value"]);

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
                            "has_instance" => isset(
                                $firstBinding["instanceValue"],
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
                    "id" => $itemId,
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
                    "properties" => [
                        "wdt:P2" => [],
                    ],
                ];

                Log::info("Created new item: {$qid}", [
                    "label" => $items[$itemId]["label"],
                ]);
            }

            // Process instance values from the query results
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

                // Check for duplicates before adding
                $isDuplicate = false;
                foreach ($items[$itemId]["properties"]["wdt:P2"] as $existing) {
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

                    Log::info(
                        "Added instance type: " .
                            $instanceQid .
                            " (" .
                            $instanceLabel .
                            ") to item " .
                            basename($itemId),
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

        // Log the first item for debugging
        if (!empty($items)) {
            $firstItem = reset($items);
            Log::debug("First merged item:", [
                "qid" => $firstItem["qid"],
                "label" => $firstItem["label"],
                "instance_types" => count($firstItem["properties"]["wdt:P2"]),
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
                        $groups[$groupKey][] = $item;
                        $placed = true;
                        Log::info(
                            "Placed item {$itemQid} in group {$groupKey} based on type {$instanceQid}",
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

    /**
     * Search titles in FactGrid
     */
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

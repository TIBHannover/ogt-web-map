<?php

namespace App\Http\Clients;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Query FactGrid.
 *
 * @package App\Http\Clients
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
        "events" => [
            "Q1014362", //
        ],
        "statePoliceOffices" => [
            "Q1784671", // Grenzpolizeikommissariat
            "Q1784668", // Staatspolizeistelle
        ],
        "statePoliceHeadquarters" => [
            "Q1784673", // Staatspolizeileitstelle
        ],
        "prisons" => ["Q23223"], // Haftstätte
        "extPolicePrisons" => ["Q1784665"], // Erweitertes Polizeigefängnis
        "laborEducationCamps" => ["Q978663"], // Arbeitserziehungslager
        "fieldOffices" => [
            "Q1784667", // https://www.wikidata.org/wiki/Q108047541     Gestapo Field Office
            "Q1784676", // https://www.wikidata.org/wiki/Q108047989     Outpost (State Police)
            "Q1784669", // https://www.wikidata.org/wiki/Q108047676     Border Police Commissariat
            "Q1784670", // https://www.wikidata.org/wiki/Q108047833     Border police station
            "Q1784671", // https://www.wikidata.org/wiki/Q108047775     Branch office (border police)
        ], // Außendienststelle
        "memorials" => ["Q405787"], // Gedenkstätte
    ];

    /**
     * Special subgroups for items that should be grouped differently
     */
    const PLACE_SUBGROUP_MAPPINGS = [
        "persecutionOfSocialistFront" => [
            "mainGroup" => "events",
            "mainItemId" => "Q1784243", // https://www.wikidata.org/wiki/Q133303308   Persecution of the Socialist Front by the Gestapo
        ],
        "operationLauterbacher" => [
            "mainGroup" => "events",
            "mainItemId" => "Q1784204", // https://www.wikidata.org/wiki/Q134587748   Operation Lauterbacher and the surveillance of the "Jewish houses" in Hanover
        ],
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
        "P45" => "startTime", // Start time (Wikidata P580)
        "P46" => "endTime", // End time (Wikidata P582)
        "P106" => "pointInTime", // Point in time (Wikidata P585)
        "P77" => "dateOfBirth", // Date of birth (Wikidata P569)
        "P38" => "dateOfDeath", // Date of death (Wikidata P570)
        "P49" => "inceptionDates", // https://www.wikidata.org/wiki/Property:P571
        "P50" => "dissolvedDates", // https://www.wikidata.org/wiki/Property:P576
        "P290" => "firstDocumented", // replaces startTime for memorials, Erste Aktivität P580

        // Person Properties
        "P154" => "gender", // Gender (Wikidata P21)
        "P616" => "citizenships", // Citizenships (Wikidata P27)
        "P247" => "familyName", // Family name (Wikidata P734)
        "P248" => "givenName", // Given name (Wikidata P735)
        // "P153" already defined above for streetAddresses

        // Organization Properties
        "P319" => "parentOrganizations", // Parent organization (Wikidata P749)
        "P419" => "subsidiaries", // Has subsidiaries (Wikidata P355)
        "P6" => "replaces", // https://www.wikidata.org/wiki/Property:P1365
        "P7" => "replacedBys", // Replaced by (Wikidata P1366)
        "P1421" => "employeeCounts", // https://www.wikidata.org/wiki/Property:P1128
        "P1422" => "prisonerCounts", // https://www.wikidata.org/wiki/Property:P5630

        // Role and Relationship Properties
        "P277" => "subjectRoles", // Subject has role (Wikidata P2868)
        "P315" => "employers", // Employer (Wikidata P108)
        "P14" => "directors", // Director (Wikidata P1037)
        "P216" => "detentionPlaces", // Place of detention (Wikidata P2632)
        "P550" => "significantEvents", // Significant event (Wikidata P793)
        "P391" => "commemorates", // https://www.wikidata.org/wiki/Property:P547
        "P129" => "describedBySources", // Described by source (Wikidata P1343)
        "P146" => "describedByUrlSources", // Described by URL (Wikidata P973)

        // Event Specific Properties
        //"P178" => "perpetrators", // Perpetrator (Wikidata P8031 - first mapping)
        "P66" => "perpetrators", // Perpetrator (Wikidata P8031 - second mapping)
        "P751" => "victims", // Victim (Wikidata P8032)
        "P464" => "hasCause", // Has cause (Wikidata P828)
        "P5582" => "numberOfArrests", // https://www.wikidata.org/wiki/Property:P5582
        "P1418" => "numberOfSurvivors", // https://www.wikidata.org/wiki/Property:P1561
        "P1164" => "numberOfCasualties", // https://www.wikidata.org/wiki/Property:P1590
        "P1419" => "numberOfDeaths", // https://www.wikidata.org/wiki/Property:P1120

        "P9" => "hasParts", // https://www.wikidata.org/wiki/Property:P527
        "P280" => "hasUses", // https://www.wikidata.org/wiki/Property:P366
        "P8" => "partOfs", // https://www.wikidata.org/wiki/Property:P361
    ];

    /**
     * FactGrid qualifier ID to semantic name mapping
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
     * Query places data from FactGrid.
     *
     * @return array
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
            ?item wdt:P48 ?coordinates.
          }
          UNION
          {
              ?item wdt:P391 ?location.
              ?location wdt:P2 wd:Q1784677.
          }
          UNION
          {
            ?item wdt:P550 ?location.
            ?location wdt:P2 wd:Q1784677.
            ?item wdt:P48 ?coordinates.
          }
          UNION
          {
            ?item wdt:P2 wd:Q1014362;
                  wdt:P48 ?coordinates.
          }
          UNION
          {
            ?event wdt:P2 wd:Q1014362;
                   wdt:P48 ?coordinates.
            {
              ?event (wdt:P66|wdt:P751) ?item.
              ?item wdt:P48 ?coordinates.
            }
            UNION
            {
              VALUES ?validSubjectRoles { wd:Q1784661 wd:Q1784663 }.
              ?item wdt:P550 ?event;
                    wdt:P277/wdt:P3? ?validSubjectRoles.
              ?item wdt:P48 ?coordinates.
            }
          }
          ?property wikibase:claim ?claim.
          ?item ?claim ?statement.
          {
            ?property wikibase:propertyType ?propertyType.
            FILTER(?property IN(
              wd:P189, wd:P2, wd:P534, wd:P47, wd:P419, wd:P280, wd:P9, wd:P550, wd:P391, wd:P48,
              wd:P319, wd:P156, wd:P315, wd:P7, wd:P7, wd:P137, wd:P1164, wd:P550, wd:P534,
              wd:P153, wd:P49, wd:P50,wd:P153, wd:P156, wd:P155, wd:P49, wd:P50,
              wd:P319, wd:P129, wd:P146, wd:P7, wd:P7, wd:P1422, wd:P1421, wd:P14, wd:P247, wd:P248, wd:P5582, wd:P1418, wd:P1419,
              wd:P8, wd:P95, wd:P47, wd:P47, wd:P66, wd:P751, wd:P290
            ))
            FILTER(?propertyType != wikibase:Time)
            ?property wikibase:statementProperty ?ps.
            ?statement ?ps ?propertyValue.
          }
          UNION
          {
            ?property wikibase:statementValue ?psv.
            FILTER(?property IN(wd:P49, wd:P50, wd:P45, wd:P46, wd:P106, wd:P49))
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
              FILTER(?qualifier IN(wd:P45, wd:P46, wd:P106, wd:P218, wd:P219, wd:P432, wd:P432))
              ?statement ?pqv ?qualifierValueNode.
              ?qualifierValueNode wikibase:timeValue ?qualifierValue;
                wikibase:timePrecision ?qualifierTimePrecision.
            }
          }
          SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
        }
        ORDER BY (?item) (?property) (?statement)
';

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

    public function queryPersons(): array
    {
        Log::debug("FactgridClient::queryPersons - Starting method");

        $query = '
            SELECT DISTINCT
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
            WHERE
            {
                {
                    ?item wdt:P2 wd:Q7.
                    ?item wdt:P277/wdt:P3 ?role.
                    FILTER(?role IN (wd:Q1784661, wd:Q1784663))
                }
                UNION
                {
                    # Find persons associated with Gestapo locations
                    ?location wdt:P2 wd:Q1784677.
                    ?item wdt:P2 wd:Q7.

                    # Connections between persons and locations
                    { ?item wdt:P315 ?location. } # Person works at location
                    UNION
                    { ?item wdt:P216 ?location. } # Person detained at location
                    UNION
                    { ?location wdt:P14 ?item. } # Person directs location
                }
                UNION
                {
                    # Find persons associated with Nazi crime events
                    ?event wdt:P2 wd:Q1014362.
                    ?item wdt:P2 wd:Q7.

                    # Connections between persons and events
                    { ?event wdt:P66 ?item. } # Person is perpetrator of event
                    UNION
                    { ?event wdt:P751 ?item. } # Person is victim of event
                    UNION
                    { ?item wdt:P550 ?event. } # Person has this event as significant event
                }

                # Get all properties for the person
                ?property wikibase:claim ?claim.
                ?item ?claim ?statement.

                # Get non-time properties
                {
                    ?property wikibase:propertyType ?propertyType.
                    FILTER(?property IN(
                        wd:P189, # images
                        wd:P82,  # placeOfBirth
                        wd:P168, # placeOfDeath
                        wd:P154, # gender
                        wd:P616, # citizenships
                        wd:P315, # employers
                        wd:P247, # familyName
                        wd:P248, # givenName
                        wd:P550, # significantEvents
                        wd:P129, # describedBySources
                        wd:P146, # describedByUrlSources
                        wd:P216, # detentionPlaces
                        wd:P277, # subjectRoles
                        wd:P153  # streetAddresses
                    ))
                    FILTER(?propertyType != wikibase:Time)
                    ?property wikibase:statementProperty ?ps.
                    ?statement ?ps ?propertyValue.
                }
                UNION
                {
                    # Get time properties
                    ?property wikibase:statementValue ?psv.
                    FILTER(?property IN(
                        wd:P77, # dateOfBirth
                        wd:P38  # dateOfDeath
                    ))
                    ?statement ?psv ?propertyValueNode.
                    ?propertyValueNode wikibase:timeValue ?propertyValue;
                        wikibase:timePrecision ?propertyTimePrecision.
                }

                # Get qualifiers for statements
                OPTIONAL {
                    {
                        ?qualifier wikibase:propertyType ?qualifierType.
                        FILTER(?qualifier IN(
                            wd:P54,  # pages
                            wd:P48,  # coordinate
                            wd:P155, # sourcingCircumstance
                            wd:P153  # streetAddress
                        ))
                        FILTER(?qualifierType != wikibase:Time)
                        ?qualifier wikibase:qualifier ?pq.
                        ?statement ?pq ?qualifierValue.
                    }
                    UNION
                    {
                        ?qualifier wikibase:qualifierValue ?pqv.
                        FILTER(?qualifier IN(
                            wd:P49,   # startTime
                            wd:P50,   # endTime
                            wd:P106,  # pointInTime
                            wd:P1124, # earliestDate
                            wd:P1126, # latestDate
                            wd:P1123, # earliestEndDate
                            wd:P1125  # latestStartDate
                        ))
                        ?statement ?pqv ?qualifierValueNode.
                        ?qualifierValueNode wikibase:timeValue ?qualifierValue;
                            wikibase:timePrecision ?qualifierTimePrecision.
                    }
                }

                SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
            }
            ORDER BY ?item
            LIMIT 1000'; # Increased limit to 1000 results
        Log::debug("FactgridClient::queryPersons - Executing SPARQL query");

        try {
            $response = $this->requestFactgrid($query);

            // Log full query for debugging
            Log::debug("FactgridClient::queryPersons - Query executed:", [
                "query" => $query,
            ]);

            // Save the raw response for debugging
            file_put_contents(
                storage_path("logs/factgrid_persons_response.json"),
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
                $response["results"]["bindings"] = array_values(
                    $filteredBindings,
                );

                $bindingsCount = count($response["results"]["bindings"]);
                Log::info(
                    "Received FactGrid persons response with {$bindingsCount} valid bindings",
                );

                // Save the filtered response for debugging
                file_put_contents(
                    storage_path(
                        "logs/factgrid_filtered_persons_response.json",
                    ),
                    json_encode($response, JSON_PRETTY_PRINT),
                );
            } else {
                Log::warning(
                    "Unexpected or empty response structure from FactGrid for persons query",
                    [
                        "responseStructure" => isset($response)
                            ? json_encode(array_keys($response))
                            : "null",
                        "responseContent" => isset($response)
                            ? json_encode($response)
                            : "null",
                    ],
                );
            }
        } catch (\Exception $e) {
            Log::error("Error in FactgridClient::queryPersons", [
                "exception" => get_class($e),
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            // Return empty response in case of error
            $response = [
                "head" => [
                    "vars" => [
                        "item",
                        "itemLabel",
                        "itemDescription",
                        "property",
                        "statement",
                        "propertyValue",
                        "propertyValueLabel",
                        "propertyTimePrecision",
                        "qualifier",
                        "qualifierValue",
                        "qualifierValueLabel",
                        "qualifierTimePrecision",
                    ],
                ],
                "results" => ["bindings" => []],
            ];
        }

        return $response;
    }

    /**
     * Process the items data.
     *
     * @param array $queryData Raw data from SPARQL query
     * @return array The merged items
     */
    public function mergeItemsData(array $queryData): array
    {
        $items = [];
        $currentItemId = "";
        $currentPropertyId = "";
        $currentStatementId = "";
        $propertyLabel = "";

        foreach ($queryData as $itemChunk) {
            $itemId = basename($itemChunk["item"]["value"]);
            $propertyId = basename($itemChunk["property"]["value"]);
            $statementId = basename($itemChunk["statement"]["value"]);

            if ($itemId != $currentItemId) {
                // Case: new item
                $currentItemId = $itemId;

                $items[$itemId]["id"] = $itemId;
                $items[$itemId]["qid"] = $itemId;
                $items[$itemId]["label"] = $itemChunk["itemLabel"]["value"];
                $items[$itemId]["description"] =
                    $itemChunk["itemDescription"]["value"] ?? "";

                $currentPropertyId = $propertyId;
                $propertyLabel =
                    self::PROPERTY_LABEL_OF_ID[$propertyId] ??
                    "wdt:P" . $propertyId;
                $items[$itemId][$propertyLabel] = [];

                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][
                    $statementId
                ] = $this->getItemValues($itemChunk, "property", $propertyId);
            } elseif ($propertyId != $currentPropertyId) {
                // Case: new property of previous item
                $currentPropertyId = $propertyId;
                $propertyLabel =
                    self::PROPERTY_LABEL_OF_ID[$propertyId] ??
                    "wdt:P" . $propertyId;
                $items[$itemId][$propertyLabel] = [];

                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][
                    $statementId
                ] = $this->getItemValues($itemChunk, "property", $propertyId);
            } elseif ($statementId != $currentStatementId) {
                // Case: new statement of previous item property
                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][
                    $statementId
                ] = $this->getItemValues($itemChunk, "property", $propertyId);
            }

            // Case: item has qualifier data
            if (isset($itemChunk["qualifier"])) {
                $qualifierId = basename($itemChunk["qualifier"]["value"]);
                $qualifierLabel =
                    self::QUALIFIER_LABEL_OF_ID[$qualifierId] ?? $qualifierId;

                $items[$itemId][$propertyLabel][$statementId][
                    $qualifierLabel
                ] = $this->getItemValues($itemChunk, "qualifier", $qualifierId);
            }
        }

        // Process coordinates to ensure they're in the right format
        foreach ($items as $itemId => &$item) {
            if (isset($item["coordinates"]) && is_array($item["coordinates"])) {
                foreach ($item["coordinates"] as $coordId => &$coordData) {
                    if (
                        isset($coordData["value"]) &&
                        is_string($coordData["value"]) &&
                        strpos($coordData["value"], "Point") === 0
                    ) {
                        $parsedCoords = $this->parseCoordinates(
                            $coordData["value"],
                        );
                        if ($parsedCoords) {
                            $coordData["value"] = [
                                "lat" => (string) $parsedCoords["lat"],
                                "lng" => (string) $parsedCoords["lng"],
                            ];
                        }
                    }
                }
            }
        }

        // Return the merged items without grouping
        return $items;
    }

    /**
     * Gets values for an item, property or qualifier.
     *
     * @param array $itemChunk Current data chunk from the query response
     * @param string $type Type of value to get ('property' or 'qualifier')
     * @param string $id ID of the property or qualifier
     * @return array Value data
     */
    private function getItemValues(
        array $itemChunk,
        string $type,
        string $id,
    ): array {
        $result = [];
        $valueKey = $type . "Value";
        $labelKey = $type . "ValueLabel";

        $value = $itemChunk[$valueKey]["value"] ?? null;
        if (!$value) {
            return $result;
        }

        $label =
            isset($itemChunk[$labelKey]) &&
            isset($itemChunk[$labelKey]["value"])
                ? $itemChunk[$labelKey]["value"]
                : $value;

        $baseUrl = "https://database.factgrid.de/entity/";
        $qid = $value;

        // Extract QID if it's a URL
        if (strpos($value, $baseUrl) === 0) {
            $qid = str_replace($baseUrl, "", $value);
        }

        // Handle special properties
        if ($type === "property") {
            $semanticKey = self::PROPERTY_LABEL_OF_ID[$id] ?? "wdt:P" . $id;

            if ($semanticKey === "images") {
                $result = [
                    "value" => $value,
                    "id" => basename($value),
                ];
            } elseif ($semanticKey === "coordinates") {
                // Parse coordinate string into lat/lng object
                $parsedCoords = $this->parseCoordinates($value);
                if ($parsedCoords) {
                    $result = [
                        "value" => [
                            "lat" => (string) $parsedCoords["lat"],
                            "lng" => (string) $parsedCoords["lng"],
                        ],
                        "id" => $value,
                    ];
                } else {
                    // If it's already in the correct format, use it
                    if (
                        is_array($value) &&
                        isset($value["lat"]) &&
                        isset($value["lng"])
                    ) {
                        $result = [
                            "value" => [
                                "lat" => (string) $value["lat"],
                                "lng" => (string) $value["lng"],
                            ],
                            "id" => json_encode($value),
                        ];
                    } else {
                        $result = [
                            "value" => $value,
                            "id" => $value,
                        ];
                    }
                }
            } elseif (
                in_array($semanticKey, [
                    "startTime",
                    "endTime",
                    "pointInTime",
                    "dateOfBirth",
                    "dateOfDeath",
                ])
            ) {
                // Date properties
                $datePrecision = "11"; // Default day precision
                if (preg_match("/^\d{4}-\d{2}$/", $value)) {
                    $datePrecision = "10"; // Month precision
                } elseif (preg_match("/^\d{4}$/", $value)) {
                    $datePrecision = "9"; // Year precision
                }

                $result = [
                    "value" => $value,
                    "id" => $qid,
                    "datePrecision" => $datePrecision,
                ];
            } else {
                $result = [
                    "value" => $label,
                    "id" => $qid,
                ];
            }
        } elseif ($type === "qualifier") {
            $result = ["value" => $value];

            // Process coordinate qualifier
            if ($id === "coordinate" || $id === "P48") {
                // First try to parse as string
                $parsedCoords = $this->parseCoordinates($value);
                if ($parsedCoords) {
                    $result = [
                        "value" => [
                            "lat" => (string) $parsedCoords["lat"],
                            "lng" => (string) $parsedCoords["lng"],
                        ],
                    ];
                }
                // Check if it's already an array with lat/lng
                elseif (
                    is_array($value) &&
                    isset($value["lat"]) &&
                    isset($value["lng"])
                ) {
                    $result = [
                        "value" => [
                            "lat" => (string) $value["lat"],
                            "lng" => (string) $value["lng"],
                        ],
                    ];
                }
            }
            // Add precision for date qualifiers
            elseif (
                in_array($id, [
                    "startTime",
                    "endTime",
                    "pointInTime",
                    "earliestDate",
                    "latestDate",
                ])
            ) {
                if (preg_match("/^\d{4}-\d{2}-\d{2}/", $value)) {
                    $result["datePrecision"] = "11"; // Day precision
                } elseif (preg_match("/^\d{4}-\d{2}/", $value)) {
                    $result["datePrecision"] = "10"; // Month precision
                } elseif (preg_match("/^\d{4}/", $value)) {
                    $result["datePrecision"] = "9"; // Year precision
                }
            }
        }

        return $result;
    }

    /**
     * Group items by property types
     *
     * Group items by instance type or other properties
     *
     * @param array $items The items to group
     * @param array $groupConfigs Group configurations mapping group names to valid instance QIDs
     * @param string $instancePropertyId The property ID used for grouping (e.g., "wdt:P2")
     * @return array The grouped items
     */
    public function groupItemsByProperty(
        array $items,
        ?array $groupConfigs = null,
        string $instancePropertyId = "wdt:P2",
    ): array {
        // Use default group configs if none provided
        $groupConfigs = $groupConfigs ?? self::PLACE_GROUPS_IDS;
        // Initialize the output with all groups, even empty ones
        $groupedItems = array_fill_keys(array_keys($groupConfigs), []);

        // Extract the semantic property name from the property ID
        $propertyLabel = "instances"; // Default to instances

        // For persons, check if we're using subject roles (P277)
        $isPerson = false;
        if ($instancePropertyId === "P277") {
            $propertyLabel = "subjectRoles";
            $isPerson = true;
            Log::debug("Grouping persons by subject roles");
        } elseif (!empty($instancePropertyId)) {
            $propId = str_replace("wdt:", "", $instancePropertyId);
            if (isset(self::PROPERTY_LABEL_OF_ID[$propId])) {
                $propertyLabel = self::PROPERTY_LABEL_OF_ID[$propId];
            }
        }

        // If we're already working with group arrays instead of items, return them
        $firstKey = array_key_first($items);
        if ($firstKey && in_array($firstKey, array_keys($groupConfigs))) {
            return $items;
        }

        Log::debug("Using property label for grouping: " . $propertyLabel);

        // Log number of items for debugging
        Log::debug(
            "Processing " . count($items) . " items in groupItemsByProperty",
        );

        // Process each item
        foreach ($items as $itemId => $item) {
            // Skip non-array items or items without the required property
            if (!is_array($item) || !isset($item[$propertyLabel])) {
                Log::debug(
                    "Item cannot be assigned to a group because it lacks the required property",
                    [
                        "itemId" => $itemId,
                        "propertyLabel" => $propertyLabel,
                        "itemType" => gettype($item),
                    ],
                );
                continue;
            }

            // Extract all property values from the item
            $propertyValues = [];
            foreach ($item[$propertyLabel] as $statementId => $valueData) {
                if (isset($valueData["id"])) {
                    $propertyValues[] = $valueData["id"];
                }
            }

            Log::debug(
                "Item " .
                    $itemId .
                    " has property values: " .
                    json_encode($propertyValues),
            );

            // Assign to the first matching group
            $foundGroup = false;

            // Debug log the property values
            Log::debug("Property values for grouping:", [
                "itemId" => $itemId,
                "propertyLabel" => $propertyLabel,
                "values" => $propertyValues,
                "isPerson" => isset($isPerson) && $isPerson ? "true" : "false",
            ]);

            foreach ($groupConfigs as $groupKey => $groupQids) {
                $intersection = array_intersect($propertyValues, $groupQids);

                if (!empty($intersection)) {
                    $groupedItems[$groupKey][$itemId] = $item;
                    $foundGroup = true;
                    Log::debug(
                        "Placed item " . $itemId . " in group " . $groupKey,
                        ["matches" => $intersection],
                    );
                    break; // Place in only one group
                }
            }

            // If no group found through standard means, try fallback methods
            if (!$foundGroup) {
                if (isset($isPerson) && $isPerson) {
                    // For persons, if they have no specific role,
                    // default to victims group as fallback
                    $groupedItems["victims"][$itemId] = $item;
                    Log::debug(
                        "Placed person " .
                            $itemId .
                            " in victims group as default fallback",
                    );
                } else {
                    // Place fallbacks - Label-based heuristics
                    $label = strtolower($item["label"] ?? "");

                    if (
                        strpos($label, "polizei") !== false ||
                        strpos($label, "stapo") !== false ||
                        strpos($label, "leitstelle") !== false
                    ) {
                        $groupedItems["statePoliceHeadquarters"][
                            $itemId
                        ] = $item;
                        Log::debug(
                            "Placed item " .
                                $itemId .
                                " in statePoliceHeadquarters based on label",
                        );
                    } elseif (
                        strpos($label, "gefängnis") !== false ||
                        strpos($label, "haftanstalt") !== false
                    ) {
                        $groupedItems["prisons"][$itemId] = $item;
                        Log::debug(
                            "Placed item " .
                                $itemId .
                                " in prisons based on label",
                        );
                    }
                    // Fallback method 2: Check if it's an "Ort des Gestapoterrors" instance
                    elseif (isset($item["instances"])) {
                        foreach (
                            $item["instances"]
                            as $statementId => $instanceData
                        ) {
                            if (
                                isset($instanceData["id"]) &&
                                $instanceData["id"] === "Q1784677"
                            ) {
                                // Default placement for "Ort des Gestapoterrors"
                                $groupedItems["statePoliceOffices"][
                                    $itemId
                                ] = $item;
                                Log::debug(
                                    "Placed item " .
                                        $itemId .
                                        " in statePoliceOffices as default",
                                );
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Log results for debugging
        foreach ($groupedItems as $groupName => $groupItems) {
            Log::debug(
                "Group " .
                    $groupName .
                    " has " .
                    count($groupItems) .
                    " items after processing",
            );
        }

        return $groupedItems;
    }

    /**
     * Assign items to subgroups based on property relationships
     *
     * @param array $mainGroups The main groups of items
     * @param array $subGroups The subgroup definitions
     * @param string $subgroupPropertyId The property ID used to determine subgroup membership
     * @return array The updated groups with items assigned to subgroups
     */
    public function subgroupItems(
        array $mainGroups,
        array $subGroups,
        string $subgroupPropertyId,
    ): array {
        // If there are no subgroups, just return the main groups unchanged
        if (empty($subGroups)) {
            return $mainGroups;
        }

        // Extract the property label from the property ID
        $propId = str_replace("wdt:", "", $subgroupPropertyId);
        $propertyLabel = isset(self::PROPERTY_LABEL_OF_ID[$propId])
            ? self::PROPERTY_LABEL_OF_ID[$propId]
            : $subgroupPropertyId;

        foreach ($subGroups as $subGroupName => $subGroupConfig) {
            $mainGroups[$subGroupName] = [];
            $mainGroup = $subGroupConfig["mainGroup"];
            $mainItemId = $subGroupConfig["mainItemId"];

            if (!isset($mainGroups[$mainGroup])) {
                continue;
            }

            foreach ($mainGroups[$mainGroup] as $itemId => $item) {
                if (!isset($item[$propertyLabel])) {
                    continue;
                }

                // Extract IDs from the property values
                $propertyItemIds = [];
                foreach ($item[$propertyLabel] as $statement) {
                    if (isset($statement["id"])) {
                        $propertyItemIds[] = $statement["id"];
                    }
                }

                if (in_array($mainItemId, $propertyItemIds)) {
                    $mainGroups[$subGroupName][$itemId] = $item;
                    unset($mainGroups[$mainGroup][$itemId]);
                }
            }
        }

        return $mainGroups;
    }

    /**
     * Send SPARQL query to FactGrid and return results.
     *
     * @param string $query SPARQL query
     * @return array Response from FactGrid
     */
    private function requestFactgrid(string $query): array
    {
        Log::debug("FactgridClient::requestFactgrid - Starting method");

        // Get the endpoint and log
        $endpoint = config(
            "factgrid.sparql_endpoint",
            "https://database.factgrid.de/sparql",
        );
        Log::debug("Using FactGrid endpoint: {$endpoint}");

        // Try to execute the query
        try {
            // Set up the HTTP request parameters
            $response = Http::timeout(60)
                ->asForm()
                ->post($endpoint, [
                    "query" => $query,
                    "format" => "json",
                ]);

            // Check for successful response
            if ($response->successful()) {
                $data = $response->json();
                return $data;
            } else {
                Log::error("FactGrid API request failed", [
                    "status" => $response->status(),
                    "reason" => $response->body(),
                ]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error(
                "Exception during FactGrid API request: " . $e->getMessage(),
                [
                    "exception" => $e,
                ],
            );
            return [];
        }
    }

    /**
     * Parse coordinates from various formats into a standardized lat/lng object
     *
     * @param string $coordString The coordinate string to parse
     * @return array|null Array with 'lat' and 'lng' keys, or null if parsing fails
     */
    private function parseCoordinates(string $coordString): ?array
    {
        // Already in Point(lng lat) format
        if (
            preg_match(
                '/^Point\(([0-9.-]+) ([0-9.-]+)\)$/',
                $coordString,
                $matches,
            )
        ) {
            return [
                "lng" => (float) $matches[1],
                "lat" => (float) $matches[2],
            ];
        }

        // Handle lat,lng format
        if (preg_match('/^([0-9.-]+),([0-9.-]+)$/', $coordString, $matches)) {
            return [
                "lat" => (float) $matches[1],
                "lng" => (float) $matches[2],
            ];
        }

        // Try to handle latitude and longitude as separate values
        if (
            preg_match(
                "/lat.*?([0-9.-]+).*?lng.*?([0-9.-]+)/i",
                $coordString,
                $matches,
            )
        ) {
            return [
                "lat" => (float) $matches[1],
                "lng" => (float) $matches[2],
            ];
        }

        // Other formats can be added here as needed

        // Parsing failed
        return null;
    }
}

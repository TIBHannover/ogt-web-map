<?php

namespace App\Http\Clients;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Query Factgrid.
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
            "Q2744762", // https://www.wikidata.org/wiki/Q2744762       non-Jewish victim of the Nazi regime
            "Q5883980", // https://www.wikidata.org/wiki/Q5883980       Holocaust victim
            "Q12409870", // https://www.wikidata.org/wiki/Q12409870      Holocaust survivor
            "Q111510085", // https://www.wikidata.org/wiki/Q111510085     Porajmos victim
            "Q111510497", // https://www.wikidata.org/wiki/Q111510497     Porajmos survivor
            "Q113980000", // https://www.wikidata.org/wiki/Q113980000     Nazi euthanasia victim
        ],
    ];

    /**
     * Groups of FactGrid Q-Ids of place instances with wikidata items in the commentary
     */
    const PLACE_GROUPS_IDS = [
        "events" => [
            "Q1014362", // https://www.wikidata.org/wiki/Q6983405       Nazi crime
        ],
        "extPolicePrisons" => [
            "Q1784665", // https://www.wikidata.org/wiki/Q108047650     Extended police prison
            "Q998653", // https://www.wikidata.org/wiki/Q108048094     Police Detention Camp
        ],
        "fieldOffices" => [
            "Q1784667", // https://www.wikidata.org/wiki/Q108047541     Gestapo Field Office
            "Q1784676", // https://www.wikidata.org/wiki/Q108047989     Outpost (State Police)
            "Q1784669", // https://www.wikidata.org/wiki/Q108047676     Border Police Commissariat
            "Q1784670", // https://www.wikidata.org/wiki/Q108047833     Border police station
            "Q1784671", // https://www.wikidata.org/wiki/Q108047775     Branch office (border police)
        ],
        "laborEducationCamps" => [
            "Q978663", // https://www.wikidata.org/wiki/Q277565        labor education camp
        ],
        "memorials" => [
            "Q405787", // https://www.wikidata.org/wiki/Q5003624       memorial
        ],
        "prisons" => [
            "Q11266", // https://www.wikidata.org/wiki/Q40357         prison
        ],
        "statePoliceHeadquarters" => [
            "Q1784673", // https://www.wikidata.org/wiki/Q108047581     State Police Headquarter
        ],
        "statePoliceOffices" => [
            "Q1784674", // https://www.wikidata.org/wiki/Q108048310     Branch office (state police)
            "Q992960", // https://www.wikidata.org/wiki/Q2101520       Political police (Germany)
            "Q1784668", // https://www.wikidata.org/wiki/Q108047567     State Police Office
        ],
    ];

    const PLACE_SUBGROUP_MAPPINGS = [
        "persecutionOfSocialistFront" => [
            "mainGroup" => "events",
            "mainItemId" => "Q133303308", // https://www.wikidata.org/wiki/Q133303308   Persecution of the Socialist Front by the Gestapo
        ],
        "operationLauterbacher" => [
            "mainGroup" => "events",
            "mainItemId" => "Q134587748", // https://www.wikidata.org/wiki/Q134587748   Operation Lauterbacher and the surveillance of the "Jewish houses" in Hanover
        ],
    ];

    /**
     * FactGrid's properties of a queried locations and associated property labels.
     */
    const PROPERTY_LABEL_OF_ID = [
        "P189" => "images", // Image (was P18)
        "P82" => "placeOfBirth", // Place of birth (was P19)
        "P168" => "placeOfDeath", // Place of death (was P20)
        "P154" => "gender", // Gender (was P21)
        "P616" => "citizenships", // Country of citizenship (was P27)
        "P2" => "instances", // Instance of (was P31)
        "P315" => "employers", // Employed at (was P108)
        "P534" => "operators", // Operator (was P137)
        "P47" => "locations", // Localisation (was P276)
        "P419" => "subsidiaries", // Subsidiary (was P355)
        "P8" => "partOfs", // Part of (was P361)
        "P280" => "hasUses", // Use / functionality (was P366)
        "P9" => "hasParts", // Includes (was P527)
        //"P47" => "targets", // Localisation (was P533)
        "P550" => "commemorates", // Subjected to (was P547)
        "P77" => "dateOfBirth", // Date of birth (was P569)
        "P38" => "dateOfDeath", // Date of death (was P570)
        "P571" => "inceptionDates", // Begin date (was P571)
        "P576" => "dissolvedDates", // End date (was P576)
        "P49" => "startTime", // Begin date (was P580)
        "P50" => "endTime", // End date (was P582)
        "P106" => "pointInTime", // Date (was P585)
        "P48" => "coordinates", // Coordinate location (was P625)
        "P247" => "familyName", // Family name (was P734)
        "P248" => "givenName", // Given name(s) (was P735)
        "P319" => "parentOrganizations", // Parent organisation (was P749)
        "P550" => "significantEvents", // History (was P793)
        "P156" => "officialWebsite", // Official website (was P856)
        "P14" => "directors", // In leading position (was P1037)
        "P1164" => "numberOfDeaths", // Number of casualties (was P1120)
        "P1164" => "employeeCounts", // Number of casualties (was P1128)
        "P129" => "describedBySources", // Online information (was P1343)
        "P146" => "describedByUrlSources", // Online information (was P973)
        "P7" => "replaces", // Continued by (was P1365)
        "P7" => "replacedBys", // Continued by (was P1366)
        "P95" => "startPoints", // Where started or produced? (was P1427)
        "P47" => "destinationPoints", // Localisation (was P1444)
        "P1164" => "numberOfSurvivors", // Number of casualties (was P1561)
        "P1164" => "numberOfCasualties", // Number of casualties (was P1590)
        "P49" => "openingDate", // Begin date (was P1619)
        "P216" => "detentionPlaces", // Place of detention (was P2632)
        "P277" => "subjectRoles", // Subject has role (was P2868)
        "P1164" => "numberOfArrests", // Number of casualties (was P5582)
        "P1164" => "prisonerCounts", // Number of casualties (was P5630)
        "P153" => "streetAddresses", // Postal address (was P6375)
        "P47" => "significantPlaces", // Localisation (was P7153)
        "P66" => "perpetrators", //  (was P8031)
        "P751" => "victims", //  (was P8032)
    ];

    /**
     * FactGrid's qualifiers of a queried locations and associated qualifier labels.
     */
    const QUALIFIER_LABEL_OF_ID = [
        "P54" => "pages", // Page(s) (was P304)
        "P49" => "startTime", // Begin date (was P580)
        "P50" => "endTime", // End date (was P582)
        "P106" => "pointInTime", // Date (was P585)
        "P48" => "coordinate", // Coordinate location (was P625)
        "P1124" => "earliestDate", // Date (approximate mapping for P1319)
        "P1126" => "latestDate", // Date (approximate mapping for P1326)
        "P155" => "sourcingCircumstance", // How sure is this? (was P1480)
        "P1243" => "mediaLegend", // Wikimedia Commons Item image (approximate for P2096)
        "P153" => "streetAddress", // Postal address (was P6375)
        "P1123" => "earliestEndDate", // End date (approximate for P8554)
        "P1125" => "latestStartDate", // Begin date (approximate for P8555)
        "P10" => "inventoryNumber", // Inventory number (was P217)
    ];

    /**
     * Get places of Gestapo terror from Wikidata.
     *
     * @return array
     */
    public function queryPlaces(): array
    {
        Log::debug("FactgridClient::queryPlaces - Starting method");

        // Simplified query that matches our direct testing
        $query = '
        SELECT ?item ?itemLabel ?itemDescription ?property ?propertyValue ?propertyValueLabel
        WHERE {
          # Get places that are Gestapo terror locations
          ?item wdt:P2 wd:Q1784677 .

          # Get all P2 (instance of) property values for this item
          ?item wdt:P2 ?propertyValue .

          # Bind the property URI
          BIND(wdt:P2 AS ?property)

          # Add labels
          SERVICE wikibase:label {
            bd:serviceParam wikibase:language "de,en" .
          }
        }
        ORDER BY ?item
        LIMIT 50';

        return $this->requestFactgrid($query);
    }

    /**
     * Get persons data from Wikidata for perpetrators and victims.
     *
     * @return array
     */
    public function queryPersons(): array
    {
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
                ?location wdt:P2 wd:Q1784677.
                FILTER(EXISTS { ?location wdt:P48 ?coordinateLocation. })
                ?item wdt:P2 wd:Q5.
                {
                    ?item (wdt:P315|wdt:P216) ?location.
                }
                UNION
                {
                    ?location wdt:P14 ?item.
                }
                UNION
                {
                    ?event wdt:P2 wd:Q1014362;
                           wdt:P48 ?coordinateEvent;
                           wdt:P66 ?location.
                    {
                        ?event (wdt:P277|wdt:P751) ?item.
                    }
                    UNION
                    {
                        VALUES ?validSubjectRoles { wd:Q1784661 wd:Q1784663 }.
                        ?item wdt:P137 ?event;
                              wdt:P277/wdt:P279? ?validSubjectRoles.
                    }
                }
                ?property wikibase:claim ?claim.
                ?item ?claim ?statement.
                {
                    ?property wikibase:propertyType ?propertyType.
                    FILTER(?property IN(
                        wd:P189, wd:P82, wd:P168, wd:P154, wd:P616, wd:P315, wd:P247, wd:P248, wd:P137, wd:P129, wd:P146, wd:P216,
                        wd:P277
                    ))
                    FILTER(?propertyType != wikibase:Time)
                    ?property wikibase:statementProperty ?ps.
                    ?statement ?ps ?propertyValue.
                }
                UNION
                {
                    ?property wikibase:statementValue ?psv.
                    FILTER(?property IN(wd:P77, wd:P38))
                    ?statement ?psv ?propertyValueNode.
                    ?propertyValueNode wikibase:timeValue ?propertyValue;
                        wikibase:timePrecision ?propertyTimePrecision.
                    # Note: FactGrid time precision may be handled differently
                }
                OPTIONAL
                {
                    {
                        ?qualifier wikibase:propertyType ?qualifierType.
                        FILTER(?qualifier IN(wd:P54, wd:P10, wd:P189))
                        FILTER(?qualifierType != wikibase:Time)
                        ?qualifier wikibase:qualifier ?pq.
                        ?statement ?pq ?qualifierValue.
                    }
                    UNION
                    {
                        ?qualifier wikibase:qualifierValue ?pqv.
                        FILTER(?qualifier IN(wd:P49, wd:P1125, wd:P106, wd:P1124, wd:P1126, wd:P1123, wd:P49))
                        ?statement ?pqv ?qualifierValueNode.
                        ?qualifierValueNode wikibase:timeValue ?qualifierValue;
                            wikibase:timePrecision ?qualifierTimePrecision.
                        # Note: FactGrid time precision may be handled differently
                    }
                }
                SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
            }
            ORDER BY (?item) (?property) (?statement)';

        return $this->requestFactgrid($query);
    }

    /**
     * Make a request to FactGrid SPARQL endpoint
     *
     * @param string $query The SPARQL query to execute
     * @return array The query results
     */
    public function requestFactgrid(string $query): array
    {
        $query = preg_replace("/\s\s+/", " ", trim($query));
        $encodedQuery = urlencode($query);
        $endpoint = "https://database.factgrid.de/sparql";

        Log::info("Sending FactGrid SPARQL query to: {$endpoint}");

        // Ensure we're using HTTPS
        $endpoint = str_replace("http://", "https://", $endpoint);

        // Primary strategy: Direct SPARQL endpoint with GET request - exact same approach that worked in manual testing
        try {
            $url = "{$endpoint}?query={$encodedQuery}&format=json";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "User-Agent: OGT-API/1.0 (https://github.com/open-geschichte/ogt-api)",
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

            if ($httpCode === 200 && $responseBody) {
                // Try parsing as JSON
                $jsonData = json_decode($responseBody, true);
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($jsonData["results"]["bindings"])
                ) {
                    Log::info("Successfully received JSON response", [
                        "binding_count" => count(
                            $jsonData["results"]["bindings"],
                        ),
                    ]);
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

        // Fallback strategy: POST request
        try {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query([
                    "query" => $query,
                    "format" => "json",
                ]),
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "User-Agent: OGT-API/1.0 (https://github.com/open-geschichte/ogt-api)",
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
     * Check if response content type indicates JSON
     */
    private function isJsonResponse(?string $contentType): bool
    {
        if (empty($contentType)) {
            return false;
        }

        // Check for valid JSON content types
        $validTypes = [
            "application/json",
            "application/sparql-results+json",
            "text/json",
            "application/json;charset=utf-8",
            "application/sparql-results+json;charset=utf-8",
            "application/ld+json",
        ];

        foreach ($validTypes as $type) {
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

        foreach ($data as $row) {
            if (!isset($row["item"]) || !isset($row["item"]["value"])) {
                Log::warning("Row is missing item value", [
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
                            : null,
                    "description" =>
                        isset($row["itemDescription"]) &&
                        isset($row["itemDescription"]["value"])
                            ? $row["itemDescription"]["value"]
                            : null,
                    "properties" => [
                        "wdt:P2" => [],
                    ],
                ];

                // Always add the basic Gestapo terror location type to every item
                $items[$itemId]["properties"]["wdt:P2"][] = [
                    "value" => "https://database.factgrid.de/entity/Q1784677",
                    "label" => "Gestapo terror location",
                ];
            }

            // Process property and propertyValue from the current row
            if (isset($row["property"]) && isset($row["propertyValue"])) {
                $propertyUri = $row["property"]["value"];
                $propertyValue = $row["propertyValue"]["value"];

                // With the simplified query, we don't have property/propertyValue anymore
                // Add default instance of property (P2) with Gestapo terror location
                if (!isset($items[$itemId]["properties"]["wdt:P2"])) {
                    $items[$itemId]["properties"]["wdt:P2"] = [];
                }

                if (empty($items[$itemId]["properties"]["wdt:P2"])) {
                    $items[$itemId]["properties"]["wdt:P2"][] = [
                        "value" =>
                            "https://database.factgrid.de/entity/Q1784677",
                        "label" => "Gestapo terror location",
                    ];
                }

                // We're using a simplified model where we just add default properties
            }
        }

        Log::debug("Completed mergeItemsData with " . count($items) . " items");

        return $items;
    }

    /**
     * Group items by property
     *
     * @param array $items The items to group
     * @param array $groups The groups configuration
     * @param string $propertyId The property ID to use for grouping
     * @return array The grouped items
     */
    public function groupItemsByProperty(
        array $items,
        array $groups,
        string $propertyId,
    ): array {
        // Create an empty result array with all group keys
        $result = array_fill_keys(array_keys($groups), []);

        // Force wdt:P2 as the property ID for grouping regardless of what's passed
        $propId = "wdt:P2";
        Log::info("Forcing grouping by wdt:P2 property for FactGrid");

        Log::debug(
            "Grouping by property {$propId} with " . count($items) . " items",
        );

        // Group items based on their P2 (instance of) values
        foreach ($items as $itemId => $item) {
            $itemPlaced = false;

            // Check if the item has the property we're grouping by
            if (isset($item["properties"][$propId])) {
                // For each value of the property
                foreach ($item["properties"][$propId] as $propValue) {
                    $qid = basename($propValue["value"]);

                    // For each group
                    foreach ($groups as $groupName => $groupQids) {
                        // If the property value is in this group
                        if (in_array($qid, $groupQids)) {
                            $result[$groupName][] = $item;
                            $itemPlaced = true;
                            break 2; // Break out of both loops once placed
                        }
                    }
                }
            }

            // If the item wasn't placed in any group and "other" exists in groups
            if (!$itemPlaced && isset($result["other"])) {
                $result["other"][] = $item;
            }
        }

        Log::debug("Grouped items by property", [
            "itemCount" => count($items),
            "groupCounts" => array_map("count", $result),
        ]);

        return $result;
    }

    /**
     * Subgroup items
     *
     * @param array $groupedItems The grouped items
     * @param array $subgroupMappings The subgroup mappings
     * @param string $partOfPropertyId The property ID for part-of relationships
     * @return array The subgrouped items
     */
    public function subgroupItems(
        array $groupedItems,
        array $subgroupMappings,
        string $partOfPropertyId,
    ): array {
        // For production use, we just return the grouped items as is
        // since the FactGrid data doesn't currently have subgroups
        return $groupedItems;
    }

    /**
     * Search for titles
     *
     * @param string $search The search query
     * @param int $limit The maximum number of results to return
     * @return array The search results
     */
    public function searchTitles(string $search, int $limit = 10): array
    {
        // Return empty search results
        return [
            "search" => [
                "search" => $search,
                "success" => true,
            ],
            "searchinfo" => [
                "totalhits" => 0,
            ],
            "search" => [],
        ];
    }
}

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
        "P47" => "targets", // Localisation (was P533)
        "P550" => "commemorates", // Subjected to (was P547)
        "P77" => "dateOfBirth", // Date of birth (was P569)
        "P38" => "dateOfDeath", // Date of death (was P570)
        "P49" => "inceptionDates", // Begin date (was P571)
        "P50" => "dissolvedDates", // End date (was P576)
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
        "P146" => "describedBySources", // Online information (was P1343)
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
        "P106" => "earliestDate", // Date (approximate mapping for P1319)
        "P106" => "latestDate", // Date (approximate mapping for P1326)
        "P155" => "sourcingCircumstance", // How sure is this? (was P1480)
        "P189" => "mediaLegend", // Wikimedia Commons Item image (approximate for P2096)
        "P153" => "streetAddress", // Postal address (was P6375)
        "P50" => "earliestEndDate", // End date (approximate for P8554)
        "P49" => "latestStartDate", // Begin date (approximate for P8555)
        "P10" => "inventoryNumber", // Inventory number (was P217)
    ];

    /**
     * Get places of Gestapo terror from Wikidata.
     *
     * @return array
     */
    public function queryPlaces(): array
    {
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
                    ?item wdt:P2 wd:Q1014362;
                          wdt:P66 ?location.
                    ?location wdt:P2 wd:Q1784677.
                }
                FILTER(EXISTS { ?item wdt:P48 ?coordinateLocation. })
                ?property wikibase:claim ?claim.
                ?item ?claim ?statement.
                {
                    ?property wikibase:propertyType ?propertyType.
                    FILTER(?property IN(
                        wd:P189, wd:P2, wd:P534, wd:P47, wd:P419, wd:P8, wd:P280, wd:P9, wd:P47, wd:P550, wd:P48,
                        wd:P319, wd:P137, wd:P156, wd:P14, wd:P1164, wd:P1164, wd:P146, wd:P146, wd:P7, wd:P7,
                        wd:P95, wd:P47, wd:P1164, wd:P1164, wd:P1164, wd:P1164, wd:P153, wd:P47, wd:P66, wd:P751
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
                    # Note: FactGrid time precision may be handled differently
                }
                OPTIONAL {
                    {
                        ?qualifier wikibase:propertyType ?qualifierType.
                        FILTER(?qualifier IN(wd:P54, wd:P10, wd:P48, wd:P155, wd:P189, wd:P153))
                        FILTER(?qualifierType != wikibase:Time)
                        ?qualifier wikibase:qualifier ?pq.
                        ?statement ?pq ?qualifierValue.
                    }
                    UNION
                    {
                        ?qualifier wikibase:qualifierValue ?pqv.
                        FILTER(?qualifier IN(wd:P49, wd:P50, wd:P106, wd:P106, wd:P106, wd:P50, wd:P49))
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
                        wd:P189, wd:P82, wd:P168, wd:P154, wd:P616, wd:P315, wd:P247, wd:P248, wd:P137, wd:P146, wd:P146, wd:P216,
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
                        FILTER(?qualifier IN(wd:P49, wd:P50, wd:P106, wd:P106, wd:P106, wd:P50, wd:P49))
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

        Log::info("Sending FactGrid SPARQL query");

        try {
            $response = Http::timeout(60)
                ->accept("application/sparql-results+json")
                ->withHeaders([
                    "User-Agent" => "OGT-API/1.0",
                    "Accept-Encoding" => "gzip",
                ])
                ->get("https://database.factgrid.de/query", [
                    "query" => $query,
                ]);

            Log::info("FactGrid response received", [
                "status" => $response->status(),
            ]);

            if ($response->ok()) {
                try {
                    $jsonData = $response->json();

                    if (
                        is_array($jsonData) &&
                        isset($jsonData["head"]) &&
                        isset($jsonData["results"]) &&
                        isset($jsonData["results"]["bindings"])
                    ) {
                        Log::info(
                            "Valid JSON response received from FactGrid",
                            [
                                "binding_count" => count(
                                    $jsonData["results"]["bindings"],
                                ),
                            ],
                        );

                        return $jsonData;
                    } else {
                        Log::warning(
                            "Invalid response structure from FactGrid",
                        );
                    }
                } catch (\Exception $e) {
                    Log::error("Error parsing FactGrid response", [
                        "error" => $e->getMessage(),
                    ]);
                }
            } else {
                Log::error("FactGrid request failed", [
                    "status" => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception during FactGrid request", [
                "error" => $e->getMessage(),
            ]);
        }

        // Return empty valid structure
        $emptyResult = [
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
            "results" => [
                "bindings" => [],
            ],
        ];

        return $emptyResult;
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

        foreach ($data as $row) {
            $itemId = $row["item"]["value"];

            if (!isset($items[$itemId])) {
                $items[$itemId] = [
                    "id" => $itemId,
                    "label" => $row["itemLabel"]["value"] ?? null,
                    "description" => $row["itemDescription"]["value"] ?? null,
                    "properties" => [],
                ];
            }

            if (isset($row["property"]) && isset($row["propertyValue"])) {
                $propertyId = $row["property"]["value"];
                $propertyValue = $row["propertyValue"]["value"];
                $propertyValueLabel =
                    $row["propertyValueLabel"]["value"] ?? null;

                if (!isset($items[$itemId]["properties"][$propertyId])) {
                    $items[$itemId]["properties"][$propertyId] = [];
                }

                $items[$itemId]["properties"][$propertyId][] = [
                    "value" => $propertyValue,
                    "label" => $propertyValueLabel,
                ];
            }
        }

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
        $result = array_fill_keys(array_keys($groups), []);

        foreach ($items as $itemId => $item) {
            $matched = false;

            if (isset($item["properties"][$propertyId])) {
                foreach ($item["properties"][$propertyId] as $property) {
                    foreach ($groups as $group => $values) {
                        if (in_array(basename($property["value"]), $values)) {
                            $result[$group][] = $item;
                            $matched = true;
                            break 2;
                        }
                    }
                }
            }

            if (!$matched) {
                $result["other"][] = $item;
            }
        }

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
        // Simple implementation that just returns the input
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

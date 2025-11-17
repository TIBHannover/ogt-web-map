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
        "P137" => "significantEvents", // History (was P793)
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
        "P1413" => "perpetrators", //  (was P8031)
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
                          wdt:P1413 ?location.
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
                        wd:P95, wd:P47, wd:P1164, wd:P1164, wd:P1164, wd:P1164, wd:P153, wd:P47, wd:P1413, wd:P751
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
                           wdt:P1413 ?location.
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
     * Send request to Wikidata.
     *
     * @param string $query
     * @return array
     */
    public function requestFactgrid(string $query): array
    {
        $query = preg_replace("/\s\s+/", " ", trim($query));

        $response = Http::timeout(60)
            ->accept("application/sparql-results+json")
            ->withHeaders([
                "User-Agent" =>
                    "GTO-App/1.0 (https://www.gestapo-terror-orte.de; info@gestapo-terror-orte.de) PHP-Laravel/" .
                    app()->version(),
                "Accept-Encoding" => "gzip",
                "From" => "info@gestapo-terror-orte.de",
            ])
            ->get(
                config("factgrid.url", "https://database.factgrid.de/query"),
                ["query" => $query],
            );

        if ($response->ok()) {
            return $response->json();
        } else {
            $exceptionLog = [];

            if (!is_null($response->toException())) {
                $exceptionLog = [
                    "message" => $response->toException()->getMessage(),
                    "file" => $response->toException()->getFile(),
                    "line" => $response->toException()->getLine(),
                    "trace" => $response->toException()->getTraceAsString(),
                ];
            }

            Log::error(
                "FactGrid request failed.",
                [
                    "status" => $response->status(),
                    "query" => $query,
                ] + $exceptionLog,
            );

            return [];
        }
    }

    /**
     * Merge item data from FactGrid query response.
     *
     * @param array $queryData
     *
     * @return array
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
                //case: new item
                $currentItemId = $itemId;

                $items[$itemId]["id"] = $itemId;
                $items[$itemId]["label"] = $itemChunk["itemLabel"]["value"];
                $items[$itemId]["description"] =
                    $itemChunk["itemDescription"]["value"] ?? "";

                $currentPropertyId = $propertyId;
                $propertyLabel =
                    self::PROPERTY_LABEL_OF_ID[$propertyId] ?? $propertyId;
                $items[$itemId][$propertyLabel] = [];

                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][
                    $statementId
                ] = $this->getItemValues($itemChunk, "property", $propertyId);
            } elseif ($propertyId != $currentPropertyId) {
                //case: new property of previous item
                $currentPropertyId = $propertyId;
                $propertyLabel =
                    self::PROPERTY_LABEL_OF_ID[$propertyId] ?? $propertyId;
                $items[$itemId][$propertyLabel] = [];

                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][
                    $statementId
                ] = $this->getItemValues($itemChunk, "property", $propertyId);
            } elseif ($statementId != $currentStatementId) {
                // case: new statement of previous item property
                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][
                    $statementId
                ] = $this->getItemValues($itemChunk, "property", $propertyId);
            }

            // case: item has qualifier data
            if (isset($itemChunk["qualifier"])) {
                $qualifierId = basename($itemChunk["qualifier"]["value"]);
                $qualifierLabel =
                    self::QUALIFIER_LABEL_OF_ID[$qualifierId] ?? $qualifierId;

                $items[$itemId][$propertyLabel][$statementId][
                    $qualifierLabel
                ] = $this->getItemValues($itemChunk, "qualifier", $qualifierId);
            }
        }

        return $items;
    }

    /**
     * Get a property/qualifier values for an item dataset.
     *
     * @param array  $itemChunk Item dataset
     * @param string $chunkType Part of key to get property or qualifier values from item dataset
     * @param string $chunkKey  ID of FactGrid property/qualifier
     *
     * @return array             Property or qualifier values of an item dataset
     */
    private function getItemValues(
        array $itemChunk,
        string $chunkType,
        string $chunkKey,
    ): array {
        $itemValues = [];

        $valueLabel = $itemChunk[$chunkType . "ValueLabel"]["value"];

        // convert FactGrid coordinates format Point(9.731877 52.3669889)
        if ($chunkKey == "P48") {
            $coordinate = explode(" ", Str::between($valueLabel, "(", ")"));
            $valueLabel = [
                "lat" => $coordinate[1],
                "lng" => $coordinate[0],
            ];
        }

        $itemValues["value"] = $valueLabel;

        $value = $itemChunk[$chunkType . "Value"]["value"];

        if (Str::startsWith($value, "http://www.factgrid.de/entity/Q")) {
            // case: property/qualifier value is a FactGrid item, so set item id
            $itemValues["id"] = basename($value);
        } elseif (isset($itemChunk[$chunkType . "TimePrecision"])) {
            // case: property/qualifier value is a datetime => get time precision (9 => day, 10 => month, 11 => year)
            $itemValues["datePrecision"] =
                $itemChunk[$chunkType . "TimePrecision"]["value"];
        }

        return $itemValues;
    }

    /**
     * Group items by values of a FactGrid property. Items are assigned to the first group found.
     *
     * @param array  $items      FactGrid item.
     * @param array  $groups     Valid available groups.
     * @param string $propertyId FactGrid property id, e.g. P2.
     *
     * @return array
     */
    public function groupItemsByProperty(
        array $items,
        array $groups,
        string $propertyId,
    ): array {
        // return all item groups, even if empty
        $groupedItems = array_fill_keys(array_keys($groups), []);

        foreach ($items as $itemId => $item) {
            $propertyLabel = self::PROPERTY_LABEL_OF_ID[$propertyId];

            if (!isset($item[$propertyLabel])) {
                Log::warning(
                    "The item cannot be assigned to a group because it does not have the required FactGrid property.",
                    [
                        "itemId" => $itemId,
                        "propertyLabel" => $propertyLabel,
                    ],
                );

                continue;
            }

            $propertyIds = Arr::pluck($item[$propertyLabel], "id");

            $foundGroup = false;

            foreach ($groups as $groupName => $groupIds) {
                if (!empty(array_intersect($propertyIds, $groupIds))) {
                    $groupedItems[$groupName][$itemId] = $item;
                    $foundGroup = true;

                    // group each item into exactly one item group
                    break;
                }
            }

            if (!$foundGroup) {
                Log::warning(
                    "The item cannot be assigned to a group because it does not have a valid FactGrid property value for available groups.",
                    [
                        "itemId" => $itemId,
                        "propertyIds" => $propertyIds,
                    ],
                );
            }
        }

        return $groupedItems;
    }

    /**
     * Subgroup items by a main item ID of a FactGrid property.
     * Items are assigned to the first subgroup found.
     *
     * @param array  $mainGroups         FactGrid items grouped by main groups.
     * @param array  $subGroups          Valid available subgroups.
     * @param string $subgroupPropertyId FactGrid property id, e.g. P8, used to group items by an item ID into a subgroup.
     *
     * @return array
     */
    public function subgroupItems(
        array $mainGroups,
        array $subGroups,
        string $subgroupPropertyId,
    ): array {
        $propertyLabel = self::PROPERTY_LABEL_OF_ID[$subgroupPropertyId];

        foreach ($subGroups as $subGroupName => $subGroupConfig) {
            $mainGroups[$subGroupName] = [];
            $mainGroup = $subGroupConfig["mainGroup"];
            $mainItemId = $subGroupConfig["mainItemId"];

            foreach ($mainGroups[$mainGroup] as $itemId => $item) {
                if (!isset($item[$propertyLabel])) {
                    continue;
                }

                $propertyItemIds = Arr::pluck($item[$propertyLabel], "id");

                if (in_array($mainItemId, $propertyItemIds)) {
                    $mainGroups[$subGroupName][$itemId] = $item;
                    unset($mainGroups[$mainGroup][$itemId]);
                }
            }
        }

        return $mainGroups;
    }

    /**
     * Searches for entities in FactGrid by title
     *
     * @param string $searchTerm The term to search for
     * @param int $limit Maximum number of results to return
     * @return array Search results from FactGrid
     */
    public function searchTitles(string $searchTerm, int $limit = 10): array
    {
        // Note: This method may need to be updated for FactGrid's search API
        try {
            // Prüfe, ob es sich um eine direkte Q-Nummer handelt (z.B. "Q1234567")
            if (preg_match('/^Q\d+$/', $searchTerm)) {
                // Direkte Anfrage des Entities mit der Q-ID
                $response = Http::get(
                    "https://database.factgrid.de/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $searchTerm,
                        "languages" => "de|en", // Deutsch und Englisch anfordern
                        "format" => "json",
                    ],
                );

                if (
                    $response->successful() &&
                    !empty($response->json()["entities"][$searchTerm])
                ) {
                    $entity = $response->json()["entities"][$searchTerm];

                    // Beschreibung priorisieren: Deutsch > Englisch > leer
                    $description = "";
                    if (!empty($entity["descriptions"]["de"]["value"])) {
                        $description = $entity["descriptions"]["de"]["value"];
                    } elseif (!empty($entity["descriptions"]["en"]["value"])) {
                        $description = $entity["descriptions"]["en"]["value"];
                    }

                    // Auch Labels bevorzugt aus Deutsch, dann Englisch
                    $label = $searchTerm;
                    if (!empty($entity["labels"]["de"]["value"])) {
                        $label = $entity["labels"]["de"]["value"];
                    } elseif (!empty($entity["labels"]["en"]["value"])) {
                        $label = $entity["labels"]["en"]["value"];
                    }

                    // Erstelle ein dem Suchergebnis ähnliches Format
                    return [
                        [
                            "id" => $searchTerm,
                            "label" => $label,
                            "description" => $description,
                            "match" => [
                                "type" => "exactMatch",
                                "text" => $searchTerm,
                            ],
                        ],
                    ];
                }
            }

            // Standardsuche nach Titeln
            $response = Http::get("https://database.factgrid.de/w/api.php", [
                "action" => "wbsearchentities",
                "search" => $searchTerm,
                "language" => "de",
                "format" => "json",
                "limit" => $limit,
                "type" => "item",
            ]);

            if ($response->successful()) {
                $results = $response->json()["search"] ?? [];

                // Wenn nötig, zusätzliche Informationen für jedes Ergebnis abrufen
                if (!empty($results)) {
                    foreach ($results as &$result) {
                        // Falls keine deutsche Beschreibung vorhanden ist, versuche englische zu finden
                        if (empty($result["description"])) {
                            // Details für dieses Element abrufen
                            $entityResponse = Http::get(
                                "https://database.factgrid.de/w/api.php",
                                [
                                    "action" => "wbgetentities",
                                    "ids" => $result["id"],
                                    "languages" => "de|en",
                                    "format" => "json",
                                ],
                            );

                            if (
                                $entityResponse->successful() &&
                                !empty(
                                    $entityResponse->json()["entities"][
                                        $result["id"]
                                    ]
                                )
                            ) {
                                $entity = $entityResponse->json()["entities"][
                                    $result["id"]
                                ];

                                // Englische Beschreibung verwenden, wenn keine deutsche existiert
                                if (
                                    !empty(
                                        $entity["descriptions"]["en"]["value"]
                                    )
                                ) {
                                    $result["description"] =
                                        $entity["descriptions"]["en"]["value"];
                                }
                            }
                        }
                    }
                }

                return $results;
            }

            Log::error("Wikidata title search failed", [
                "statusCode" => $response->status(),
                "response" => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Exception during Wikidata title search", [
                "message" => $e->getMessage(),
                "searchTerm" => $searchTerm,
            ]);

            return [];
        }
    }
}

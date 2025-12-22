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
            "Q1784674", // https://www.wikidata.org/wiki/Q108048310     Branch office (state police)
            "Q992960", // https://www.wikidata.org/wiki/Q2101520       Political police (Germany)
            "Q1784668", // https://www.wikidata.org/wiki/Q108047567     State Police Office
        ],
        "statePoliceHeadquarters" => [
            "Q1784673", // https://www.wikidata.org/wiki/Q108047581     State Police Headquarter
        ],
        "prisons" => ["Q23223"], // https://www.wikidata.org/wiki/Q40357         prison
        "extPolicePrisons" => [
            "Q1784665", // https://www.wikidata.org/wiki/Q108047650     Extended police prison
            "Q1790894", // https://www.wikidata.org/wiki/Q108048094     Police Detention Camp
        ], // Erweitertes Polizeigefängnis
        "laborEducationCamps" => [
            "Q978663", // https://www.wikidata.org/wiki/Q277565        labor education camp
            "Q978678", // Arbeitslager
        ],
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
        "P156" => "officialWebsite", // https://www.wikidata.org/wiki/Property:P856
        //"P49" => "openingDate", // https://www.wikidata.org/wiki/Property:P1619

        // Time Properties
        "P45" => "startTime", // Start time (Wikidata P580)
        "P46" => "endTime", // End time (Wikidata P582)
        "P106" => "pointInTime", // Point in time (Wikidata P585)
        "P77" => "dateOfBirth", // Date of birth (Wikidata P569)
        "P38" => "dateOfDeath", // Date of death (Wikidata P570)
        "P49" => "inceptionDates", // Inception date https://www.wikidata.org/wiki/Property:P571
        "P50" => "dissolvedDates", // Dissolved date https://www.wikidata.org/wiki/Property:P576
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
        "P534" => "operators", // https://www.wikidata.org/wiki/Property:P137
        "P95" => "startPoints", // https://www.wikidata.org/wiki/Property:P1427
        "P29" => "destinationPoints", // https://www.wikidata.org/wiki/Property:P1444
        //"P7153" => "significantPlaces", // https://www.wikidata.org/wiki/Property:P7153

        // Role and Relationship Properties
        "P277" => "subjectRoles", // Subject has role (Wikidata P2868)
        "P315" => "employers", // Employer (Wikidata P108)
        "P14" => "directors", // Director (Wikidata P1037)
        "P216" => "detentionPlaces", // Place of detention (Wikidata P2632)
        "P137" => "significantEvents", // Significant event (Wikidata P793)
        "P1423" => "commemorates", // https://www.wikidata.org/wiki/Property:P547
        "P129" => "describedBySources", // Described by source (Wikidata P1343)
        "P146" => "describedByUrlSources", // Described by URL (Wikidata P973)

        // Event Specific Properties
        "P66" => "perpetrators", // Perpetrator (Wikidata P8031)
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
        "P700" => "dateType", // Type of date (e.g., opening date, first documented, etc.)
    ];

    /**
     * Query places data from FactGrid.
     *
     * @return array
     */
    public function queryPlaces(): array
    {
        // Enhanced query to capture P700 qualifiers for P49 date properties
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
          ?dateTypeQualifier
          ?dateTypeValue
          ?dateTypeValueLabel
          ?dateTypeId
          ?coordinates
          ?dateType
        WHERE {
          {
            ?item p:P2 ?itemStatement.
            ?itemStatement ps:P2 wd:Q1784677.
            ?item wdt:P48 ?coordinates.
          }
          UNION
          {
              ?item wdt:P1423 ?location.
              ?location wdt:P2 wd:Q1784677.
          }
          UNION
          {
            ?item wdt:P137 ?location.
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
              ?item wdt:P137 ?event;
                    wdt:P277/wdt:P3? ?validSubjectRoles.
              ?item wdt:P48 ?coordinates.
            }
          }
          ?property wikibase:claim ?claim.
          ?item ?claim ?statement.
          {
            ?property wikibase:propertyType ?propertyType.
            FILTER(?property IN(
              wd:P189, wd:P2, wd:P534, wd:P47, wd:P419, wd:P280, wd:P9, wd:P1423, wd:P48,
              wd:P319, wd:P156, wd:P315, wd:P7, wd:P7, wd:P137, wd:P1164, wd:P29,
              wd:P153, wd:P49, wd:P50,wd:P153, wd:P155,
              wd:P319, wd:P129, wd:P146, wd:P7, wd:P7, wd:P1422, wd:P1421, wd:P14, wd:P247, wd:P248, wd:P5582, wd:P1418, wd:P1419,
              wd:P8, wd:P95, wd:P66, wd:P751, wd:P290
            ))
            FILTER(?propertyType != wikibase:Time)
            ?property wikibase:statementProperty ?ps.
            ?statement ?ps ?propertyValue.
          }
          UNION
          {
            ?property wikibase:statementValue ?psv.
            FILTER(?property IN(wd:P49, wd:P50, wd:P45, wd:P46, wd:P106, wd:P77, wd:P38, wd:P290))
            ?statement ?psv ?propertyValueNode.
            ?propertyValueNode wikibase:timeValue ?propertyValue.
            ?propertyValueNode wikibase:timePrecision ?propertyTimePrecision.

            # Special handling for time properties with P700 qualifiers
            OPTIONAL {
              # This section handles all time property statements with P700 qualifier
              # No filter for specific property - applies to all properties in the FILTER above
              OPTIONAL {
                ?statement pq:P700 ?dateTypeValue.
                BIND(wd:P700 AS ?dateTypeQualifier)
                # Extract the ID part from the dateTypeValue URI
                BIND(STRAFTER(STR(?dateTypeValue), "http://database.factgrid.de/entity/") AS ?dateTypeId)
                BIND(?dateTypeValue AS ?dateType)
                # Get the label for the date type
                OPTIONAL {
                  ?dateTypeValue rdfs:label ?dateTypeValueLabel.
                  FILTER(LANG(?dateTypeValueLabel) = "de" || LANG(?dateTypeValueLabel) = "en")
                }
              }
            }
          }
          OPTIONAL {
            {
              ?qualifier wikibase:propertyType ?qualifierType.
              FILTER(?qualifier IN(wd:P54, wd:P10, wd:P48, wd:P155, wd:P55, wd:P153, wd:P700))
              FILTER(?qualifierType != wikibase:Time)
              ?qualifier wikibase:qualifier ?pq.
              ?statement ?pq ?qualifierValue.
            }
            UNION
            {
              ?qualifier wikibase:qualifierValue ?pqv.
              FILTER(?qualifier IN(wd:P45, wd:P46, wd:P49, wd:P50, wd:P106, wd:P218, wd:P219, wd:P432, wd:P432))
              ?statement ?pqv ?qualifierValueNode.
              ?qualifierValueNode wikibase:timeValue ?qualifierValue.
              ?qualifierValueNode wikibase:timePrecision ?qualifierTimePrecision.
            }
          }
          SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
        }
        ORDER BY (?item) (?property) (?statement)
';

        $response = $this->requestFactgrid($query);

        // Check for date type qualifiers in the response
        $dateTypeQualifierCount = 0;
        $openingDateCount = 0;
        $firstActivityDateCount = 0;

        // Process dateType values directly in the query response
        foreach ($response["results"]["bindings"] as &$binding) {
            if (
                isset($binding["dateTypeQualifier"]) &&
                isset($binding["dateTypeValue"])
            ) {
                $dateTypeQualifierCount++;

                // Clean up the dateType value immediately by extracting the Q-ID
                if (isset($binding["dateTypeValue"]["value"])) {
                    $rawValue = $binding["dateTypeValue"]["value"];
                    if (preg_match("/Q\d+/", $rawValue, $matches)) {
                        // Replace the full URL with just the Q-ID
                        $binding["dateTypeValue"]["value"] = $matches[0];
                    } elseif (strpos($rawValue, "http") === 0) {
                        // If it's a URL but no Q-ID matched, use basename
                        $binding["dateTypeValue"]["value"] = basename(
                            $rawValue,
                        );
                    }
                }

                // Also clean the dateTypeId field if present
                if (isset($binding["dateTypeId"]["value"])) {
                    $rawValue = $binding["dateTypeId"]["value"];
                    if (preg_match("/Q\d+/", $rawValue, $matches)) {
                        $binding["dateTypeId"]["value"] = $matches[0];
                    }
                }

                // Check for specific date types
                if (
                    isset($binding["dateTypeId"]) &&
                    isset($binding["dateTypeId"]["value"])
                ) {
                    $typeId = $binding["dateTypeId"]["value"];
                    if ($typeId == "Q483988") {
                        $openingDateCount++;
                    } elseif ($typeId == "Q1057808" || $typeId == "Q1790956") {
                        $firstActivityDateCount++;
                    }
                }
            }
        }
        // Check for timePrecision values in the response
        $timePrecisionCount = 0;
        $inceptionTimeCount = 0;
        $dissolvedTimeCount = 0;

        foreach ($response["results"]["bindings"] as $binding) {
            if (isset($binding["propertyTimePrecision"])) {
                $timePrecisionCount++;

                // Look for specific properties
                if (
                    isset($binding["property"]) &&
                    isset($binding["property"]["value"])
                ) {
                    $propValue = $binding["property"]["value"];
                    if (strpos($propValue, "P49") !== false) {
                        $inceptionTimeCount++;
                    } elseif (strpos($propValue, "P50") !== false) {
                        $dissolvedTimeCount++;
                    }
                }
            }
        }

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
     * Query persons data from FactGrid using a two-stage approach to avoid timeouts.
     *
     * @return array Response from FactGrid
     */
    public function queryPersons(): array
    {
        // First stage: Get just the distinct person IDs
        $firstStageQuery = '
        SELECT DISTINCT
            ?item
            ?itemLabel
            ?itemDescription
        WHERE {
            {
                SELECT DISTINCT ?item WHERE {
                    {
                        ?item wdt:P2 wd:Q7.
                        ?item wdt:P277/wdt:P3 ?role.
                        FILTER(?role IN (wd:Q1784661, wd:Q1784663))
                    }
                    UNION
                    {
                        ?location wdt:P2 wd:Q1784677.
                        ?item wdt:P2 wd:Q7.

                        { ?item wdt:P315 ?location. }
                        UNION
                        { ?item wdt:P216 ?location. }
                        UNION
                        { ?location wdt:P14 ?item. }
                    }
                    UNION
                    {
                        ?event wdt:P2 wd:Q1014362.
                        ?item wdt:P2 wd:Q7.

                        { ?event wdt:P66 ?item. }
                        UNION
                        { ?event wdt:P751 ?item. }
                        UNION
                        { ?item wdt:P137 ?event. }
                    }
                }
            }

            OPTIONAL {
                ?item rdfs:label ?itemLabel.
                FILTER(LANG(?itemLabel) = "de" || LANG(?itemLabel) = "en")
            }

            OPTIONAL {
                ?item schema:description ?itemDescription.
                FILTER(LANG(?itemDescription) = "de" || LANG(?itemDescription) = "en")
            }

            # First ensure we have the subject role for grouping
            {
                ?item wdt:P277 ?roleValue.
                BIND(wd:P277 AS ?property)
                BIND(?item AS ?statement)
                BIND(?roleValue AS ?propertyValue)

                OPTIONAL {
                    ?propertyValue rdfs:label ?propertyValueLabel.
                    FILTER(LANG(?propertyValueLabel) = "de" || LANG(?propertyValueLabel) = "en")
                }
            }
            UNION
            {
                ?item wdt:P2 wd:Q7.
                FILTER NOT EXISTS { ?item wdt:P277 ?anyRole. }

                BIND(wd:P277 AS ?property)
                BIND(?item AS ?statement)
                BIND(wd:Q1784661 AS ?propertyValue)
                BIND("Victim (default role)" AS ?propertyValueLabel)
            }

            # Now get ALL properties for each person to ensure comprehensive data
            UNION
            {
                # Get all statements for this person
                ?item ?directProp ?directObj.

                # Extract the property and statement info
                ?property wikibase:directClaim ?directProp.
                ?property wikibase:claim ?claim.
                ?item ?claim ?statement.

                # Get property values
                OPTIONAL {
                    ?property wikibase:statementProperty ?ps.
                    ?statement ?ps ?propertyValue.

                    # Get labels for property values when applicable
                    OPTIONAL {
                        ?propertyValue rdfs:label ?propertyValueLabel.
                        FILTER(LANG(?propertyValueLabel) = "de" || LANG(?propertyValueLabel) = "en")
                    }
                }

                # Get time-based property values
                OPTIONAL {
                    ?property wikibase:statementValue ?psv.
                    ?statement ?psv ?propertyValueNode.
                    ?propertyValueNode wikibase:timeValue ?propertyValue;
                        wikibase:timePrecision ?propertyTimePrecision.
                }

                # Include only meaningful properties, but with an expanded list
                VALUES ?property {
                    # Identity properties
                    wd:P2      # instance of
                    wd:P277    # subject role
                    wd:P82     # given name
                    wd:P168    # family name
                    wd:P154    # occupation

                    # Biographical properties
                    wd:P38     # date of birth
                    wd:P77     # date of death
                    wd:P129    # place of birth
                    wd:P146    # place of death
                    wd:P315    # place of residence
                    wd:P216    # place of education
                    wd:P137    # employer/organization

                    # Event-related properties
                    wd:P66     # participated in
                    wd:P751    # affected by
                    wd:P189    # location of detention
                    wd:P616    # significant event

                    # Metadata properties
                    wd:P248    # described by source
                    wd:P247    # image
                    wd:P534    # external identifier
                    wd:P153    # address
                }

                # Get qualifiers for each statement
                OPTIONAL {
                    # Non-time qualifiers
                    ?qualifier wikibase:qualifier ?pq.
                    ?statement ?pq ?qualifierValue.

                    OPTIONAL {
                        ?qualifierValue rdfs:label ?qualifierValueLabel.
                        FILTER(LANG(?qualifierValueLabel) = "de" || LANG(?qualifierValueLabel) = "en")
                    }

                    # Include only meaningful qualifiers
                    VALUES ?qualifier {
                        wd:P48  # coordinate
                        wd:P54  # pages
                        wd:P55  # curator
                        wd:P153 # address
                        wd:P155 # circumstance
                    }
                }

                # Time-based qualifiers
                OPTIONAL {
                    ?qualifier wikibase:qualifierValue ?pqv.
                    ?statement ?pqv ?qualifierValueNode.
                    ?qualifierValueNode wikibase:timeValue ?qualifierValue;
                        wikibase:timePrecision ?qualifierTimePrecision.

                    # Include only time-based qualifiers
                    VALUES ?qualifier {
                        wd:P49  # start time
                        wd:P50  # end time
                        wd:P106 # point in time
                        wd:P432 # published
                    }
                }
            }
        }
        ORDER BY ?item
';
        Log::debug(
            "FactgridClient::queryPersons - Executing first stage query for person IDs",
        );

        try {
            // First stage: Get just the list of person IDs
            $response = $this->requestFactgrid($firstStageQuery);

            // Log query for debugging
            Log::debug(
                "FactgridClient::queryPersons - First stage query executed",
            );

            // Extract person IDs from the response
            $personIds = [];
            if (
                isset($response["results"]["bindings"]) &&
                is_array($response["results"]["bindings"])
            ) {
                foreach ($response["results"]["bindings"] as $binding) {
                    if (isset($binding["item"]["value"])) {
                        $personId = basename($binding["item"]["value"]);
                        if (!in_array($personId, $personIds)) {
                            $personIds[] = $personId;
                        }
                    }
                }
            }

            Log::info(
                "Found " . count($personIds) . " unique person IDs in FactGrid",
            );

            // If we have no person IDs, return an empty response
            if (empty($personIds)) {
                Log::warning("No person IDs found in FactGrid");
                return [
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

            // Second stage: Get all properties for these person IDs
            // Process in batches to avoid timeouts
            $batchSize = 50;
            $batches = array_chunk($personIds, $batchSize);

            $allBindings = [];

            foreach ($batches as $index => $batch) {
                $personValues = implode(
                    " ",
                    array_map(function ($id) {
                        return "wd:$id";
                    }, $batch),
                );

                $secondStageQuery =
                    '
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
                WHERE {
                    VALUES ?item { ' .
                    $personValues .
                    ' }

                    OPTIONAL {
                        ?item rdfs:label ?itemLabel.
                        FILTER(LANG(?itemLabel) = "de" || LANG(?itemLabel) = "en")
                    }

                    OPTIONAL {
                        ?item schema:description ?itemDescription.
                        FILTER(LANG(?itemDescription) = "de" || LANG(?itemDescription) = "en")
                    }

                    # First ensure we have the subject role for grouping
                    {
                        ?item wdt:P277 ?roleValue.
                        BIND(wd:P277 AS ?property)
                        BIND(?item AS ?statement)
                        BIND(?roleValue AS ?propertyValue)

                        OPTIONAL {
                            ?propertyValue rdfs:label ?propertyValueLabel.
                            FILTER(LANG(?propertyValueLabel) = "de" || LANG(?propertyValueLabel) = "en")
                        }
                    }
                    UNION
                    {
                        ?item wdt:P2 wd:Q7.
                        FILTER NOT EXISTS { ?item wdt:P277 ?anyRole. }

                        BIND(wd:P277 AS ?property)
                        BIND(?item AS ?statement)
                        BIND(wd:Q1784661 AS ?propertyValue)
                        BIND("Victim (default role)" AS ?propertyValueLabel)
                    }

                    # Now get ALL properties for each person to ensure comprehensive data
                    UNION
                    {
                        # Get all statements for this person
                        ?item ?directProp ?directObj.

                        # Extract the property and statement info
                        ?property wikibase:directClaim ?directProp.
                        ?property wikibase:claim ?claim.
                        ?item ?claim ?statement.

                        # Get property values
                        OPTIONAL {
                            ?property wikibase:statementProperty ?ps.
                            ?statement ?ps ?propertyValue.

                            # Get labels for property values when applicable
                            OPTIONAL {
                                ?propertyValue rdfs:label ?propertyValueLabel.
                                FILTER(LANG(?propertyValueLabel) = "de" || LANG(?propertyValueLabel) = "en")
                            }
                        }

                        # Get time-based property values
                        OPTIONAL {
                            ?property wikibase:statementValue ?psv.
                            ?statement ?psv ?propertyValueNode.
                            ?propertyValueNode wikibase:timeValue ?propertyValue;
                                wikibase:timePrecision ?propertyTimePrecision.
                        }

                        # Include only meaningful properties, but with an expanded list
                        VALUES ?property {
                            # Identity properties
                            wd:P2      # instance of
                            wd:P277    # subject role
                            wd:P82     # given name
                            wd:P168    # family name
                            wd:P154    # occupation

                            # Biographical properties
                            wd:P38     # date of birth
                            wd:P77     # date of death
                            wd:P129    # place of birth
                            wd:P146    # place of death
                            wd:P315    # place of residence
                            wd:P216    # place of education
                            wd:P137    # employer/organization

                            # Event-related properties
                            wd:P66     # participated in
                            wd:P751    # affected by
                            wd:P189    # location of detention
                            wd:P616    # significant event

                            # Metadata properties
                            wd:P248    # described by source
                            wd:P247    # image
                            wd:P534    # external identifier
                            wd:P153    # address
                        }

                        # Get qualifiers for each statement
                        OPTIONAL {
                            # Non-time qualifiers
                            ?qualifier wikibase:qualifier ?pq.
                            ?statement ?pq ?qualifierValue.

                            OPTIONAL {
                                ?qualifierValue rdfs:label ?qualifierValueLabel.
                                FILTER(LANG(?qualifierValueLabel) = "de" || LANG(?qualifierValueLabel) = "en")
                            }

                            # Include only meaningful qualifiers
                            VALUES ?qualifier {
                                wd:P48  # coordinate
                                wd:P54  # pages
                                wd:P55  # curator
                                wd:P153 # address
                                wd:P155 # circumstance
                            }
                        }

                        # Time-based qualifiers
                        OPTIONAL {
                            ?qualifier wikibase:qualifierValue ?pqv.
                            ?statement ?pqv ?qualifierValueNode.
                            ?qualifierValueNode wikibase:timeValue ?qualifierValue;
                                wikibase:timePrecision ?qualifierTimePrecision.

                            # Include only time-based qualifiers
                            VALUES ?qualifier {
                                wd:P49  # start time
                                wd:P50  # end time
                                wd:P106 # point in time
                                wd:P432 # published
                            }
                        }
                    }
                }
                ORDER BY ?item ?property ?statement
                ';

                $batchResponse = $this->requestFactgrid($secondStageQuery, 240); // Increased timeout

                if (
                    isset($batchResponse["results"]["bindings"]) &&
                    is_array($batchResponse["results"]["bindings"])
                ) {
                    // Merge the bindings from this batch
                    $allBindings = array_merge(
                        $allBindings,
                        $batchResponse["results"]["bindings"],
                    );
                }
            }

            // Construct the final response with the exact 12 variables expected by the validator
            // Combined all batches into final response with all bindings
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
                "results" => [
                    "bindings" => $allBindings,
                ],
            ];

            // Extract unique person IDs for debugging
            $uniquePersonIds = [];
            foreach ($allBindings as $binding) {
                if (isset($binding["item"]["value"])) {
                    $personId = basename($binding["item"]["value"]);
                    if (!in_array($personId, $uniquePersonIds)) {
                        $uniquePersonIds[] = $personId;
                    }
                }
            }
            Log::info(
                "Found " .
                    count($uniquePersonIds) .
                    " unique person IDs in final bindings",
            );

            // Save the unique person IDs for reference
            file_put_contents(
                storage_path("logs/factgrid_unique_person_ids.json"),
                json_encode($uniquePersonIds, JSON_PRETTY_PRINT),
            );

            // Save the full bindings separately for debugging
            Log::info("Saving full bindings data to disk for analysis");
            file_put_contents(
                storage_path("logs/factgrid_full_bindings.json"),
                json_encode($allBindings, JSON_PRETTY_PRINT),
            );

            Log::info(
                "Combined all batches into final response with " .
                    count($allBindings) .
                    " total bindings for " .
                    count($personIds) .
                    " unique persons",
            );

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
     * Process the items data, with improved handling for incomplete FactGrid responses.
     *
     * @param array $queryData Raw data from SPARQL query
     * @return array The merged items
     */
    public function mergeItemsData(array $queryData): array
    {
        // First pass: clean up all dateType values in the raw query data
        foreach ($queryData as &$chunk) {
            if (isset($chunk["dateTypeValue"]["value"])) {
                $rawValue = $chunk["dateTypeValue"]["value"];
                if (preg_match("/Q\d+/", $rawValue, $matches)) {
                    $chunk["dateTypeValue"]["value"] = $matches[0];
                } elseif (strpos($rawValue, "http") === 0) {
                    // If it's a URL but no Q-ID matched, use basename
                    $chunk["dateTypeValue"]["value"] = basename($rawValue);
                }
            }

            // Also clean the dateTypeId field if present
            if (isset($chunk["dateTypeId"]["value"])) {
                $rawValue = $chunk["dateTypeId"]["value"];
                if (preg_match("/Q\d+/", $rawValue, $matches)) {
                    $chunk["dateTypeId"]["value"] = $matches[0];
                } elseif (strpos($rawValue, "http") === 0) {
                    $chunk["dateTypeId"]["value"] = basename($rawValue);
                }
            }
        }
        unset($chunk); // Break the reference

        $items = [];
        $currentItemId = "";
        $currentPropertyId = "";
        $currentStatementId = "";
        $propertyLabel = "";
        $missingPropertyCount = 0;

        // Count statistics for date types
        $openingDateCount = 0;
        $firstActivityDateCount = 0;
        $regularInceptionDateCount = 0;

        foreach ($queryData as $index => $itemChunk) {
            // Check for required fields and provide defaults if missing
            if (
                !isset($itemChunk["item"]) ||
                !isset($itemChunk["item"]["value"])
            ) {
                Log::warning("Skipping result binding: Missing item", [
                    "index" => $index,
                    "data" => $itemChunk,
                ]);
                continue;
            }

            $itemId = basename($itemChunk["item"]["value"]);

            // Handle missing property - provide a default if needed
            if (
                !isset($itemChunk["property"]) ||
                !isset($itemChunk["property"]["value"])
            ) {
                $missingPropertyCount++;
                // If this is the first time we're seeing this item, log it
                if (!isset($items[$itemId])) {
                    Log::debug("Item missing property field", [
                        "itemId" => $itemId,
                        "index" => $index,
                    ]);
                }
                // Use P277 as default property (role/function property) which is commonly used
                $propertyId = "277";
                // Create a synthetic property value for consistency
                $itemChunk["property"] = [
                    "type" => "uri",
                    "value" => "http://database.factgrid.de/entity/P277",
                ];
            } else {
                $propertyId = basename($itemChunk["property"]["value"]);
            }

            // Handle missing statement - provide a default if needed
            if (
                !isset($itemChunk["statement"]) ||
                !isset($itemChunk["statement"]["value"])
            ) {
                // Use the item URI as the statement URI
                $statementId = $itemId . "-statement-" . $propertyId;
                // Create a synthetic statement value for consistency
                $itemChunk["statement"] = [
                    "type" => "uri",
                    "value" =>
                        "http://database.factgrid.de/entity/" . $statementId,
                ];
            } else {
                $statementId = basename($itemChunk["statement"]["value"]);
            }

            // Create a new item if this is the first time we've seen this ID
            if (!isset($items[$itemId])) {
                $items[$itemId]["id"] = $itemId;
                $items[$itemId]["qid"] = $itemId;

                // Get label or use ID as fallback
                $items[$itemId]["label"] =
                    isset($itemChunk["itemLabel"]) &&
                    isset($itemChunk["itemLabel"]["value"])
                        ? $itemChunk["itemLabel"]["value"]
                        : "Entity " . $itemId;

                // Get description if available
                $items[$itemId]["description"] =
                    isset($itemChunk["itemDescription"]) &&
                    isset($itemChunk["itemDescription"]["value"])
                        ? $itemChunk["itemDescription"]["value"]
                        : "";

                $currentItemId = $itemId;
            }

            // Handle property processing
            if (
                $itemId != $currentItemId ||
                !isset($items[$itemId][$propertyLabel]) ||
                $propertyId != $currentPropertyId
            ) {
                // Either new item or new property for existing item
                $currentPropertyId = $propertyId;
                $propertyLabel =
                    self::PROPERTY_LABEL_OF_ID[$propertyId] ??
                    "wdt:P" . $propertyId;

                if (!isset($items[$itemId][$propertyLabel])) {
                    $items[$itemId][$propertyLabel] = [];
                }

                $currentStatementId = $statementId;

                // Process property values
                $propertyValues = $this->getItemValues(
                    $itemChunk,
                    "property",
                    $propertyId,
                );
                if (!empty($propertyValues)) {
                    $items[$itemId][$propertyLabel][
                        $statementId
                    ] = $propertyValues;
                } else {
                    // If no property values found, add a placeholder to maintain structure
                    $items[$itemId][$propertyLabel][$statementId] = [
                        "value" => "Unknown",
                        "id" => $statementId,
                    ];
                }
            } elseif ($statementId != $currentStatementId) {
                // New statement for existing item+property
                $currentStatementId = $statementId;

                // Process property values
                $propertyValues = $this->getItemValues(
                    $itemChunk,
                    "property",
                    $propertyId,
                );
                if (!empty($propertyValues)) {
                    $items[$itemId][$propertyLabel][
                        $statementId
                    ] = $propertyValues;
                }
            }

            // Process qualifier data if available
            if (
                isset($itemChunk["qualifier"]) &&
                isset($itemChunk["qualifier"]["value"])
            ) {
                $qualifierId = basename($itemChunk["qualifier"]["value"]);
                $qualifierLabel =
                    self::QUALIFIER_LABEL_OF_ID[$qualifierId] ?? $qualifierId;

                $qualifierValues = $this->getItemValues(
                    $itemChunk,
                    "qualifier",
                    $qualifierId,
                );
                if (!empty($qualifierValues)) {
                    $items[$itemId][$propertyLabel][$statementId][
                        $qualifierLabel
                    ] = $qualifierValues;
                }
            }

            // Create base inceptionDates structure if this is a P49 property
            if ($propertyId == "49" && $propertyLabel == "inceptionDates") {
                // Initialize top-level structures for different date types
                if (!isset($items[$itemId]["openingDate"])) {
                    $items[$itemId]["openingDate"] = [];
                }
                if (!isset($items[$itemId]["firstActivity"])) {
                    $items[$itemId]["firstActivity"] = [];
                }
                // Ensure inceptionDates structure exists
                if (!isset($items[$itemId]["inceptionDates"])) {
                    $items[$itemId]["inceptionDates"] = [];
                }
            }

            /**
             * Extract the clean Q-ID from a FactGrid entity URL or string
             *
             * @param string $value The raw value that might contain a Q-ID
             * @return string The clean Q-ID (like Q483988) or the original value if no Q-ID is found
             */
            $extractQId = function ($value) {
                if (!is_string($value)) {
                    return $value;
                }

                // Extract Q-ID using regex if possible
                if (preg_match("/Q\d+/", $value, $matches)) {
                    return $matches[0];
                }

                // Clean up URL parts
                if (strpos($value, "http") === 0) {
                    $value = basename($value);
                }

                // Remove hash or query parameters
                if (strpos($value, "#") !== false) {
                    $value = strtok($value, "#");
                }
                if (strpos($value, "?") !== false) {
                    $value = strtok($value, "?");
                }

                return $value;
            };

            // Process P700 date type qualifier for date properties (P49 inception dates and P50 dissolved dates)
            if (
                ($propertyId == "49" || $propertyId == "50") &&
                isset($itemChunk["dateTypeQualifier"]) &&
                isset($itemChunk["dateTypeQualifier"]["value"]) &&
                basename($itemChunk["dateTypeQualifier"]["value"]) == "P700" &&
                isset($itemChunk["dateTypeValue"]) &&
                isset($itemChunk["dateTypeValue"]["value"])
            ) {
                // Extract the Q-ID directly from the value using regex
                $rawValue = $itemChunk["dateTypeValue"]["value"];
                if (preg_match("/Q\d+/", $rawValue, $matches)) {
                    $dateTypeId = $matches[0];
                } else {
                    $dateTypeId = basename($rawValue);
                }

                Log::debug("Processing date type qualifier", [
                    "itemId" => $itemId,
                    "dateTypeId" => $dateTypeId,
                    "dateTypeRawValue" => $rawValue,
                    "statementId" => $statementId,
                ]);

                $dateTypeLabel =
                    isset($itemChunk["dateTypeValueLabel"]) &&
                    isset($itemChunk["dateTypeValueLabel"]["value"])
                        ? $itemChunk["dateTypeValueLabel"]["value"]
                        : $dateTypeId;

                // Log for debugging
                Log::debug("Found P700 qualifier for date property", [
                    "itemId" => $itemId,
                    "propertyId" => $propertyId,
                    "dateTypeId" => $dateTypeId,
                    "dateTypeLabel" => $dateTypeLabel,
                ]);

                // Store the clean Q-ID in the dateType value
                $items[$itemId][$propertyLabel][$statementId]["dateType"] = [
                    "value" => $dateTypeId, // Just the Q ID
                    "id" => $dateTypeId,
                    "label" => $dateTypeLabel,
                ];

                // For opening date (Q483988)
                if ($dateTypeId == "Q483988") {
                    // Different handling based on property type
                    $targetProperty =
                        $propertyId == "49" ? "openingDate" : "closureDate";

                    // Add to the appropriate top-level property
                    $items[$itemId][$targetProperty][
                        $statementId
                    ] = array_merge(
                        [],
                        $items[$itemId][$propertyLabel][$statementId],
                    );

                    // Make sure the statement is marked with the right date type marker
                    $items[$itemId][$propertyLabel][$statementId][
                        "dateTypeMarker"
                    ] = $targetProperty;

                    // Ensure the dateType is consistently set with the clean Q-ID
                    $items[$itemId][$targetProperty][$statementId][
                        "dateType"
                    ] = [
                        "value" => $dateTypeId,
                        "id" => $dateTypeId,
                        "label" => $dateTypeLabel,
                    ];
                }
                // For first documented date (Q1057808/Q1790956)
                elseif (
                    $dateTypeId == "Q1057808" ||
                    $dateTypeId == "Q1790956"
                ) {
                    // Choose the appropriate property based on whether this is an inception or dissolution date
                    $targetProperty =
                        $propertyId == "49" ? "firstActivity" : "lastActivity";

                    // Add to the appropriate top-level property
                    $items[$itemId][$targetProperty][
                        $statementId
                    ] = array_merge(
                        [],
                        $items[$itemId][$propertyLabel][$statementId],
                    );

                    // Make sure the statement is marked with the right type
                    $items[$itemId][$propertyLabel][$statementId][
                        "dateTypeMarker"
                    ] = $targetProperty;

                    // Ensure the dateType is consistently set with the clean Q-ID
                    $items[$itemId][$targetProperty][$statementId][
                        "dateType"
                    ] = [
                        "value" => $dateTypeId,
                        "id" => $dateTypeId,
                        "label" => $dateTypeLabel,
                    ];

                    $firstActivityDateCount++;
                }
            }
        }

        // Log information about missing properties and date types
        if ($missingPropertyCount > 0) {
            Log::info(
                "Fixed {$missingPropertyCount} missing properties during mergeItemsData",
            );
        }

        // Final structure adjustment: make sure specialized date types appear in proper structure
        foreach ($items as $itemId => &$item) {
            // Process inceptionDates if present
            if (
                isset($item["inceptionDates"]) &&
                !empty($item["inceptionDates"])
            ) {
                // Clean up all dateType values to ensure they're just Q IDs
                foreach ($item["inceptionDates"] as &$dateEntry) {
                    if (
                        isset($dateEntry["dateType"]) &&
                        isset($dateEntry["dateType"]["value"])
                    ) {
                        $rawValue = $dateEntry["dateType"]["value"];
                        if (preg_match("/Q\d+/", $rawValue, $matches)) {
                            $dateEntry["dateType"]["value"] = $matches[0];
                        }
                        if (isset($dateEntry["dateType"]["id"])) {
                            if (
                                preg_match(
                                    "/Q\d+/",
                                    $dateEntry["dateType"]["id"],
                                    $matches,
                                )
                            ) {
                                $dateEntry["dateType"]["id"] = $matches[0];
                            }
                        }
                    }
                }
                unset($dateEntry); // Break the reference
            }

            // Process dissolvedDates if present (independent of inceptionDates)
            if (
                isset($item["dissolvedDates"]) &&
                !empty($item["dissolvedDates"])
            ) {
                // Clean up all dateType values to ensure they're just Q IDs
                foreach ($item["dissolvedDates"] as &$dateEntry) {
                    if (
                        isset($dateEntry["dateType"]) &&
                        isset($dateEntry["dateType"]["value"])
                    ) {
                        $rawValue = $dateEntry["dateType"]["value"];
                        if (preg_match("/Q\d+/", $rawValue, $matches)) {
                            $dateEntry["dateType"]["value"] = $matches[0];
                        }
                        if (isset($dateEntry["dateType"]["id"])) {
                            if (
                                preg_match(
                                    "/Q\d+/",
                                    $dateEntry["dateType"]["id"],
                                    $matches,
                                )
                            ) {
                                $dateEntry["dateType"]["id"] = $matches[0];
                            }
                        }
                    }
                }
                unset($dateEntry); // Break the reference
            }

            // Ensure openingDate structure exists at the nested level
            if (isset($item["openingDate"]) && !empty($item["openingDate"])) {
                if (!isset($item["inceptionDates"]["openingDate"])) {
                    $item["inceptionDates"]["openingDate"] =
                        $item["openingDate"];
                }
            }

            // Ensure firstActivity structure exists at the nested level
            if (
                isset($item["firstActivity"]) &&
                !empty($item["firstActivity"])
            ) {
                if (!isset($item["inceptionDates"]["firstActivity"])) {
                    $item["inceptionDates"]["firstActivity"] =
                        $item["firstActivity"];
                }
            }

            // Add closureDate and lastActivity to dissolvedDates if they exist
            if (
                isset($item["dissolvedDates"]) &&
                !empty($item["dissolvedDates"])
            ) {
                // Ensure closureDate structure exists
                if (
                    isset($item["closureDate"]) &&
                    !empty($item["closureDate"])
                ) {
                    if (!isset($item["dissolvedDates"]["closureDate"])) {
                        $item["dissolvedDates"]["closureDate"] =
                            $item["closureDate"];
                    }
                }

                // Ensure lastActivity structure exists
                if (
                    isset($item["lastActivity"]) &&
                    !empty($item["lastActivity"])
                ) {
                    if (!isset($item["dissolvedDates"]["lastActivity"])) {
                        $item["dissolvedDates"]["lastActivity"] =
                            $item["lastActivity"];
                    }
                }
            }

            // Also clean top-level date entries
            if (isset($item["openingDate"]) && !empty($item["openingDate"])) {
                foreach ($item["openingDate"] as &$dateEntry) {
                    if (
                        isset($dateEntry["dateType"]) &&
                        isset($dateEntry["dateType"]["value"])
                    ) {
                        $rawValue = $dateEntry["dateType"]["value"];
                        if (preg_match("/Q\d+/", $rawValue, $matches)) {
                            $dateEntry["dateType"]["value"] = $matches[0];
                        }
                        if (isset($dateEntry["dateType"]["id"])) {
                            if (
                                preg_match(
                                    "/Q\d+/",
                                    $dateEntry["dateType"]["id"],
                                    $matches,
                                )
                            ) {
                                $dateEntry["dateType"]["id"] = $matches[0];
                            }
                        }
                    }
                }
                unset($dateEntry);
            }

            if (
                isset($item["firstActivity"]) &&
                !empty($item["firstActivity"])
            ) {
                foreach ($item["firstActivity"] as &$dateEntry) {
                    if (
                        isset($dateEntry["dateType"]) &&
                        isset($dateEntry["dateType"]["value"])
                    ) {
                        $rawValue = $dateEntry["dateType"]["value"];
                        if (preg_match("/Q\d+/", $rawValue, $matches)) {
                            $dateEntry["dateType"]["value"] = $matches[0];
                        }
                        if (isset($dateEntry["dateType"]["id"])) {
                            if (
                                preg_match(
                                    "/Q\d+/",
                                    $dateEntry["dateType"]["id"],
                                    $matches,
                                )
                            ) {
                                $dateEntry["dateType"]["id"] = $matches[0];
                            }
                        }
                    }
                }
                unset($dateEntry);
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

        // Final pass: clean all dateType values that might have URLs
        foreach ($items as &$item) {
            // Clean inceptionDates dateType values
            if (
                isset($item["inceptionDates"]) &&
                is_array($item["inceptionDates"])
            ) {
                foreach ($item["inceptionDates"] as &$dateObj) {
                    if (
                        isset($dateObj["dateType"]) &&
                        isset($dateObj["dateType"]["value"])
                    ) {
                        $value = $dateObj["dateType"]["value"];
                        if (strpos($value, "http") === 0) {
                            if (preg_match("/Q\d+/", $value, $matches)) {
                                $dateObj["dateType"]["value"] = $matches[0];
                                $dateObj["dateType"]["id"] = $matches[0];
                            } else {
                                $dateObj["dateType"]["value"] = basename(
                                    $value,
                                );
                                $dateObj["dateType"]["id"] = basename($value);
                            }
                        }
                    }
                }
            }

            // Clean openingDate dateType values
            if (isset($item["openingDate"]) && is_array($item["openingDate"])) {
                foreach ($item["openingDate"] as &$dateObj) {
                    if (
                        isset($dateObj["dateType"]) &&
                        isset($dateObj["dateType"]["value"])
                    ) {
                        $value = $dateObj["dateType"]["value"];
                        if (strpos($value, "http") === 0) {
                            if (preg_match("/Q\d+/", $value, $matches)) {
                                $dateObj["dateType"]["value"] = $matches[0];
                                $dateObj["dateType"]["id"] = $matches[0];
                            } else {
                                $dateObj["dateType"]["value"] = basename(
                                    $value,
                                );
                                $dateObj["dateType"]["id"] = basename($value);
                            }
                        }
                    }
                }
            }

            // Clean firstActivity dateType values
            if (
                isset($item["firstActivity"]) &&
                is_array($item["firstActivity"])
            ) {
                foreach ($item["firstActivity"] as &$dateObj) {
                    if (
                        isset($dateObj["dateType"]) &&
                        isset($dateObj["dateType"]["value"])
                    ) {
                        $value = $dateObj["dateType"]["value"];
                        if (strpos($value, "http") === 0) {
                            if (preg_match("/Q\d+/", $value, $matches)) {
                                $dateObj["dateType"]["value"] = $matches[0];
                                $dateObj["dateType"]["id"] = $matches[0];
                            } else {
                                $dateObj["dateType"]["value"] = basename(
                                    $value,
                                );
                                $dateObj["dateType"]["id"] = basename($value);
                            }
                        }
                    }
                }
            }
        }

        $personCount = 0;
        $victimCount = 0;
        $perpetratorCount = 0;

        foreach ($items as $item) {
            $personCount++;

            // Check if this is a victim or perpetrator based on subject role
            $isVictim = false;
            $isPerpetrator = false;

            if (isset($item["subjectRoles"])) {
                foreach ($item["subjectRoles"] as $role) {
                    if (
                        isset($role["id"]) &&
                        ($role["id"] === "Q1788534" ||
                            stripos($role["value"], "victim") !== false ||
                            stripos($role["value"], "opfer") !== false)
                    ) {
                        $isVictim = true;
                    }
                    if (
                        isset($role["id"]) &&
                        ($role["id"] === "Q1784661" ||
                            stripos($role["value"], "perpetrator") !== false ||
                            stripos($role["value"], "täter") !== false)
                    ) {
                        $isPerpetrator = true;
                    }
                }
            }

            if ($isVictim) {
                $victimCount++;
            }
            if ($isPerpetrator) {
                $perpetratorCount++;
            }
        }

        // Save detailed information about the persons we found
        $victimIds = [];
        $perpetratorIds = [];
        foreach ($items as $itemId => $item) {
            // Check if this is a victim or perpetrator
            $isVictim = false;
            $isPerpetrator = false;

            if (isset($item["subjectRoles"])) {
                foreach ($item["subjectRoles"] as $role) {
                    if (
                        isset($role["id"]) &&
                        ($role["id"] === "Q1788534" ||
                            stripos($role["value"], "victim") !== false ||
                            stripos($role["value"], "opfer") !== false)
                    ) {
                        $isVictim = true;
                    }
                    if (
                        isset($role["id"]) &&
                        ($role["id"] === "Q1784661" ||
                            stripos($role["value"], "perpetrator") !== false ||
                            stripos($role["value"], "täter") !== false)
                    ) {
                        $isPerpetrator = true;
                    }
                }
            }

            if ($isVictim) {
                $victimIds[] = $itemId;
            }
            if ($isPerpetrator) {
                $perpetratorIds[] = $itemId;
            }
        }

        // Save the IDs for debugging
        file_put_contents(
            storage_path("logs/factgrid_victim_ids.json"),
            json_encode($victimIds, JSON_PRETTY_PRINT),
        );

        file_put_contents(
            storage_path("logs/factgrid_perpetrator_ids.json"),
            json_encode($perpetratorIds, JSON_PRETTY_PRINT),
        );

        file_put_contents(
            storage_path("logs/factgrid_merged_items.json"),
            json_encode(array_keys($items), JSON_PRETTY_PRINT),
        );

        Log::info(
            "Finished merging data into " .
                count($items) .
                " unique items with $personCount persons ($victimCount victims, $perpetratorCount perpetrators)",
        );

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
                    "inceptionDates",
                    "dissolvedDates",
                    "openingDate",
                    "firstActivity",
                    "closureDate",
                    "lastActivity",
                ])
            ) {
                // Date properties
                // Check if the propertyTimePrecision is available from the query
                $datePrecision =
                    isset($itemChunk["propertyTimePrecision"]) &&
                    isset($itemChunk["propertyTimePrecision"]["value"])
                        ? $itemChunk["propertyTimePrecision"]["value"]
                        : "11"; // Default day precision

                // Fallback to inferring precision from the date format if not available from query
                if (
                    !isset($itemChunk["propertyTimePrecision"]) ||
                    !isset($itemChunk["propertyTimePrecision"]["value"])
                ) {
                    // For date properties, inspect the format to determine precision
                    if ($value && is_string($value)) {
                        if (preg_match("/^\d{4}-\d{2}$/", $value)) {
                            $datePrecision = "10"; // Month precision
                        } elseif (preg_match("/^\d{4}$/", $value)) {
                            $datePrecision = "9"; // Year precision
                        } elseif (preg_match("/^\d{4}-\d{2}-\d{2}/", $value)) {
                            // Look at the day part - if it's "01" for non-January months, likely only month precision
                            if (
                                preg_match(
                                    "/^\d{4}-(?!01-)(0[2-9]|1[0-2])-01/",
                                    $value,
                                )
                            ) {
                                $datePrecision = "10"; // Month precision for non-January dates with day=01
                            }
                        }
                    }
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
        }

        // Check if this is an inception date property with a date type qualifier
        if (
            $type === "property" &&
            $id === "49" &&
            isset($itemChunk["dateTypeValue"])
        ) {
            // Extract a clean Q-ID
            $extractQId = function ($value) {
                if (!is_string($value)) {
                    return $value;
                }

                // Extract Q-ID using regex if possible
                if (preg_match("/Q\d+/", $value, $matches)) {
                    return $matches[0];
                }

                // Clean up URL parts
                if (strpos($value, "http") === 0) {
                    $value = basename($value);
                }

                // Remove hash or query parameters
                if (strpos($value, "#") !== false) {
                    $value = strtok($value, "#");
                }
                if (strpos($value, "?") !== false) {
                    $value = strtok($value, "?");
                }

                return $value;
            };

            // Get date type ID either from the explicit dateTypeId field or by parsing the value
            if (
                isset($itemChunk["dateTypeId"]) &&
                isset($itemChunk["dateTypeId"]["value"])
            ) {
                $dateTypeId = $itemChunk["dateTypeId"]["value"];
            } elseif (
                isset($itemChunk["dateTypeValue"]) &&
                isset($itemChunk["dateTypeValue"]["value"])
            ) {
                // The dateType value should already be cleaned in the processing stage
                $dateTypeId = $itemChunk["dateTypeValue"]["value"];
                // Double-check - if it's still a URL, extract the Q-ID
                if (strpos($dateTypeId, "http") === 0) {
                    if (preg_match("/Q\d+/", $dateTypeId, $matches)) {
                        $dateTypeId = $matches[0];
                    } else {
                        $dateTypeId = basename($dateTypeId);
                    }
                }
            } else {
                // Fallback
                $dateTypeId = "";
            }

            Log::debug("Processing date type in getItemValues", [
                "dateTypeId" => $dateTypeId,
                "rawValue" => $itemChunk["dateTypeValue"]["value"] ?? "unknown",
                "propertyId" => $id,
            ]);

            if ($dateTypeId) {
                $dateTypeLabel =
                    isset($itemChunk["dateTypeValueLabel"]) &&
                    isset($itemChunk["dateTypeValueLabel"]["value"])
                        ? $itemChunk["dateTypeValueLabel"]["value"]
                        : $dateTypeId;

                // Extract Q ID - already done above
                $result["dateType"] = [
                    "value" => $dateTypeId, // Just the Q ID
                    "id" => $dateTypeId,
                    "label" => $dateTypeLabel, // Preserve the human-readable label
                ];

                // Add a clear marker for the date type
                if ($dateTypeId == "Q483988") {
                    $result["dateTypeMarker"] = "openingDate";
                } elseif (
                    $dateTypeId == "Q1057808" ||
                    $dateTypeId == "Q1790956"
                ) {
                    $result["dateTypeMarker"] = "firstActivity";
                }
            }
        }

        if ($type === "qualifier") {
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
                // Check if the qualifierTimePrecision is available from the query
                if (
                    isset($itemChunk["qualifierTimePrecision"]) &&
                    isset($itemChunk["qualifierTimePrecision"]["value"])
                ) {
                    $result["datePrecision"] =
                        $itemChunk["qualifierTimePrecision"]["value"];
                }
                // Fallback to inferring precision from date format
                else {
                    if (preg_match("/^\d{4}-\d{2}-\d{2}/", $value)) {
                        $result["datePrecision"] = "11"; // Day precision
                    } elseif (preg_match("/^\d{4}-\d{2}/", $value)) {
                        $result["datePrecision"] = "10"; // Month precision
                    } elseif (preg_match("/^\d{4}/", $value)) {
                        $result["datePrecision"] = "9"; // Year precision
                    }
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
        file_put_contents(
            storage_path("logs/factgrid_items_before_grouping.json"),
            json_encode(array_keys($items), JSON_PRETTY_PRINT),
        );
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

            // Assign to the first matching group
            $foundGroup = false;

            foreach ($groupConfigs as $groupKey => $groupQids) {
                $intersection = array_intersect($propertyValues, $groupQids);

                if (!empty($intersection)) {
                    $groupedItems[$groupKey][$itemId] = $item;
                    $foundGroup = true;
                    break; // Place in only one group
                }
            }

            // If no group found through standard means, try fallback methods
            if (!$foundGroup) {
                if (isset($isPerson) && $isPerson) {
                    // For persons, if they have no specific role,
                    // default to victims group as fallback
                    $groupedItems["victims"][$itemId] = $item;
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
                    } elseif (
                        strpos($label, "gefängnis") !== false ||
                        strpos($label, "haftanstalt") !== false
                    ) {
                        $groupedItems["prisons"][$itemId] = $item;
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
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Log the results
        $counts = [];
        foreach ($groupedItems as $groupName => $group) {
            $counts[$groupName] = count($group);
        }

        // Save detailed grouped data
        file_put_contents(
            storage_path("logs/factgrid_grouped_items_details.json"),
            json_encode(
                [
                    "counts" => $counts,
                    "victimKeys" => array_keys($groupedItems["victims"] ?? []),
                    "perpetratorKeys" => array_keys(
                        $groupedItems["perpetrators"] ?? [],
                    ),
                ],
                JSON_PRETTY_PRINT,
            ),
        );

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
    /**
     * Send a SPARQL query to the FactGrid endpoint
     *
     * @param string $query SPARQL query to execute
     * @param int $timeout Timeout in seconds (default: 180)
     * @return array Response from FactGrid
     */
    private function requestFactgrid(string $query, int $timeout = 180): array
    {
        // Get the endpoint and log
        $endpoint = config(
            "factgrid.sparql_endpoint",
            "https://database.factgrid.de/sparql",
        );

        // Try to execute the query
        try {
            // Set up the HTTP request parameters
            $response = Http::timeout($timeout)
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

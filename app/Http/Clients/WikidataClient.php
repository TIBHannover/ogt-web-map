<?php


namespace App\Http\Clients;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Query Wikidata.
 *
 * @package App\Http\Clients
 */
class WikidataClient
{
    /**
     * Groups of Wikidata Q-Ids of person roles.
     */
    const PERSON_GROUPS_IDS = [
        'perpetrators' => [
            'Q111080573',   // https://www.wikidata.org/wiki/Q111080573     perpetrator of the Nazi regime
        ],
        'victims'      => [
            'Q2026714',     // https://www.wikidata.org/wiki/Q2026714       victim of the Nazi regime
            'Q2744762',     // https://www.wikidata.org/wiki/Q2744762       non-Jewish victim of the Nazi regime
            'Q5883980',     // https://www.wikidata.org/wiki/Q5883980       Holocaust victim
            'Q12409870',    // https://www.wikidata.org/wiki/Q12409870      Holocaust survivor
            'Q111510085',   // https://www.wikidata.org/wiki/Q111510085     Porajmos victim
            'Q111510497',   // https://www.wikidata.org/wiki/Q111510497     Porajmos survivor
            'Q113980000',   // https://www.wikidata.org/wiki/Q113980000     Nazi euthanasia victim
        ],
    ];

    /**
     * Groups of Wikidata Q-Ids of place instances.
     */
    const PLACE_GROUPS_IDS = [
        'events'                  => [
            'Q6983405',     // https://www.wikidata.org/wiki/Q6983405       Nazi crime
        ],
        'extPolicePrisons'        => [
            'Q108047650',   // https://www.wikidata.org/wiki/Q108047650     Extended police prison
            'Q108048094',   // https://www.wikidata.org/wiki/Q108048094     Police Detention Camp
        ],
        'fieldOffices'            => [
            'Q108047541',   // https://www.wikidata.org/wiki/Q108047541     Gestapo Field Office
            'Q108047989',   // https://www.wikidata.org/wiki/Q108047989     Outpost (State Police)
            'Q108047676',   // https://www.wikidata.org/wiki/Q108047676     Border Police Commissariat
            'Q108047833',   // https://www.wikidata.org/wiki/Q108047833     Border police station
            'Q108047775',   // https://www.wikidata.org/wiki/Q108047775     Branch office (border police)
        ],
        'laborEducationCamps'     => [
            'Q277565',      // https://www.wikidata.org/wiki/Q277565        labor education camp
        ],
        'memorials'               => [
            'Q5003624',     // https://www.wikidata.org/wiki/Q5003624       memorial
        ],
        'prisons'                 => [
            'Q40357',       // https://www.wikidata.org/wiki/Q40357         prison
        ],
        'statePoliceHeadquarters' => [
            'Q108047581',   // https://www.wikidata.org/wiki/Q108047581     State Police Headquarter
        ],
        'statePoliceOffices'      => [
            'Q108048310',   // https://www.wikidata.org/wiki/Q108048310     Branch office (state police)
            'Q2101520',     // https://www.wikidata.org/wiki/Q2101520       Political police (Germany)
            'Q108047567',   // https://www.wikidata.org/wiki/Q108047567     State Police Office
        ],
    ];

    /**
     * Wikidata's properties of a queried locations and associated property labels.
     */
    const PROPERTY_LABEL_OF_ID = [
        'P18'   => 'images',                // https://www.wikidata.org/wiki/Property:P18
        'P19'   => 'placeOfBirth',          // https://www.wikidata.org/wiki/Property:P19
        'P20'   => 'placeOfDeath',          // https://www.wikidata.org/wiki/Property:P20
        'P21'   => 'gender',                // https://www.wikidata.org/wiki/Property:P21
        'P27'   => 'citizenships',          // https://www.wikidata.org/wiki/Property:P27
        'P31'   => 'instances',             // https://www.wikidata.org/wiki/Property:P31
        'P108'  => 'employers',             // https://www.wikidata.org/wiki/Property:P108
        'P137'  => 'operators',             // https://www.wikidata.org/wiki/Property:P137
        'P276'  => 'locations',             // https://www.wikidata.org/wiki/Property:P276
        'P355'  => 'subsidiaries',          // https://www.wikidata.org/wiki/Property:P355
        'P366'  => 'hasUses',               // https://www.wikidata.org/wiki/Property:P366
        'P533'  => 'targets',               // https://www.wikidata.org/wiki/Property:P533
        'P547'  => 'commemorates',          // https://www.wikidata.org/wiki/Property:P547
        'P569'  => 'dateOfBirth',           // https://www.wikidata.org/wiki/Property:P569
        'P570'  => 'dateOfDeath',           // https://www.wikidata.org/wiki/Property:P570
        'P571'  => 'inceptionDates',        // https://www.wikidata.org/wiki/Property:P571
        'P576'  => 'dissolvedDates',        // https://www.wikidata.org/wiki/Property:P576
        'P580'  => 'startTime',             // https://www.wikidata.org/wiki/Property:P580
        'P582'  => 'endTime',               // https://www.wikidata.org/wiki/Property:P582
        'P585'  => 'pointInTime',           // https://www.wikidata.org/wiki/Property:P585
        'P625'  => 'coordinates',           // https://www.wikidata.org/wiki/Property:P625
        'P734'  => 'familyName',            // https://www.wikidata.org/wiki/Property:P734
        'P735'  => 'givenName',             // https://www.wikidata.org/wiki/Property:P735
        'P749'  => 'parentOrganizations',   // https://www.wikidata.org/wiki/Property:P749
        'P793'  => 'significantEvents',     // https://www.wikidata.org/wiki/Property:P793
        'P856'  => 'officialWebsite',       // https://www.wikidata.org/wiki/Property:P856
        'P1037' => 'directors',             // https://www.wikidata.org/wiki/Property:P1037
        'P1120' => 'numberOfDeaths',        // https://www.wikidata.org/wiki/Property:P1120
        'P1128' => 'employeeCounts',        // https://www.wikidata.org/wiki/Property:P1128
        'P1343' => 'describedBySources',    // https://www.wikidata.org/wiki/Property:P1343
        'P1365' => 'replaces',              // https://www.wikidata.org/wiki/Property:P1365
        'P1366' => 'replacedBys',           // https://www.wikidata.org/wiki/Property:P1366
        'P1427' => 'startPoints',           // https://www.wikidata.org/wiki/Property:P1427
        'P1444' => 'destinationPoints',     // https://www.wikidata.org/wiki/Property:P1444
        'P1561' => 'numberOfSurvivors',     // https://www.wikidata.org/wiki/Property:P1561
        'P1590' => 'numberOfCasualties',    // https://www.wikidata.org/wiki/Property:P1590
        'P1619' => 'openingDate',           // https://www.wikidata.org/wiki/Property:P1619
        'P2632' => 'detentionPlaces',       // https://www.wikidata.org/wiki/Property:P2632
        'P2868' => 'subjectRoles',          // https://www.wikidata.org/wiki/Property:P2868
        'P5582' => 'numberOfArrests',       // https://www.wikidata.org/wiki/Property:P5582
        'P5630' => 'prisonerCounts',        // https://www.wikidata.org/wiki/Property:P5630
        'P6375' => 'streetAddresses',       // https://www.wikidata.org/wiki/Property:P6375
        'P7153' => 'significantPlaces',     // https://www.wikidata.org/wiki/Property:P7153
        'P8031' => 'perpetrators',          // https://www.wikidata.org/wiki/Property:P8031
        'P8032' => 'victims',               // https://www.wikidata.org/wiki/Property:P8032
    ];

    /**
     * Wikidata's qualifiers of a queried locations and associated qualifier labels.
     */
    const QUALIFIER_LABEL_OF_ID = [
        'P304'  => 'pages',                 // https://www.wikidata.org/wiki/Property:P304
        'P580'  => 'startTime',             // https://www.wikidata.org/wiki/Property:P580
        'P582'  => 'endTime',               // https://www.wikidata.org/wiki/Property:P582
        'P585'  => 'pointInTime',           // https://www.wikidata.org/wiki/Property:P585
        'P625'  => 'coordinate',            // https://www.wikidata.org/wiki/Property:P625
        'P1319' => 'earliestDate',          // https://www.wikidata.org/wiki/Property:P1319
        'P1326' => 'latestDate',            // https://www.wikidata.org/wiki/Property:P1326
        'P1480' => 'sourcingCircumstance',  // https://www.wikidata.org/wiki/Property:P1480
        'P2096' => 'mediaLegend',           // https://www.wikidata.org/wiki/Property:P2096
        'P6375' => 'streetAddress',         // https://www.wikidata.org/wiki/Property:P6375
        'P8554' => 'earliestEndDate',       // https://www.wikidata.org/wiki/Property:P8554
        'P8555' => 'latestStartDate',       // https://www.wikidata.org/wiki/Property:P8555
    ];

    /**
     * Get places of Gestapo terror from Wikidata.
     *
     * @return array
     */
    public function queryPlaces() : array
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
                    ?item wdt:P31 wd:Q106996250.
                }
                UNION
                {
                    ?item wdt:P547 ?location.
                    ?location wdt:P31 wd:Q106996250.
                }
                UNION
                {
                    ?item wdt:P31 wd:Q6983405;
                          wdt:P8031 ?location.
                    ?location wdt:P31 wd:Q106996250.
                }
                FILTER(EXISTS { ?item wdt:P625 ?coordinateLocation. })
                ?property wikibase:claim ?claim.
                ?item ?claim ?statement.
                {
                    ?property wikibase:propertyType ?propertyType.
                    FILTER(?property IN(
                        wd:P18, wd:P31, wd:P137, wd:P276, wd:P355, wd:P366, wd:P533, wd:P547, wd:P625, wd:P749, wd:P793, 
                        wd:P856, wd:P1037, wd:P1120, wd:P1128, wd:P1343, wd:P1365, wd:P1366, wd:P1427, wd:P1444, wd:P1561, 
                        wd:P1590, wd:P5582, wd:P5630, wd:P6375, wd:P7153, wd:P8031, wd:P8032
                    ))
                    FILTER(?propertyType != wikibase:Time)
                    ?property wikibase:statementProperty ?ps.
                    ?statement ?ps ?propertyValue.
                }
                UNION
                {
                    ?property wikibase:statementValue ?psv.
                    FILTER(?property IN(wd:P571, wd:P576, wd:P580, wd:P582, wd:P585, wd:P1619))
                    ?statement ?psv ?propertyValueNode.
                    ?propertyValueNode wikibase:timeValue ?propertyValue;
                        wikibase:timePrecision ?propertyTimePrecision.
                }
                OPTIONAL {
                    {
                        ?qualifier wikibase:propertyType ?qualifierType.
                        FILTER(?qualifier IN(wd:P304, wd:P625, wd:P1480, wd:P2096, wd:P6375))
                        FILTER(?qualifierType != wikibase:Time)
                        ?qualifier wikibase:qualifier ?pq.
                        ?statement ?pq ?qualifierValue.
                    }
                    UNION
                    {
                        ?qualifier wikibase:qualifierValue ?pqv.
                        FILTER(?qualifier IN(wd:P580, wd:P582, wd:P585, wd:P1319, wd:P1326, wd:P8554, wd:P8555))
                        ?statement ?pqv ?qualifierValueNode.
                        ?qualifierValueNode wikibase:timeValue ?qualifierValue;
                            wikibase:timePrecision ?qualifierTimePrecision.
                    }
                }
                SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
            }
            ORDER BY (?item) (?property) (?statement)';

        return $this->requestWikidata($query);
    }

    /**
     * Get persons data from Wikidata for perpetrators and victims.
     *
     * @return array
     */
    public function queryPersons() : array
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
                ?location wdt:P31 wd:Q106996250.
                FILTER(EXISTS { ?location wdt:P625 ?coordinateLocation. })
                ?item wdt:P31 wd:Q5.
                {
                    ?item (wdt:P108|wdt:P2632) ?location.
                }
                UNION
                {
                    ?location wdt:P1037 ?item.
                }
                UNION
                {
                    ?event wdt:P31 wd:Q6983405;
                           wdt:P625 ?coordinateEvent;
                           wdt:P8031 ?location.
                    {
                        ?event (wdt:P8031|wdt:P8032) ?item. 
                    }
                    UNION
                    {
                        VALUES ?validSubjectRoles { wd:Q111080573 wd:Q2026714 }.
                        ?item wdt:P793 ?event;
                              wdt:P2868/wdt:P279? ?validSubjectRoles.
                    }
                }
                ?property wikibase:claim ?claim.
                ?item ?claim ?statement.
                {
                    ?property wikibase:propertyType ?propertyType.
                    FILTER(?property IN(
                        wd:P18, wd:P19, wd:P20, wd:P21, wd:P27, wd:P108, wd:P734, wd:P735, wd:P793, wd:P1343, wd:P2632, 
                        wd:P2868
                    ))
                    FILTER(?propertyType != wikibase:Time)
                    ?property wikibase:statementProperty ?ps.
                    ?statement ?ps ?propertyValue.
                }
                UNION
                {
                    ?property wikibase:statementValue ?psv.
                    FILTER(?property IN(wd:P569, wd:P570))
                    ?statement ?psv ?propertyValueNode.
                    ?propertyValueNode wikibase:timeValue ?propertyValue;
                        wikibase:timePrecision ?propertyTimePrecision.
                }
                OPTIONAL 
                {
                    {
                        ?qualifier wikibase:propertyType ?qualifierType.
                        FILTER(?qualifier IN(wd:P304, wd:P2096))
                        FILTER(?qualifierType != wikibase:Time)
                        ?qualifier wikibase:qualifier ?pq.
                        ?statement ?pq ?qualifierValue.
                    }
                    UNION
                    {
                        ?qualifier wikibase:qualifierValue ?pqv.
                        FILTER(?qualifier IN(wd:P580, wd:P582, wd:P585, wd:P1319, wd:P1326, wd:P8554, wd:P8555))
                        ?statement ?pqv ?qualifierValueNode.
                        ?qualifierValueNode wikibase:timeValue ?qualifierValue;
                            wikibase:timePrecision ?qualifierTimePrecision.
                    }
                }
                SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
            }
            ORDER BY (?item) (?property) (?statement)';

        return $this->requestWikidata($query);
    }

    /**
     * Send request to Wikidata.
     *
     * @param string $query
     * @return array
     */
    public function requestWikidata(string $query) : array
    {
        $query = preg_replace('/\s\s+/', ' ', trim($query));

        $response = Http::accept('application/sparql-results+json')
            ->get(config('wikidata.url'), ['query' => $query,]);

        if ($response->ok()) {
            return $response->json();
        }
        else {
            $exceptionLog = [];

            if (! is_null($response->toException())) {
                $exceptionLog = [
                    'message' => $response->toException()->getMessage(),
                    'file'    => $response->toException()->getFile(),
                    'line'    => $response->toException()->getLine(),
                    'trace'   => $response->toException()->getTraceAsString(),
                ];
            }

            Log::error(
                'Wikidata request failed.',
                [
                    'status' => $response->status(),
                    'query'  => $query,
                ] + $exceptionLog
            );

            return [];
        }
    }

    /**
     * Merge item data from Wikidata query response.
     *
     * @param array $queryData
     *
     * @return array
     */
    public function mergeItemsData(array $queryData) : array
    {
        $items = [];
        $currentItemId = '';
        $currentPropertyId = '';
        $currentStatementId = '';
        $propertyLabel = '';

        foreach ($queryData as $itemChunk)
        {
            $itemId = basename($itemChunk['item']['value']);
            $propertyId = basename($itemChunk['property']['value']);
            $statementId = basename($itemChunk['statement']['value']);

            if ($itemId != $currentItemId)
            {
                //case: new item
                $currentItemId = $itemId;

                $items[$itemId]['id'] = $itemId;
                $items[$itemId]['label'] = $itemChunk['itemLabel']['value'];
                $items[$itemId]['description'] = $itemChunk['itemDescription']['value'] ?? '';

                $currentPropertyId = $propertyId;
                $propertyLabel = self::PROPERTY_LABEL_OF_ID[$propertyId] ?? $propertyId;
                $items[$itemId][$propertyLabel] = [];

                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][$statementId] =
                    $this->getItemValues($itemChunk, 'property', $propertyId);
            }
            elseif ($propertyId != $currentPropertyId)
            {
                //case: new property of previous item
                $currentPropertyId = $propertyId;
                $propertyLabel = self::PROPERTY_LABEL_OF_ID[$propertyId] ?? $propertyId;
                $items[$itemId][$propertyLabel] = [];

                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][$statementId] =
                    $this->getItemValues($itemChunk, 'property', $propertyId);
            }
            elseif ($statementId != $currentStatementId)
            {
                // case: new statement of previous item property
                $currentStatementId = $statementId;
                $items[$itemId][$propertyLabel][$statementId] =
                    $this->getItemValues($itemChunk, 'property', $propertyId);
            }

            // case: item has qualifier data
            if (isset($itemChunk['qualifier']))
            {
                $qualifierId = basename($itemChunk['qualifier']['value']);
                $qualifierLabel = self::QUALIFIER_LABEL_OF_ID[$qualifierId] ?? $qualifierId;

                $items[$itemId][$propertyLabel][$statementId][$qualifierLabel] =
                    $this->getItemValues($itemChunk, 'qualifier', $qualifierId);
            }
        }

        return $items;
    }

    /**
     * Get a property/qualifier values for an item dataset.
     *
     * @param array  $itemChunk Item dataset
     * @param string $chunkType Part of key to get property or qualifier values from item dataset
     * @param string $chunkKey  ID of Wikidata property/qualifier
     *
     * @return array             Property or qualifier values of an item dataset
     */
    private function getItemValues(array $itemChunk, string $chunkType, string $chunkKey) : array
    {
        $itemValues = [];

        $valueLabel = $itemChunk[$chunkType . 'ValueLabel']['value'];

        // convert Wikidata coordinates format Point(9.731877 52.3669889)
        if ($chunkKey == 'P625')
        {
            $coordinate = explode(' ', Str::between($valueLabel, '(', ')'));
            $valueLabel = [
                'lat' => $coordinate[1],
                'lng' => $coordinate[0],
            ];
        }

        $itemValues['value'] = $valueLabel;

        $value = $itemChunk[$chunkType . 'Value']['value'];

        if (Str::startsWith($value, 'http://www.wikidata.org/entity/Q'))
        {
            // case: property/qualifier value is a Wikidata item, so set item id
            $itemValues['id'] = basename($value);
        }
        elseif (isset($itemChunk[$chunkType . 'TimePrecision']))
        {
            // case: property/qualifier value is a datetime => get time precision (9 => day, 10 => month, 11 => year)
            $itemValues['datePrecision'] = $itemChunk[$chunkType . 'TimePrecision']['value'];
        }

        return $itemValues;
    }

    /**
     * Group items by values of a Wikidata property. Items are assigned to the first group found.
     *
     * @param array  $items      Wikidata item.
     * @param array  $groups     Valid available groups.
     * @param string $propertyId Wikidata property id, e.g. P31.
     *
     * @return array
     */
    public function groupItemsByProperty(array $items, array $groups, string $propertyId) : array
    {
        // return all item groups, even if empty
        $groupedItems = array_fill_keys(array_keys($groups), []);

        foreach ($items as $itemId => $item)
        {
            $propertyLabel = self::PROPERTY_LABEL_OF_ID[$propertyId];

            if (! isset($item[$propertyLabel]))
            {
                Log::warning(
                    'The item cannot be assigned to a group because it does not have the required Wikidata property.',
                    [
                        'itemId'        => $itemId,
                        'propertyLabel' => $propertyLabel,
                    ]
                );

                continue;
            }

            $propertyIds = Arr::pluck($item[$propertyLabel], 'id');

            $foundGroup = false;

            foreach ($groups as $groupName => $groupIds)
            {
                if (! empty(array_intersect($propertyIds, $groupIds)))
                {
                    Arr::forget($item, $propertyLabel);
                    $groupedItems[$groupName][$itemId] = $item;
                    $foundGroup = true;

                    // group each item into exactly one item group
                    break;
                }
            }

            if (! $foundGroup)
            {
                Log::warning(
                    'The item cannot be assigned to a group because it does not have a valid Wikidata property value for available groups.',
                    [
                        'itemId'      => $itemId,
                        'propertyIds' => $propertyIds,
                    ]
                );
            }
        }

        return $groupedItems;
    }
}

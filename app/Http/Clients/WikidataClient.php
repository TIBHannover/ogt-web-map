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
     * Groups of Wikidata Q-Ids of place instances.
     */
    const PLACE_GROUPS_IDS = [
        'events'                  => [],
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
        'memorials'               => [],
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
     * Properties of a queried Wikidata place.
     */
    const PLACE_PROPERTIES = [
        'item',
        'itemLabel',
        'itemDescription',
        'instanceUrls',
        'instanceLabels',
        'coordinates',
        'imageUrl',
        'source',
        'sourceAuthorLabels',
        'sourceLabel',
        'sourcePublisherCityLabel',
        'sourcePublisherLabel',
        'sourcePublicationYear',
        'sourcePages',
        'sourceDnbLink',
    ];

    /**
     * Valid properties of a queried Wikidata locations and associated property labels.
     */
    const VALID_PROPERTIES = [
        'P18'   => 'images',                // https://www.wikidata.org/wiki/Property:P18
        'P31'   => 'instances',             // https://www.wikidata.org/wiki/Property:P31
        'P131'  => 'administrativeUnits',   // https://www.wikidata.org/wiki/Property:P131
        'P355'  => 'subsidiaries',          // https://www.wikidata.org/wiki/Property:P355
        'P571'  => 'inceptionDates',        // https://www.wikidata.org/wiki/Property:P571
        'P576'  => 'dissolvedDates',        // https://www.wikidata.org/wiki/Property:P576
        'P625'  => 'coordinates',           // https://www.wikidata.org/wiki/Property:P625
        'P749'  => 'parentOrganizations',   // https://www.wikidata.org/wiki/Property:P749
        'P793'  => 'significantEvents',     // https://www.wikidata.org/wiki/Property:P793
        'P1037' => 'directors',             // https://www.wikidata.org/wiki/Property:P1037
        'P1128' => 'employeeCounts',        // https://www.wikidata.org/wiki/Property:P1128
        'P1343' => 'describedBySources',    // https://www.wikidata.org/wiki/Property:P1343
        'P1365' => 'replaces',              // https://www.wikidata.org/wiki/Property:P1365
        'P1366' => 'replacedBys',            // https://www.wikidata.org/wiki/Property:P1366
        'P5630' => 'prisonerCounts',        // https://www.wikidata.org/wiki/Property:P5630
        'P6375' => 'streetAddresses',       // https://www.wikidata.org/wiki/Property:P6375
    ];

    /**
     * Valid qualifiers of a queried Wikidata locations and associated property labels.
     */
    const VALID_QUALIFIERS = [
        'P304'  => 'pages',                 // https://www.wikidata.org/wiki/Property:P304
        'P580'  => 'startTime',             // https://www.wikidata.org/wiki/Property:P580
        'P582'  => 'endTime',               // https://www.wikidata.org/wiki/Property:P582
        'P585'  => 'pointInTime',           // https://www.wikidata.org/wiki/Property:P585
        'P625'  => 'coordinates',           // https://www.wikidata.org/wiki/Property:P625
        'P1319' => 'earliestDate',          // https://www.wikidata.org/wiki/Property:P1319
        'P1326' => 'latestDate',            // https://www.wikidata.org/wiki/Property:P1326
        'P1480' => 'sourcingCircumstances', // https://www.wikidata.org/wiki/Property:P1480
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
                (GROUP_CONCAT(DISTINCT ?instance ; SEPARATOR="|") AS ?instanceUrls)
                (GROUP_CONCAT(DISTINCT ?instanceLabel ; SEPARATOR=", ") AS ?instanceLabels)
                (GROUP_CONCAT(DISTINCT CONCAT(STR(?lat), ",", STR(?lng)) ; SEPARATOR="|") AS ?coordinates)
                (SAMPLE(?image) AS ?imageUrl)
                ?source
                (GROUP_CONCAT(DISTINCT ?sourceAuthorLabel ; SEPARATOR=" & ") AS ?sourceAuthorLabels)
                ?sourceLabel
                ?sourcePublisherCityLabel
                ?sourcePublisherLabel
                (YEAR(?sourcePublicationDate) AS ?sourcePublicationYear)
                ?sourcePages
                ?sourceDnbLink
            WHERE {
                ?item wdt:P31 wd:Q106996250;
                    wdt:P31 ?instance;
                    p:P625 ?itemGeo.
                ?itemGeo psv:P625 ?geoNode.
                ?geoNode wikibase:geoLatitude ?lat;
                    wikibase:geoLongitude ?lng.
                OPTIONAL { ?item wdt:P18 ?image }.
                OPTIONAL {
                    ?item p:P1343 ?sourceStatement.
                    ?sourceStatement ps:P1343 ?source.
                    OPTIONAL { ?sourceStatement pq:P304 ?sourcePages }.
                    OPTIONAL { ?source wdt:P50 ?sourceAuthor }.
                    OPTIONAL {
                        ?source wdt:P123 ?sourcePublisher.
                        OPTIONAL { ?sourcePublisher wdt:P131 ?sourcePublisherCity }.
                    }.
                    OPTIONAL { ?source wdt:P1292 ?sourceDnbLink }.
                    OPTIONAL { ?source wdt:P577 ?sourcePublicationDate }.
                }.
                SERVICE wikibase:label {
                    bd:serviceParam wikibase:language "de".
                    ?item rdfs:label ?itemLabel.
                    ?item schema:description ?itemDescription.
                    ?instance rdfs:label ?instanceLabel.
                    ?source rdfs:label ?sourceLabel.
                    ?sourceAuthor rdfs:label ?sourceAuthorLabel.
                    ?sourcePublisher rdfs:label ?sourcePublisherLabel.
                    ?sourcePublisherCity rdfs:label ?sourcePublisherCityLabel.
                }
            }
            GROUP BY
                ?item ?itemLabel ?itemDescription ?source ?sourceLabel ?sourcePublisherCityLabel ?sourcePublisherLabel
                ?sourcePublicationDate ?sourcePages ?sourceDnbLink
            ORDER BY ?item';

        return $this->requestWikidata($query);
    }

    /**
     * Get places of Gestapo terror from Wikidata.
     *
     * @return array
     */
    public function queryLocations() : array
    {
        # ?propLabel
        # ?qualifierLabel
        # VALUES ?item {wd:Q106109048 wd:Q106625639 wd:Q108127300 wd:Q108127360 wd:Q106625285 wd:Q627414 wd:Q106625716}.

        $query = '
            SELECT
                ?item
                ?itemLabel
                ?itemDescription
                ?prop
                ?statement
                ?propValue
                ?propValueLabel
                ?propPrecision
                ?qualifier                
                ?qualifierValue
                ?qualifierValueLabel
                ?qualifierPrecision
            WHERE
            {
                
                ?item wdt:P31 wd:Q106996250.
                FILTER(EXISTS {?item wdt:P625 ?coordinateLocation}).
                ?item ?p ?statement.
                ?statement ?ps ?propValue.
                ?prop wikibase:claim ?p;
                    wikibase:statementProperty ?ps.
                {
                    ?property wikibase:propertyType ?property_type .
                    FILTER (?property_type != wikibase:Time)
                    ?property wikibase:statementProperty ?propertyStatement .
                    ?statement ?propertyStatement ?value .
                }
                UNION
                {
                    ?property wikibase:statementValue ?statementValue .
                    ?statement ?statementValue [wikibase:timePrecision ?propPrecision] .
                }
                OPTIONAL
                {
                    {
                        ?statement ?pq ?qualifierValue .
                        ?qualifier wikibase:qualifier ?pq .
                        ?qualifier wikibase:propertyType ?qualifier_property_type .
                        FILTER (?qualifier_property_type != wikibase:Time)
                    }
                    UNION
                    {
                        ?statement ?pqv [wikibase:timeValue ?qualifierValue ; wikibase:timePrecision ?qualifierPrecision] .
                        ?qualifier wikibase:qualifierValue ?pqv .
                    }
                }
                SERVICE wikibase:label
                {
                    bd:serviceParam wikibase:language "de,en".
                }
            }
            ORDER BY (?item) (?prop) (?statement) (?propValue)';

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
     * Filter Wikidata place data and group places by
     * - Events
     * - Extended police prisons / Labor education camps
     * - Field Offices
     * - Prisons
     * - State Police Headquarters
     * - State Police Offices
     *
     * @param array $places
     * @return array
     */
    public function groupFilteredPlacesByType(array $places) : array
    {
        $groupedPlaces = array_fill_keys(array_keys(self::PLACE_GROUPS_IDS), []);

        foreach ($places as $place) {
            $updatedPlace = $this->convertPlaceData($this->filterPlaceData($place));

            $instanceUrls = $updatedPlace['instanceUrls']['value'];
            $instanceQIds = str_replace('http://www.wikidata.org/entity/', '', $instanceUrls);
            $instanceQIdsArray = explode('|', $instanceQIds);

            $foundGroupForPlace = false;

            foreach ($groupedPlaces as $groupedPlaceName => $groupedPlace) {
                if (count(array_intersect($instanceQIdsArray, self::PLACE_GROUPS_IDS[$groupedPlaceName])) > 0) {
                    $groupedPlaces[$groupedPlaceName][] = $updatedPlace;
                    $foundGroupForPlace = true;
                }
            }

            if (! $foundGroupForPlace) {
                Log::warning(
                    'The location cannot be assigned to a map marker category based on its Wikidata instances.',
                    [
                        'instanceQIds' => $instanceUrls,
                        'placeQId'     => $updatedPlace['item']['value'],
                    ]
                );
            }
        }

        return $groupedPlaces;
    }

    /**
     * Filtering of the required place data.
     *
     * @param array $place
     * @return array
     */
    private function filterPlaceData(array $place) : array
    {
        foreach ($place as $key => $placeData) {
            $place[$key] = Arr::only($placeData, 'value');
        }

        return $place;
    }

    /**
     * Conversion to the appropriate format for further processing.
     *
     * @param array $place
     * @return array
     */
    private function convertPlaceData(array $place) : array
    {
        /* Example for location data with multiple coordinates
           ... from Wikidata ...
           [
                'type' => 'literal',
                'value' => '52.3667941,9.7448449240635|52.3642957,9.7473133',
           ]
           ... for Leaflet convert to ...
           [
                [lat => 52.3667941, lng => 9.7448449240635], [lat => 52.3642957, lng => 9.7473133],
           ]
        */
        $coordinatesArray = explode('|', $place['coordinates']['value']);

        foreach ($coordinatesArray as &$coordinate) {
            $latLng = explode(',', $coordinate);

            $coordinate = [
                'lat' => $latLng[0],
                'lng' => $latLng[1],
            ];
        }

        $place['coordinates'] = $coordinatesArray;

        return $place;
    }

    /**
     * Merge location data from wikidata query response.
     *
     * @param array $locationsQueryData
     * @return array
     */
    public function mergeLocationData(array $locationsQueryData) : array
    {
        $places = [];
        $currentPlaceId = '';
        $currentPropId = '';
        $currentStatementId = '';
        $currentPropStatementCounter = 0;

        foreach ($locationsQueryData as $placeData) {
            //Log::debug($placeData);
            $placeId = basename($placeData['item']['value']);
            $propId = basename($placeData['prop']['value']);
            $statementId = basename($placeData['statement']['value']);

            $propLabel = self::VALID_PROPERTIES[$propId] ?? '';

            if ($propLabel == '') {
                // skip not required property
                continue;
            }

            /*
            Log::debug('data', [
                'placeId'     => $placeId,
                'propId'      => $propId,
                'statementId' => $statementId,
            ]);
            */

            if ($currentPlaceId != $placeId) {
                //case: new item
                $currentPlaceId = $placeId;
                $currentPropId = $propId;
                //$currentStatementId = $statementId;
                $currentPropStatementCounter = 0;

                //$places[$placeId]['id'] = $placeId; // required? because its the key too
                $places[$placeId]['label'] = $placeData['itemLabel']['value'];
                $places[$placeId]['description'] = $placeData['itemDescription']['value'] ?? '';

                /*
                $places[$placeId][$propLabel] = [
                    // 'propertyLabel'      => $placeData['propLabel']['value'], // not required until now?
                    'propertyStatements' => [],
                ];
                */
            }
            else if ($currentPropId != $propId) {
                //case: same item with new property
                $currentPropId = $propId;
                //$currentStatementId = $statementId;
                $currentPropStatementCounter = 0;

                /*
                $places[$placeId][$propLabel] = [
                    // 'propertyLabel'      => $placeData['propLabel']['value'], // not required until now?
                    'propertyStatements' => [],
                ];
                */
            }
            else if ($currentStatementId != $statementId) {
                //case: same item and property, which have a further statement value
                //$currentStatementId = $statementId;
                $currentPropStatementCounter += 1;
            }
            else {
                // done
            }

            if ($currentStatementId != $statementId) {
                $currentStatementId = $statementId;

                $propertyValue = $placeData['propValueLabel']['value'];

                if ($propId == 'P625') {
                    // reformat coordinate location: Point(9.731877 52.3669889) => [52.3669889, 9.731877]
                    $propertyValue = array_reverse(explode(' ', Str::between($propertyValue, '(', ')')));
                }

                $places[$placeId][$propLabel][$currentPropStatementCounter] = [
                    'value' => $propertyValue,
                    /*
                    'propertyValueId' => (Str::startsWith($placeData['propValue']['value'], 'http://www.wikidata.org/entity/Q')) ? basename($placeData['propValue']['value']) : null,
                    'propertyValueDatePrecision' => $placeData['propPrecision']['value'] ?? null,
                    */
                ];

                if (Str::startsWith($placeData['propValue']['value'], 'http://www.wikidata.org/entity/Q')) {
                    // case: property value is an object => get Wikidata id
                    $places[$placeId][$propLabel][$currentPropStatementCounter]['id'] =
                        basename($placeData['propValue']['value']);
                }
                else if (isset($placeData['propPrecision'])) {
                    // case: property value is a datetime => get time precision (9 => day, 10 => month, 11 => year)
                    $places[$placeId][$propLabel][$currentPropStatementCounter]['datePrecision'] =
                        $placeData['propPrecision']['value'];
                }
            }


            if (isset($placeData['qualifier'])) {
                $qualifierId = basename($placeData['qualifier']['value']);
                $qualifierLabel = self::VALID_QUALIFIERS[$qualifierId] ?? '';

                if ($qualifierLabel == '') {
                    // skip not required qualifier
                    continue;
                }

                $qualifierValue = $placeData['qualifierValueLabel']['value'];

                if ($qualifierId == 'P625') {
                    // reformat coordinate location: Point(9.731877 52.3669889) => [52.3669889, 9.731877]
                    $qualifierValue = array_reverse(explode(' ', Str::between($qualifierValue, '(', ')')));
                }

                $places[$placeId][$propLabel][$currentPropStatementCounter][$qualifierLabel] = [
                    'value' => $qualifierValue,
                ];

                if (Str::startsWith($placeData['qualifierValue']['value'], 'http://www.wikidata.org/entity/Q')) {
                    Log::debug($placeData['qualifierValue']);
                    // case: property value is an object => get Wikidata id
                    $places[$placeId][$propLabel][$currentPropStatementCounter][$qualifierLabel]['id'] =
                        basename($placeData['qualifierValue']['value']);
                }
                else if (isset($placeData['qualifierPrecision'])) {
                    // case: qualifier value is a datetime => get time precision (9 => day, 10 => month, 11 => year)
                    $places[$placeId][$propLabel][$currentPropStatementCounter][$qualifierLabel]['datePrecision'] =
                        $placeData['qualifierPrecision']['value'];
                }

                /*
                $places[$placeId][$propId]['propertyStatements'][$currentPropStatementCounter][$qualifierId] = [
                    'qualifierLabel' => $placeData['qualifierLabel']['value'],
                    'qualifierValue' => $placeData['qualifierValueLabel']['value'],
                    'qualifierValueId' => (Str::startsWith($placeData['qualifierValue']['value'], 'http://www.wikidata.org/entity/Q')) ? basename($placeData['qualifierValue']['value']) : null,
                    'qualifierValueDatePrecision' => $placeData['qualifierPrecision']['value'] ?? null,
                ];
                */
            }
        }

        return $places;
    }

    /**
     *
     *
     * @param array $locations
     * @return array
     */
    public function getLocationsByType(array $places) : array
    {
        $groupedPlaces = array_fill_keys(array_keys(self::PLACE_GROUPS_IDS), []);

        foreach ($places as $placeId => $place) {
            $statements = $place[self::VALID_PROPERTIES['P31']];

            foreach ($statements as $statement) {
                //Log::debug($statement['id']);

                foreach ($groupedPlaces as $groupedPlaceName => $groupedPlace) {
                    if (count(array_intersect([$statement['id']], self::PLACE_GROUPS_IDS[$groupedPlaceName])) > 0) {
                        $groupedPlaces[$groupedPlaceName][$placeId] = $place;
                    }
                }
            }
        }

        //return $places;
        return $groupedPlaces;
    }
}

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
        'humans'      => [
            'Q5',           // https://www.wikidata.org/wiki/Q5     human
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
    public function queryPlacesExtended() : array
    {
        /*
         * VALUES ?item { }
                    wd:Q64768399
                    wd:Q106625285
                    wd:Q106625716
                    wd:Q108127286
                    wd:Q108127300
                    wd:Q627414
                    wd:Q108127300
                    wd:Q106625639
                    wd:Q106109048

                VALUES ?p {
                    p:P18
                    p:P31
                    p:P571
                    p:P625
                    p:P749
                    p:P1343
                }

           VALUES ?placeInstanceOf {
     wd:Q106996250
#      wd:Q6983405
   }
  ?place wdt:P31 ?placeInstanceOf.
  ?item wdt:P547 ?place;
        */
        $query = '
            SELECT
                ?item ?itemLabel ?itemDescription
                ?prop ?propLabel
                ?statement
                ?propValue ?propValueLabel ?propPrecision
                ?qualifier ?qualifierLabel ?qualifierValue ?qualifierValueLabel ?qualifierPrecision
            WHERE {
                {
                    ?item wdt:P2868 wd:Q2026714;
                          wdt:P31 wd:Q5;
                          wdt:P793 ?event.
                    ?event wdt:P31 wd:Q6983405;
                           wdt:P8031 ?place.
                    ?place wdt:P31 wd:Q106996250.
                }
                UNION
                {
                    ?item wdt:P2868 wd:Q111080573;
                          wdt:P31 wd:Q5;
                          wdt:P793 ?event.
                    ?event wdt:P31 wd:Q6983405;
                           wdt:P8031 ?place.
                    ?place wdt:P31 wd:Q106996250.
                }
                UNION
                {
                  ?place wdt:P31 wd:Q106996250.
                  ?item wdt:P8031 ?place.
                }
                UNION
                {
                  ?place wdt:P31 wd:Q106996250.
                  ?item wdt:P547 ?place.
                }
                UNION
                {
                  ?item wdt:P31 wd:Q106996250.
                }
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

                OPTIONAL {
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
                    SERVICE wikibase:label {
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
     * Filter Wikidata place data and group places by
     * - Events
     * - Extended police prisons / Labor education camps
     * - Field Offices
     * - Prisons
     * - State Police Headquarters
     * - State Police Offices
     *
     * @param array $placesData
     * @return array
     */
    public function groupFilteredPlacesByTypeExtended(array $placesData) : array
    {
        $placesTemp = [
            'Q123' => [
                'id' => 'Q123', // nötig?
                'label' => 'qwertz',
                'description' => 'just a test',
                'P31' => [
                    'label' => 'ist ein(e)',
                    'values' =>
                        [
                            'id' => 'Q232243',
                            'value' => 'Außendienststelle',
                        ],
                        [
                            'id' => 'Q7673',
                            'value' => 'Ort des Gestapoterrors',
                        ],
                        [
                            'id' => 'Q75878',
                            'value' => 'Organisation',
                        ],
                ],
                'P571' => [
                    'label' => 'Datum der Gründung, Erstellung, Entstehung, Erbauung',
                    'values' =>
                        [
                            'id' => null,
                            'value' => '1942-04-01T00:00:00Z',
                        ],
                ],
                'P749' => [
                    'label' => 'übergeordnete Organisation',
                    'values' =>
                        [
                            'id' => 'Q522',
                            'value' => 'Staatspolizeileitstelle Münster',
                            'P582' => [
                                'id' => null,
                                'label' => '',
                                'value' => '1 April 1944',
                            ],
                            'P580' => [
                                'id' => null,
                                'label' => '',
                                'value' => '1 April 1942',
                            ],
                        ],
                        [
                            'id' => 'Q76756544',
                            'value' => 'Staatspolizeistelle Bremen',
                            'P582' => [
                                'id' => null,
                                'label' => '',
                                'value' => '1 April 1945',
                            ],
                            'P580' => [
                                'id' => null,
                                'label' => '',
                                'value' => '1 April 1944',
                            ],
                        ],
                ],
            ],
        ];

        // @todo
        $mainImageByCoordinate = [
            'Q106625639' => [
                '9.7321152 52.3664978' => '',
                '9.7473133 52.3642957' => '',
                '9.744844924 52.3667941' => '',
                '9.7532908 52.3907961' => '',
            ],
            // http://commons.wikimedia.org/wiki/Special:FilePath/Stadtbibliothek%20Hannover%20au%C3%9Fen.jpg
            // http://commons.wikimedia.org/wiki/Special:FilePath/R%C3%BChmkorffstra%C3%9Fe%2C%20Hannover.jpg
            // http://commons.wikimedia.org/wiki/Special:FilePath/Polizeipr%C3%A4sidium%20Hardenbergstra%C3%9Fe.jpg
            // http://commons.wikimedia.org/wiki/Special:FilePath/Stadtbibliothek%20Hannover%20Innenansicht.jpg
        ];

        $places = [];
        $currentPlaceId = '';
        $currentPropId = '';
        $currentStatementId = '';
        $currentPropStatementCounter = 0;

        foreach ($placesData as $placeData) {
            //Log::debug($placeData);

            $placeId = basename($placeData['item']['value']);
            $propId = basename($placeData['prop']['value']);
            $statementId = basename($placeData['statement']['value']);

            if ($currentPlaceId == $placeId && $currentPropId == $propId && $currentStatementId != $statementId) {
                $currentPropStatementCounter += 1;
            }

            if ($currentPlaceId != $placeId) {
                // if new id, then add place basic infos
                $currentPlaceId = $placeId;
                $places[$placeId]['id'] = $placeId;
                $places[$placeId]['label'] = $placeData['itemLabel']['value'];
                $places[$placeId]['description'] = $placeData['itemDescription']['value'] ?? '';

                $currentPropId = '';
                $currentStatementId = '';
                $currentPropStatementCounter = 0;
            }

            if ($currentPropId != $propId) {
                $currentPropId = $propId;

                $places[$placeId][$propId] = [
                    'propertyLabel' => $placeData['propLabel']['value'],
                    'propertyStatements' => [],
                ];

                $currentStatementId = '';
                $currentPropStatementCounter = 0;
            }

            if ($currentStatementId != $statementId) {
                $currentStatementId = $statementId;

                if ($propId == 'P625') {
                    //Log::debug($placeData['propValueLabel']);

                    // reformat coordinate location
                    $propertyValue = array_reverse(explode(' ', Str::between($placeData['propValueLabel']['value'], '(', ')')));
                } else {
                    $propertyValue = $placeData['propValueLabel']['value'];
                }

                $places[$placeId][$propId]['propertyStatements'][] = [
                    'propertyValue' => $propertyValue,
                    'propertyValueId' => (Str::startsWith($placeData['propValue']['value'], 'http://www.wikidata.org/entity/Q')) ? basename($placeData['propValue']['value']) : null,
                    'propertyValueDatePrecision' => $placeData['propPrecision']['value'] ?? null,
                ];
            }

            if (isset($placeData['qualifier'])) {
                $qualifierId = basename($placeData['qualifier']['value']);

                $places[$placeId][$propId]['propertyStatements'][$currentPropStatementCounter][$qualifierId] = [
                    'qualifierLabel' => $placeData['qualifierLabel']['value'],
                    'qualifierValue' => $placeData['qualifierValueLabel']['value'],
                    'qualifierValueId' => (Str::startsWith($placeData['qualifierValue']['value'], 'http://www.wikidata.org/entity/Q')) ? basename($placeData['qualifierValue']['value']) : null,
                    'qualifierValueDatePrecision' => $placeData['qualifierPrecision']['value'] ?? null,
                ];
            }

            /*
            if ($currentStatementId != $statementId) {
                $currentStatementId = $statementId;

                $places[$placeId][$propId] = [
                    'label' => $placeData['propLabel']['value'],
                    'values' => [],
                ];
            }
            */

            /*
            if ($currentPropId != $propId) {
                $currentPropId = $propId;

                $places[$placeId][$propId] = [
                    //'id' => $placeData['propValue']['value'], hier falsch
                    'label' => $placeData['propLabel']['value'],
                    'values' => [],
                ];
            }

            $places[$placeId][$propId]['values'][] = [
                'id' => ($placeData['propValue']['type'] == 'uri') ? $placeData['propValue']['value'] : null,
                'value' => $placeData['propValueLabel']['value'],
            ];
            */




            /*
            $qIdUrl = $placeData['item']['value']; // http://www.wikidata.org/entity/Q106625716
            $qIdLabel = $placeData['itemLabel']['value'];
            $qIdDescription = $placeData['itemDescription']['value'];

            $itemPropertyId = $placeData['prop']['value']; // http://www.wikidata.org/entity/P31
            $itemPropertyValue = $placeData['propValueLabel']['value'];

            Log::debug("place", [
                'itemUrl' => $qIdUrl,
                'itemLabel' => $qIdLabel,
                'itemDescription' => $qIdDescription,
                $itemPropertyId => $itemPropertyValue,
            ]);
            */

            /*
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
            */
        }

        $groupedPlaces = array_fill_keys(array_keys(self::PLACE_GROUPS_IDS), []);

        foreach ($places as $placeId => $place) {
            $statements = $place['P31']['propertyStatements'];
            foreach ($statements as $statement) {

                foreach ($groupedPlaces as $groupedPlaceName => $groupedPlace) {
                    if (count(array_intersect([$statement['propertyValueId']], self::PLACE_GROUPS_IDS[$groupedPlaceName])) > 0) {
                        $groupedPlaces[$groupedPlaceName][$placeId] = $place;
                    }
                }
                //Log::debug($statement['propertyValueId']);
            }
        }

        //return $places;
        return $groupedPlaces;
    }
}

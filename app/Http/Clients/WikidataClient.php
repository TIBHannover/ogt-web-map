<?php


namespace App\Http\Clients;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
}

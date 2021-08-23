<?php


namespace App\Http\Clients;

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
                (GROUP_CONCAT(DISTINCT ?instance ; separator="|") as ?instanceUrls)
                (GROUP_CONCAT(DISTINCT ?instanceLabel ; separator=", ") as ?instanceLabels)
                ?lat
                ?lng
            WHERE {
                ?item wdt:P31 wd:Q106996250;
                    wdt:P31 ?instance;
                    p:P625 ?itemGeo.
                ?itemGeo psv:P625 ?geoNode.
                ?geoNode wikibase:geoLatitude ?lat;
                    wikibase:geoLongitude ?lng.
                SERVICE wikibase:label {
                    bd:serviceParam wikibase:language "de".
                    ?item rdfs:label ?itemLabel.
                    ?item schema:description ?itemDescription.
                    ?instance rdfs:label ?instanceLabel.
                }
            }
            GROUP BY ?item ?itemLabel ?itemDescription ?lat ?lng
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
            return $response->json()['results']['bindings'];
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
     * Group Wikidata places by
     * - Field Offices
     * - Extended police prisons / Labor education camps
     * - Prisons
     * - State Police Headquarters
     * - State Police Offices
     *
     * @param array $places
     * @return array
     */
    public function groupPlacesByType(array $places) : array
    {
        $fieldOfficesGroup = ['Q108047541', 'Q108047989', 'Q108047676', 'Q108047833', 'Q108047775'];
        $extPolicePrisonsAndLaborEducationCampsGroup = ['Q277565', 'Q108047650', 'Q108048094'];
        $prisonsGroup = ['Q40357'];
        $statePoliceHeadquartersGroup = ['Q108047581'];
        $statePoliceOfficesGroup = ['Q108048310', 'Q2101520', 'Q108047567'];

        $groupedPlaces = [
            'fieldOffices'                           => [],
            'extPolicePrisonsAndLaborEducationCamps' => [],
            'prisons'                                => [],
            'statePoliceHeadquarters'                => [],
            'statePoliceOffices'                     => [],
        ];

        foreach ($places as $place) {
            $instanceUrls = $place['instanceUrls']['value'];
            $instanceQIds = str_replace('http://www.wikidata.org/entity/', '', $instanceUrls);
            $instanceQIdsArray = explode('|', $instanceQIds);

            if (count(array_intersect($instanceQIdsArray, $statePoliceOfficesGroup)) > 0) {
                $groupedPlaces['statePoliceOffices'][] = $place;
            }
            elseif (count(array_intersect($instanceQIdsArray, $extPolicePrisonsAndLaborEducationCampsGroup)) > 0) {
                $groupedPlaces['extPolicePrisonsAndLaborEducationCamps'][] = $place;
            }
            elseif (count(array_intersect($instanceQIdsArray, $fieldOfficesGroup)) > 0) {
                $groupedPlaces['fieldOffices'][] = $place;
            }
            elseif (count(array_intersect($instanceQIdsArray, $prisonsGroup)) > 0) {
                $groupedPlaces['prisons'][] = $place;
            }
            elseif (count(array_intersect($instanceQIdsArray, $statePoliceHeadquartersGroup)) > 0) {
                $groupedPlaces['statePoliceHeadquarters'][] = $place;
            }
            else {
                Log::warning(
                    'The location cannot be assigned to a map marker category based on its Wikidata instances.',
                    [
                        'placeQId'     => place['item']['value'],
                        'instanceQIds' => $instanceUrls,
                    ]
                );
            }
        }

        return $groupedPlaces;
    }
}

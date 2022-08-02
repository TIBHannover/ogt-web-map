<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use Carbon\CarbonInterval;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WikidataController extends Controller
{
    /**
     * Retrieve location data from Wikidata for website map view, filter unneeded data, and group by location type.
     *
     * @param WikidataClient $wikidataClient
     * @return Response
     */
    public function getPlaces(WikidataClient $wikidataClient) : Response
    {
        $start1 = now();
        $placesResponse = $wikidataClient->queryPlaces();

        $time1 = $start1->diffInMilliseconds(now());

        $start2 = now();
        $validator = Validator::make($placesResponse, [
            'head'                       => 'required|array:vars|size:1',
            'head.vars'                  => [
                'required',
                'array',
                'size:' . count(WikidataClient::PLACE_PROPERTIES),
                'in:' . implode(',', WikidataClient::PLACE_PROPERTIES),
            ],
            'results'                    => 'required|array:bindings|size:1',
            'results.bindings'           => 'required|array',
            'results.bindings.*'         => 'required|array',
            'results.bindings.*.*'       => 'required|array',
            'results.bindings.*.*.value' => 'present|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation of Wikidata places response failed.', $validator->errors()->all());

            return response([], Response::HTTP_NO_CONTENT);
        }

        $time2 = $start2->diffInMilliseconds(now());

        $start3 = now();
        $groupedPlaces = $wikidataClient->groupFilteredPlacesByType($placesResponse['results']['bindings']);
        $time3 = $start3->diffInMilliseconds(now());


        Log::debug("get places times", [
            'queryPlaces' => $time1,
            'validation' => $time2,
            'groupFilteredPlacesByType' => $time3,
        ]);

        return response($groupedPlaces, Response::HTTP_OK);
    }

    /**
     * Retrieve location data from Wikidata for website map view, filter unneeded data, and group by location type.
     * Works like getPlaces(...), but adapted and generalized to retrieve more and complementary data such as
     * properties and qualifiers.
     *
     * @param WikidataClient $wikidataClient
     * @return Response
     */
    public function getLocations(WikidataClient $wikidataClient) : Response
    {
        $start1 = now();
        $locationsQueryData = $wikidataClient->queryLocations();
        $time1 = $start1->diffInMilliseconds(now());

        $start2 = now();

        $validator = Validator::make($locationsQueryData, [
            'head'                       => 'required|array:vars|size:1',
            'head.vars'                  => [
                'required',
                'array',
                'size:12',
                //'in:' . implode(',', WikidataClient::PLACE_PROPERTIES),
            ],
            'results'                    => 'required|array:bindings|size:1',
            'results.bindings'           => 'required|array',
            'results.bindings.*'         => 'required|array',
            //'results.bindings.*.*'       => 'required|array',
            /*
            'results.bindings.*.*.value' => 'present|string',
            */
        ]);

        if ($validator->fails()) {
            Log::warning('Validation of Wikidata places response failed.', $validator->errors()->all());

            return response([], Response::HTTP_NO_CONTENT);
        }

        $time2 = $start2->diffInMilliseconds(now());

        $start3 = now();
        $locations = $wikidataClient->mergeLocationData($locationsQueryData['results']['bindings']);
        $locationsByType = $wikidataClient->getLocationsByType($locations);
        $time3 = $start3->diffInMilliseconds(now());

        Log::debug("get locations times", [
            'queryPlaces' => $time1,
            'validation' => $time2,
            'mergedAndGrouped' => $time3,
        ]);

        return response($locationsByType, Response::HTTP_OK);
    }
}

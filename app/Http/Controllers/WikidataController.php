<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WikidataController extends Controller
{
    /**
     * Get location data from Wikidata for website map view, merge required data by location and group by location type.
     *
     * @param WikidataClient $wikidataClient
     * @return Response
     */
    public function getPlaces(WikidataClient $wikidataClient) : Response
    {
        $placesResponse = $wikidataClient->queryPlaces();

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

        $locations = $wikidataClient->mergeItemsData($placesResponse['results']['bindings']);
        $locationsByType = $wikidataClient->groupLocationsByType($locations);

        return response($locationsByType, Response::HTTP_OK);
    }
}

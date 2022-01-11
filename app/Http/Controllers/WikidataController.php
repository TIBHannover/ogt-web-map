<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WikidataController extends Controller
{
    public function getPlaces(WikidataClient $wikidataClient) : Response
    {
        $placesResponse = $wikidataClient->queryPlaces();

        $validator = Validator::make($placesResponse, [
            'head'                       => 'required|array:vars|size:1',
            'head.vars'                  =>
                'required|array|size:8|in:' . implode(',', WikidataClient::PLACE_PROPERTIES),
            'results'                    => 'required|array:bindings|size:1',
            'results.bindings'           => 'required|array',
            'results.bindings.*'         => 'required|array',
            'results.bindings.*.*'       => 'required|array',
            'results.bindings.*.*.value' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation of Wikidata places response failed.', $validator->errors()->all());

            return response([], Response::HTTP_NO_CONTENT);
        }

        $groupedPlaces = $wikidataClient->groupFilteredPlacesByType($placesResponse['results']['bindings']);

        return response($groupedPlaces, Response::HTTP_OK);
    }
}

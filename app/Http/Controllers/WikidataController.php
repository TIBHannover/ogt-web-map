<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use Illuminate\Http\Response;

class WikidataController extends Controller
{
    public function getPlaces(WikidataClient $wikidataClient)
    {
        $places = $wikidataClient->queryPlaces();

        if (empty($places)) {
            return response([], Response::HTTP_NO_CONTENT);
        }

        $groupedPlaces = $wikidataClient->groupFilteredPlacesByType($places);

        return response($groupedPlaces, Response::HTTP_OK);
    }
}

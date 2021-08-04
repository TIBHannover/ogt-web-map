<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use Illuminate\Http\Response;

class WikidataController extends Controller
{
    public function getPlaces(WikidataClient $wikidataClient)
    {
        $places = $wikidataClient->queryPlaces();

        $responseStatus = Response::HTTP_OK;

        if (empty($places)) {
            $responseStatus = Response::HTTP_NO_CONTENT;
        }

        return response($places, $responseStatus);
    }
}

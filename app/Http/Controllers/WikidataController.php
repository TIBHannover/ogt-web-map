<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WikidataController extends Controller
{
    /**
     * Get persons data from Wikidata for website map view, merge required data by person, group by person role, and
     * cache data in file.
     *
     * @param WikidataClient $wikidataClient
     *
     * @return Response
     */
    public function getPersons(WikidataClient $wikidataClient) : Response
    {
        $personsResponse = $wikidataClient->queryPersons();

        $validator = Validator::make($personsResponse, [
            'head'             => 'required|array:vars|size:1',
            'head.vars'        => [
                'required',
                'array',
                'size:12',
                Rule::in(
                    [
                        'item',
                        'itemLabel',
                        'itemDescription',
                        'property',
                        'statement',
                        'propertyValue',
                        'propertyValueLabel',
                        'propertyTimePrecision',
                        'qualifier',
                        'qualifierValue',
                        'qualifierValueLabel',
                        'qualifierTimePrecision',
                    ]
                ),
            ],
            'results'          => 'required|array:bindings|size:1',
            'results.bindings' => 'required|array',
        ]);

        if ($validator->fails())
        {
            Log::warning('Validation of Wikidata persons response failed.', $validator->errors()->all());

            return response([], Response::HTTP_NO_CONTENT);
        }

        $persons = $wikidataClient->mergeItemsData($personsResponse['results']['bindings']);
        $groupedPersons = $wikidataClient->groupItemsByProperty($persons, WikidataClient::PERSON_GROUPS_IDS, 'P2868');

        Storage::disk('local')->put(config('wikidata.cachedPersonsFile'), json_encode($groupedPersons));

        return response($groupedPersons, Response::HTTP_OK);
    }

    /**
     * Get cached Wikidata persons data from file.
     *
     * @return Response
     * @throws FileNotFoundException
     */
    public function getCachedPersons() : Response
    {
        $groupedPersons = array_fill_keys(array_keys(WikidataClient::PERSON_GROUPS_IDS), []);

        if (Storage::disk('local')->exists(config('wikidata.cachedPersonsFile')))
        {
            $groupedPersons = Storage::disk('local')->get(config('wikidata.cachedPersonsFile'));
        }

        return response($groupedPersons, Response::HTTP_OK);
    }

    /**
     * Get location data from Wikidata for website map view, merge required data by location and group by location type.
     *
     * @param WikidataClient $wikidataClient
     *
     * @return Response
     */
    public function getPlaces(WikidataClient $wikidataClient) : Response
    {
        $placesResponse = $wikidataClient->queryPlaces();
        $validator = Validator::make($placesResponse, [
            'head'             => 'required|array:vars|size:1',
            'head.vars'        => [
                'required',
                'array',
                'size:12',
                Rule::in(
                    [
                        'item',
                        'itemLabel',
                        'itemDescription',
                        'property',
                        'statement',
                        'propertyValue',
                        'propertyValueLabel',
                        'propertyTimePrecision',
                        'qualifier',
                        'qualifierValue',
                        'qualifierValueLabel',
                        'qualifierTimePrecision',
                    ]
                ),
            ],
            'results'          => 'required|array:bindings|size:1',
            'results.bindings' => 'required|array',
        ]);

        if ($validator->fails())
        {
            Log::warning('Validation of Wikidata places response failed.', $validator->errors()->all());

            return response([], Response::HTTP_NO_CONTENT);
        }

        $locations = $wikidataClient->mergeItemsData($placesResponse['results']['bindings']);
        $groupedLocations = $wikidataClient->groupItemsByProperty($locations, WikidataClient::PLACE_GROUPS_IDS, 'P31');

        return response($groupedLocations, Response::HTTP_OK);
    }
}

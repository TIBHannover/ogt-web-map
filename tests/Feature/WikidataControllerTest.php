<?php

namespace Tests\Feature;

use App\Http\Clients\WikidataClient;
use Faker\Generator;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WikidataControllerTest extends TestCase
{
    /** @var Generator $faker */
    protected $faker = null;

    protected function setUp() : void
    {
        parent::setUp();

        $this->faker = app(Generator::class);
    }

    /**
     * Test get Wikidata places successfully request.
     */
    public function testGetWikidataPlacesSuccess()
    {
        $placeData = [];

        $testCases = [
            [Arr::random(WikidataClient::PLACE_GROUPS_IDS['statePoliceOffices'])],
            [Arr::random(WikidataClient::PLACE_GROUPS_IDS['prisons'])],
            [Arr::random(WikidataClient::PLACE_GROUPS_IDS['fieldOffices'])],
            [Arr::random(WikidataClient::PLACE_GROUPS_IDS['extPolicePrisonsAndLaborEducationCamps'])],
            [Arr::random(WikidataClient::PLACE_GROUPS_IDS['statePoliceHeadquarters'])],
            [
                Arr::random(WikidataClient::PLACE_GROUPS_IDS['extPolicePrisonsAndLaborEducationCamps']),
                Arr::random(WikidataClient::PLACE_GROUPS_IDS['statePoliceOffices']),
                Arr::random(WikidataClient::PLACE_GROUPS_IDS['fieldOffices']),
                Arr::random(WikidataClient::PLACE_GROUPS_IDS['prisons']),
                Arr::random(WikidataClient::PLACE_GROUPS_IDS['statePoliceHeadquarters']),
            ],
        ];

        foreach ($testCases as $testCase) {
            $placeData[] = $this->generatePlaceData($testCase + ['Q123456789']);
        }

        $responseContentFake = [
            'head'    => [
                'vars' => [
                    'item',
                    'itemLabel',
                    'itemDescription',
                    'instanceUrls',
                    'instanceLabels',
                    'lat',
                    'lng',
                ],
            ],
            'results' => [
                'bindings' => $placeData,
            ],
        ];

        $expectedResponse = [
            'events'                                 => [],
            'fieldOffices'                           => [
                $responseContentFake['results']['bindings'][2],
                $responseContentFake['results']['bindings'][5],
            ],
            'extPolicePrisonsAndLaborEducationCamps' => [
                $responseContentFake['results']['bindings'][3],
                $responseContentFake['results']['bindings'][5],
            ],
            'prisons'                                => [
                $responseContentFake['results']['bindings'][1],
                $responseContentFake['results']['bindings'][5],
            ],
            'statePoliceHeadquarters'                => [
                $responseContentFake['results']['bindings'][4],
                $responseContentFake['results']['bindings'][5],
            ],
            'statePoliceOffices'                     => [
                $responseContentFake['results']['bindings'][0],
                $responseContentFake['results']['bindings'][5],
            ],
        ];

        Http::fake(
            [
                config('wikidata.url') . '*' => Http::response($responseContentFake, Response::HTTP_OK),
            ]
        );

        Log::shouldReceive('warning')->never();

        $this->get('/wikidata/places')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson($expectedResponse);
    }

    /**
     * Generate a valid place wikidata entry for response mockup.
     *
     * @param array $instanceQIds
     * @return array
     */
    private function generatePlaceData(array $instanceQIds) : array
    {
        $instanceQIds = substr_replace($instanceQIds, 'http://www.wikidata.org/entity/', 0, 0);

        return [
            'item'            => [
                'value' => $this->faker->url,
            ],
            'itemLabel'       => [
                'value' => $this->faker->word,
            ],
            'itemDescription' => [
                'value' => $this->faker->sentence,
            ],
            'instanceUrls'    => [
                'value' => implode('|', $instanceQIds),
            ],
            'instanceLabels'  => [
                'value' => implode(',', $this->faker->words),
            ],
            'lat'             => [
                'value' => $this->faker->latitude,
            ],
            'lng'             => [
                'value' => $this->faker->longitude,
            ],
        ];
    }

    /**
     * Test get Wikidata places successfully request, but one place has instances, where no group assignment exists.
     */
    public function testPlaceToGroupAssignmentNotFound()
    {
        $placeData = [];

        $testCases = [
            [Arr::random(WikidataClient::PLACE_GROUPS_IDS['statePoliceOffices'])],
            ['Q987654321'],
        ];

        foreach ($testCases as $testCase) {
            $placeData[] = $this->generatePlaceData($testCase + ['Q123456789']);
        }

        $responseContentFake = [
            'head'    => [
                'vars' => [
                    'item',
                    'itemLabel',
                    'itemDescription',
                    'instanceUrls',
                    'instanceLabels',
                    'lat',
                    'lng',
                ],
            ],
            'results' => [
                'bindings' => $placeData,
            ],
        ];

        $expectedResponse = [
            'events'                                 => [],
            'fieldOffices'                           => [],
            'extPolicePrisonsAndLaborEducationCamps' => [],
            'prisons'                                => [],
            'statePoliceHeadquarters'                => [],
            'statePoliceOffices'                     => [$responseContentFake['results']['bindings'][0]],
        ];

        Http::fake(
            [
                config('wikidata.url') . '*' => Http::response($responseContentFake, Response::HTTP_OK),
            ]
        );

        Log::shouldReceive('warning')->once()->with(
            'The location cannot be assigned to a map marker category based on its Wikidata instances.',
            [
                'instanceQIds' => $responseContentFake['results']['bindings'][1]['instanceUrls']['value'],
                'placeQId'     => $responseContentFake['results']['bindings'][1]['item']['value'],
            ]
        );

        $this->get('/wikidata/places')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson($expectedResponse);
    }

    /**
     * Test get Wikidata places failed request.
     */
    public function testGetWikidataPlacesFailedRequest()
    {
        Http::fake(
            [
                config('wikidata.url') . '*' => Http::sequence()
                    ->push(['content' => 'text bad request'], Response::HTTP_BAD_REQUEST)
                    ->push(['content' => 'text internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR),
            ]
        );

        Log::shouldReceive('error')->twice();

        $this->get('/wikidata/places')
            ->assertNoContent();

        $this->get('/wikidata/places')
            ->assertNoContent();
    }

    /**
     * Test get Wikidata places empty response.
     */
    public function testGetWikidataPlacesEmptyResponse()
    {
        $responseNoDataReturned = [
            'head' => [
                'vars' => [
                    'item',
                    'itemLabel',
                    'itemDescription',
                    'instanceUrls',
                    'instanceLabels',
                    'lat',
                    'lng',
                ],
            ],
            'results' => [
                'bindings' => [],
            ],
        ];

        Http::fake(
            [
                config('wikidata.url') . '*' => Http::response($responseNoDataReturned, Response::HTTP_OK),
            ]
        );

        $this->get('/wikidata/places')
            ->assertNoContent();
    }
}

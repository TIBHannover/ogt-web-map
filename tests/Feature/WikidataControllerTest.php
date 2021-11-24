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
        $placeDataArray = [];
        $expectedFilteredPlaceData = [];

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
            $placeData = $this->generatePlaceData($testCase + ['Q123456789']);

            $placeDataArray[] = $placeData;

            foreach ($placeData as $key => $data) {
                $placeData[$key] = [
                    'value' => $data['value'],
                ];
            }

            $expectedFilteredPlaceData[] = $placeData;
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
                    'imageUrl',
                ],
            ],
            'results' => [
                'bindings' => $placeDataArray,
            ],
        ];

        $expectedResponse = [
            'events'                                 => [],
            'fieldOffices'                           => [
                $expectedFilteredPlaceData[2],
                $expectedFilteredPlaceData[5],
            ],
            'extPolicePrisonsAndLaborEducationCamps' => [
                $expectedFilteredPlaceData[3],
                $expectedFilteredPlaceData[5],
            ],
            'prisons'                                => [
                $expectedFilteredPlaceData[1],
                $expectedFilteredPlaceData[5],
            ],
            'statePoliceHeadquarters'                => [
                $expectedFilteredPlaceData[4],
                $expectedFilteredPlaceData[5],
            ],
            'statePoliceOffices'                     => [
                $expectedFilteredPlaceData[0],
                $expectedFilteredPlaceData[5],
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
        $instanceUrlQIds = substr_replace($instanceQIds, 'http://www.wikidata.org/entity/', 0, 0);

        return [
            'item'            => [
                'type'  => 'uri',
                'value' => 'http://www.wikidata.org/entity/' . $this->faker->randomNumber(9),
            ],
            'itemLabel'       => [
                'type'     => 'literal',
                'value'    => $this->faker->word,
                'xml:lang' => 'de',
            ],
            'itemDescription' => [
                'type'     => 'literal',
                'value'    => $this->faker->sentence,
                'xml:lang' => 'de',
            ],
            'lat'             => [
                'datatype' => 'http://www.w3.org/2001/XMLSchema#double',
                'type'     => 'literal',
                'value'    => $this->faker->latitude,
            ],
            'lng'             => [
                'datatype' => 'http://www.w3.org/2001/XMLSchema#double',
                'type'     => 'literal',
                'value'    => $this->faker->longitude,
            ],
            'instanceUrls'    => [
                'type'  => 'literal',
                'value' => implode('|', $instanceUrlQIds),
            ],
            'instanceLabels'  => [
                'type'  => 'literal',
                'value' => implode(',', $this->faker->words),
            ],
            'imageUrl'        => [
                'type'  => 'uri',
                'value' => $this->faker->imageUrl,
            ],
        ];
    }

    /**
     * Test get Wikidata places successfully request, but one place has instances, where no group assignment exists.
     */
    public function testPlaceToGroupAssignmentNotFound()
    {
        $placeDataArray = [];
        $expectedFilteredPlaceData = [];

        $testCases = [
            [Arr::random(WikidataClient::PLACE_GROUPS_IDS['statePoliceOffices'])],
            ['Q987654321'],
        ];

        foreach ($testCases as $testCase) {
            $placeData = $this->generatePlaceData($testCase + ['Q123456789']);

            $placeDataArray[] = $placeData;

            foreach ($placeData as $key => $data) {
                $placeData[$key] = [
                    'value' => $data['value'],
                ];
            }

            $expectedFilteredPlaceData[] = $placeData;
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
                    'imageUrl',
                ],
            ],
            'results' => [
                'bindings' => $placeDataArray,
            ],
        ];

        $expectedResponse = [
            'events'                                 => [],
            'fieldOffices'                           => [],
            'extPolicePrisonsAndLaborEducationCamps' => [],
            'prisons'                                => [],
            'statePoliceHeadquarters'                => [],
            'statePoliceOffices'                     => [$expectedFilteredPlaceData[0]],
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
            'head'    => [
                'vars' => [
                    'item',
                    'itemLabel',
                    'itemDescription',
                    'instanceUrls',
                    'instanceLabels',
                    'lat',
                    'lng',
                    'imageUrl',
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

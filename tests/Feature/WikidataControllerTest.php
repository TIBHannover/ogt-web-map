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
            'coordinates'     => [
                'type'  => 'literal',
                'value' => $this->faker->latitude . ',' . $this->faker->longitude,
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
                'instanceQIds' => $expectedFilteredPlaceData[1]['instanceUrls']['value'],
                'placeQId'     => $expectedFilteredPlaceData[1]['item']['value'],
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

        Log::shouldReceive('warning')->twice()->with(
            'Validation of Wikidata places response failed.',
            [
                'The head field is required.',
                'The head.vars field is required.',
                'The results field is required.',
                'The results.bindings field is required.',
            ]
        );

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

        Log::shouldReceive('warning')->once()->with(
            'Validation of Wikidata places response failed.',
            ['The results.bindings field is required.']
        );

        $this->get('/wikidata/places')
            ->assertNoContent();
    }

    /**
     * Provide test data for test case get-Wikidata-places-validation-for-properties.
     *
     * @return array \string[][][] Each test case has
     * - an array of returned response place properties and
     * - an array of expected failed validation messages.
     */
    public function providePlacePropertiesTestData() : array
    {
        return [
            'removed property item' => [
                [
                    //'item',
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
                ],
                [
                    'The head.vars must contain 16 items.',
                ],
            ],
            'added property item'   => [
                [
                    'test', // added
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
                ],
                [
                    'The head.vars must contain 16 items.',
                    'The selected head.vars is invalid.',
                ],
            ],
            'renamed property item' => [
                [
                    'itemRenamed', // renamed
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
                ],
                [
                    'The selected head.vars is invalid.',
                ],
            ],
        ];
    }

    /**
     * Test validation for queried Wikidata places, if place properties added, missed or modified.
     *
     * @dataProvider providePlacePropertiesTestData
     *
     * @param array $responsePlaceProperties Returned response place properties
     * @param array $failedValidationMessages Expected failed validation messages
     */
    public function testGetWikidataPlacesValidationForProperties(
        array $responsePlaceProperties,
        array $failedValidationMessages
    ) {
        $placeData = $this->generatePlaceData([Arr::random(WikidataClient::PLACE_GROUPS_IDS['statePoliceOffices'])]);

        $responseNoDataReturned = [
            'head'    => [
                'vars' => $responsePlaceProperties,
            ],
            'results' => [
                'bindings' => [$placeData],
            ],
        ];

        Http::fake(
            [
                config('wikidata.url') . '*' => Http::response($responseNoDataReturned, Response::HTTP_OK),
            ]
        );

        Log::shouldReceive('warning')->once()->with(
            'Validation of Wikidata places response failed.',
            $failedValidationMessages
        );

        $this->get('/wikidata/places')
            ->assertNoContent();
    }
}

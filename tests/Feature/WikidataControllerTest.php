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
     * - test each valid place group
     */
    public function testGetWikidataPlacesSuccess()
    {
        $placeDataArray = [];
        $expectedResponse = [];

        $locationGroupNames = [
            'extPolicePrisons',
            'fieldOffices',
            'laborEducationCamps',
            'prisons',
            'statePoliceHeadquarters',
            'statePoliceOffices',
        ];

        foreach ($locationGroupNames as $index => $locationGroupName)
        {
            $instanceId = Arr::random(WikidataClient::PLACE_GROUPS_IDS[$locationGroupName]);
            [$placeData, $expectedPlaceData] = $this->generatePlaceData('Q' . $index, $instanceId);
            $placeDataArray[] = $placeData;

            if ($expectedPlaceData)
            {
                $expectedResponse[$locationGroupName] = $expectedPlaceData;
            }
        }

        $responseContentFake = [
            'head'    => [
                'vars' => [
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
                ],
            ],
            'results' => [
                'bindings' => $placeDataArray,
            ],
        ];

        Http::fake(
            [
                config('wikidata.url') . '*' => Http::response($responseContentFake, Response::HTTP_OK),
            ]
        );

        Log::shouldReceive('warning')->never();

        $this->get('/api/wikidata/places')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson($expectedResponse);
    }

    /**
     * Generate a valid place wikidata entry for response mockup and expected data returned by request.
     *
     * @param string $itemId e.g. Q42
     * @param string $instanceId
     *
     * @return array
     */
    private function generatePlaceData(string $itemId, string $instanceId) : array
    {
        $itemLabel = $itemId . ' item label';
        $itemDescription = $itemId . ' item description';
        $wikidataEntityUrl = 'http://www.wikidata.org/entity/';

        $locationData = [
            'item'               => [
                'type'  => 'uri',
                'value' => $wikidataEntityUrl . $itemId,
            ],
            'itemLabel'          => [
                'type'     => 'literal',
                'value'    => $itemLabel,
                'xml:lang' => 'de',
            ],
            'itemDescription'    => [
                'type'     => 'literal',
                'value'    => $itemDescription,
                'xml:lang' => 'de',
            ],
            'property'           => [
                'type'  => 'uri',
                'value' => $wikidataEntityUrl . 'P31', // instance-of property id
            ],
            'statement'          => [
                'type'  => 'uri',
                'value' => $wikidataEntityUrl . 'statement/' . $itemId . '-' . $this->faker->uuid,
            ],
            'propertyValue'      => [
                'type'  => 'uri',
                'value' => $wikidataEntityUrl . $instanceId, // instance-of item id
            ],
            'propertyValueLabel' => [
                'type'     => 'literal',
                'value'    => 'Label of instance-of item',
                'xml:lang' => 'de',
            ],
        ];

        $expectedLocationData = [];

        if (in_array($instanceId, Arr::flatten(WikidataClient::PLACE_GROUPS_IDS)))
        {
            $expectedLocationData = [
                $itemId => [
                    'description' => $itemDescription,
                    'id'          => $itemId,
                    'label'       => $itemLabel,
                ],
            ];
        }

        return [$locationData, $expectedLocationData];
    }

    /**
     * Test get Wikidata places successfully request, but one place has instance, where no group assignment exists.
     */
    public function testPlaceToGroupAssignmentNotFound()
    {
        $placeDataArray = [];
        $expectedResponse = [];

        $instanceIds = [
            'Q108047541', // valid instance id of group fieldOffices
            'Q987654321', // invalid instance id
        ];

        foreach ($instanceIds as $index => $instanceId)
        {
            [$placeData, $expectedPlaceData] = $this->generatePlaceData('Q' . $index, $instanceId);

            $placeDataArray[] = $placeData;

            if ($expectedPlaceData)
            {
                $expectedResponse['fieldOffices'] = $expectedPlaceData;
            }
        }

        $responseContentFake = [
            'head'    => [
                'vars' => [
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
                ],
            ],
            'results' => [
                'bindings' => $placeDataArray,
            ],
        ];

        Http::fake(
            [
                config('wikidata.url') . '*' => Http::response($responseContentFake, Response::HTTP_OK),
            ]
        );

        Log::shouldReceive('warning')->once()->with(
            'The location cannot be assigned to a map marker category based on its Wikidata instances.',
            [
                'instanceIds' => ['Q987654321'],
                'locationId'  => 'Q1',
            ]
        );

        $this->get('/api/wikidata/places')
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

        $this->get('/api/wikidata/places')
            ->assertNoContent();

        $this->get('/api/wikidata/places')
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
                    'property',
                    'statement',
                    'propertyValue',
                    'propertyValueLabel',
                    'propertyTimePrecision',
                    'qualifier',
                    'qualifierValue',
                    'qualifierValueLabel',
                    'qualifierTimePrecision',
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

        $this->get('/api/wikidata/places')
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
            'removed data header' => [
                [
                    //'item',
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
                ],
                [
                    'The head.vars must contain 12 items.',
                ],
            ],
            'added data header'   => [
                [
                    'test', // added
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
                ],
                [
                    'The head.vars must contain 12 items.',
                    'The selected head.vars is invalid.',
                ],
            ],
            'renamed data header' => [
                [
                    'itemRenamed', // renamed
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
     * @param array $responsePlaceProperties  Returned response place properties
     * @param array $failedValidationMessages Expected failed validation messages
     */
    public function testGetWikidataPlacesValidationForProperties(
        array $responsePlaceProperties,
        array $failedValidationMessages
    ) {
        $instanceId = Arr::random(WikidataClient::PLACE_GROUPS_IDS['statePoliceOffices']);
        [$placeData, $expectedPlaceData] = $this->generatePlaceData('Q1', $instanceId);

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

        $this->get('/api/wikidata/places')
            ->assertNoContent();
    }
}

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
    const WIKIDATA_ENTITY_URL = 'http://www.wikidata.org/entity/';

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
            $itemId = 'Q' . $index;
            [$placeData, $expectedPlaceData] = $this->generatePlaceData($itemId, $instanceId);
            $placeDataArray[] = $placeData;

            [$placeTimeData, $expectedPlaceTimeData] = $this->createPlaceTimeData($itemId);
            $placeDataArray[] = $placeTimeData;

            [$placeCoordinateData, $expectedPlaceCoordinateData] = $this->createPlaceCoordinateData($itemId);
            $placeDataArray[] = $placeCoordinateData;

            $expectedResponse[$locationGroupName][$itemId] = array_merge(
                $expectedPlaceData[$itemId],
                $expectedPlaceTimeData[$itemId],
                $expectedPlaceCoordinateData[$itemId]
            );
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

        $locationData = [
            'item'               => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $itemId,
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
                'value' => self::WIKIDATA_ENTITY_URL . 'P31', // instance-of property id
            ],
            'statement'          => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . 'statement/' . $itemId . '-' . $this->faker->uuid,
            ],
            'propertyValue'      => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $instanceId, // instance-of item id
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
     * Create location time property and qualifier data and return expected processed data too.
     *
     * @param string $itemId e.g. Q42
     *
     * @return array
     */
    private function createPlaceTimeData(string $itemId) : array
    {
        $itemLabel = $itemId . ' item label';
        $itemDescription = $itemId . ' item description';
        $statementId = $itemId . '-' . $this->faker->uuid;

        $validProperties = [
            'P571' => 'inceptionDates',
            'P576' => 'dissolvedDates',
        ];
        $propertyId = $this->faker->randomKey($validProperties);
        $propertyTime = $this->faker->date() . 'T00:00:00Z';
        $propertyTimePrecision = $this->faker->randomElements(['9', '10', '11']);

        $validQualifiers = [
            'P580'  => 'startTime',
            'P582'  => 'endTime',
            'P585'  => 'pointInTime',
            'P1319' => 'earliestDate',
            'P1326' => 'latestDate',
            'P8554' => 'earliestEndDate',
            'P8555' => 'latestStartDate',
        ];
        $qualifierId = $this->faker->randomKey($validQualifiers);
        $qualifierTime = $this->faker->date() . 'T00:00:00Z';
        $qualifierTimePrecision = $this->faker->randomElements(['9', '10', '11']);

        $locationData = [
            'item'                   => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $itemId,
            ],
            'itemLabel'              => [
                'type'     => 'literal',
                'value'    => $itemLabel,
                'xml:lang' => 'de',
            ],
            'itemDescription'        => [
                'type'     => 'literal',
                'value'    => $itemDescription,
                'xml:lang' => 'de',
            ],
            'property'               => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $propertyId, // e.g. inception date property id
            ],
            'statement'              => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . 'statement/' . $statementId,
            ],
            'propertyValue'          => [
                'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                'type'     => 'literal',
                'value'    => $propertyTime,
            ],
            'propertyValueLabel'     => [
                'type'  => 'literal',
                'value' => $propertyTime,
            ],
            'propertyTimePrecision'  => [
                'datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                'type'     => 'literal',
                'value'    => $propertyTimePrecision,
            ],
            'qualifier'              => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $qualifierId, // e.g. inception date property id
            ],
            'qualifierValue'         => [
                'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                'type'     => 'literal',
                'value'    => $qualifierTime,
            ],
            'qualifierValueLabel'    => [
                'type'  => 'literal',
                'value' => $qualifierTime,
            ],
            'qualifierTimePrecision' => [
                'datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                'type'     => 'literal',
                'value'    => $qualifierTimePrecision,
            ],
        ];

        $expectedLocationData = [
            $itemId => [
                'description'                 => $itemDescription,
                'id'                          => $itemId,
                'label'                       => $itemLabel,
                $validProperties[$propertyId] => [
                    $statementId => [
                        'value'                        => $propertyTime,
                        'datePrecision'                => $propertyTimePrecision,
                        $validQualifiers[$qualifierId] => [
                            'value'         => $qualifierTime,
                            'datePrecision' => $qualifierTimePrecision,
                        ],
                    ],
                ],
            ],
        ];

        return [$locationData, $expectedLocationData];
    }

    /**
     * Create location coordinate property and qualifier data and return expected processed data too.
     *
     * @param string $itemId e.g. Q42
     *
     * @return array
     */
    private function createPlaceCoordinateData(string $itemId) : array
    {
        $itemLabel = $itemId . ' item label';
        $itemDescription = $itemId . ' item description';
        $statementId = $itemId . '-' . $this->faker->uuid;
        $propertyLat = $this->faker->latitude;
        $propertyLng = $this->faker->longitude;
        $propertyCoordinates = 'Point(' . $propertyLng . ' ' . $propertyLat . ')';
        $qualifierLat = $this->faker->latitude;
        $qualifierLng = $this->faker->longitude;
        $qualifierCoordinates = 'Point(' . $qualifierLng . ' ' . $qualifierLat . ')';

        $locationData = [
            'item'                => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $itemId,
            ],
            'itemLabel'           => [
                'type'     => 'literal',
                'value'    => $itemLabel,
                'xml:lang' => 'de',
            ],
            'itemDescription'     => [
                'type'     => 'literal',
                'value'    => $itemDescription,
                'xml:lang' => 'de',
            ],
            'property'            => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . 'P625', // e.g. coordinates property id
            ],
            'statement'           => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . 'statement/' . $statementId,
            ],
            'propertyValue'       => [
                'datatype' => 'http://www.opengis.net/ont/geosparql#wktLiteral',
                'type'     => 'literal',
                'value'    => $propertyCoordinates,
            ],
            'propertyValueLabel'  => [
                'type'  => 'literal',
                'value' => $propertyCoordinates,
            ],
            'qualifier'           => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . 'P625', // e.g. coordinates property id
            ],
            'qualifierValue'      => [
                'datatype' => 'http://www.opengis.net/ont/geosparql#wktLiteral',
                'type'     => 'literal',
                'value'    => $qualifierCoordinates,
            ],
            'qualifierValueLabel' => [
                'type'  => 'literal',
                'value' => $qualifierCoordinates,
            ],
        ];

        $expectedLocationData = [
            $itemId => [
                'description' => $itemDescription,
                'id'          => $itemId,
                'label'       => $itemLabel,
                'coordinates' => [
                    $statementId => [
                        'value'      => [
                            'lat' => "$propertyLat",
                            'lng' => "$propertyLng",
                        ],
                        'coordinate' => [
                            'value' => [
                                'lat' => "$qualifierLat",
                                'lng' => "$qualifierLng",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return [$locationData, $expectedLocationData];
    }

    /**
     * Test get Wikidata places successfully request, but one place has multiple group assignments.
     * A location is assigned to the first location group found.
     *
     * @return void
     */
    public function testPlaceToMultipleGroupAssignments()
    {
        $placeDataArray = [];
        $expectedResponse = [];

        // valid instance id of group statePoliceOffices
        [$placeData, $expectedPlaceData] = $this->generatePlaceData('Q1', 'Q108048310');
        $placeDataArray[] = $placeData;

        // valid instance id of group fieldOffices
        [$placeData, $expectedPlaceData] = $this->generatePlaceData('Q1', 'Q108047775');
        $placeDataArray[] = $placeData;
        $expectedResponse['fieldOffices'] = $expectedPlaceData;

        // valid instance id of group prisons
        [$placeData, $expectedPlaceData] = $this->generatePlaceData('Q1', 'Q40357');
        $placeDataArray[] = $placeData;

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

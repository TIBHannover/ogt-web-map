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
     * Test get Wikidata persons successfully request.
     */
    public function testGetWikidataPersonsSuccess()
    {
        $placeDataArray = [];
        $expectedResponse = [
            'perpetrators' => [],
            'victims'      => [],
        ];

        $personGroupNames = [
            'perpetrators',
            'victims',
        ];

        foreach ($personGroupNames as $index => $personGroupName)
        {
            $groupId = Arr::random(WikidataClient::PERSON_GROUPS_IDS[$personGroupName]);
            $itemId = 'Q' . $index;

            [$placeInstanceData, $expectedPlaceInstanceData] =
                $this->createPropertyDataForGrouping($itemId, $groupId, 'P2868');
            $placeDataArray[] = $placeInstanceData;

            [$placeTimeData, $expectedPlaceTimeData] = $this->createPlaceTimeData($itemId);
            $placeDataArray[] = $placeTimeData;

            [$placeCoordinateData, $expectedPlaceCoordinateData] = $this->createPlaceCoordinateData($itemId);
            $placeDataArray[] = $placeCoordinateData;

            [$placeObjectData, $expectedPlaceObjectData] = $this->createPlaceObjectData($itemId);
            $placeDataArray[] = $placeObjectData;

            $expectedResponse[$personGroupName][$itemId] = array_merge(
                $expectedPlaceInstanceData[$itemId],
                $expectedPlaceTimeData[$itemId],
                $expectedPlaceCoordinateData[$itemId],
                $expectedPlaceObjectData[$itemId]
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

        $this->get('/api/wikidata/persons')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson($expectedResponse);
    }

    /**
     * Test get Wikidata places successfully request.
     * - test each valid place group
     */
    public function testGetWikidataPlacesSuccess()
    {
        $placeDataArray = [];
        $expectedResponse = [
            'events'                  => [],
            'extPolicePrisons'        => [],
            'fieldOffices'            => [],
            'laborEducationCamps'     => [],
            'memorials'               => [],
            'prisons'                 => [],
            'statePoliceHeadquarters' => [],
            'statePoliceOffices'      => [],
        ];

        $locationGroupNames = [
            'events',
            'extPolicePrisons',
            'fieldOffices',
            'laborEducationCamps',
            'memorials',
            'prisons',
            'statePoliceHeadquarters',
            'statePoliceOffices',
        ];

        foreach ($locationGroupNames as $index => $locationGroupName)
        {
            $instanceId = Arr::random(WikidataClient::PLACE_GROUPS_IDS[$locationGroupName]);
            $itemId = 'Q' . $index;
            [$placeInstanceData, $expectedPlaceInstanceData] =
                $this->createPropertyDataForGrouping($itemId, $instanceId, 'P31');
            $placeDataArray[] = $placeInstanceData;

            [$placeTimeData, $expectedPlaceTimeData] = $this->createPlaceTimeData($itemId);
            $placeDataArray[] = $placeTimeData;

            [$placeCoordinateData, $expectedPlaceCoordinateData] = $this->createPlaceCoordinateData($itemId);
            $placeDataArray[] = $placeCoordinateData;

            [$placeObjectData, $expectedPlaceObjectData] = $this->createPlaceObjectData($itemId);
            $placeDataArray[] = $placeObjectData;

            $expectedResponse[$locationGroupName][$itemId] = array_merge(
                $expectedPlaceInstanceData[$itemId],
                $expectedPlaceTimeData[$itemId],
                $expectedPlaceCoordinateData[$itemId],
                $expectedPlaceObjectData[$itemId]
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
     * Create valid data for Wikidata response mockup to group item and expected data returned by request.
     *
     * @param string $itemId          e.g. Q42
     * @param string $groupId         e.g. Q111080573 for perpetrators
     * @param string $groupPropertyId e.g. P31 for instance of or P2868 for subject has role
     *
     * @return array
     */
    private function createPropertyDataForGrouping(string $itemId, string $groupId, string $groupPropertyId) : array
    {
        $itemLabel = $itemId . ' item label';
        $itemDescription = $itemId . ' item description';
        $statementId = $itemId . '-' . $this->faker->uuid;
        $propertyValueLabel = $groupId . ' item label';

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
                'value' => self::WIKIDATA_ENTITY_URL . $groupPropertyId,
            ],
            'statement'          => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . 'statement/' . $statementId,
            ],
            'propertyValue'      => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $groupId,
            ],
            'propertyValueLabel' => [
                'type'     => 'literal',
                'value'    => $propertyValueLabel,
                'xml:lang' => 'de',
            ],
        ];

        $validGroupProperties = [
            'P31'   => 'instances',
            'P2868' => 'subjectRoles',
        ];

        $expectedLocationData = [
            $itemId => [
                'description'                           => $itemDescription,
                'id'                                    => $itemId,
                'label'                                 => $itemLabel,
                $validGroupProperties[$groupPropertyId] => [
                    $statementId => [
                        'id'    => $groupId,
                        'value' => $propertyValueLabel,
                    ],
                ],
            ],
        ];

        return [$locationData, $expectedLocationData];
    }

    /**
     * Create valid location time property- and qualifier-data for Wikidata response mockup and
     * expected data returned by get location data request.
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
            'P569'  => 'dateOfBirth',
            'P570'  => 'dateOfDeath',
            'P571'  => 'inceptionDates',
            'P576'  => 'dissolvedDates',
            'P580'  => 'startTime',
            'P582'  => 'endTime',
            'P585'  => 'pointInTime',
            'P1619' => 'openingDate',
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
                'value' => self::WIKIDATA_ENTITY_URL . $propertyId,
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
                'value' => self::WIKIDATA_ENTITY_URL . $qualifierId,
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
     * Create valid location coordinate property- and qualifier-data for Wikidata response mockup and
     * expected data returned by get location data request.
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
     * Create valid location linked object property- and qualifier-data for Wikidata response mockup and
     * expected data returned by get location data request.
     *
     * @param string $itemId e.g. Q42
     *
     * @return array
     */
    private function createPlaceObjectData(string $itemId) : array
    {
        $itemLabel = $itemId . ' item label';
        $itemDescription = $itemId . ' item description';
        $statementId = $itemId . '-' . $this->faker->uuid;
        $propertyValueId = 'Q' . $this->faker->numberBetween();
        $propertyValueLabel = $propertyValueId . ' item label';
        $qualifierValueId = 'Q' . $this->faker->numberBetween();
        $qualifierValueLabel = $qualifierValueId . ' item label';

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
                'value' => self::WIKIDATA_ENTITY_URL . 'P749', // parent organization property id
            ],
            'statement'           => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . 'statement/' . $statementId,
            ],
            'propertyValue'       => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $propertyValueId,
            ],
            'propertyValueLabel'  => [
                'type'     => 'literal',
                'value'    => $propertyValueLabel,
                'xml:lang' => 'de',
            ],
            'qualifier'           => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . 'P1480', // sourcing circumstances property id
            ],
            'qualifierValue'      => [
                'type'  => 'uri',
                'value' => self::WIKIDATA_ENTITY_URL . $qualifierValueId,
            ],
            'qualifierValueLabel' => [
                'type'     => 'literal',
                'value'    => $qualifierValueLabel,
                'xml:lang' => 'de',
            ],
        ];

        $expectedLocationData = [
            $itemId => [
                'description'         => $itemDescription,
                'id'                  => $itemId,
                'label'               => $itemLabel,
                'parentOrganizations' => [
                    $statementId => [
                        'id'                   => $propertyValueId,
                        'value'                => $propertyValueLabel,
                        'sourcingCircumstance' => [
                            'id'    => $qualifierValueId,
                            'value' => $qualifierValueLabel,
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
        $expectedResponse = [
            'events'                  => [],
            'extPolicePrisons'        => [],
            'fieldOffices'            => [],
            'laborEducationCamps'     => [],
            'memorials'               => [],
            'prisons'                 => [],
            'statePoliceHeadquarters' => [],
            'statePoliceOffices'      => [],
        ];

        // valid instance id of group statePoliceOffices
        [$placeInstanceData, $expectedPlaceInstanceData] =
            $this->createPropertyDataForGrouping('Q1', 'Q108048310', 'P31');
        $placeDataArray[] = $placeInstanceData;
        $expectedResponse['fieldOffices'] = $expectedPlaceInstanceData;

        // valid instance id of group fieldOffices
        [$placeInstanceData, $expectedPlaceInstanceData] =
            $this->createPropertyDataForGrouping('Q1', 'Q108047775', 'P31');
        $placeDataArray[] = $placeInstanceData;
        $expectedResponse['fieldOffices']['Q1']['instances'] += $expectedPlaceInstanceData['Q1']['instances'];

        // valid instance id of group prisons
        [$placeInstanceData, $expectedPlaceInstanceData] = $this->createPropertyDataForGrouping('Q1', 'Q40357', 'P31');
        $placeDataArray[] = $placeInstanceData;
        $expectedResponse['fieldOffices']['Q1']['instances'] += $expectedPlaceInstanceData['Q1']['instances'];

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
        $expectedResponse = [
            'events'                  => [],
            'extPolicePrisons'        => [],
            'fieldOffices'            => [],
            'laborEducationCamps'     => [],
            'memorials'               => [],
            'prisons'                 => [],
            'statePoliceHeadquarters' => [],
            'statePoliceOffices'      => [],
        ];

        // valid instance id Q108047541 of group fieldOffices
        [$placeInstanceData, $expectedPlaceInstanceData] =
            $this->createPropertyDataForGrouping('Q1', 'Q108047541', 'P31');
        $placeDataArray[] = $placeInstanceData;
        $expectedResponse['fieldOffices'] = $expectedPlaceInstanceData;

        // invalid instance id Q987654321
        [$placeInstanceData, $expectedPlaceInstanceData] =
            $this->createPropertyDataForGrouping('Q2', 'Q111111111', 'P31');
        $placeDataArray[] = $placeInstanceData;

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
            'The item cannot be assigned to a group because it does not have a valid Wikidata property value for available groups.',
            [
                'itemId' => 'Q2',
                'propertyIds' => ['Q111111111'],
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
        [$placeInstanceData, $expectedPlaceInstanceData] =
            $this->createPropertyDataForGrouping('Q1', $instanceId, 'P31');

        $responseNoDataReturned = [
            'head'    => [
                'vars' => $responsePlaceProperties,
            ],
            'results' => [
                'bindings' => [$placeInstanceData],
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

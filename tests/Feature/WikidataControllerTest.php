<?php

namespace Tests\Feature;

use Faker\Generator;
use Illuminate\Http\Response;
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
        $responseContentFake = [
            'head'    => [
                'vars' => [
                    'item',
                    'itemLabel',
                    'itemDescription',
                    'itemInstanceLabelConcat',
                    'lat',
                    'lng',
                ],
            ],
            'results' => [
                'bindings' => [
                    [
                        'item'                    => [
                            'value' => $this->faker->url,
                        ],
                        'itemLabel'               => [
                            'value' => $this->faker->word,
                        ],
                        'itemDescription'         => [
                            'value' => $this->faker->sentence,
                        ],
                        'itemInstanceLabelConcat' => [
                            'value' => implode(',', $this->faker->words),
                        ],
                        'lat'                     => [
                            'value' => $this->faker->latitude,
                        ],
                        'lng'                     => [
                            'value' => $this->faker->longitude,
                        ],
                    ],
                ],
            ],
        ];

        Http::fake(
            [
                config('wikidata.url') . '*' => Http::response($responseContentFake, Response::HTTP_OK),
            ]
        );

        $this->get('/wikidata/places')
            ->assertStatus(Response::HTTP_OK)
            ->assertJson($responseContentFake);
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
                    'itemInstanceLabelConcat',
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
            ->assertStatus(Response::HTTP_OK)
            ->assertJson($responseNoDataReturned);
    }
}

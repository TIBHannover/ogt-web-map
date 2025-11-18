<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use App\Http\Clients\FactgridClient;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WikibaseController extends Controller
{
    /**
     * Get the appropriate client based on configuration
     *
     * @return WikidataClient|FactgridClient
     */
    protected function getClient()
    {
        if (Config::get("wikibase.export_target") === "factgrid") {
            return app(FactgridClient::class);
        }

        return app(WikidataClient::class);
    }

    /**
     * Get the configuration prefix based on the export target
     *
     * @return string
     */
    protected function getConfigPrefix()
    {
        return Config::get("wikibase.export_target") === "factgrid"
            ? "factgrid"
            : "wikidata";
    }

    /**
     * Get the appropriate property ID for grouping based on the export target
     *
     * @param string $wikidataProperty The Wikidata property ID
     * @param string $factgridProperty The FactGrid property ID
     * @return string
     */
    protected function getPropertyId(
        string $wikidataProperty,
        string $factgridProperty,
    ) {
        return Config::get("wikibase.export_target") === "factgrid"
            ? $factgridProperty
            : $wikidataProperty;
    }

    /**
     * Get persons data for website map view, merge required data by person, group by person role, and
     * cache data in file.
     *
     * @return Response
     */
    public function getPersons(): Response
    {
        $client = $this->getClient();
        $configPrefix = $this->getConfigPrefix();

        $personsResponse = $client->queryPersons();

        $validator = Validator::make($personsResponse, [
            "head" => "required|array:vars|size:1",
            "head.vars" => [
                "required",
                "array",
                "size:12",
                Rule::in([
                    "item",
                    "itemLabel",
                    "itemDescription",
                    "property",
                    "statement",
                    "propertyValue",
                    "propertyValueLabel",
                    "propertyTimePrecision",
                    "qualifier",
                    "qualifierValue",
                    "qualifierValueLabel",
                    "qualifierTimePrecision",
                ]),
            ],
            "results" => "required|array:bindings|size:1",
            "results.bindings" => "required|array",
        ]);

        if ($validator->fails()) {
            Log::warning(
                "Validation of {$configPrefix} persons response failed.",
                $validator->errors()->all(),
            );

            return response([], Response::HTTP_NO_CONTENT);
        }

        $persons = $client->mergeItemsData(
            $personsResponse["results"]["bindings"],
        );

        // Get the correct groups and property ID based on the data source
        $personGroups = $configPrefix === 'factgrid'
            ? FactgridClient::PERSON_GROUPS_IDS
            : WikidataClient::PERSON_GROUPS_IDS;

        $propertyId = $this->getPropertyId("P2868", "P277");

        $groupedPersons = $client->groupItemsByProperty(
            $persons,
            $personGroups,
            $propertyId,
        );

        // Ensure the directory exists
        $filePath = config("{$configPrefix}.cachedPersonsFile");
        $directory = dirname($filePath);

        if ($directory !== "." && !Storage::disk("local")->exists($directory)) {
            Storage::disk("local")->makeDirectory($directory);
        }

        Storage::disk("local")->put($filePath, json_encode($groupedPersons));

        return response($groupedPersons, Response::HTTP_OK);
    }

    /**
     * Get cached persons data from file or create cache if it doesn't exist.
     *
     * @return Response
     * @throws FileNotFoundException
     */
    public function getCachedPersons(): Response
    {
        $client = $this->getClient();
        $configPrefix = $this->getConfigPrefix();

        // Get the correct groups based on the data source
        $personGroups = $configPrefix === 'factgrid'
            ? FactgridClient::PERSON_GROUPS_IDS
            : WikidataClient::PERSON_GROUPS_IDS;

        $propertyId = $this->getPropertyId("P2868", "P277");

        $groupedPersons = array_fill_keys(array_keys($personGroups), []);

        if (
            Storage::disk("local")->exists(
                config("{$configPrefix}.cachedPersonsFile"),
            )
        ) {
            $groupedPersons =
                json_decode(
                    Storage::disk("local")->get(
                        config("{$configPrefix}.cachedPersonsFile"),
                    ),
                    true,
                ) ?? $groupedPersons;
        } else {
            // Cache doesn't exist, create it by querying data
            $personsResponse = $client->queryPersons();

            $validator = Validator::make($personsResponse, [
                "head" => "required|array:vars|size:1",
                "head.vars" => [
                    "required",
                    "array",
                    "size:12",
                    Rule::in([
                        "item",
                        "itemLabel",
                        "itemDescription",
                        "property",
                        "statement",
                        "propertyValue",
                        "propertyValueLabel",
                        "propertyTimePrecision",
                        "qualifier",
                        "qualifierValue",
                        "qualifierValueLabel",
                        "qualifierTimePrecision",
                    ]),
                ],
                "results" => "required|array:bindings|size:1",
                "results.bindings" => "required|array",
            ]);

            if (!$validator->fails()) {
                $persons = $client->mergeItemsData(
                    $personsResponse["results"]["bindings"],
                );
                $groupedPersons = $client->groupItemsByProperty(
                    $persons,
                    $personGroups,
                    $propertyId,
                );

                // Ensure the directory exists
                $filePath = config("{$configPrefix}.cachedPersonsFile");
                $directory = dirname($filePath);

                if (
                    $directory !== "." &&
                    !Storage::disk("local")->exists($directory)
                ) {
                    Storage::disk("local")->makeDirectory($directory);
                }

                Storage::disk("local")->put(
                    $filePath,
                    json_encode($groupedPersons),
                );
            } else {
                Log::warning(
                    "Validation of {$configPrefix} persons response failed during cache generation.",
                    $validator->errors()->all(),
                );
            }
        }

        return response($groupedPersons, Response::HTTP_OK);
    }

    /**
     * Get location data for website map view, merge required data by location and group by location type.
     *
     * @return Response
     */
    public function getPlaces(): Response
    {
        $client = $this->getClient();
        $configPrefix = $this->getConfigPrefix();

        $placesResponse = $client->queryPlaces();
        $validator = Validator::make($placesResponse, [
            "head" => "required|array:vars|size:1",
            "head.vars" => [
                "required",
                "array",
                "size:12",
                Rule::in([
                    "item",
                    "itemLabel",
                    "itemDescription",
                    "property",
                    "statement",
                    "propertyValue",
                    "propertyValueLabel",
                    "propertyTimePrecision",
                    "qualifier",
                    "qualifierValue",
                    "qualifierValueLabel",
                    "qualifierTimePrecision",
                ]),
            ],
            "results" => "required|array:bindings|size:1",
            "results.bindings" => "required|array",
        ]);

        if ($validator->fails()) {
            Log::warning(
                "Validation of {$configPrefix} places response failed.",
                $validator->errors()->all(),
            );

            return response([], Response::HTTP_NO_CONTENT);
        }

        $locations = $client->mergeItemsData(
            $placesResponse["results"]["bindings"],
        );

        // Get the correct groups and property IDs based on the data source
        $placeGroups = $configPrefix === 'factgrid'
            ? FactgridClient::PLACE_GROUPS_IDS
            : WikidataClient::PLACE_GROUPS_IDS;

        $instancePropertyId = $this->getPropertyId("P31", "P2");
        $partOfPropertyId = $this->getPropertyId("P361", "P8");

        // Get the appropriate subgroup mappings based on data source
        $placeSubgroupMappings = $configPrefix === 'factgrid'
            ? FactgridClient::PLACE_SUBGROUP_MAPPINGS
            : WikidataClient::PLACE_SUBGROUP_MAPPINGS;

        $groupedLocations = $client->groupItemsByProperty(
            $locations,
            $placeGroups,
            $instancePropertyId,
        );
        $groupedLocations = $client->subgroupItems(
            $groupedLocations,
            $placeSubgroupMappings,
            $partOfPropertyId,
        );

        // Ensure the directory exists
        $filePath = config("{$configPrefix}.cachedPlacesFile");
        $directory = dirname($filePath);

        if ($directory !== "." && !Storage::disk("local")->exists($directory)) {
            Storage::disk("local")->makeDirectory($directory);
        }

        Storage::disk("local")->put($filePath, json_encode($groupedLocations));

        return response($groupedLocations, Response::HTTP_OK);
    }

    /**
     * Get cached places data from file or create cache if it doesn't exist.
     *
     * @return Response
     * @throws FileNotFoundException
     */
    public function getCachedPlaces(): Response
    {
        $client = $this->getClient();
        $configPrefix = $this->getConfigPrefix();

        // Get the correct groups based on the data source
        $placeGroups = $configPrefix === 'factgrid'
            ? FactgridClient::PLACE_GROUPS_IDS
            : WikidataClient::PLACE_GROUPS_IDS;

        $groupedLocations = array_fill_keys(array_keys($placeGroups), []);

        if (
            Storage::disk("local")->exists(
                config("{$configPrefix}.cachedPlacesFile"),
            )
        ) {
            $groupedLocations =
                json_decode(
                    Storage::disk("local")->get(
                        config("{$configPrefix}.cachedPlacesFile"),
                    ),
                    true,
                ) ?? $groupedLocations;
        } else {
            // Cache doesn't exist, create it by querying data
            $placesResponse = $client->queryPlaces();

            $validator = Validator::make($placesResponse, [
                "head" => "required|array:vars|size:1",
                "head.vars" => [
                    "required",
                    "array",
                    "size:12",
                    Rule::in([
                        "item",
                        "itemLabel",
                        "itemDescription",
                        "property",
                        "statement",
                        "propertyValue",
                        "propertyValueLabel",
                        "propertyTimePrecision",
                        "qualifier",
                        "qualifierValue",
                        "qualifierValueLabel",
                        "qualifierTimePrecision",
                    ]),
                ],
                "results" => "required|array:bindings|size:1",
                "results.bindings" => "required|array",
            ]);

            if (!$validator->fails()) {
                $locations = $client->mergeItemsData(
                    $placesResponse["results"]["bindings"],
                );

                // Get the appropriate property IDs based on data source
                $instancePropertyId = $this->getPropertyId("P31", "P2");
                $partOfPropertyId = $this->getPropertyId("P361", "P8");

                // Get the appropriate subgroup mappings based on data source
                $placeSubgroupMappings = $configPrefix === 'factgrid'
                    ? FactgridClient::PLACE_SUBGROUP_MAPPINGS
                    : WikidataClient::PLACE_SUBGROUP_MAPPINGS;

                $groupedLocations = $client->groupItemsByProperty(
                    $locations,
                    $placeGroups,
                    $instancePropertyId,
                );
                $groupedLocations = $client->subgroupItems(
                    $groupedLocations,
                    $placeSubgroupMappings,
                    $partOfPropertyId,
                );

                // Ensure the directory exists
                $filePath = config("{$configPrefix}.cachedPlacesFile");
                $directory = dirname($filePath);

                if (
                    $directory !== "." &&
                    !Storage::disk("local")->exists($directory)
                ) {
                    Storage::disk("local")->makeDirectory($directory);
                }

                Storage::disk("local")->put(
                    $filePath,
                    json_encode($groupedLocations),
                );
            } else {
                Log::warning(
                    "Validation of {$configPrefix} places response failed during cache generation.",
                    $validator->errors()->all(),
                );
            }
        }

        return response($groupedLocations, Response::HTTP_OK);
    }
}

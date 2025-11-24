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
        $personGroups =
            $configPrefix === "factgrid"
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
        $personGroups =
            $configPrefix === "factgrid"
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

        Log::debug("WikibaseController::getPlaces - Starting method");
        Log::debug("Using client type: " . get_class($client));
        Log::debug("Config prefix: " . $configPrefix);

        $placesResponse = $client->queryPlaces();
        Log::debug("Received response from queryPlaces", [
            "hasHead" => isset($placesResponse["head"]),
            "hasResults" => isset($placesResponse["results"]),
            "bindingsCount" => isset($placesResponse["results"]["bindings"])
                ? count($placesResponse["results"]["bindings"])
                : 0,
        ]);

        // Validator for the SPARQL query response structure
        $validator = Validator::make($placesResponse, [
            "head" => "required|array",
            "head.vars" => "required|array",
            "results" => "required|array",
            "results.bindings" => "required|array",
        ]);

        if ($validator->fails()) {
            Log::warning(
                "Validation of {$configPrefix} places response failed.",
                $validator->errors()->all(),
            );

            Log::debug("Full response for debugging:", [
                "head" => isset($placesResponse["head"])
                    ? json_encode($placesResponse["head"])
                    : "missing",
                "results" => isset($placesResponse["results"])
                    ? json_encode($placesResponse["results"])
                    : "missing",
                "vars" => isset($placesResponse["head"]["vars"])
                    ? json_encode($placesResponse["head"]["vars"])
                    : "missing",
                "first_binding" => isset(
                    $placesResponse["results"]["bindings"][0],
                )
                    ? json_encode($placesResponse["results"]["bindings"][0])
                    : "missing",
                "status" => isset($placesResponse["_status_code"])
                    ? $placesResponse["_status_code"]
                    : "unknown",
            ]);

            // Log more details about the response for debugging
            Log::error("SPARQL validation failed. Response details:", [
                "responseType" => gettype($placesResponse),
                "isArray" => is_array($placesResponse),
                "keys" => is_array($placesResponse)
                    ? array_keys($placesResponse)
                    : "N/A",
                "error" => $validator->errors()->all(),
                "raw" => substr(json_encode($placesResponse), 0, 1000),
            ]);

            // Return empty response if validation fails
            return response([], Response::HTTP_NO_CONTENT);
        }

        Log::debug(
            "Calling mergeItemsData with " .
                (isset($placesResponse["results"]["bindings"])
                    ? count($placesResponse["results"]["bindings"])
                    : 0) .
                " bindings",
        );

        try {
            $locations = $client->mergeItemsData(
                $placesResponse["results"]["bindings"],
            );
        } catch (\Exception $e) {
            Log::error("Error in mergeItemsData", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return response([], Response::HTTP_NO_CONTENT);
        }

        Log::debug("mergeItemsData returned " . count($locations) . " items");
        if (count($locations) > 0) {
            Log::debug(
                "Sample item keys: " .
                    implode(", ", array_keys(reset($locations))),
            );
            Log::debug(
                "First item: " . json_encode(array_values($locations)[0]),
            );
        }

        // Get the correct groups and property IDs based on the data source
        $placeGroups =
            $configPrefix === "factgrid"
                ? FactgridClient::PLACE_GROUPS_IDS
                : WikidataClient::PLACE_GROUPS_IDS;

        $instancePropertyId = $this->getPropertyId("P31", "wdt:P2");
        $partOfPropertyId = $this->getPropertyId("P361", "wdt:P8");

        // Get the appropriate subgroup mappings based on data source
        $placeSubgroupMappings =
            $configPrefix === "factgrid"
                ? FactgridClient::PLACE_SUBGROUP_MAPPINGS
                : WikidataClient::PLACE_SUBGROUP_MAPPINGS;

        Log::debug("Calling groupItemsByProperty with parameters", [
            "locationsCount" => count($locations),
            "placeGroupsKeys" => array_keys($placeGroups),
            "instancePropertyId" => $instancePropertyId,
        ]);

        try {
            $groupedLocations = $client->groupItemsByProperty(
                $locations,
                $placeGroups,
                $instancePropertyId,
            );
        } catch (\Exception $e) {
            Log::error("Error in groupItemsByProperty", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            // If grouping fails, return empty arrays for all groups
            $groupedLocations = array_fill_keys(array_keys($placeGroups), []);
        }

        Log::debug(
            "groupItemsByProperty returned groups with counts: " .
                json_encode(array_map("count", $groupedLocations)),
        );

        try {
            $groupedLocations = $client->subgroupItems(
                $groupedLocations,
                $placeSubgroupMappings,
                $partOfPropertyId,
            );
        } catch (\Exception $e) {
            Log::error("Error in subgroupItems", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            // If subgrouping fails, continue with the grouped locations as is
        }

        Log::debug(
            "subgroupItems returned groups with counts: " .
                json_encode(array_map("count", $groupedLocations)),
        );

        // Ensure the directory exists
        $filePath = config("{$configPrefix}.cachedPlacesFile");
        $directory = dirname($filePath);

        if ($directory !== "." && !Storage::disk("local")->exists($directory)) {
            Storage::disk("local")->makeDirectory($directory);
        }

        Storage::disk("local")->put($filePath, json_encode($groupedLocations));

        Log::debug(
            "WikibaseController::getPlaces - Completed successfully with " .
                array_sum(array_map("count", $groupedLocations)) .
                " total places",
        );

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
        $placeGroups =
            $configPrefix === "factgrid"
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
                $placeSubgroupMappings =
                    $configPrefix === "factgrid"
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

    /**
     * Test the FactGrid SPARQL connection with various endpoint formats.
     *
     * @return Response
     */
    public function testFactgridConnection(): Response
    {
        $testResults = [];
        // Super simple query that should definitely work
        $query =
            'SELECT ?item ?itemLabel WHERE { ?item wdt:P2 wd:Q1784677 . SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en" } } LIMIT 5';
        $encodedQuery = urlencode($query);

        // Test with direct SPARQLR approach (like Wikidata's query.wikidata.org)
        try {
            // This is the approach that often works with Wikidata's query service
            $url =
                "https://database.factgrid.de/sparqlr?query=" . $encodedQuery;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "User-Agent: Mozilla/5.0 (compatible; OGT-API/1.0)",
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $testResults["sparqlr"] = [
                "method" => "GET SPARQLR",
                "url" => $url,
                "status" => $httpCode,
                "content_type" => $contentType,
                "curl_error" => $error,
                "is_json" => $this->isJsonString($responseBody),
                "body_preview" => substr($responseBody, 0, 200),
                "has_results" => false,
            ];

            if ($this->isJsonString($responseBody)) {
                $data = json_decode($responseBody, true);
                $testResults["sparqlr"]["has_head"] = isset($data["head"]);
                $testResults["sparqlr"]["has_results"] = isset(
                    $data["results"],
                );
                $testResults["sparqlr"]["has_bindings"] = isset(
                    $data["results"]["bindings"],
                );
                $testResults["sparqlr"]["binding_count"] = isset(
                    $data["results"]["bindings"],
                )
                    ? count($data["results"]["bindings"])
                    : 0;

                if (
                    isset($data["results"]["bindings"]) &&
                    count($data["results"]["bindings"]) > 0
                ) {
                    $testResults["sparqlr"]["first_result"] =
                        $data["results"]["bindings"][0];
                }
            }
        } catch (\Exception $e) {
            $testResults["sparqlr"] = [
                "method" => "GET SPARQLR",
                "url" => $url ?? "https://database.factgrid.de/sparqlr",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        // Test with XMLHttpRequest approach
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://database.factgrid.de/query");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query([
                    "query" => $query,
                ]),
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json,*/*;q=0.9",
                "Accept-Language: en-US,en;q=0.9",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "Origin: https://database.factgrid.de",
                "Pragma: no-cache",
                "Referer: https://database.factgrid.de/query/",
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
                "X-Requested-With: XMLHttpRequest",
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $testResults["xhr"] = [
                "method" => "POST with XMLHttpRequest",
                "url" => "https://database.factgrid.de/query",
                "status" => $httpCode,
                "content_type" => $contentType,
                "curl_error" => $error,
                "is_json" => $this->isJsonString($responseBody),
                "body_preview" => substr($responseBody, 0, 200),
                "has_results" => false,
            ];

            if ($this->isJsonString($responseBody)) {
                $data = json_decode($responseBody, true);
                $testResults["xhr"]["has_head"] = isset($data["head"]);
                $testResults["xhr"]["has_results"] = isset($data["results"]);
                $testResults["xhr"]["has_bindings"] = isset(
                    $data["results"]["bindings"],
                );
                $testResults["xhr"]["binding_count"] = isset(
                    $data["results"]["bindings"],
                )
                    ? count($data["results"]["bindings"])
                    : 0;

                if (
                    isset($data["results"]["bindings"]) &&
                    count($data["results"]["bindings"]) > 0
                ) {
                    $testResults["xhr"]["first_result"] =
                        $data["results"]["bindings"][0];
                }
            }
        } catch (\Exception $e) {
            $testResults["xhr"] = [
                "method" => "POST with XMLHttpRequest",
                "url" => "https://database.factgrid.de/query",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        // Test with Blazegraph direct endpoint
        try {
            $url = "https://database.factgrid.de/bigdata/namespace/wdq/sparql";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query([
                    "query" => $query,
                    "format" => "json",
                ]),
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "Content-Type: application/x-www-form-urlencoded",
                "User-Agent: OGT-API/1.0",
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $testResults["blazegraph"] = [
                "method" => "POST to Blazegraph",
                "url" => $url,
                "status" => $httpCode,
                "content_type" => $contentType,
                "curl_error" => $error,
                "is_json" => $this->isJsonString($responseBody),
                "body_preview" => substr($responseBody, 0, 200),
                "has_results" => false,
            ];

            if ($this->isJsonString($responseBody)) {
                $data = json_decode($responseBody, true);
                $testResults["blazegraph"]["has_head"] = isset($data["head"]);
                $testResults["blazegraph"]["has_results"] = isset(
                    $data["results"],
                );
                $testResults["blazegraph"]["has_bindings"] = isset(
                    $data["results"]["bindings"],
                );
                $testResults["blazegraph"]["binding_count"] = isset(
                    $data["results"]["bindings"],
                )
                    ? count($data["results"]["bindings"])
                    : 0;

                if (
                    isset($data["results"]["bindings"]) &&
                    count($data["results"]["bindings"]) > 0
                ) {
                    $testResults["blazegraph"]["first_result"] =
                        $data["results"]["bindings"][0];
                }
            }
        } catch (\Exception $e) {
            $testResults["blazegraph"] = [
                "method" => "POST to Blazegraph",
                "url" =>
                    $url ??
                    "https://database.factgrid.de/bigdata/namespace/wdq/sparql",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        // Try the wikibase proxy endpoint
        try {
            $url = "https://database.factgrid.de/proxy/wikibase/sparql";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query([
                    "query" => $query,
                    "format" => "json",
                ]),
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "Content-Type: application/x-www-form-urlencoded",
                "User-Agent: OGT-API/1.0",
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $testResults["wikibase_proxy"] = [
                "method" => "POST to Wikibase proxy",
                "url" => $url,
                "status" => $httpCode,
                "content_type" => $contentType,
                "curl_error" => $error,
                "is_json" => $this->isJsonString($responseBody),
                "body_preview" => substr($responseBody, 0, 200),
                "has_results" => false,
            ];

            if ($this->isJsonString($responseBody)) {
                $data = json_decode($responseBody, true);
                $testResults["wikibase_proxy"]["has_head"] = isset(
                    $data["head"],
                );
                $testResults["wikibase_proxy"]["has_results"] = isset(
                    $data["results"],
                );
                $testResults["wikibase_proxy"]["has_bindings"] = isset(
                    $data["results"]["bindings"],
                );
                $testResults["wikibase_proxy"]["binding_count"] = isset(
                    $data["results"]["bindings"],
                )
                    ? count($data["results"]["bindings"])
                    : 0;

                if (
                    isset($data["results"]["bindings"]) &&
                    count($data["results"]["bindings"]) > 0
                ) {
                    $testResults["wikibase_proxy"]["first_result"] =
                        $data["results"]["bindings"][0];
                }
            }
        } catch (\Exception $e) {
            $testResults["wikibase_proxy"] = [
                "method" => "POST to Wikibase proxy",
                "url" =>
                    $url ??
                    "https://database.factgrid.de/proxy/wikibase/sparql",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        // Standard POST request
        try {
            $ch = curl_init("https://database.factgrid.de/query");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Maximum redirects to follow
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query([
                    "query" => $query,
                    "format" => "json",
                ]),
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "User-Agent: OGT-API/1.0",
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            $testResults["post"] = [
                "method" => "POST",
                "status" => $httpCode,
                "content_type" => $contentType,
                "curl_error" => $error,
                "is_json" => $this->isJsonString($responseBody),
                "body_preview" => substr($responseBody, 0, 200),
                "has_results" => false,
            ];

            if ($this->isJsonString($responseBody)) {
                $data = json_decode($responseBody, true);
                $testResults["post"]["has_head"] = isset($data["head"]);
                $testResults["post"]["has_results"] = isset($data["results"]);
                $testResults["post"]["has_bindings"] = isset(
                    $data["results"]["bindings"],
                );
                $testResults["post"]["binding_count"] = isset(
                    $data["results"]["bindings"],
                )
                    ? count($data["results"]["bindings"])
                    : 0;

                if (
                    isset($data["results"]["bindings"]) &&
                    count($data["results"]["bindings"]) > 0
                ) {
                    $testResults["post"]["first_result"] =
                        $data["results"]["bindings"][0];
                }
            }
        } catch (\Exception $e) {
            $testResults["post"] = [
                "method" => "POST",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        // Test direct GET request
        try {
            $url = "https://database.factgrid.de/query?query={$encodedQuery}&format=json";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Maximum redirects to follow
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/sparql-results+json",
                "User-Agent: OGT-API/1.0",
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            $testResults["get"] = [
                "method" => "GET",
                "url" => $url,
                "status" => $httpCode,
                "content_type" => $contentType,
                "is_json" => $this->isJsonString($responseBody),
                "body_preview" => substr($responseBody, 0, 200),
            ];

            if ($this->isJsonString($responseBody)) {
                $data = json_decode($responseBody, true);
                $testResults["get"]["has_head"] = isset($data["head"]);
                $testResults["get"]["has_results"] = isset($data["results"]);
                $testResults["get"]["has_bindings"] = isset(
                    $data["results"]["bindings"],
                );
                $testResults["get"]["binding_count"] = isset(
                    $data["results"]["bindings"],
                )
                    ? count($data["results"]["bindings"])
                    : 0;

                if (
                    isset($data["results"]["bindings"]) &&
                    count($data["results"]["bindings"]) > 0
                ) {
                    $testResults["get"]["first_result"] =
                        $data["results"]["bindings"][0];
                }
            }
        } catch (\Exception $e) {
            $testResults["get"] = [
                "method" => "GET",
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        // Configuration info
        $testResults["config"] = [
            "export_target" => Config::get("wikibase.export_target"),
            "debug_mode" => Config::get("wikibase.debug_mode"),
            "factgrid_url" => config("factgrid.url"),
        ];

        // Try the most direct approach using exactly the parameters from the web interface
        $testResults["direct_api"] = $this->testFactgridDirectRequest();

        return response($testResults);
    }

    /**
     * Check if a string is valid JSON
     */
    private function isJsonString($string)
    {
        if (!is_string($string) || empty($string)) {
            return false;
        }

        // Try to decode and check for errors
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Simple helper to clean up SPARQL URLs for logging and display
     */
    private function sanitizeSparqlUrl($url)
    {
        // Extract just the base and parameters without the long query
        $parts = parse_url($url);
        $sanitized = $parts["scheme"] . "://" . $parts["host"];
        if (isset($parts["path"])) {
            $sanitized .= $parts["path"];
        }
        $sanitized .= "?..."; // Indicate parameters were present but not shown

        return $sanitized;
    }

    /**
     * Make a request that uses exactly the same parameters as the FactGrid query interface
     */
    private function testFactgridDirectRequest(): array
    {
        $result = [
            "method" => "Direct API Call",
            "has_results" => false,
        ];

        try {
            // This extremely simple query should definitely work
            $query =
                "SELECT ?item ?itemLabel WHERE { ?item wdt:P2 wd:Q1784677 . SERVICE wikibase:label { bd:serviceParam wikibase:language \"de,en\" } } LIMIT 5";

            // Call the query API directly with format=json
            $url =
                "https://database.factgrid.de/sparql?format=json&query=" .
                urlencode($query);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/json",
                "User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            $result["status"] = $httpCode;
            $result["content_type"] = $contentType;
            $result["is_json"] = $this->isJsonString($responseBody);
            $result["body_preview"] = substr($responseBody, 0, 200);

            if ($this->isJsonString($responseBody)) {
                $data = json_decode($responseBody, true);
                $result["has_head"] = isset($data["head"]);
                $result["has_results"] = isset($data["results"]);
                $result["has_bindings"] = isset($data["results"]["bindings"]);

                if (isset($data["results"]["bindings"])) {
                    $result["binding_count"] = count(
                        $data["results"]["bindings"],
                    );

                    if (count($data["results"]["bindings"]) > 0) {
                        $result["first_result"] =
                            $data["results"]["bindings"][0];
                    }
                }
            }
        } catch (\Exception $e) {
            $result["error"] = $e->getMessage();
            $result["trace"] = $e->getTraceAsString();
        }

        return $result;
    }

    /**
     * Analyze a response body to determine if it's HTML or JSON
     */
    private function analyzeResponseBody($body)
    {
        // Check if it's JSON
        json_decode($body);
        $isJson = json_last_error() === JSON_ERROR_NONE;

        // Check for HTML markers
        $hasHtmlTags =
            strpos($body, "<!DOCTYPE html>") !== false ||
            strpos($body, "<html") !== false ||
            strpos($body, "<head") !== false ||
            strpos($body, "<body") !== false;

        return [
            "is_json" => $isJson,
            "is_html" => $hasHtmlTags,
            "length" => strlen($body),
            "preview" => substr($body, 0, 100),
        ];
    }
}

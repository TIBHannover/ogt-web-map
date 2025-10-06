<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;
use App\Services\Wikibase\WikibaseService;

class WikidataSearchController extends Controller
{
    /**
     * Create a properly configured HTTP client with user agent
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function getHttpClient()
    {
        return Http::accept("application/json")
            ->timeout(10)
            ->withHeaders([
                "User-Agent" =>
                    "GTO-Wiki-App/1.0 (https://www.gestapo-terror-orte.de/; info@gestapo-terror-orte.de)",
            ]);
    }

    /**
     * Create a properly configured SPARQL client with user agent
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function getSparqlClient()
    {
        return Http::accept("application/sparql-results+json")
            ->timeout(15) // SPARQL queries can take longer
            ->withHeaders([
                "User-Agent" =>
                    "GTO-Wiki-App/1.0 (https://www.gestapo-terror-orte.de/; info@gestapo-terror-orte.de)",
            ]);
    }

    /**
     * Search for occupations (professions) in Wikidata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchOccupations(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
        ]);

        $query = $request->input("query");
        $limit = $request->input("limit", 10);

        try {
            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $query)) {
                $entity = $this->getEntityDetails($query);

                if ($entity) {
                    $resultObj = ["id" => $query];

                    $label = $this->getPrioritizedLabel($entity, $resultObj);
                    $description = $this->getPrioritizedDescription(
                        $entity,
                        $resultObj,
                    );

                    return response()->json([
                        "success" => true,
                        "results" => [
                            [
                                "id" => $query,
                                "label" => $label,
                                "description" => $description,
                            ],
                        ],
                    ]);
                }
            }

            // SPARQL query to search for occupations (P31=Q28640 instance of occupation)
            $sparqlQuery =
                'SELECT DISTINCT ?item ?itemLabel ?itemDescription
                WHERE {
                  ?item wdt:P31 wd:Q28640.
                  SERVICE wikibase:label {
                    bd:serviceParam wikibase:language "de,en".
                    ?item rdfs:label ?itemLabel.
                    ?item schema:description ?itemDescription.
                  }
                  FILTER(CONTAINS(LCASE(?itemLabel), LCASE("' .
                $query .
                '")))
                  FILTER(LANG(?itemLabel) = "de" || LANG(?itemLabel) = "en")
                }
                LIMIT ' .
                $limit;

            $response = $this->getSparqlClient()->get(
                "https://query.wikidata.org/sparql",
                [
                    "query" => $sparqlQuery,
                ],
            );

            if (!$response->successful()) {
                Log::error("SPARQL query failed", [
                    "status" => $response->status(),
                    "body" => $response->body(),
                ]);
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Fehler bei der Suche",
                    ],
                    500,
                );
            }

            $data = $response->json();
            $results = [];

            if (
                isset($data["results"]["bindings"]) &&
                count($data["results"]["bindings"]) > 0
            ) {
                foreach ($data["results"]["bindings"] as $binding) {
                    $id = basename($binding["item"]["value"]);
                    $label = $binding["itemLabel"]["value"] ?? $id;
                    $description = $binding["itemDescription"]["value"] ?? "";

                    $results[] = [
                        "id" => $id,
                        "label" => $label,
                        "description" => $description,
                    ];
                }
            }

            return response()->json([
                "success" => true,
                "results" => $results,
            ]);
        } catch (\Exception $e) {
            Log::error("Error searching for occupations", [
                "query" => $query,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Ein Fehler ist aufgetreten: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Search for nationalities in Wikidata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchNationalities(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
        ]);

        $query = $request->input("query");
        $limit = $request->input("limit", 10);

        try {
            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $query)) {
                $entity = $this->getEntityDetails($query);

                if ($entity) {
                    $resultObj = ["id" => $query];

                    $label = $this->getPrioritizedLabel($entity, $resultObj);
                    $description = $this->getPrioritizedDescription(
                        $entity,
                        $resultObj,
                    );

                    return response()->json([
                        "success" => true,
                        "results" => [
                            [
                                "id" => $query,
                                "label" => $label,
                                "description" => $description,
                            ],
                        ],
                    ]);
                }
            }

            // For an empty query or very short query, return some predefined nationalities including Deutsches Reich
            if (strlen($query) < 3) {
                $predefinedNationalities = [
                    [
                        "id" => "Q1206012",
                        "label" => "Deutsches Reich",
                        "description" => "deutscher Nationalstaat (1871-1945)",
                    ],
                    [
                        "id" => "Q183",
                        "label" => "Deutschland",
                        "description" =>
                            "demokratischer und föderativer Staat in Mitteleuropa",
                    ],
                    [
                        "id" => "Q40",
                        "label" => "Österreich",
                        "description" => "Staat in Mitteleuropa",
                    ],
                    [
                        "id" => "Q39",
                        "label" => "Schweiz",
                        "description" => "Staat in Mitteleuropa",
                    ],
                ];

                // If we have a query, filter the predefined results
                if (!empty($query)) {
                    $predefinedNationalities = array_filter(
                        $predefinedNationalities,
                        function ($nationality) use ($query) {
                            return stripos($nationality["label"], $query) !==
                                false;
                        },
                    );
                }

                return response()->json([
                    "success" => true,
                    "results" => array_values($predefinedNationalities),
                ]);
            }

            // SPARQL query to search for countries and historical countries
            $sparqlQuery =
                'SELECT DISTINCT ?item ?itemLabel ?itemDescription
                WHERE {
                  {
                    ?item wdt:P31 wd:Q6256.   # instance of country
                  } UNION {
                    ?item wdt:P31 wd:Q3024240. # instance of historical country
                  } UNION {
                    ?item wdt:P31 wd:Q1206012. # specifically include Deutsches Reich
                  }
                  SERVICE wikibase:label {
                    bd:serviceParam wikibase:language "de,en".
                    ?item rdfs:label ?itemLabel.
                    ?item schema:description ?itemDescription.
                  }
                  FILTER(CONTAINS(LCASE(?itemLabel), LCASE("' .
                $query .
                '")))
                  FILTER(LANG(?itemLabel) = "de" || LANG(?itemLabel) = "en")
                }
                LIMIT ' .
                $limit;

            $response = $this->getSparqlClient()->get(
                "https://query.wikidata.org/sparql",
                [
                    "query" => $sparqlQuery,
                ],
            );

            if (!$response->successful()) {
                Log::error("SPARQL query failed", [
                    "status" => $response->status(),
                    "body" => $response->body(),
                ]);
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Fehler bei der Suche",
                    ],
                    500,
                );
            }

            $data = $response->json();
            $results = [];

            if (
                isset($data["results"]["bindings"]) &&
                count($data["results"]["bindings"]) > 0
            ) {
                foreach ($data["results"]["bindings"] as $binding) {
                    $id = basename($binding["item"]["value"]);
                    $label = $binding["itemLabel"]["value"] ?? $id;
                    $description = $binding["itemDescription"]["value"] ?? "";

                    $results[] = [
                        "id" => $id,
                        "label" => $label,
                        "description" => $description,
                    ];
                }
            }

            // Always ensure Deutsches Reich is in results if query matches
            $includeReich = false;
            if (stripos("deutsches reich", $query) !== false) {
                $includeReich = true;

                // Check if it's already in results
                $reichExists = false;
                foreach ($results as $result) {
                    if ($result["id"] === "Q1206012") {
                        $reichExists = true;
                        break;
                    }
                }

                // Add if not already there
                if (!$reichExists) {
                    array_unshift($results, [
                        "id" => "Q1206012",
                        "label" => "Deutsches Reich",
                        "description" => "deutscher Nationalstaat (1871-1945)",
                    ]);
                }
            }

            return response()->json([
                "success" => true,
                "results" => $results,
            ]);
        } catch (\Exception $e) {
            Log::error("Error searching for nationalities", [
                "query" => $query,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Ein Fehler ist aufgetreten: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Search for people, organizations, and Gestapo terror sites in Wikidata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchPeople(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
        ]);

        $query = $request->input("query");

        try {
            // Pru00fcfe, ob es sich um eine direkte Q-Nummer handelt (z.B. "Q1234567")
            if (preg_match('/^Q\d+$/', $query)) {
                // Direkte Anfrage des Entities mit der Q-ID
                $response = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Deutsch und Englisch anfordern
                        "format" => "json",
                    ],
                );

                if (
                    $response->successful() &&
                    !empty($response->json()["entities"][$query])
                ) {
                    $entity = $response->json()["entities"][$query];

                    // Pru00fcfe, ob es sich um eine Person, Organisation oder Ort des Gestapoterrors handelt (P31 = instance of)
                    if (isset($entity["claims"]["P31"])) {
                        foreach ($entity["claims"]["P31"] as $claim) {
                            $entityType =
                                $claim["mainsnak"]["datavalue"]["value"]["id"];
                            if (
                                $entityType === "Q5" || // Human
                                $entityType === "Q43229" || // Organization
                                $entityType === "Q106996250" // Orte des Gestapoterrors
                            ) {
                                // Geburtsdatum und Todesdatum ermitteln falls vorhanden
                                $birth = null;
                                $death = null;

                                if (isset($entity["claims"]["P569"])) {
                                    // P569 = Geburtsdatum
                                    $birth =
                                        $entity["claims"]["P569"][0][
                                            "mainsnak"
                                        ]["datavalue"]["value"]["time"] ?? null;
                                    $birth = $birth
                                        ? substr($birth, 1, 10)
                                        : null; // '+' und Pru00e4zision entfernen
                                }

                                if (isset($entity["claims"]["P570"])) {
                                    // P570 = Todesdatum
                                    $death =
                                        $entity["claims"]["P570"][0][
                                            "mainsnak"
                                        ]["datavalue"]["value"]["time"] ?? null;
                                    $death = $death
                                        ? substr($death, 1, 10)
                                        : null;
                                }

                                $resultObj = ["id" => $query];

                                $label = $this->getPrioritizedLabel(
                                    $entity,
                                    $resultObj,
                                );
                                $description = $this->getPrioritizedDescription(
                                    $entity,
                                    $resultObj,
                                );

                                return response()->json([
                                    [
                                        "id" => $query,
                                        "label" => $label,
                                        "description" => $description,
                                        "birth" => $birth,
                                        "death" => $death,
                                    ],
                                ]);
                            }
                        }
                    }
                }
            }

            // Standard-Suche, wenn es keine Q-ID ist oder die Q-ID nicht gefunden wurde
            $response = $this->getHttpClient()->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 10,
                ],
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500,
                );
            }

            $results = $response->json()["search"] ?? [];

            // Filter results to only include humans (Q5)
            $filteredResults = [];
            foreach ($results as $result) {
                // Get detailed information about the entity
                $entityResponse = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $result["id"],
                        "languages" => "de|en", // Deutsch und Englisch anfordern
                        "format" => "json",
                    ],
                );

                if ($entityResponse->successful()) {
                    $entity =
                        $entityResponse->json()["entities"][$result["id"]] ??
                        null;

                    // Check if entity is a person, organization, or Gestapo terror site (P31 = instance of)
                    if ($entity && isset($entity["claims"]["P31"])) {
                        foreach ($entity["claims"]["P31"] as $claim) {
                            $entityType =
                                $claim["mainsnak"]["datavalue"]["value"]["id"];
                            if (
                                $entityType === "Q5" || // Human
                                $entityType === "Q43229" || // Organization
                                $entityType === "Q106996250" // Orte des Gestapoterrors
                            ) {
                                // Get birth and death dates if available
                                $birth = null;
                                $death = null;

                                if (isset($entity["claims"]["P569"])) {
                                    // P569 = date of birth
                                    $birth =
                                        $entity["claims"]["P569"][0][
                                            "mainsnak"
                                        ]["datavalue"]["value"]["time"] ?? null;
                                    $birth = $birth
                                        ? substr($birth, 1, 10)
                                        : null; // Remove '+' and precision
                                }

                                if (isset($entity["claims"]["P570"])) {
                                    // P570 = date of death
                                    $death =
                                        $entity["claims"]["P570"][0][
                                            "mainsnak"
                                        ]["datavalue"]["value"]["time"] ?? null;
                                    $death = $death
                                        ? substr($death, 1, 10)
                                        : null; // Remove '+' and precision
                                }

                                // Prioritäten für Beschreibung anwenden
                                $description = $this->getPrioritizedDescription(
                                    $entity,
                                    $result,
                                );

                                $filteredResults[] = [
                                    "id" => $result["id"],
                                    "label" => $this->getPrioritizedLabel(
                                        $entity,
                                        $result,
                                    ),
                                    "description" => $description,
                                    "birth" => $birth,
                                    "death" => $death,
                                ];
                                break;
                            }
                        }
                    }
                }
            }

            return response()->json($filteredResults);
        } catch (\Exception $e) {
            Log::error("Wikidata search error: " . $e->getMessage());
            return response()->json(
                ["error" => "Failed to search Wikidata: " . $e->getMessage()],
                500,
            );
        }
    }

    /**
     * Prioritize description extraction: German > English > Search Result > Empty
     *
     * @param array $entity Full entity data from Wikidata
     * @param array $result Initial search result from Wikidata
     * @return string|null The prioritized description or null if not found
     */
    private function getPrioritizedDescription(
        array $entity,
        array $result,
    ): ?string {
        // Default to null if nothing is found
        $description = null;

        // First try German description from full entity lookup
        if (!empty($entity["descriptions"]["de"]["value"])) {
            $description = $entity["descriptions"]["de"]["value"];
        }
        // Fallback to English description from entity lookup
        elseif (!empty($entity["descriptions"]["en"]["value"])) {
            $description = $entity["descriptions"]["en"]["value"];
        }
        // Fallback to description from search result
        elseif (!empty($result["description"])) {
            $description = $result["description"];
        }

        return $description;
    }

    /**
     * Prioritize label extraction: German > English > Search Result > ID
     *
     * @param array $entity Full entity data from Wikidata
     * @param array $result Initial search result from Wikidata
     * @return string The prioritized label
     */
    private function getPrioritizedLabel(array $entity, array $result): string
    {
        // Default to ID if nothing else is found
        $label = $result["id"];

        // First try German label from full entity lookup
        if (!empty($entity["labels"]["de"]["value"])) {
            $label = $entity["labels"]["de"]["value"];
        }
        // Fallback to English label from entity lookup
        elseif (!empty($entity["labels"]["en"]["value"])) {
            $label = $entity["labels"]["en"]["value"];
        }
        // Fallback to label from search result
        elseif (!empty($result["label"])) {
            $label = $result["label"];
        }

        return $label;
    }
    /**
     * Get detailed information about a specific entity by its Q-ID
     *
     * @param string $entityId The Wikidata Q-ID
     * @param array $languages Languages to fetch (defaults to German and English)
     * @return array|null Entity data or null if not found
     */
    protected function getEntityDetails(
        string $entityId,
        array $languages = ["de", "en"],
    ): ?array {
        try {
            // Determine which API URL to use
            $apiUrl = config(
                "wikidata.api_url",
                "https://www.wikidata.org/w/api.php",
            );

            // Format languages for the API request
            $languagesParam = implode("|", $languages);

            // Get entity details from Wikidata API
            $response = $this->getHttpClient()->get($apiUrl, [
                "action" => "wbgetentities",
                "ids" => $entityId,
                "languages" => $languagesParam,
                "format" => "json",
            ]);

            if (!$response->successful()) {
                Log::warning("Failed to get entity details", [
                    "entity_id" => $entityId,
                    "status" => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();
            if (empty($data["entities"][$entityId])) {
                Log::warning("Entity not found", ["entity_id" => $entityId]);
                return null;
            }

            return $data["entities"][$entityId];
        } catch (\Exception $e) {
            Log::error("Error fetching entity details", [
                "entity_id" => $entityId,
                "error" => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function searchByTitle(Request $request): JsonResponse
    {
        $request->validate([
            "title" => "required|string|min:2",
        ]);

        $title = $request->input("title");
        $limit = $request->input("limit", 10);

        try {
            // Get the WikibaseService instance
            $wikibaseService = app(WikibaseService::class);

            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $title)) {
                Log::info("Direct Q-ID search", ["qid" => $title]);

                // Handle direct entity lookup using the service
                // For now, we'll still use Http facade as WikibaseService doesn't have a getEntity method yet
                // Get entity details using our helper method
                $entity = $this->getEntityDetails($title);

                if ($entity) {
                    $resultObj = ["id" => $title];

                    $label = $this->getPrioritizedLabel($entity, $resultObj);
                    $description = $this->getPrioritizedDescription(
                        $entity,
                        $resultObj,
                    );

                    return response()->json([
                        "success" => true,
                        "results" => [
                            [
                                "id" => $title,
                                "label" => $label,
                                "description" => $description,
                            ],
                        ],
                    ]);
                }
            }

            // Search for entities matching the title using WikibaseService
            Log::info("Searching for entities by title", [
                "title" => $title,
                "limit" => $limit,
            ]);

            // Try using WikibaseService first
            $useDirectApi = false;
            $searchResults = null;

            try {
                // Log the WikibaseService instance to help with debugging
                Log::info("WikibaseService instance details", [
                    "class" => get_class($wikibaseService),
                    "baseUrl_exists" => property_exists(
                        $wikibaseService,
                        "baseUrl",
                    ),
                    "methods" => get_class_methods($wikibaseService),
                ]);

                $searchResults = $wikibaseService->searchEntities(
                    $title,
                    "de", // German language code
                    "item", // Entity type
                );
            } catch (\Exception $serviceException) {
                // Log the service exception but continue with direct API fallback
                Log::warning(
                    "WikibaseService search failed, falling back to direct API",
                    [
                        "error" => $serviceException->getMessage(),
                        "title" => $title,
                    ],
                );
                $useDirectApi = true;
            }

            // If WikibaseService failed or returned null, fall back to direct API call
            if ($useDirectApi || !$searchResults) {
                Log::info("Using direct API fallback for search", [
                    "title" => $title,
                ]);

                // Use the API URL from config instead of hardcoding it
                $apiUrl = config(
                    "wikidata.api_url",
                    "https://www.wikidata.org/w/api.php",
                );

                Log::info("Using direct API call", [
                    "api_url" => $apiUrl,
                    "search" => $title,
                    "limit" => $limit,
                ]);

                $response = $this->getHttpClient()->get($apiUrl, [
                    "action" => "wbsearchentities",
                    "search" => $title,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => $limit,
                ]);

                if (!$response->successful()) {
                    return response()->json(
                        [
                            "success" => false,
                            "error" => "Wikidata API request failed",
                        ],
                        500,
                    );
                }

                $searchResults = $response->json();
            }

            $results = $searchResults["search"] ?? [];

            if (empty($results)) {
                return response()->json([
                    "success" => true,
                    "results" => [],
                ]);
            }

            // Limit results to requested number
            $results = array_slice($results, 0, $limit);

            $processedResults = [];
            foreach ($results as $result) {
                // Get detailed entity information using our helper method
                $entity = $this->getEntityDetails($result["id"]);

                if ($entity) {
                    $label = $this->getPrioritizedLabel($entity, $result);
                    $description = $this->getPrioritizedDescription(
                        $entity,
                        $result,
                    );

                    $processedResults[] = [
                        "id" => $result["id"],
                        "label" => $label,
                        "description" => $description,
                    ];
                } else {
                    // Fallback auf originales Ergebnis
                    $processedResults[] = $result;
                }
            }

            return response()->json([
                "success" => true,
                "results" => $processedResults,
            ]);
        } catch (\Exception $e) {
            // Include detailed error information and format it for debugging
            $errorDetails = [
                "message" => $e->getMessage(),
                "exception" => get_class($e),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTrace(),
                "title" => $title,
                "limit" => $limit,
            ];

            Log::error("Wikidata title search error", $errorDetails);

            // Check if this is a user-agent related error and provide a more helpful message
            $errorMessage = $e->getMessage();
            if (
                strpos($errorMessage, "user-agent") !== false ||
                strpos($errorMessage, "403 Forbidden") !== false
            ) {
                $errorMessage =
                    "Wikidata API requires a valid User-Agent. This has been fixed in the latest update.";
            }

            return response()->json(
                [
                    "error" => "Failed to search Wikidata: " . $errorMessage,
                    "details" => $errorDetails,
                ],
                500,
            );
        }
    }

    /**
     * Process search results to prioritize German labels and descriptions
     *
     * @param array $results Raw search results from Wikidata API
     * @return array Processed results with prioritized German labels and descriptions
     */
    private function processSearchResults(array $results): array
    {
        $processedResults = [];
        foreach ($results as $result) {
            $entityResponse = $this->getHttpClient()->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbgetentities",
                    "ids" => $result["id"],
                    "languages" => "de|en",
                    "format" => "json",
                ],
            );

            if ($entityResponse->successful()) {
                $entity =
                    $entityResponse->json()["entities"][$result["id"]] ?? null;

                if ($entity) {
                    $label = $this->getPrioritizedLabel($entity, $result);
                    $description = $this->getPrioritizedDescription(
                        $entity,
                        $result,
                    );

                    $processedResults[] = [
                        "id" => $result["id"],
                        "label" => $label,
                        "description" => $description,
                    ];
                } else {
                    // Fallback auf originales Ergebnis
                    $processedResults[] = $result;
                }
            } else {
                // Fallback auf originales Ergebnis
                $processedResults[] = $result;
            }
        }

        return $processedResults;
    }

    /**
     * Search for concepts (causes) in Wikidata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchCauses(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
        ]);

        $query = $request->input("query");

        try {
            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $query)) {
                // Direct query for the entity with that Q-ID
                $response = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Request German and English
                        "format" => "json",
                    ],
                );

                if (
                    $response->successful() &&
                    !empty($response->json()["entities"][$query])
                ) {
                    $entity = $response->json()["entities"][$query];

                    // Check if this entity exists in Wikidata
                    $sparqlQuery = "SELECT ?item WHERE {
                        VALUES ?item { wd:$query }
                    }";

                    $sparqlResponse = $this->getSparqlClient()->get(
                        "https://query.wikidata.org/sparql",
                        [
                            "query" => $sparqlQuery,
                            "format" => "json",
                        ],
                    );

                    if ($sparqlResponse->successful()) {
                        $results =
                            $sparqlResponse->json()["results"]["bindings"] ??
                            [];

                        if (!empty($results)) {
                            $resultObj = ["id" => $query];

                            $label = $this->getPrioritizedLabel(
                                $entity,
                                $resultObj,
                            );
                            $description = $this->getPrioritizedDescription(
                                $entity,
                                $resultObj,
                            );

                            return response()->json([
                                [
                                    "id" => $query,
                                    "label" => $label,
                                    "description" => $description,
                                ],
                            ]);
                        }
                    }
                }
            }

            // Standard search for concepts matching our query
            // First, get entities matching the search term
            $searchResponse = $this->getHttpClient()->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 20, // Get more results
                ],
            );

            if (!$searchResponse->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500,
                );
            }

            $searchResults = $searchResponse->json()["search"] ?? [];

            if (empty($searchResults)) {
                return response()->json([]);
            }

            // Extract all entity IDs for the SPARQL query
            $entityIds = array_map(function ($result) {
                return $result["id"];
            }, $searchResults);

            // Build VALUES clause for SPARQL query
            $valuesClause =
                "VALUES ?item { " .
                implode(
                    " ",
                    array_map(function ($id) {
                        return "wd:$id";
                    }, $entityIds),
                ) .
                " }";

            // Query to get entity data with labels and descriptions
            $sparqlQuery = "SELECT ?item ?itemLabel ?itemDescription WHERE {
                $valuesClause
                SERVICE wikibase:label { bd:serviceParam wikibase:language 'de,en'. }
            }";

            $sparqlResponse = $this->getSparqlClient()->get(
                "https://query.wikidata.org/sparql",
                [
                    "query" => $sparqlQuery,
                    "format" => "json",
                ],
            );

            if (!$sparqlResponse->successful()) {
                return response()->json(
                    ["error" => "Wikidata SPARQL request failed"],
                    500,
                );
            }

            $sparqlResults =
                $sparqlResponse->json()["results"]["bindings"] ?? [];

            // Process the results
            $filteredResults = [];
            foreach ($sparqlResults as $result) {
                $entityId = str_replace(
                    "http://www.wikidata.org/entity/",
                    "",
                    $result["item"]["value"],
                );

                // Try to find the original search result to get its description
                $originalResult = null;
                foreach ($searchResults as $sr) {
                    if ($sr["id"] === $entityId) {
                        $originalResult = $sr;
                        break;
                    }
                }

                // Get label and description
                $label = null;
                $description = null;

                // Zuerst versuchen, die Daten aus dem Originalergebnis zu bekommen
                if ($originalResult) {
                    // Priorisieren: Deutsche Labels aus der Suche > Englische Labels > ID
                    if (!empty($result["itemLabel"]["value"])) {
                        // Das Label aus dem SPARQL-Ergebnis ist bereits nach de,en priorisiert
                        $label = $result["itemLabel"]["value"];
                    } elseif (!empty($originalResult["label"])) {
                        $label = $originalResult["label"];
                    } else {
                        $label = $entityId;
                    }

                    // Priorisieren: Deutsche Beschreibung aus der Suche > Englische Beschreibung > null
                    if (!empty($result["itemDescription"]["value"])) {
                        // Die Beschreibung aus dem SPARQL-Ergebnis ist bereits nach de,en priorisiert
                        $description = $result["itemDescription"]["value"];
                    } elseif (!empty($originalResult["description"])) {
                        $description = $originalResult["description"];
                    }
                } else {
                    // Fallback auf SPARQL-Ergebnisse
                    $label = $result["itemLabel"]["value"] ?? $entityId;
                    $description = $result["itemDescription"]["value"] ?? null;
                }

                $filteredResults[] = [
                    "id" => $entityId,
                    "label" => $label,
                    "description" => $description,
                ];
            }

            return response()->json($filteredResults);
        } catch (\Exception $e) {
            Log::error("Wikidata concept search error: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to search Wikidata concepts: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }
    /**
     * Search for books in Wikidata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchBooks(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
        ]);

        $query = $request->input("query");

        try {
            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $query)) {
                // Direct query for the entity with that Q-ID
                $response = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Request German and English
                        "format" => "json",
                    ],
                );

                if (
                    $response->successful() &&
                    !empty($response->json()["entities"][$query])
                ) {
                    $entity = $response->json()["entities"][$query];

                    // Check if this is a book (instance of book Q571)
                    $isBook = false;
                    if (isset($entity["claims"]["P31"])) {
                        foreach ($entity["claims"]["P31"] as $claim) {
                            if (
                                isset(
                                    $claim["mainsnak"]["datavalue"]["value"][
                                        "id"
                                    ],
                                )
                            ) {
                                $instanceOf =
                                    $claim["mainsnak"]["datavalue"]["value"][
                                        "id"
                                    ];
                                // Check for book (Q571) or any subclass of book
                                if (
                                    $instanceOf === "Q571" ||
                                    $instanceOf === "Q7725634" ||
                                    $instanceOf === "Q1667921"
                                ) {
                                    $isBook = true;
                                    break;
                                }
                            }
                        }
                    }

                    if (!$isBook) {
                        return response()->json([
                            "success" => false,
                            "message" =>
                                "Das ausgewählte Element ist kein Buch",
                            "results" => [],
                        ]);
                    }

                    // Extract author information if available (P50)
                    $authors = [];
                    if (isset($entity["claims"]["P50"])) {
                        foreach ($entity["claims"]["P50"] as $claim) {
                            if (
                                isset(
                                    $claim["mainsnak"]["datavalue"]["value"][
                                        "id"
                                    ],
                                )
                            ) {
                                $authorId =
                                    $claim["mainsnak"]["datavalue"]["value"][
                                        "id"
                                    ];
                                $authors[] = $authorId;
                            }
                        }
                    }

                    $resultObj = ["id" => $query];

                    $label = $this->getPrioritizedLabel($entity, $resultObj);
                    $description = $this->getPrioritizedDescription(
                        $entity,
                        $resultObj,
                    );

                    return response()->json([
                        "success" => true,
                        "results" => [
                            [
                                "id" => $query,
                                "label" => $label,
                                "description" => $description,
                                "authors" => $authors,
                            ],
                        ],
                    ]);
                }
            }

            // Search for books in Wikidata
            $response = $this->getHttpClient()->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 10,
                ],
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500,
                );
            }

            $results = $response->json()["search"] ?? [];

            if (empty($results)) {
                return response()->json([
                    "success" => true,
                    "results" => [],
                ]);
            }

            $processedResults = [];
            foreach ($results as $result) {
                $entityResponse = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $result["id"],
                        "languages" => "de|en",
                        "format" => "json",
                    ],
                );

                if ($entityResponse->successful()) {
                    $entity =
                        $entityResponse->json()["entities"][$result["id"]] ??
                        null;

                    if ($entity) {
                        // Prioritäten für Label und Beschreibung anwenden
                        $label = $this->getPrioritizedLabel($entity, $result);
                        $description = $this->getPrioritizedDescription(
                            $entity,
                            $result,
                        );

                        $processedResults[] = [
                            "id" => $result["id"],
                            "label" => $label,
                            "description" => $description,
                        ];
                    } else {
                        // Fallback auf originales Ergebnis
                        $processedResults[] = $result;
                    }
                } else {
                    // Fallback auf originales Ergebnis
                    $processedResults[] = $result;
                }
            }

            return response()->json([
                "success" => true,
                "results" => $processedResults,
            ]);
        } catch (\Exception $e) {
            Log::error("Wikidata book search error: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to search Wikidata books: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Search for archives in Wikidata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchArchives(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
        ]);

        $query = $request->input("query");

        try {
            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $query)) {
                // Direct query for the entity with that Q-ID
                $response = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Request German and English
                        "format" => "json",
                    ],
                );

                if (
                    $response->successful() &&
                    !empty($response->json()["entities"][$query])
                ) {
                    $entity = $response->json()["entities"][$query];

                    // Check if this is an archive (instance of archive Q166118)
                    $isArchive = false;
                    if (isset($entity["claims"]["P31"])) {
                        foreach ($entity["claims"]["P31"] as $claim) {
                            if (
                                isset(
                                    $claim["mainsnak"]["datavalue"]["value"][
                                        "id"
                                    ],
                                )
                            ) {
                                $instanceOf =
                                    $claim["mainsnak"]["datavalue"]["value"][
                                        "id"
                                    ];
                                // Check for archive (Q166118) or archives (Q211650)
                                if (
                                    $instanceOf === "Q166118" ||
                                    $instanceOf === "Q211650"
                                ) {
                                    $isArchive = true;
                                    break;
                                }
                            }
                        }
                    }

                    if (!$isArchive) {
                        return response()->json([
                            "success" => false,
                            "message" =>
                                "Das ausgewählte Element ist kein Archiv",
                            "results" => [],
                        ]);
                    }

                    // Manuelles Objekt erstellen, um unsere Hilfsfunktionen zu nutzen
                    $resultObj = ["id" => $query];

                    // Prioritu00e4ten fu00fcr Label und Beschreibung anwenden
                    $label = $this->getPrioritizedLabel($entity, $resultObj);
                    $description = $this->getPrioritizedDescription(
                        $entity,
                        $resultObj,
                    );

                    return response()->json([
                        "success" => true,
                        "results" => [
                            [
                                "id" => $query,
                                "label" => $label,
                                "description" => $description,
                            ],
                        ],
                    ]);
                }
            }

            // Search for archives in Wikidata
            $response = $this->getHttpClient()->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 10,
                ],
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500,
                );
            }

            $results = $response->json()["search"] ?? [];

            if (empty($results)) {
                return response()->json([
                    "success" => true,
                    "results" => [],
                ]);
            }

            // Fu00fcr jedes Suchergebnis sollten wir die Beschreibung und Labels auf Deutsch priorisieren
            $processedResults = [];
            foreach ($results as $result) {
                // Vollstu00e4ndige Entitu00e4tsdaten abrufen
                $entityResponse = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $result["id"],
                        "languages" => "de|en", // Deutsch und Englisch anfordern
                        "format" => "json",
                    ],
                );

                if ($entityResponse->successful()) {
                    $entity =
                        $entityResponse->json()["entities"][$result["id"]] ??
                        null;

                    if ($entity) {
                        // Prioritu00e4ten fu00fcr Label und Beschreibung anwenden
                        $label = $this->getPrioritizedLabel($entity, $result);
                        $description = $this->getPrioritizedDescription(
                            $entity,
                            $result,
                        );

                        $processedResults[] = [
                            "id" => $result["id"],
                            "label" => $label,
                            "description" => $description,
                        ];
                    } else {
                        // Fallback auf originales Ergebnis
                        $processedResults[] = $result;
                    }
                } else {
                    // Fallback auf originales Ergebnis
                    $processedResults[] = $result;
                }
            }

            return response()->json([
                "success" => true,
                "results" => $processedResults,
            ]);
        } catch (\Exception $e) {
            Log::error("Wikidata places search error: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to search Wikidata archives: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }
    /**
     * Search for places/locations in Wikidata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchPlaces(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
        ]);

        $query = $request->input("query");

        try {
            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $query)) {
                // Direct query for the entity with that Q-ID
                $response = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Request German and English
                        "format" => "json",
                    ],
                );

                if (
                    $response->successful() &&
                    !empty($response->json()["entities"][$query])
                ) {
                    $entity = $response->json()["entities"][$query];

                    // Check if this is a place (instance of place Q82794)
                    $isPlace = false;
                    if (isset($entity["claims"]["P31"])) {
                        foreach ($entity["claims"]["P31"] as $claim) {
                            if (
                                isset(
                                    $claim["mainsnak"]["datavalue"]["value"][
                                        "id"
                                    ],
                                )
                            ) {
                                $instanceOf =
                                    $claim["mainsnak"]["datavalue"]["value"][
                                        "id"
                                    ];
                                // Check for place (Q82794) or human settlement (Q486972)
                                if (
                                    $instanceOf === "Q82794" || // place
                                    $instanceOf === "Q486972" || // human settlement
                                    $instanceOf === "Q515" || // city
                                    $instanceOf === "Q6256" // country
                                ) {
                                    $isPlace = true;
                                    break;
                                }
                            }
                        }
                    }

                    // Manuelles Objekt erstellen, um unsere Hilfsfunktionen zu nutzen
                    $resultObj = ["id" => $query];

                    // Prioritäten für Label und Beschreibung anwenden
                    $label = $this->getPrioritizedLabel($entity, $resultObj);
                    $description = $this->getPrioritizedDescription(
                        $entity,
                        $resultObj,
                    );

                    return response()->json([
                        [
                            "id" => $query,
                            "label" => $label,
                            "description" => $description,
                        ],
                    ]);
                }
            }

            // Search for places in Wikidata
            $response = $this->getHttpClient()->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 10,
                ],
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500,
                );
            }

            $results = $response->json()["search"] ?? [];

            if (empty($results)) {
                return response()->json([]);
            }

            // Get more details for each result
            $processedResults = [];
            foreach ($results as $result) {
                $entityResponse = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $result["id"],
                        "languages" => "de|en", // Deutsch und Englisch anfordern
                        "format" => "json",
                    ],
                );

                if ($entityResponse->successful()) {
                    $entity =
                        $entityResponse->json()["entities"][$result["id"]] ??
                        null;

                    if ($entity) {
                        // Check if entity is a place
                        $isPlace = false;

                        if (isset($entity["claims"]["P31"])) {
                            foreach ($entity["claims"]["P31"] as $claim) {
                                if (
                                    isset(
                                        $claim["mainsnak"]["datavalue"][
                                            "value"
                                        ]["id"],
                                    )
                                ) {
                                    $instanceOf =
                                        $claim["mainsnak"]["datavalue"][
                                            "value"
                                        ]["id"];
                                    // Check for place (Q82794) or human settlement (Q486972)
                                    if (
                                        $instanceOf === "Q82794" || // place
                                        $instanceOf === "Q486972" || // human settlement
                                        $instanceOf === "Q515" || // city
                                        $instanceOf === "Q6256" // country
                                    ) {
                                        $isPlace = true;
                                        break;
                                    }
                                }
                            }
                        }

                        // Include this result if it's a place or we couldn't determine
                        // Prioritäten für Label und Beschreibung anwenden
                        $label = $this->getPrioritizedLabel($entity, $result);
                        $description = $this->getPrioritizedDescription(
                            $entity,
                            $result,
                        );

                        $processedResults[] = [
                            "id" => $result["id"],
                            "label" => $label,
                            "description" => $description,
                        ];
                    } else {
                        // Fallback auf originales Ergebnis
                        $processedResults[] = $result;
                    }
                } else {
                    // Fallback auf originales Ergebnis
                    $processedResults[] = $result;
                }
            }

            return response()->json($processedResults);
        } catch (\Exception $e) {
            Log::error("Wikidata search error: " . $e->getMessage());
            return response()->json(
                ["error" => "Failed to search Wikidata: " . $e->getMessage()],
                500,
            );
        }
    }

    /**
     * General search method for Wikidata entities
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
            "type" => "nullable|string",
        ]);

        $query = $request->input("query");
        $type = $request->input("type");

        try {
            // If a specific type is provided, route to the appropriate search method
            if ($type) {
                switch ($type) {
                    case "person":
                    case "people":
                        return $this->searchPeople($request);

                    case "place":
                    case "places":
                        return $this->searchPlaces($request);

                    case "book":
                    case "books":
                        return $this->searchBooks($request);

                    case "archive":
                    case "archives":
                        return $this->searchArchives($request);

                    case "cause":
                    case "causes":
                        return $this->searchCauses($request);
                }
            }

            // Default generic search
            $response = Http::get("https://www.wikidata.org/w/api.php", [
                "action" => "wbsearchentities",
                "search" => $query,
                "language" => "de",
                "format" => "json",
                "type" => "item",
                "limit" => 15,
            ]);

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500,
                );
            }

            $results = $response->json()["search"] ?? [];
            $processedResults = [];

            foreach ($results as $result) {
                $processedResults[] = [
                    "id" => $result["id"],
                    "label" => $result["label"] ?? $result["id"],
                    "description" => $result["description"] ?? "",
                    "url" => "https://www.wikidata.org/wiki/" . $result["id"],
                ];
            }

            return response()->json([
                "results" => $processedResults,
                "query" => $query,
                "count" => count($processedResults),
            ]);
        } catch (\Exception $e) {
            Log::error("General Wikidata search error: " . $e->getMessage());
            return response()->json(
                ["error" => "Failed to search Wikidata: " . $e->getMessage()],
                500,
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
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
                "Accept" => "application/sparql-results+json",
                "Content-Type" => "application/x-www-form-urlencoded",
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
     * Get a prioritized label from an entity's data
     *
     * @param array $entity Full entity data from Wikidata
     * @param array $result Initial search result from Wikidata
     * @return string The prioritized label
     */
    private function getPrioritizedLabel(array $entity, array $result): string
    {
        // Default to ID if nothing else is found
        $label = isset($result["id"]) ? $result["id"] : "";

        // Check if we have direct labels in the entity
        if (isset($entity["labels"])) {
            // First try German label from full entity lookup
            if (!empty($entity["labels"]["de"]["value"])) {
                $label = $entity["labels"]["de"]["value"];
            }
            // Fallback to English label from entity lookup
            elseif (!empty($entity["labels"]["en"]["value"])) {
                $label = $entity["labels"]["en"]["value"];
            }
        }

        // Fallback to label from search result
        if (empty($label) && !empty($result["label"])) {
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
            Log::debug("Getting entity details", [
                "api_url" => $apiUrl,
                "entity_id" => $entityId,
                "languages" => $languagesParam,
            ]);

            try {
                $response = $this->getHttpClient()->get($apiUrl, [
                    "query" => [
                        "action" => "wbgetentities",
                        "ids" => $entityId,
                        "languages" => $languagesParam,
                        "format" => "json",
                        "origin" => "*",
                    ],
                ]);

                Log::debug("Entity details response", [
                    "status" => $response->status(),
                    "successful" => $response->successful(),
                    "body_preview" => substr($response->body(), 0, 200),
                ]);
            } catch (\Exception $e) {
                Log::error("Exception getting entity details", [
                    "message" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]);
                return null; // Return null instead of throwing an exception to gracefully handle the error
            }

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

    /**
     * Get Wikidata entities by their IDs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEntitiesByIds(Request $request): JsonResponse
    {
        $request->validate([
            "ids" => "required|string",
        ]);

        $ids = $request->input("ids");

        // Remove any invalid characters and split by pipe or comma
        $cleanIds = preg_replace("/[^Q0-9|,]/", "", $ids);
        $idArray = preg_split("/[|,]/", $cleanIds, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($idArray)) {
            return response()->json(["entities" => []], 200);
        }

        try {
            $idString = implode("|", $idArray);

            $response = $this->getHttpClient()->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbgetentities",
                    "ids" => $idString,
                    "languages" => "de|en",
                    "format" => "json",
                ],
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500,
                );
            }

            $data = $response->json();

            $entities = [];
            if (isset($data["entities"]) && is_array($data["entities"])) {
                foreach ($data["entities"] as $entityId => $entity) {
                    $label = $this->getPrioritizedLabel(
                        $entity["labels"] ?? [],
                    );
                    $description = $this->getPrioritizedDescription(
                        $entity["descriptions"] ?? [],
                    );

                    $entities[$entityId] = [
                        "id" => $entityId,
                        "label" => $label ?: $entityId,
                        "description" => $description ?: "",
                    ];
                }
            }

            return response()->json(["entities" => $entities], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching entities by IDs", [
                "ids" => $ids,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                ["error" => "Failed to fetch entities"],
                500,
            );
        }
    }

    /**
     * Get a single Wikidata entity by ID
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getEntityById(string $id): JsonResponse
    {
        // Validate that $id is a valid Wikidata ID
        if (!preg_match('/^Q\d+$/', $id)) {
            return response()->json(
                ["error" => "Invalid Wikidata entity ID format"],
                422,
            );
        }

        try {
            $data = null;

            // First attempt with HTTP client
            try {
                $response = $this->getHttpClient()->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $id,
                        "languages" => "de|en",
                        "format" => "json",
                    ],
                );

                if ($response->successful()) {
                    $data = $response->json();
                } else {
                    Log::warning("Wikidata API returned non-success status", [
                        "id" => $id,
                        "status" => $response->status(),
                        "body" => $response->body(),
                    ]);
                    throw new \Exception(
                        "Wikidata API returned status code: " .
                            $response->status(),
                    );
                }
            } catch (\Exception $innerException) {
                Log::error("HTTP request failed in getEntityById", [
                    "id" => $id,
                    "error" => $innerException->getMessage(),
                    "trace" => $innerException->getTraceAsString(),
                ]);

                // Fallback to direct cURL request
                $url = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids={$id}&languages=de|en&format=json";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt(
                    $ch,
                    CURLOPT_USERAGENT,
                    "GTO-Wiki-App/1.0 (https://www.gestapo-terror-orte.de/; info@gestapo-terror-orte.de)",
                );
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = curl_exec($ch);
                $curlError = curl_error($ch);
                $curlInfo = curl_getinfo($ch);
                curl_close($ch);

                if ($response === false) {
                    Log::error("cURL request failed", [
                        "id" => $id,
                        "error" => $curlError,
                        "info" => $curlInfo,
                    ]);
                    throw new \Exception("cURL request failed: " . $curlError);
                }

                $data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error("JSON decoding failed", [
                        "id" => $id,
                        "error" => json_last_error_msg(),
                        "response" => substr($response, 0, 1000), // Log first 1000 chars
                    ]);
                    throw new \Exception(
                        "Failed to decode JSON: " . json_last_error_msg(),
                    );
                }
            }

            if (
                !$data ||
                !isset($data["entities"]) ||
                !isset($data["entities"][$id])
            ) {
                Log::warning("Entity not found in response", [
                    "id" => $id,
                    "data_keys" => $data ? array_keys($data) : "null",
                    "has_entities" =>
                        $data && isset($data["entities"]) ? "yes" : "no",
                ]);
                return response()->json(["error" => "Entity not found"], 404);
            }

            $entity = $data["entities"][$id];

            // Extract label with priority: German > English > entity ID
            $label = $id;
            if (
                isset($entity["labels"]["de"]) &&
                !empty($entity["labels"]["de"]["value"])
            ) {
                $label = $entity["labels"]["de"]["value"];
            } elseif (
                isset($entity["labels"]["en"]) &&
                !empty($entity["labels"]["en"]["value"])
            ) {
                $label = $entity["labels"]["en"]["value"];
            }

            // Extract description with priority: German > English > empty string
            $description = "";
            if (
                isset($entity["descriptions"]["de"]) &&
                !empty($entity["descriptions"]["de"]["value"])
            ) {
                $description = $entity["descriptions"]["de"]["value"];
            } elseif (
                isset($entity["descriptions"]["en"]) &&
                !empty($entity["descriptions"]["en"]["value"])
            ) {
                $description = $entity["descriptions"]["en"]["value"];
            }

            return response()->json(
                [
                    "id" => $id,
                    "label" => $label,
                    "description" => $description,
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error("Error fetching entity by ID", [
                "id" => $id,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ]);

            // Return a more specific error message with ID for debugging
            return response()->json(
                [
                    "error" => "Failed to fetch entity",
                    "id" => $id,
                    "message" => $e->getMessage(),
                    "class" => get_class($e),
                ],
                500,
            );
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
            // Make direct API call to Wikidata
            $client = new \GuzzleHttp\Client();
            $response = $client->request(
                "GET",
                "https://www.wikidata.org/w/api.php",
                [
                    "query" => [
                        "action" => "wbsearchentities",
                        "search" => $title,
                        "language" => "de",
                        "format" => "json",
                        "type" => "item",
                        "limit" => $limit,
                    ],
                    "headers" => [
                        "User-Agent" =>
                            "GTO-Wiki-App/1.0 (https://www.gestapo-terror-orte.de/; info@gestapo-terror-orte.de)",
                    ],
                ],
            );

            $data = json_decode($response->getBody(), true);
            $results = $data["search"] ?? [];

            return response()->json([
                "success" => true,
                "results" => $results,
            ]);
        } catch (\Exception $e) {
            Log::error("Error searching Wikidata by title", [
                "title" => $title,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "error" => "Failed to search Wikidata",
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
            "endpoint" => "nullable|string",
        ]);

        $query = $request->input("query");
        $type = $request->input("type") ?? $request->input("endpoint");

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

                    case "commemorates":
                        return $this->searchCommemorates($request);

                    case "organizations":
                        return $this->searchOrganizations($request);

                    case "service":
                    case "services":
                        return $this->searchServices($request);

                    // Add any other specific search types here...
                }
            }

            // Default generic search with German language prioritization
            $response = Http::get("https://www.wikidata.org/w/api.php", [
                "action" => "wbsearchentities",
                "search" => $query,
                "language" => "de",
                "uselang" => "de",
                "strictlanguage" => true, // Prioritize exact language matches
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
                // For German results, make sure to prioritize German labels and descriptions
                $processedResults[] = [
                    "id" => $result["id"],
                    "label" => $result["label"] ?? $result["id"],
                    "description" => $result["description"] ?? "",
                    "url" => "https://www.wikidata.org/wiki/" . $result["id"],
                    "language" => $result["match"]["language"] ?? "de",
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

    /**
     * Search for entities that can be commemorated by a memorial site
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchCommemorates(Request $request): JsonResponse
    {
        $query = $request->input("query");

        if (!$query || strlen(trim($query)) < 2) {
            return response()->json([]);
        }

        try {
            // Sanitize the query for SPARQL
            $sanitizedQuery = str_replace('"', "", $query);

            // Build a SPARQL query to find commemorative items with German labels
            $sparqlQuery = <<<SPARQL
            SELECT DISTINCT ?item ?itemLabel ?itemDescription WHERE {
              {
                # Events
                ?item wdt:P31/wdt:P279* wd:Q1190554. # instance of event
              } UNION {
                # Historical events
                ?item wdt:P31/wdt:P279* wd:Q11514315. # instance of historical event
              } UNION {
                # Groups of people
                ?item wdt:P31/wdt:P279* wd:Q16334295. # instance of group of humans
              } UNION {
                # Persons
                ?item wdt:P31 wd:Q5. # instance of human
              }

              # Filter by label containing the search term (case insensitive)
              ?item rdfs:label ?label.
              FILTER(LANG(?label) = "de" && CONTAINS(LCASE(?label), LCASE("{$sanitizedQuery}")))

              # Get labels and descriptions in German
              SERVICE wikibase:label {
                bd:serviceParam wikibase:language "de,en".
              }
            }
            LIMIT 20
            SPARQL;

            try {
                // Get the SPARQL client
                $client = $this->getSparqlClient();

                // Execute the query
                $response = $client->query($sparqlQuery);

                if ($response->successful()) {
                    $data = $response->json();
                    $results = [];

                    // Process the SPARQL results
                    if (
                        isset($data["results"]["bindings"]) &&
                        !empty($data["results"]["bindings"])
                    ) {
                        foreach ($data["results"]["bindings"] as $item) {
                            // Extract the entity ID from the URI
                            $id = str_replace(
                                "http://www.wikidata.org/entity/",
                                "",
                                $item["item"]["value"],
                            );

                            $results[] = [
                                "id" => $id,
                                "label" => $item["itemLabel"]["value"] ?? $id,
                                "description" =>
                                    $item["itemDescription"]["value"] ?? "",
                            ];
                        }
                    }

                    if (!empty($results)) {
                        return response()->json($results);
                    }
                }
            } catch (\Exception $e) {
                Log::warning(
                    "SPARQL query failed, falling back to API search",
                    [
                        "error" => $e->getMessage(),
                    ],
                );
                // If SPARQL fails, we'll fall back to the API search below
            }

            // Fall back to direct API call if SPARQL returned no results or failed
            $client = new \GuzzleHttp\Client();
            $response = $client->request(
                "GET",
                "https://www.wikidata.org/w/api.php",
                [
                    "query" => [
                        "action" => "wbsearchentities",
                        "search" => $query,
                        "language" => "de",
                        "format" => "json",
                        "type" => "item",
                        "limit" => 20,
                        "uselang" => "de",
                        "strictlanguage" => true,
                    ],
                    "headers" => [
                        "User-Agent" =>
                            "GTO-Wiki-App/1.0 (https://www.gestapo-terror-orte.de/; info@gestapo-terror-orte.de)",
                    ],
                ],
            );

            $data = json_decode($response->getBody(), true);
            $results = [];

            // Process the results
            foreach ($data["search"] ?? [] as $item) {
                $results[] = [
                    "id" => $item["id"],
                    "label" => $item["label"] ?? $item["id"],
                    "description" => $item["description"] ?? "",
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error("Error searching for commemorates", [
                "query" => $query,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "error" => "Failed to search Wikidata",
                ],
                500,
            );
        }
    }

    /**
     * Search for organizations in Wikidata that can be operators of memorial sites
     *
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Fallback search for organizations using the Wikidata API
     *
     * @param string $query The search query
     * @return JsonResponse
     */
    private function fallbackOrganizationSearch(string $query): JsonResponse
    {
        try {
            $httpClient = $this->getHttpClient();
            $response = $httpClient->get("https://www.wikidata.org/w/api.php", [
                "action" => "wbsearchentities",
                "search" => $query,
                "language" => "de",
                "uselang" => "de",
                "strictlanguage" => true,
                "format" => "json",
                "type" => "item",
                "limit" => 20,
            ]);

            if (!$response->successful()) {
                return response()->json(
                    [
                        "success" => false,
                        "error" => "Failed to search Wikidata",
                    ],
                    500,
                );
            }

            $data = $response->json();
            $results = [];

            // Process the results
            foreach ($data["search"] ?? [] as $item) {
                $results[] = [
                    "id" => $item["id"],
                    "label" => $item["label"] ?? $item["id"],
                    "description" => $item["description"] ?? "",
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error("Fallback error searching for organizations", [
                "query" => $query,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "error" => "Failed to search Wikidata",
                ],
                500,
            );
        }
    }

    public function searchOrganizations(Request $request): JsonResponse
    {
        $query = $request->input("query");

        if (!$query || strlen(trim($query)) < 2) {
            return response()->json([]);
        }

        try {
            // Sanitize the query for SPARQL
            $sanitizedQuery = str_replace('"', "", $query);

            // Build a SPARQL query to find organizations with German labels
            $sparqlQuery = <<<SPARQL
            SELECT DISTINCT ?item ?itemLabel ?itemDescription WHERE {
              {
                # Organizations
                ?item wdt:P31/wdt:P279* wd:Q43229. # instance of organization
              } UNION {
                # Institutions
                ?item wdt:P31/wdt:P279* wd:Q178706. # instance of institution
              } UNION {
                # Government agencies
                ?item wdt:P31/wdt:P279* wd:Q327333. # instance of government agency
              } UNION {
                # Non-profit organizations
                ?item wdt:P31/wdt:P279* wd:Q163740. # instance of non-profit organization
              }

              # Filter by label containing the search term (case insensitive)
              ?item rdfs:label ?label.
              FILTER(LANG(?label) = "de" && CONTAINS(LCASE(?label), LCASE("{$sanitizedQuery}")))

              # Get labels and descriptions in German
              SERVICE wikibase:label {
                bd:serviceParam wikibase:language "de,en".
              }
            }
            LIMIT 20
            SPARQL;

            // Get the SPARQL client
            $client = $this->getSparqlClient();

            // Execute the query
            $response = $client->query($sparqlQuery);

            if (!$response->successful()) {
                Log::error("SPARQL query failed", [
                    "query" => $sparqlQuery,
                    "status" => $response->status(),
                    "body" => $response->body(),
                ]);

                // Fall back to API search
                return $this->fallbackOrganizationSearch($query);
            }

            $data = $response->json();
            $results = [];

            // Process the SPARQL results
            if (
                isset($data["results"]["bindings"]) &&
                !empty($data["results"]["bindings"])
            ) {
                foreach ($data["results"]["bindings"] as $item) {
                    // Extract the entity ID from the URI
                    $id = str_replace(
                        "http://www.wikidata.org/entity/",
                        "",
                        $item["item"]["value"],
                    );

                    $results[] = [
                        "id" => $id,
                        "label" => $item["itemLabel"]["value"] ?? $id,
                        "description" =>
                            $item["itemDescription"]["value"] ?? "",
                    ];
                }
            }

            // If SPARQL results are empty, fall back to API search
            if (empty($results)) {
                return $this->fallbackOrganizationSearch($query);
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error("Error searching for organizations", [
                "query" => $query,
                "error" => $e->getMessage(),
            ]);

            // Fall back to regular search in case of exception
            return $this->fallbackOrganizationSearch($query);
        }
    }

    /**
     * Search for services and offerings (Leistungen und Angebote)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchServices(Request $request): JsonResponse
    {
        $query = $request->input("query");

        if (!$query || strlen(trim($query)) < 2) {
            return response()->json([]);
        }

        try {
            // First try a more specific search using SPARQL
            try {
                $sanitizedQuery = str_replace('"', "", $query);
                $sparqlClient = $this->getSparqlClient();

                // Build a SPARQL query for services commonly used at memorials
                $sparqlQuery = <<<SPARQL
                SELECT DISTINCT ?item ?itemLabel ?itemDescription WHERE {
                  {
                    # Museum services and exhibitions
                    ?item wdt:P31/wdt:P279* wd:Q1371995.  # exhibition
                  } UNION {
                    # Educational activities
                    ?item wdt:P31/wdt:P279* wd:Q9053012.  # educational activity
                  } UNION {
                    # Tours
                    ?item wdt:P31/wdt:P279* wd:Q2266957.  # guided tour
                  } UNION {
                    # Events
                    ?item wdt:P31/wdt:P279* wd:Q1656682.  # event
                  }

                  # Filter by German label containing the search term
                  ?item rdfs:label ?label.
                  FILTER(LANG(?label) = "de" && CONTAINS(LCASE(?label), LCASE("$sanitizedQuery")))

                  SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
                }
                LIMIT 20
                SPARQL;

                $sparqlResponse = $sparqlClient->query($sparqlQuery);

                if ($sparqlResponse->successful()) {
                    $data = $sparqlResponse->json();
                    $results = [];

                    if (
                        isset($data["results"]["bindings"]) &&
                        !empty($data["results"]["bindings"])
                    ) {
                        foreach ($data["results"]["bindings"] as $item) {
                            $id = str_replace(
                                "http://www.wikidata.org/entity/",
                                "",
                                $item["item"]["value"],
                            );

                            $results[] = [
                                "id" => $id,
                                "label" => $item["itemLabel"]["value"] ?? $id,
                                "description" =>
                                    $item["itemDescription"]["value"] ?? "",
                            ];
                        }

                        if (!empty($results)) {
                            return response()->json($results);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning(
                    "SPARQL query failed for services, falling back to API search",
                    [
                        "error" => $e->getMessage(),
                    ],
                );
            }

            // Fall back to regular API search if SPARQL fails or returns no results
            $client = new \GuzzleHttp\Client();
            $response = $client->request(
                "GET",
                "https://www.wikidata.org/w/api.php",
                [
                    "query" => [
                        "action" => "wbsearchentities",
                        "search" => $query,
                        "language" => "de",
                        "uselang" => "de",
                        "format" => "json",
                        "type" => "item",
                        "limit" => 20,
                    ],
                    "headers" => [
                        "User-Agent" =>
                            "GTO-Wiki-App/1.0 (https://www.gestapo-terror-orte.de/; info@gestapo-terror-orte.de)",
                    ],
                ],
            );

            $data = json_decode($response->getBody(), true);
            $results = [];

            // Process the results
            foreach ($data["search"] ?? [] as $item) {
                $results[] = [
                    "id" => $item["id"],
                    "label" => $item["label"] ?? $item["id"],
                    "description" => $item["description"] ?? "",
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error("Error searching for services", [
                "query" => $query,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "error" => "Failed to search Wikidata",
                ],
                500,
            );
        }
    }
}

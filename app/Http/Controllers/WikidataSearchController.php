<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;

class WikidataSearchController extends Controller
{
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
                $response = Http::accept("application/json")->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Deutsch und Englisch anfordern
                        "format" => "json",
                    ]
                );

                if (
                    $response->successful() &&
                    !empty($response->json()["entities"][$query])
                ) {
                    $entity = $response->json()["entities"][$query];

                    // Pru00fcfe, ob es sich um einen Menschen handelt (P31 = instance of, Q5 = human)
                    if (isset($entity["claims"]["P31"])) {
                        foreach ($entity["claims"]["P31"] as $claim) {
                            if (
                                $claim["mainsnak"]["datavalue"]["value"][
                                    "id"
                                ] === "Q5"
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
                                        : null; // '+' und Pru00e4zision entfernen
                                }

                                // Beschreibungstext priorisieren: Deutsch > Englisch > leer
                                $description = null;
                                if (
                                    !empty(
                                        $entity["descriptions"]["de"]["value"]
                                    )
                                ) {
                                    $description =
                                        $entity["descriptions"]["de"]["value"];
                                } elseif (
                                    !empty(
                                        $entity["descriptions"]["en"]["value"]
                                    )
                                ) {
                                    $description =
                                        $entity["descriptions"]["en"]["value"];
                                }

                                // Label priorisieren: Deutsch > Englisch > Q-ID
                                $label = $query;
                                if (!empty($entity["labels"]["de"]["value"])) {
                                    $label = $entity["labels"]["de"]["value"];
                                } elseif (
                                    !empty($entity["labels"]["en"]["value"])
                                ) {
                                    $label = $entity["labels"]["en"]["value"];
                                }

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
            $response = Http::accept("application/json")->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 10,
                ]
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500
                );
            }

            $results = $response->json()["search"] ?? [];

            // Filter results to only include humans (Q5)
            $filteredResults = [];
            foreach ($results as $result) {
                // Get detailed information about the entity
                $entityResponse = Http::accept("application/json")->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $result["id"],
                        "languages" => "de|en", // Deutsch und Englisch anfordern
                        "format" => "json",
                    ]
                );

                if ($entityResponse->successful()) {
                    $entity =
                        $entityResponse->json()["entities"][$result["id"]] ??
                        null;

                    // Check if entity is human (P31 = instance of, Q5 = human)
                    if ($entity && isset($entity["claims"]["P31"])) {
                        foreach ($entity["claims"]["P31"] as $claim) {
                            if (
                                $claim["mainsnak"]["datavalue"]["value"][
                                    "id"
                                ] === "Q5"
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

                                // Beschreibung priorisieren: Aus Suchergebnis > Deutsch > Englisch > leer
                                $description = $result["description"] ?? null;

                                // Wenn keine Beschreibung aus dem Suchergebnis vorhanden ist
                                if (empty($description)) {
                                    if (
                                        !empty(
                                            $entity["descriptions"]["de"][
                                                "value"
                                            ]
                                        )
                                    ) {
                                        $description =
                                            $entity["descriptions"]["de"][
                                                "value"
                                            ];
                                    } elseif (
                                        !empty(
                                            $entity["descriptions"]["en"][
                                                "value"
                                            ]
                                        )
                                    ) {
                                        $description =
                                            $entity["descriptions"]["en"][
                                                "value"
                                            ];
                                    }
                                }

                                $filteredResults[] = [
                                    "id" => $result["id"],
                                    "label" =>
                                        $result["label"] ?? $result["id"],
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
            \Log::error("Wikidata search error: " . $e->getMessage());
            return response()->json(
                ["error" => "Failed to search Wikidata: " . $e->getMessage()],
                500
            );
        }
    }

    public function searchByTitle(Request $request): JsonResponse
    {
        $request->validate([
            "title" => "required|string|min:2",
        ]);

        $title = $request->input("title");

        try {
            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $title)) {
                // Direct query for the entity with that Q-ID
                $response = Http::accept("application/json")->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $title,
                        "languages" => "de|en", // Request German and English
                        "format" => "json",
                    ]
                );

                if (
                    $response->successful() &&
                    !empty($response->json()["entities"][$title])
                ) {
                    $entity = $response->json()["entities"][$title];

                    // Prioritize description: German > English > empty
                    $description = null;
                    if (!empty($entity["descriptions"]["de"]["value"])) {
                        $description = $entity["descriptions"]["de"]["value"];
                    } elseif (!empty($entity["descriptions"]["en"]["value"])) {
                        $description = $entity["descriptions"]["en"]["value"];
                    }

                    // Prioritize label: German > English > Q-ID
                    $label = $title;
                    if (!empty($entity["labels"]["de"]["value"])) {
                        $label = $entity["labels"]["de"]["value"];
                    } elseif (!empty($entity["labels"]["en"]["value"])) {
                        $label = $entity["labels"]["en"]["value"];
                    }

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

            // Search for entities matching the title
            $response = Http::accept("application/json")->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $title,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 10,
                ]
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500
                );
            }

            $results = $response->json()["search"] ?? [];

            if (empty($results)) {
                return response()->json([]);
            }

            return response()->json([
                "success" => true,
                "results" => $results,
            ]);
        } catch (\Exception $e) {
            \Log::error("Wikidata title search error: " . $e->getMessage());
            return response()->json(
                ["error" => "Failed to search Wikidata: " . $e->getMessage()],
                500
            );
        }
    }

    public function searchConcepts(Request $request): JsonResponse
    {
        $request->validate([
            "query" => "required|string|min:2",
        ]);

        $query = $request->input("query");

        try {
            // Check if it's a direct Q-ID query
            if (preg_match('/^Q\d+$/', $query)) {
                // Direct query for the entity with that Q-ID
                $response = Http::accept("application/json")->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Request German and English
                        "format" => "json",
                    ]
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

                    $sparqlResponse = Http::accept(
                        "application/sparql-results+json"
                    )->get("https://query.wikidata.org/sparql", [
                        "query" => $sparqlQuery,
                        "format" => "json",
                    ]);

                    if ($sparqlResponse->successful()) {
                        $results =
                            $sparqlResponse->json()["results"]["bindings"] ??
                            [];

                        // If we got results, the entity exists
                        if (!empty($results)) {
                            // Prioritize description: German > English > empty
                            $description = null;
                            if (
                                !empty($entity["descriptions"]["de"]["value"])
                            ) {
                                $description =
                                    $entity["descriptions"]["de"]["value"];
                            } elseif (
                                !empty($entity["descriptions"]["en"]["value"])
                            ) {
                                $description =
                                    $entity["descriptions"]["en"]["value"];
                            }

                            // Prioritize label: German > English > Q-ID
                            $label = $query;
                            if (!empty($entity["labels"]["de"]["value"])) {
                                $label = $entity["labels"]["de"]["value"];
                            } elseif (
                                !empty($entity["labels"]["en"]["value"])
                            ) {
                                $label = $entity["labels"]["en"]["value"];
                            }

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
            $searchResponse = Http::accept("application/json")->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 20, // Get more results
                ]
            );

            if (!$searchResponse->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500
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
                    }, $entityIds)
                ) .
                " }";

            // Query to get entity data with labels and descriptions
            $sparqlQuery = "SELECT ?item ?itemLabel ?itemDescription WHERE {
                $valuesClause
                SERVICE wikibase:label { bd:serviceParam wikibase:language 'de,en'. }
            }";

            $sparqlResponse = Http::accept(
                "application/sparql-results+json"
            )->get("https://query.wikidata.org/sparql", [
                "query" => $sparqlQuery,
                "format" => "json",
            ]);

            if (!$sparqlResponse->successful()) {
                return response()->json(
                    ["error" => "Wikidata SPARQL request failed"],
                    500
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
                    $result["item"]["value"]
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
                $label =
                    $originalResult["label"] ??
                    ($result["itemLabel"]["value"] ?? $entityId);
                $description =
                    $originalResult["description"] ??
                    ($result["itemDescription"]["value"] ?? null);

                $filteredResults[] = [
                    "id" => $entityId,
                    "label" => $label,
                    "description" => $description,
                ];
            }

            return response()->json($filteredResults);
        } catch (\Exception $e) {
            \Log::error("Wikidata concept search error: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to search Wikidata concepts: " .
                        $e->getMessage(),
                ],
                500
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
                $response = Http::accept("application/json")->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Request German and English
                        "format" => "json",
                    ]
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
                                    ]
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
                                    ]
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

                    // Prioritize description: German > English > empty
                    $description = null;
                    if (!empty($entity["descriptions"]["de"]["value"])) {
                        $description = $entity["descriptions"]["de"]["value"];
                    } elseif (!empty($entity["descriptions"]["en"]["value"])) {
                        $description = $entity["descriptions"]["en"]["value"];
                    }

                    // Prioritize label: German > English > Q-ID
                    $label = $query;
                    if (!empty($entity["labels"]["de"]["value"])) {
                        $label = $entity["labels"]["de"]["value"];
                    } elseif (!empty($entity["labels"]["en"]["value"])) {
                        $label = $entity["labels"]["en"]["value"];
                    }

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
            $response = Http::accept("application/json")->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 10,
                ]
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500
                );
            }

            $results = $response->json()["search"] ?? [];

            if (empty($results)) {
                return response()->json([
                    "success" => true,
                    "results" => [],
                ]);
            }

            // We'll need to filter these results to only include books
            // This would be best done by checking if each entity is an instance of book (Q571)
            // But that would require additional API calls for each result
            // For now, we'll return all results and let the frontend handle filtering

            return response()->json([
                "success" => true,
                "results" => $results,
            ]);
        } catch (\Exception $e) {
            \Log::error("Wikidata book search error: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to search Wikidata books: " . $e->getMessage(),
                ],
                500
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
                $response = Http::accept("application/json")->get(
                    "https://www.wikidata.org/w/api.php",
                    [
                        "action" => "wbgetentities",
                        "ids" => $query,
                        "languages" => "de|en", // Request German and English
                        "format" => "json",
                    ]
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
                                    ]
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

                    // Prioritize description: German > English > empty
                    $description = null;
                    if (!empty($entity["descriptions"]["de"]["value"])) {
                        $description = $entity["descriptions"]["de"]["value"];
                    } elseif (!empty($entity["descriptions"]["en"]["value"])) {
                        $description = $entity["descriptions"]["en"]["value"];
                    }

                    // Prioritize label: German > English > Q-ID
                    $label = $query;
                    if (!empty($entity["labels"]["de"]["value"])) {
                        $label = $entity["labels"]["de"]["value"];
                    } elseif (!empty($entity["labels"]["en"]["value"])) {
                        $label = $entity["labels"]["en"]["value"];
                    }

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
            $response = Http::accept("application/json")->get(
                "https://www.wikidata.org/w/api.php",
                [
                    "action" => "wbsearchentities",
                    "search" => $query,
                    "language" => "de",
                    "format" => "json",
                    "type" => "item",
                    "limit" => 10,
                ]
            );

            if (!$response->successful()) {
                return response()->json(
                    ["error" => "Wikidata API request failed"],
                    500
                );
            }

            $results = $response->json()["search"] ?? [];

            if (empty($results)) {
                return response()->json([
                    "success" => true,
                    "results" => [],
                ]);
            }

            // Like with books, filtering to ensure we only return archives would need more API calls
            // For now, we'll return all results and filter on the frontend or refine the search term

            return response()->json([
                "success" => true,
                "results" => $results,
            ]);
        } catch (\Exception $e) {
            \Log::error("Wikidata archive search error: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to search Wikidata archives: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }
}

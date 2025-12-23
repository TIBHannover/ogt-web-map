<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use App\Http\Clients\FactgridClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WikibaseSearchController extends Controller
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
     * Search for entities by title
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByTitle(Request $request): JsonResponse
    {
        $client = $this->getClient();
        $term = $request->input("q", $request->input("title", ""));
        $limit = (int) $request->input("limit", 10);

        if (empty($term)) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Search term is required",
                ],
                400,
            );
        }

        try {
            $results = $client->searchTitles($term, $limit);
            return response()->json([
                "success" => true,
                "results" => $results,
            ]);
        } catch (\Exception $e) {
            Log::error("Error searching by title: " . $e->getMessage(), [
                "term" => $term,
                "limit" => $limit,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error executing search",
                    "error" => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get entity by ID
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getEntityById(string $id): JsonResponse
    {
        $client = $this->getClient();

        if (empty($id)) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Entity ID is required",
                ],
                400,
            );
        }

        try {
            // Check if ID is a valid format (Q followed by numbers)
            if (!preg_match('/^Q\d+$/', $id)) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Invalid entity ID format",
                    ],
                    400,
                );
            }

            $results = $client->searchTitles($id, 1);

            if (empty($results)) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Entity not found",
                    ],
                    404,
                );
            }

            return response()->json([
                "success" => true,
                "entity" => $results[0] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting entity by ID: " . $e->getMessage(), [
                "id" => $id,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error retrieving entity",
                    "error" => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Search for occupations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchOccupations(Request $request): JsonResponse
    {
        return $this->search($request, "occupation");
    }

    /**
     * Search for nationalities
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchNationalities(Request $request): JsonResponse
    {
        return $this->search($request, "nationality");
    }

    /**
     * Search for people
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchPeople(Request $request): JsonResponse
    {
        return $this->search($request, "human");
    }

    /**
     * Search for causes of events
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchCauses(Request $request): JsonResponse
    {
        return $this->search($request, "cause");
    }

    /**
     * Search for books
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchBooks(Request $request): JsonResponse
    {
        return $this->search($request, "book");
    }

    /**
     * Search for archives
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchArchives(Request $request): JsonResponse
    {
        return $this->search($request, "archive");
    }

    /**
     * Search for places
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchPlaces(Request $request): JsonResponse
    {
        return $this->search($request, "place");
    }

    /**
     * Search for commemorates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchCommemorates(Request $request): JsonResponse
    {
        return $this->search($request, "commemorate");
    }

    /**
     * Search for organizations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchOrganizations(Request $request): JsonResponse
    {
        return $this->search($request, "organization");
    }

    /**
     * Search for services
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchServices(Request $request): JsonResponse
    {
        return $this->search($request, "service");
    }

    /**
     * Generic search method
     *
     * @param Request $request
     * @param string $type Type of entities to search for
     * @return JsonResponse
     */
    public function search(Request $request, ?string $type = null): JsonResponse
    {
        $client = $this->getClient();
        $term = $request->input("q", $request->input("query", ""));
        $limit = (int) $request->input("limit", 10);

        if (empty($term)) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Search term is required",
                ],
                400,
            );
        }

        try {
            $searchType = $type ?? $request->input("type");

            // Map the search type to the appropriate query based on the data source
            $results = [];

            // Each data source might have different search mechanisms
            // This is a simplified example - you may need to implement specific search methods
            if (Config::get("wikibase.export_target") === "factgrid") {
                // For FactGrid, we'll just do a basic search for all types
                // and let the client filter as needed
                $results = $client->searchTitles($term, $limit);

                // Log the search for debugging
                Log::info("FactGrid search", [
                    "term" => $term,
                    "type" => $searchType,
                    "results_count" => count($results),
                ]);
            } else {
                // Use Wikidata specific search mechanisms
                $results = $client->searchTitles($term, $limit);
            }

            return response()->json([
                "success" => true,
                "results" => $results,
                "type" => $searchType,
            ]);
        } catch (\Exception $e) {
            Log::error("Error during search: " . $e->getMessage(), [
                "term" => $term,
                "type" => $type,
                "limit" => $limit,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error executing search",
                    "error" => $e->getMessage(),
                ],
                500,
            );
        }
    }
}

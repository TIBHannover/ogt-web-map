<?php

namespace App\Http\Controllers;

use App\Http\Clients\WikidataClient;
use App\Http\Clients\FactgridClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     * @return Response
     */
    public function searchByTitle(Request $request): Response
    {
        $client = $this->getClient();
        $term = $request->input("q", "");
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
     * @return Response
     */
    public function getEntityById(string $id): Response
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
     * @return Response
     */
    public function searchOccupations(Request $request): Response
    {
        return $this->search($request, "occupation");
    }

    /**
     * Search for nationalities
     *
     * @param Request $request
     * @return Response
     */
    public function searchNationalities(Request $request): Response
    {
        return $this->search($request, "nationality");
    }

    /**
     * Search for people
     *
     * @param Request $request
     * @return Response
     */
    public function searchPeople(Request $request): Response
    {
        return $this->search($request, "human");
    }

    /**
     * Search for causes of events
     *
     * @param Request $request
     * @return Response
     */
    public function searchCauses(Request $request): Response
    {
        return $this->search($request, "cause");
    }

    /**
     * Search for books
     *
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function searchBooks(Request $request): Response|JsonResponse
    {
        $client = $this->getClient();
        $query = $request->input("query", "");
        $limit = (int) $request->input("limit", 10);

        if (empty($query)) {
            $json = json_encode([
                "success" => false,
                "message" => "Search term is required",
            ]);
            $response = new Response($json, 400);
            $response->header("Content-Type", "application/json");
            return $response;
        }

        try {
            // Log the search attempt
            \Log::info("FactGrid books search request", [
                "query" => $query,
                "limit" => $limit,
            ]);

            // Get search results
            $results = $client->searchBooks($query, $limit);

            // Debug log the results
            \Log::debug("FactGrid books search controller results", [
                "count" => count($results),
            ]);

            // For compatibility with existing code that expects "success" property
            $responseData = [
                "success" => true,
                "results" => $results,
            ];

            $json = json_encode($responseData);
            $response = new Response($json, 200);
            $response->header("Content-Type", "application/json");
            return $response;
        } catch (\Exception $e) {
            \Log::error("Error searching for books: " . $e->getMessage(), [
                "query" => $query,
                "trace" => $e->getTraceAsString(),
            ]);

            $json = json_encode([
                "success" => false,
                "message" => "Error executing book search",
                "error" => $e->getMessage(),
            ]);
            $response = new Response($json, 200); // Use 200 status to prevent frontend errors
            $response->header("Content-Type", "application/json");
            return $response;
        }
    }

    /**
     * Search for archives
     *
     * @param Request $request
     * @return Response
     */
    public function searchArchives(Request $request): Response
    {
        return $this->search($request, "archive");
    }

    /**
     * Search for places
     *
     * @param Request $request
     * @return Response
     */
    public function searchPlaces(Request $request): Response
    {
        return $this->search($request, "place");
    }

    /**
     * Search for commemorates
     *
     * @param Request $request
     * @return Response
     */
    public function searchCommemorates(Request $request): Response
    {
        return $this->search($request, "commemorate");
    }

    /**
     * Search for organizations
     *
     * @param Request $request
     * @return Response
     */
    public function searchOrganizations(Request $request): Response
    {
        return $this->search($request, "organization");
    }

    /**
     * Search for services
     *
     * @param Request $request
     * @return Response
     */
    public function searchServices(Request $request): Response
    {
        return $this->search($request, "service");
    }

    /**
     * Generic search method
     *
     * @param Request $request
     * @param string $type Type of entities to search for
     * @return Response
     */
    public function search(Request $request, ?string $type = null): Response
    {
        $client = $this->getClient();
        $term = $request->input("q", "");
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
                // Use FactGrid specific search methods based on type
                if ($searchType === "human") {
                    // Example: Special search for people in FactGrid
                    $results = $client->searchTitles(
                        $term . " haswbstatement:P2/Q5",
                        $limit,
                    );
                } else {
                    // Generic search for other types
                    $results = $client->searchTitles($term, $limit);
                }
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

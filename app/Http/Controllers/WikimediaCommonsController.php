<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WikimediaCommonsController extends Controller
{
    /**
     * Suche nach Bildern in Wikimedia Commons
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchImages(Request $request): JsonResponse
    {
        $query = $request->input("query");
        $limit = $request->input("limit", 10);

        if (empty($query)) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Ein Suchbegriff ist erforderlich.",
                ],
                400,
            );
        }

        try {
            Log::info(
                "WikimediaCommonsController: Starting image search for: " .
                    $query,
            );

            // Construct the API URL with the search query
            $apiUrl =
                "https://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo&generator=search&gsrnamespace=6&gsrsearch=" .
                urlencode($query) .
                "&gsrlimit=" .
                $limit .
                "&iiprop=url|canonicaltitle&iiurlwidth=200";

            Log::info(
                "WikimediaCommonsController: Making API request to: " . $apiUrl,
            );

            // Create a stream context with a timeout
            $context = stream_context_create([
                "http" => [
                    "timeout" => 10,
                    "user_agent" => "OGT-API/1.0 (https://www.opfertafel.de)",
                ],
            ]);

            // Make the request
            $responseBody = @file_get_contents($apiUrl, false, $context);

            if ($responseBody === false) {
                Log::error(
                    "WikimediaCommonsController: Failed to get response from Wikimedia API",
                );
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Fehler bei der Verbindung zu Wikimedia Commons.",
                    ],
                    500,
                );
            }

            // Parse the JSON response
            $data = json_decode($responseBody, true);

            if ($data === null) {
                Log::error("WikimediaCommonsController: Invalid JSON response");
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Fehler beim Verarbeiten der Wikimedia-Antwort.",
                    ],
                    500,
                );
            }

            // Log response structure for debugging
            Log::info(
                "WikimediaCommonsController: Response keys: " .
                    implode(", ", array_keys($data)),
            );

            // Check if we have query results
            if (!isset($data["query"]) || !isset($data["query"]["pages"])) {
                Log::info(
                    "WikimediaCommonsController: No query results found for: " .
                        $query,
                );
                return response()->json([
                    "success" => true,
                    "images" => [],
                    "count" => 0,
                ]);
            }

            Log::info(
                "WikimediaCommonsController: Found " .
                    count($data["query"]["pages"]) .
                    " results",
            );

            // Process the images
            $images = [];
            foreach ($data["query"]["pages"] as $pageId => $page) {
                if (isset($page["imageinfo"][0])) {
                    $info = $page["imageinfo"][0];
                    $fileName = str_replace("File:", "", $page["title"] ?? "");

                    // Extract the basic information needed
                    $images[] = [
                        "title" => $fileName,
                        "url" => $info["url"],
                        "thumbUrl" => $info["thumburl"] ?? null,
                    ];
                }
            }

            Log::info(
                "WikimediaCommonsController: Successfully processed " .
                    count($images) .
                    " images",
            );

            return response()->json([
                "success" => true,
                "images" => $images,
                "count" => count($images),
            ]);
        } catch (\Exception $e) {
            Log::error(
                "WikimediaCommonsController: Exception: " . $e->getMessage(),
            );

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Die Bildsuche konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.",
                ],
                500,
            );
        }
    }
}

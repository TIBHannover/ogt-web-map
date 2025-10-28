<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WikibaseEntryRequest;
use App\Services\Wikibase\WikibaseService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Admin-Dashboard anzeigen.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // Statistiken für das Dashboard abrufen
        $stats = [
            "pending_requests" => \App\Models\WikibaseEntryRequest::where(
                "status",
                "pending",
            )->count(),
            "approved_requests" => \App\Models\WikibaseEntryRequest::where(
                "status",
                "approved",
            )->count(),
            "rejected_requests" => \App\Models\WikibaseEntryRequest::where(
                "status",
                "rejected",
            )->count(),
            "error_requests" => \App\Models\WikibaseEntryRequest::where(
                "status",
                "error",
            )->count(),
        ];

        // Letzte Anfragen abrufen
        $latestRequests = \App\Models\WikibaseEntryRequest::orderBy(
            "created_at",
            "desc",
        )
            ->limit(5)
            ->get();

        return view("admin.dashboard", [
            "stats" => $stats,
            "latestRequests" => $latestRequests,
        ]);
    }

    /**
     * Zeigt die Wikidata-Verbindungs-Einstellungen an.
     *
     * @param WikibaseService $wikidataService
     * @return \Illuminate\View\View
     */
    public function wikidataSettings(WikibaseService $wikidataService): View
    {
        $hasCredentials = $wikidataService->hasValidCredentials();

        return view("admin.wikidata-settings", [
            "hasCredentials" => $hasCredentials,
        ]);
    }

    /**
     * Anfragen-Verwaltung anzeigen.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function requests(Request $request): View
    {
        $status = $request->input("status", "all");
        $query = WikibaseEntryRequest::query();

        if ($status !== "all") {
            $query->where("status", $status);
        }

        $requests = $query->orderBy("created_at", "desc")->paginate(10);

        return view("admin.requests", [
            "requests" => $requests,
            "currentStatus" => $status,
        ]);
    }

    /**
     * Benutzer-Verwaltung anzeigen.
     *
     * @return \Illuminate\View\View
     */
    public function users(): View
    {
        // Nur für Super-Admin zugänglich
        if (!Auth::check() || !Auth::user()->is_admin) {
            abort(403, "Unbefugter Zugriff");
        }

        $users = \App\Models\User::orderBy("created_at", "desc")->paginate(10);

        return view("admin.users", ["users" => $users]);
    }

    /**
     * Zeigt die Details einer einzelnen Anfrage.
     *
     * @param int $id Die ID der Anfrage
     * @return \Illuminate\View\View
     */
    public function showRequest($id): View
    {
        $request = WikibaseEntryRequest::findOrFail($id);

        return view("admin.request-detail", [
            "request" => $request,
        ]);
    }

    /**
     * Zeigt das Formular zum Bearbeiten einer Anfrage.
     *
     * @param int $id Die ID der Anfrage
     * @return \Illuminate\View\View
     */
    public function editRequest($id): View
    {
        $request = WikibaseEntryRequest::findOrFail($id);

        return view("admin.request-edit", [
            "request" => $request,
        ]);
    }

    /**
     * Aktualisiert eine Anfrage mit den neuen Daten.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id Die ID der Anfrage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRequest(
        Request $request,
        $id,
    ): RedirectResponse|JsonResponse {
        try {
            $entryRequest = WikibaseEntryRequest::findOrFail($id);

            // Debug information
            \Log::info("Update request data", [
                "request_data" => $request->all(),
                "id" => $id,
                "content_type" => $request->header("Content-Type"),
            ]);

            // Data processing depends on content type
            $data = $request->all();

            \Log::info("Request data received for update", [
                "request_id" => $id,
                "content_type" => $request->header("Content-Type"),
                "has_victims" => isset($data["victims"]),
                "has_perpetrators" => isset($data["perpetrators"]),
            ]);

            // Special handling for victims and perpetrators to preserve IDs
            if (isset($data["victims"])) {
                \Log::info("Received victims data", [
                    "victims" => $data["victims"],
                    "is_array" => is_array($data["victims"]),
                    "is_string" => is_string($data["victims"]),
                ]);

                // If we got a string, try to decode it
                if (is_string($data["victims"])) {
                    try {
                        if (str_starts_with(trim($data["victims"]), "[")) {
                            $data["victims"] =
                                json_decode($data["victims"], true) ?: [];
                            \Log::info("Decoded victims from JSON string", [
                                "count" => count($data["victims"]),
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::warning("Failed to decode victims JSON", [
                            "error" => $e->getMessage(),
                        ]);
                    }
                }
            }

            if (isset($data["perpetrators"])) {
                \Log::info("Received perpetrators data", [
                    "perpetrators" => $data["perpetrators"],
                    "is_array" => is_array($data["perpetrators"]),
                    "is_string" => is_string($data["perpetrators"]),
                ]);

                // If we got a string, try to decode it
                if (is_string($data["perpetrators"])) {
                    try {
                        if (str_starts_with(trim($data["perpetrators"]), "[")) {
                            $data["perpetrators"] =
                                json_decode($data["perpetrators"], true) ?: [];
                            \Log::info(
                                "Decoded perpetrators from JSON string",
                                [
                                    "count" => count($data["perpetrators"]),
                                ],
                            );
                        }
                    } catch (\Exception $e) {
                        \Log::warning("Failed to decode perpetrators JSON", [
                            "error" => $e->getMessage(),
                        ]);
                    }
                }
            }

            // If we're receiving JSON data
            if ($request->header("Content-Type") == "application/json") {
                // Parse array fields from strings if needed (except victims and perpetrators)
                $jsonFields = ["selected_causes", "sources", "coordinates"];

                foreach ($jsonFields as $field) {
                    if (isset($data[$field]) && is_string($data[$field])) {
                        try {
                            $data[$field] = json_decode($data[$field], true);
                            \Log::info("Decoded JSON string {$field}", [
                                "value" => $data[$field],
                            ]);
                        } catch (\Exception $e) {
                            \Log::warning(
                                "Failed to decode JSON string {$field}",
                                [
                                    "error" => $e->getMessage(),
                                    "value" => $data[$field],
                                ],
                            );
                        }
                    }
                }
            } else {
                // Handle form data - similar to before
                $jsonFields = ["selected_causes", "sources"];

                foreach ($jsonFields as $field) {
                    if (isset($data[$field]) && is_string($data[$field])) {
                        try {
                            // Only decode if it's a string and appears to be JSON
                            if (
                                str_starts_with(trim($data[$field]), "[") ||
                                str_starts_with(trim($data[$field]), "{")
                            ) {
                                $data[$field] = json_decode(
                                    $data[$field],
                                    true,
                                );
                                \Log::info("Decoded {$field}", [
                                    "value" => $data[$field],
                                ]);
                            }
                        } catch (\Exception $jsonEx) {
                            \Log::warning("Failed to decode {$field}", [
                                "error" => $jsonEx->getMessage(),
                                "value" => $data[$field],
                            ]);
                        }
                    }
                }

                // Handle coordinates special case
                if (
                    isset($data["coordinates"]) &&
                    is_string($data["coordinates"])
                ) {
                    try {
                        if (
                            str_starts_with(trim($data["coordinates"]), "{") ||
                            str_starts_with(trim($data["coordinates"]), "[")
                        ) {
                            $data["coordinates"] = json_decode(
                                $data["coordinates"],
                                true,
                            );
                            \Log::info("Decoded coordinates", [
                                "value" => $data["coordinates"],
                            ]);
                        }
                    } catch (\Exception $coordEx) {
                        \Log::warning("Failed to decode coordinates", [
                            "error" => $coordEx->getMessage(),
                            "value" => $data["coordinates"],
                        ]);
                    }
                }
            }

            // Handle numeric fields
            $numericFields = [
                "victim_count",
                "survivor_count",
                "death_count",
                "arrest_count",
            ];
            foreach ($numericFields as $field) {
                if (isset($data[$field])) {
                    // Convert to integer for numeric fields
                    $data[$field] = intval($data[$field]);
                    \Log::info("Converted {$field} to integer", [
                        "value" => $data[$field],
                    ]);
                }
            }

            // Handle text fields - ensure they're properly handled as strings
            $textFields = ["title", "description"];
            foreach ($textFields as $field) {
                if (isset($data[$field])) {
                    // Ensure it's a string
                    $data[$field] = (string) $data[$field];
                    \Log::info("Ensured {$field} is string", [
                        "value" => $data[$field],
                    ]);
                }
            }

            // Process victims and perpetrators separately and directly update the model
            if (isset($data["victims"]) && is_array($data["victims"])) {
                // Check all victim IDs and make sure they're preserved
                foreach ($data["victims"] as $index => $victim) {
                    // Create wikidata_id field from id if present
                    if (
                        isset($victim["id"]) &&
                        !isset($victim["wikidata_id"]) &&
                        preg_match('/^Q\d+$/', $victim["id"])
                    ) {
                        $data["victims"][$index]["wikidata_id"] = $victim["id"];
                        \Log::info("Copying victim id to wikidata_id field", [
                            "index" => $index,
                            "id" => $victim["id"],
                        ]);
                    }

                    \Log::info("Processing victim", [
                        "index" => $index,
                        "id" => $victim["id"] ?? "NO_ID",
                        "wikidata_id" =>
                            $victim["wikidata_id"] ??
                            ($victim["id"] ?? "NO_ID"),
                        "data" => $victim,
                    ]);

                    // Make sure the ID is prominently logged
                    if (isset($victim["wikidata_id"])) {
                        \Log::info(
                            "Found victim with Wikidata ID (wikidata_id field): " .
                                $victim["wikidata_id"],
                        );
                    } elseif (
                        isset($victim["id"]) &&
                        preg_match('/^Q\d+$/', $victim["id"])
                    ) {
                        \Log::info(
                            "Found victim with Wikidata ID (id field): " .
                                $victim["id"],
                        );
                    }
                }

                // Set victims directly on the model
                $entryRequest->victims = $data["victims"];
                unset($data["victims"]);
            }

            if (
                isset($data["perpetrators"]) &&
                is_array($data["perpetrators"])
            ) {
                // Check all perpetrator IDs and make sure they're preserved
                foreach ($data["perpetrators"] as $index => $perpetrator) {
                    // Create wikidata_id field from id if present
                    if (
                        isset($perpetrator["id"]) &&
                        !isset($perpetrator["wikidata_id"]) &&
                        preg_match('/^Q\d+$/', $perpetrator["id"])
                    ) {
                        $data["perpetrators"][$index]["wikidata_id"] =
                            $perpetrator["id"];
                        \Log::info(
                            "Copying perpetrator id to wikidata_id field",
                            [
                                "index" => $index,
                                "id" => $perpetrator["id"],
                            ],
                        );
                    }

                    \Log::info("Processing perpetrator", [
                        "index" => $index,
                        "id" => $perpetrator["id"] ?? "NO_ID",
                        "wikidata_id" =>
                            $perpetrator["wikidata_id"] ??
                            ($perpetrator["id"] ?? "NO_ID"),
                        "data" => $perpetrator,
                    ]);

                    // Make sure the ID is prominently logged
                    if (isset($perpetrator["wikidata_id"])) {
                        \Log::info(
                            "Found perpetrator with Wikidata ID (wikidata_id field): " .
                                $perpetrator["wikidata_id"],
                        );
                    } elseif (
                        isset($perpetrator["id"]) &&
                        preg_match('/^Q\d+$/', $perpetrator["id"])
                    ) {
                        \Log::info(
                            "Found perpetrator with Wikidata ID (id field): " .
                                $perpetrator["id"],
                        );
                    }
                }

                // Set perpetrators directly on the model
                $entryRequest->perpetrators = $data["perpetrators"];
                unset($data["perpetrators"]);
            }

            // Remove these fields from jsonFields to prevent double encoding
            $jsonFields = array_diff($jsonFields, ["victims", "perpetrators"]);

            // Re-encode other JSON fields for storage
            foreach ($jsonFields as $field) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = json_encode($data[$field]);
                    \Log::info("Re-encoded {$field} for storage", [
                        "field" => $field,
                    ]);
                }
            }

            // Re-encode coordinates for storage
            if (isset($data["coordinates"]) && is_array($data["coordinates"])) {
                $data["coordinates"] = json_encode($data["coordinates"]);
                \Log::info("Re-encoded coordinates for storage");
            }

            // Use direct DB update for the data array fields
            $updateResult = \DB::table("wikidata_entry_requests")
                ->where("id", $id)
                ->update($data);

            \Log::info("Update result via direct DB query", [
                "result" => $updateResult,
            ]);

            // Now save the model to update the victims and perpetrators fields
            $entryRequest->save();

            \Log::info("Updated entry with victims and perpetrators", [
                "victim_count" => count($entryRequest->victims ?? []),
                "perpetrator_count" => count($entryRequest->perpetrators ?? []),
            ]);

            // Log the actual data saved to confirm IDs were preserved
            if (!empty($entryRequest->victims)) {
                foreach ($entryRequest->victims as $index => $victim) {
                    \Log::info("Saved victim data", [
                        "index" => $index,
                        "id" => $victim["id"] ?? "MISSING_ID",
                        "wikidata_id" =>
                            $victim["wikidata_id"] ?? "MISSING_WIKIDATA_ID",
                        "name" =>
                            $victim["title"] ?? ($victim["label"] ?? "Unknown"),
                    ]);
                }
            }

            if (!empty($entryRequest->perpetrators)) {
                foreach (
                    $entryRequest->perpetrators
                    as $index => $perpetrator
                ) {
                    \Log::info("Saved perpetrator data", [
                        "index" => $index,
                        "id" => $perpetrator["id"] ?? "MISSING_ID",
                        "wikidata_id" =>
                            $perpetrator["wikidata_id"] ??
                            "MISSING_WIKIDATA_ID",
                        "name" =>
                            $perpetrator["title"] ??
                            ($perpetrator["label"] ?? "Unknown"),
                    ]);
                }
            }

            // Refresh the model from the database to ensure we have the latest data
            $entryRequest = WikibaseEntryRequest::findOrFail($id);

            // Verify saved data has the IDs
            if (!empty($entryRequest->victims)) {
                foreach ($entryRequest->victims as $index => $victim) {
                    \Log::info("Victim after save", [
                        "index" => $index,
                        "id" => $victim["id"] ?? "MISSING_ID",
                        "wikidata_id" =>
                            $victim["wikidata_id"] ?? "MISSING_WIKIDATA_ID",
                        "title" =>
                            $victim["title"] ?? ($victim["label"] ?? "Unknown"),
                    ]);
                }
            }

            if (!empty($entryRequest->perpetrators)) {
                foreach (
                    $entryRequest->perpetrators
                    as $index => $perpetrator
                ) {
                    \Log::info("Perpetrator after save", [
                        "index" => $index,
                        "id" => $perpetrator["id"] ?? "MISSING_ID",
                        "wikidata_id" =>
                            $perpetrator["wikidata_id"] ??
                            "MISSING_WIKIDATA_ID",
                        "title" =>
                            $perpetrator["title"] ??
                            ($perpetrator["label"] ?? "Unknown"),
                    ]);
                }
            }

            // Log some fields to verify they were updated
            \Log::info("Field values after update", [
                "title" => $entryRequest->title,
                "description" => $entryRequest->description,
                "victim_count" => $entryRequest->victim_count,
                "survivor_count" => $entryRequest->survivor_count,
                "death_count" => $entryRequest->death_count,
                "arrest_count" => $entryRequest->arrest_count,
            ]);

            \Log::info("Request updated successfully", ["id" => $id]);

            // Return with a cache-busting parameter
            return redirect()
                ->route("admin.requests.show", [
                    "id" => $id,
                    "refresh" => time(),
                ])
                ->with(
                    "success",
                    "Die Anfrage wurde erfolgreich aktualisiert.",
                );
        } catch (\Exception $e) {
            \Log::error("Error updating request", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
                "request_data" => $request->all(),
                "id" => $id,
            ]);

            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
}

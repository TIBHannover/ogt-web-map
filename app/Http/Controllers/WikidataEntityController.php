<?php

namespace App\Http\Controllers;

use App\Http\Controllers\EntryStatusController;
use App\Models\WikibaseEntryRequest;
use App\Jobs\OrchestrateWikidataEntityCreation;
use App\Services\Wikibase\WikibaseFormatterModels;
use App\Services\Wikibase\WikibaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Controller für die Verwaltung von Wikidata-Entitu00e4ten und Anfragen.
 *
 * Diese Klasse ermu00f6glicht das Erstellen, Genehmigen und Ablehnen von Wikidata-Eintru00e4gen.
 */
class WikidataEntityController extends Controller
{
    /**
     * Erstellt eine neue Anfrage für einen Wikidata-Eintrag.
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // Log the incoming request data for debugging
        Log::debug("Incoming entity create request:", [
            "data" => $request->all(),
            "type" => $request->get("type"),
            "is_person" => $request->get("type") === "Q5",
        ]);

        // Daten validieren
        $validated = $request->validate([
            "title" => "required|string|max:250",
            "description" => "required|string|max:750",
            "type" => 'required|string|regex:/^Q\d+$/',
            "coordinates" => "nullable|string",
            "coordinates_qualifier" => "nullable|string",
            "victims" => "nullable|array",
            "victims.*.firstName" => "nullable|string",
            "victims.*.lastName" => "nullable|string",
            "victims.*.title" => "nullable|string",
            "victims.*.description" => "nullable|string",
            "victims.*.type" => "nullable|string",
            "victims.*.role" => "nullable|string",
            "victims.*.gender" => "nullable|array",
            "victims.*.nationality" => "nullable|array",
            "victims.*.place_of_birth" => "nullable|array",
            "victims.*.place_of_death" => "nullable|array",
            "victims.*.place_of_detention" => "nullable|array",
            "victims.*.place_of_residence" => "nullable|array",
            "victims.*.occupation" => "nullable|array",
            "victims.*.employer" => "nullable|array",
            "victims.*.imageUrl" => "nullable|string",
            "victims.*.imageFilename" => "nullable|string",
            "perpetrators" => "nullable|array",
            "perpetrators.*.firstName" => "nullable|string",
            "perpetrators.*.lastName" => "nullable|string",
            "perpetrators.*.title" => "nullable|string",
            "perpetrators.*.description" => "nullable|string",
            "perpetrators.*.type" => "nullable|string",
            "perpetrators.*.role" => "nullable|string",
            "perpetrators.*.gender" => "nullable|array",
            "perpetrators.*.nationality" => "nullable|array",
            "perpetrators.*.place_of_birth" => "nullable|array",
            "perpetrators.*.place_of_death" => "nullable|array",
            "perpetrators.*.place_of_detention" => "nullable|array",
            "perpetrators.*.place_of_residence" => "nullable|array",
            "perpetrators.*.occupation" => "nullable|array",
            "perpetrators.*.employer" => "nullable|array",
            "perpetrators.*.imageUrl" => "nullable|string",
            "perpetrators.*.imageFilename" => "nullable|string",
            "is_standalone_person" => "nullable|boolean",
            "isStandalonePerson" => "nullable|boolean",
            "eventTimeType" => "nullable|string|in:point,range",
            "eventDate" => "nullable|string",
            "eventDateQualifier" => "nullable|string",
            "eventStartDate" => "nullable|string",
            "eventStartDateQualifier" => "nullable|string",
            "eventEndDate" => "nullable|string",
            "eventEndDateQualifier" => "nullable|string",
            "victimCount" => "nullable|integer",
            "survivorCount" => "nullable|integer",
            "deathCount" => "nullable|integer",
            "arrestCount" => "nullable|integer",
            "victimCountQualifier" => "nullable|string",
            "survivorCountQualifier" => "nullable|string",
            "deathCountQualifier" => "nullable|string",
            "arrestCountQualifier" => "nullable|string",
            "cause" => "nullable|string",
            "causes" => "nullable|array",
            "sources" => "nullable|array",
            "imageUrl" => "nullable|string",
            "imageFilename" => "nullable|string",
            "statusToken" => "nullable|string",
            "gender" => "nullable|array|required_array_keys:0",
            "nationality" => "nullable|array",
            "place_of_birth" => "nullable|array",
            "place_of_death" => "nullable|array",
            "place_of_detention" => "nullable|array",
            "place_of_residence" => "nullable|array",
            "employer" => "nullable|array",
            "image" => "nullable|array",
        ]);

        try {
            $entryRequest = new WikibaseEntryRequest();
            $entryRequest->title = $validated["title"];
            $entryRequest->description = $validated["description"];
            $entryRequest->type = $validated["type"];
            $entryRequest->coordinates = $validated["coordinates"] ?? null;
            $entryRequest->coordinates_qualifier =
                $validated["coordinates_qualifier"] ?? null;
            // Handle victims with proper formatting
            if (
                !empty($validated["victims"]) &&
                is_array($validated["victims"])
            ) {
                // Ensure each victim has proper structure
                foreach ($validated["victims"] as &$victim) {
                    // Ensure gender is always an array with objects
                    if (
                        isset($victim["gender"]) &&
                        !is_array($victim["gender"])
                    ) {
                        $victim["gender"] = [["id" => $victim["gender"]]];
                    }
                    if (
                        !isset($victim["title"]) &&
                        isset($victim["firstName"], $victim["lastName"])
                    ) {
                        // Generate title from first and last name if missing
                        $victim["title"] = trim(
                            $victim["firstName"] . " " . $victim["lastName"],
                        );
                    }
                    // Set type as Q5 if not specified
                    if (!isset($victim["type"])) {
                        $victim["type"] = "Q5";
                    }
                    // Set role as victim if not specified
                    if (!isset($victim["role"])) {
                        $victim["role"] = "victim";
                    }

                    // Ensure all array fields are properly formatted
                    $arrayFields = [
                        "nationality",
                        "place_of_birth",
                        "place_of_death",
                        "place_of_detention",
                        "place_of_residence",
                        "occupation",
                        "employer",
                    ];
                    foreach ($arrayFields as $field) {
                        if (
                            isset($victim[$field]) &&
                            !is_array($victim[$field])
                        ) {
                            $victim[$field] = [["id" => $victim[$field]]];
                        } elseif (
                            isset($victim[$field]) &&
                            is_array($victim[$field]) &&
                            isset($victim[$field]["id"])
                        ) {
                            // Convert single object to array of objects
                            $victim[$field] = [$victim[$field]];
                        }
                    }
                }
            }
            $entryRequest->victims = $validated["victims"] ?? null;

            // Handle perpetrators with proper formatting
            if (
                !empty($validated["perpetrators"]) &&
                is_array($validated["perpetrators"])
            ) {
                // Ensure each perpetrator has proper structure
                foreach ($validated["perpetrators"] as &$perpetrator) {
                    if (
                        !isset($perpetrator["title"]) &&
                        isset(
                            $perpetrator["firstName"],
                            $perpetrator["lastName"],
                        )
                    ) {
                        // Generate title from first and last name if missing
                        $perpetrator["title"] = trim(
                            $perpetrator["firstName"] .
                                " " .
                                $perpetrator["lastName"],
                        );
                    }
                    // Set type as Q5 if not specified
                    if (!isset($perpetrator["type"])) {
                        $perpetrator["type"] = "Q5";
                    }
                    // Set role as perpetrator if not specified
                    if (!isset($perpetrator["role"])) {
                        $perpetrator["role"] = "perpetrator";
                    }

                    // Ensure gender is always an array with objects
                    if (
                        isset($perpetrator["gender"]) &&
                        !is_array($perpetrator["gender"])
                    ) {
                        $perpetrator["gender"] = [
                            ["id" => $perpetrator["gender"]],
                        ];
                    }

                    // Ensure all array fields are properly formatted
                    $arrayFields = [
                        "nationality",
                        "place_of_birth",
                        "place_of_death",
                        "place_of_detention",
                        "place_of_residence",
                        "occupation",
                        "employer",
                    ];
                    foreach ($arrayFields as $field) {
                        if (
                            isset($perpetrator[$field]) &&
                            !is_array($perpetrator[$field])
                        ) {
                            $perpetrator[$field] = [
                                ["id" => $perpetrator[$field]],
                            ];
                        } elseif (
                            isset($perpetrator[$field]) &&
                            is_array($perpetrator[$field]) &&
                            isset($perpetrator[$field]["id"])
                        ) {
                            // Convert single object to array of objects
                            $perpetrator[$field] = [$perpetrator[$field]];
                        }
                    }
                }
            }
            $entryRequest->perpetrators = $validated["perpetrators"] ?? null;
            // Allow both is_standalone_person and isStandalonePerson for backward compatibility
            $isStandalone =
                $validated["is_standalone_person"] ??
                ($validated["isStandalonePerson"] ?? false);
            $entryRequest->is_standalone_person = $isStandalone;

            // Ensure gender field is properly formatted
            if (
                isset($validated["gender"]) &&
                !is_array($validated["gender"])
            ) {
                $validated["gender"] = [["id" => $validated["gender"]]];
            } elseif (
                isset($validated["gender"]) &&
                is_array($validated["gender"]) &&
                isset($validated["gender"]["id"])
            ) {
                $validated["gender"] = [$validated["gender"]];
            }
            $entryRequest->gender = $validated["gender"] ?? null;

            // Process other array fields
            $arrayFields = [
                "nationality",
                "place_of_birth",
                "place_of_death",
                "place_of_detention",
                "place_of_residence",
                "occupation",
                "employer",
            ];
            foreach ($arrayFields as $field) {
                if (
                    isset($validated[$field]) &&
                    !is_array($validated[$field])
                ) {
                    $validated[$field] = [["id" => $validated[$field]]];
                } elseif (
                    isset($validated[$field]) &&
                    is_array($validated[$field]) &&
                    isset($validated[$field]["id"])
                ) {
                    $validated[$field] = [$validated[$field]];
                }
            }

            $entryRequest->nationality = $validated["nationality"] ?? null;
            $entryRequest->place_of_birth =
                $validated["place_of_birth"] ?? null;
            $entryRequest->place_of_death =
                $validated["place_of_death"] ?? null;
            $entryRequest->place_of_detention =
                $validated["place_of_detention"] ?? null;

            // Zeitpunkt oder Zeitraum je nach Typ
            if (isset($validated["eventTimeType"])) {
                if ($validated["eventTimeType"] === "point") {
                    $entryRequest->time_point = $validated["eventDate"] ?? null;
                    $entryRequest->time_point_qualifier =
                        $validated["eventDateQualifier"] ?? null;
                } elseif ($validated["eventTimeType"] === "range") {
                    $entryRequest->time_period_start =
                        $validated["eventStartDate"] ?? null;
                    $entryRequest->time_period_start_qualifier =
                        $validated["eventStartDateQualifier"] ?? null;
                    $entryRequest->time_period_end =
                        $validated["eventEndDate"] ?? null;
                    $entryRequest->time_period_end_qualifier =
                        $validated["eventEndDateQualifier"] ?? null;
                }
            }

            // Statistiken
            $entryRequest->victim_count = $validated["victimCount"] ?? null;
            $entryRequest->survivor_count = $validated["survivorCount"] ?? null;
            $entryRequest->death_count = $validated["deathCount"] ?? null;
            $entryRequest->arrest_count = $validated["arrestCount"] ?? null;

            // Qualifier für Statistiken
            $entryRequest->victim_count_qualifier =
                $validated["victimCountQualifier"] ?? null;
            $entryRequest->survivor_count_qualifier =
                $validated["survivorCountQualifier"] ?? null;
            $entryRequest->death_count_qualifier =
                $validated["deathCountQualifier"] ?? null;
            $entryRequest->arrest_count_qualifier =
                $validated["arrestCountQualifier"] ?? null;

            // Debug-Ausgabe für Qualifier
            Log::debug("Qualifier-Werte", [
                "coordinates_qualifier" =>
                    $validated["coordinates_qualifier"] ?? "nicht gesetzt",
                "victim_qualifier" =>
                    $validated["victimCountQualifier"] ?? "nicht gesetzt",
                "survivor_qualifier" =>
                    $validated["survivorCountQualifier"] ?? "nicht gesetzt",
                "death_qualifier" =>
                    $validated["deathCountQualifier"] ?? "nicht gesetzt",
                "arrest_qualifier" =>
                    $validated["arrestCountQualifier"] ?? "nicht gesetzt",
            ]);

            // Ursache
            $entryRequest->cause = $validated["cause"] ?? null;
            // Speichern der ausgewählten Ursachen
            if (!empty($validated["causes"])) {
                $entryRequest->selected_causes = json_encode(
                    $validated["causes"],
                );
            }

            // Quellen - Clean up before storing
            if (!empty($validated["sources"])) {
                $formatter = app(
                    \App\Services\Wikibase\WikibaseFormatterModels::class,
                );
                $validated["sources"] = $formatter->cleanupSourceData(
                    $validated["sources"],
                );
            }
            $entryRequest->sources = $validated["sources"] ?? null;

            // Bildinformationen
            $entryRequest->image_url = $validated["imageUrl"] ?? null;
            $entryRequest->image_filename = $validated["imageFilename"] ?? null;

            $entryRequest->status = "pending";

            // If this is a person entry, handle person data specifically
            if ($validated["type"] === "Q5") {
                // Make sure is_standalone_person is set correctly
                if ($entryRequest->victims || $entryRequest->perpetrators) {
                    $entryRequest->is_standalone_person = false;
                    Log::debug(
                        "Setting is_standalone_person to false because victims or perpetrators are present",
                        [
                            "victims" => $entryRequest->victims,
                            "perpetrators" => $entryRequest->perpetrators,
                        ],
                    );
                }

                // For Q5 entities, always ensure person data is only in victims/perpetrators arrays, not at root level
                // Explicitly set root person fields to null
                $entryRequest->gender = null;
                $entryRequest->nationality = null;
                $entryRequest->place_of_birth = null;
                $entryRequest->place_of_death = null;
                $entryRequest->place_of_detention = null;
                $entryRequest->place_of_residence = null;
                $entryRequest->occupation = null;
                $entryRequest->employer = null;

                // Log this decision
                Log::debug(
                    "Person entity (Q5): Setting all root person fields to null to ensure data is only in victims/perpetrators arrays",
                );

                // For Q5 entities, make sure we have either victims or perpetrators array populated
                // We don't need to copy data to root fields as those are now explicitly set to null

                if (
                    !empty($validated["victims"]) &&
                    is_array($validated["victims"])
                ) {
                    Log::debug("Person entity has victims array data:", [
                        "count" => count($validated["victims"]),
                        "first_victim" => $validated["victims"][0] ?? null,
                    ]);
                }

                if (
                    !empty($validated["perpetrators"]) &&
                    is_array($validated["perpetrators"])
                ) {
                    Log::debug("Person entity has perpetrators array data:", [
                        "count" => count($validated["perpetrators"]),
                        "first_perpetrator" =>
                            $validated["perpetrators"][0] ?? null,
                    ]);
                }

                // If this is a standalone person with no role specified, ensure it's in one of the arrays
                if (
                    $entryRequest->is_standalone_person &&
                    empty($validated["victims"]) &&
                    empty($validated["perpetrators"])
                ) {
                    Log::debug(
                        "Standalone person with no role - defaulting to victims array",
                    );

                    // Create a default person object based on the entry title/description
                    $personObj = [
                        "firstName" => "",
                        "lastName" => $validated["title"] ?? "",
                        "description" => $validated["description"] ?? "",
                        "role" => "victim",
                        "type" => "Q5",
                    ];

                    // Store in victims array
                    $entryRequest->victims = [$personObj];
                }
            }

            // Initialize status history
            $statusHistory = [
                [
                    "status" => "pending",
                    "date" => now(),
                    "comment" => "Eintrag wurde eingereicht.",
                ],
            ];
            $entryRequest->status_history = $statusHistory;
            $entryRequest->status_updated_at = now();

            // Store the status token if provided
            if (!empty($validated["statusToken"])) {
                $entryRequest->status_token = $validated["statusToken"];
            } else {
                // Generate a random token if none is provided
                $entryRequest->status_token = bin2hex(random_bytes(24));
            }

            $entryRequest->submitted_by = "anonymous";
            $entryRequest->save();

            // Send notification to admin about new entry request
            try {
                $entryStatusController = new EntryStatusController();
                $entryStatusController->notifyAdmin($entryRequest);
            } catch (\Exception $e) {
                Log::error(
                    "Failed to send admin notification: " . $e->getMessage(),
                );
                // Continue anyway, don't block the process if email fails
            }

            // Erfolgreiche Antwort
            return response()->json(
                [
                    "success" => true,
                    "requestId" => $entryRequest->status_token,
                    "message" =>
                        "Eintrag wurde zur Prüsfung eingereicht und wird nach Freigabe erstellt.",
                ],
                Response::HTTP_CREATED,
            );
        } catch (\Exception $e) {
            Log::error("Wikidata Entry Request Error: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim Speichern der Anfrage: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Gibt eine Liste aller ausstehenden Anfragen zuru00fcck.
     *
     * @return JsonResponse
     */
    public function getPendingRequests(Request $request): JsonResponse
    {
        try {
            $status = $request->input("status", "pending");
            $perPage = $request->input("perPage", 10);
            $page = $request->input("page", 1);

            // Filtere nach Status
            $query = WikibaseEntryRequest::where("status", $status);

            // Sortiere nach Erstellungsdatum (neueste zuerst)
            $query->orderBy("created_at", "desc");

            // Paginierung anwenden
            if ($perPage > 0) {
                $requests = $query->paginate($perPage, ["*"], "page", $page);
            } else {
                $requests = $query->get();
            }

            return response()->json(
                [
                    "success" => true,
                    "requests" => $requests,
                ],
                Response::HTTP_OK,
            );
        } catch (\Exception $e) {
            Log::error("Error getting requests: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim Abrufen der Anfragen: " . $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Genehmigt eine Anfrage und erstellt den entsprechenden Wikidata-Eintrag.
     *
     * @param  Request  $request
     * @param  int  $id
     *
     * @return JsonResponse
     */
    public function approveRequest(Request $request, int $id): JsonResponse
    {
        try {
            $entryRequest = WikibaseEntryRequest::findOrFail($id);
            $statusController = app(
                \App\Http\Controllers\EntryStatusController::class,
            );

            // Pru00fcfen, ob die Anfrage bereits genehmigt wurde
            if ($entryRequest->status === "approved") {
                if ($request->has("redirect")) {
                    return response()->json(
                        [
                            "redirect" => $request->input("redirect"),
                            "success" => false,
                            "message" =>
                                "Diese Anfrage wurde bereits genehmigt.",
                        ],
                        Response::HTTP_BAD_REQUEST,
                    );
                }

                return response()->json(
                    [
                        "success" => false,
                        "message" => "Diese Anfrage wurde bereits genehmigt.",
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Anfrage genehmigen und Status-History aktualisieren
            $comment = $request->input("comment", "Eintrag wurde genehmigt.");

            // Use the EntryStatusController to update status history
            $statusController->updateStatusHistory(
                $entryRequest,
                "approved",
                $comment,
            );

            $entryRequest->approved_by = "admin"; // Spu00e4ter durch authentifizierten Benutzer ersetzen
            $entryRequest->save();

            // Job in die Queue stellen, um Wikidata-Eintrag asynchron zu erstellen
            // Add callback URL for updating status when the entity is created
            // Use the Docker internal URL instead of the public URL to ensure the callback works from within Docker
            $callbackUrl =
                "http://host.docker.internal:8000/api/wikidata/entity-callback";

            // Fallback to configured app URL if available and we're not in development mode
            if (
                config("app.env") !== "local" &&
                config("app.url") &&
                config("app.url") !== "http://localhost"
            ) {
                $appUrl = config("app.url");
                $callbackUrl =
                    rtrim($appUrl, "/") . "/api/wikidata/entity-callback";
            }

            // Log the callback URL for debugging
            \Illuminate\Support\Facades\Log::info(
                "Using callback URL: " . $callbackUrl,
            );

            // Check if we should send an email notification to the user
            if ($entryRequest->email && $entryRequest->notify_on_publish) {
                \Illuminate\Support\Facades\Log::info(
                    "User has opted in for notifications. Will send email when the entry is published.",
                    ["email" => $entryRequest->email],
                );
            }

            // Make sure any formatter instance has debug mode disabled
            $formatter = app(
                \App\Services\Wikibase\WikibaseFormatterModels::class,
            );
            if (method_exists($formatter, "setDebugMode")) {
                $formatter->setDebugMode(false);
                Log::info(
                    "Debug mode explicitly disabled before dispatching CreateWikidataEntity job",
                );
            }

            // Pre-check if this entity might already exist in Wikidata
            // This prevents duplicate creation attempts and provides better user feedback
            $existingEntityCheck = $this->checkForExistingEntity($entryRequest);
            if ($existingEntityCheck["isDuplicate"]) {
                // Update status and return early
                $errorMessage = $existingEntityCheck["message"];
                $existingEntityId = $existingEntityCheck["entityId"];

                // Update the request status to error (for duplicates)
                // Note: We use "error" instead of a more specific status like "duplicate"
                // because SQLite CHECK constraints only allow specific status values
                // (pending, approved, rejected, error, published)
                $entryRequest->status = "error";
                $entryRequest->rejection_reason = $errorMessage;
                $entryRequest->wikidata_id = $existingEntityId; // Store the existing ID for reference
                $entryRequest->status_updated_at = now();
                $entryRequest->save();

                // Return success with additional info about the duplicate
                if ($request->has("redirect")) {
                    return response()->json([
                        "redirect" => $request->input("redirect"),
                        "success" => false,
                        "message" =>
                            "Entität existiert bereits in Wikidata mit ID: " .
                            $existingEntityId,
                        "existing_entity_id" => $existingEntityId,
                        "wikidata_url" =>
                            "https://www.wikidata.org/wiki/" .
                            $existingEntityId,
                    ]);
                }

                return response()->json([
                    "success" => false,
                    "message" =>
                        "Entität existiert bereits in Wikidata mit ID: " .
                        $existingEntityId,
                    "existing_entity_id" => $existingEntityId,
                    "wikidata_url" =>
                        "https://www.wikidata.org/wiki/" . $existingEntityId,
                ]);
            }

            // No duplicate found, proceed with normal dispatch
            // The job will update the status to "published" upon successful completion
            // (we use "published" instead of "wikidata_published" due to SQLite constraints)
            Log::info("No duplicate entity found, dispatching creation job", [
                "request_id" => $entryRequest->id,
                "title" => $entryRequest->title,
                "type" => $entryRequest->type,
            ]);

            \App\Jobs\CreateWikidataEntity::dispatch(
                $entryRequest->toArray(),
                $entryRequest->type,
                null, // parent entity ID
                null, // relationship type
                $entryRequest->id, // request ID for direct status updates
            );

            // Log the job dispatch with request ID for debugging
            Log::info("Dispatched CreateWikidataEntity job for request", [
                "request_id" => $entryRequest->id,
                "type" => $entryRequest->type,
                "status" => $entryRequest->status,
            ]);

            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => true,
                        "message" =>
                            "Anfrage genehmigt und Wikidata-Eintrag zur Queue hinzugefügt.",
                    ],
                    Response::HTTP_OK,
                );
            }

            return response()->json(
                [
                    "success" => true,
                    "message" =>
                        "Anfrage genehmigt und Wikidata-Eintrag zur Queue hinzugefügt.",
                ],
                Response::HTTP_OK,
            );
        } catch (\Exception $e) {
            Log::error("Error approving request: " . $e->getMessage());

            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => false,
                        "message" =>
                            "Fehler bei der Genehmigung der Anfrage: " .
                            $e->getMessage(),
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                );
            }

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler bei der Genehmigung der Anfrage: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Lehnt eine Anfrage ab.
     *
     * @param  Request  $request
     * @param  int  $id
     *
     * @return JsonResponse
     */
    public function rejectRequest(Request $request, int $id): JsonResponse
    {
        // Validieren Sie die Eingaben
        $validator = Validator::make($request->all(), [
            "reason" => "required|string|max:1000",
        ]);

        if ($validator->fails()) {
            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => false,
                        "message" => "Ungültige Eingabe",
                        "errors" => $validator->errors(),
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            return response()->json(
                [
                    "success" => false,
                    "message" => "Ungültige Eingabe",
                    "errors" => $validator->errors(),
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $entryRequest = WikibaseEntryRequest::findOrFail($id);

            // Pru00fcfen, ob die Anfrage bereits abgelehnt wurde
            if ($entryRequest->status === "rejected") {
                if ($request->has("redirect")) {
                    return response()->json(
                        [
                            "redirect" => $request->input("redirect"),
                            "success" => false,
                            "message" =>
                                "Diese Anfrage wurde bereits abgelehnt.",
                        ],
                        Response::HTTP_BAD_REQUEST,
                    );
                }

                return response()->json(
                    [
                        "success" => false,
                        "message" => "Diese Anfrage wurde bereits abgelehnt.",
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Anfrage ablehnen und Status-History aktualisieren
            $comment = $request->input("reason", "Eintrag wurde abgelehnt.");

            // Use the EntryStatusController to update status history
            app(
                \App\Http\Controllers\EntryStatusController::class,
            )->updateStatusHistory($entryRequest, "rejected", $comment);

            $entryRequest->rejected_by = "admin"; // Spu00e4ter durch authentifizierten Benutzer ersetzen
            $entryRequest->rejection_reason = $request->input("reason");
            $entryRequest->save();

            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => true,
                        "message" => "Anfrage erfolgreich abgelehnt.",
                    ],
                    Response::HTTP_OK,
                );
            }

            return response()->json(
                [
                    "success" => true,
                    "message" => "Anfrage erfolgreich abgelehnt.",
                ],
                Response::HTTP_OK,
            );
        } catch (\Exception $e) {
            Log::error("Error rejecting request: " . $e->getMessage());

            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => false,
                        "message" =>
                            "Fehler bei der Ablehnung der Anfrage: " .
                            $e->getMessage(),
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                );
            }

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler bei der Ablehnung der Anfrage: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Fu00fchrt eine erneute Verarbeitung einer fehlgeschlagenen Anfrage durch.
     *
     * @param  Request  $request
     * @param  int  $id  The ID of the entry request to retry
     *
     * @return JsonResponse
     */
    public function retryRequest(Request $request, $id): JsonResponse
    {
        try {
            $entryRequest = WikibaseEntryRequest::findOrFail($id);

            // Prüfen, ob die Anfrage im Status "error" oder "wikidata_failed" ist
            if (
                $entryRequest->status !== "error" &&
                $entryRequest->status !== "wikidata_failed"
            ) {
                if ($request->has("redirect")) {
                    return response()->json(
                        [
                            "redirect" => $request->input("redirect"),
                            "success" => false,
                            "message" =>
                                "Nur Anfragen mit dem Status 'error' oder 'wikidata_failed' können erneut verarbeitet werden.",
                        ],
                        Response::HTTP_BAD_REQUEST,
                    );
                }

                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Nur Anfragen mit dem Status 'error' oder 'wikidata_failed' können erneut verarbeitet werden.",
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Status aktualisieren und Status-History aktualisieren
            $comment = "Eintrag wurde erneut zur Verarbeitung eingereicht.";

            // Use the EntryStatusController to update status history
            app(
                \App\Http\Controllers\EntryStatusController::class,
            )->updateStatusHistory($entryRequest, "pending", $comment);

            $entryRequest->error_message = null; // Fehler zurücksetzen
            $entryRequest->status = "pending"; // Status auf pending setzen
            $entryRequest->save();

            // Job in die Queue stellen, um Wikidata-Eintrag asynchron zu erstellen
            // Use the Docker internal URL instead of the public URL to ensure the callback works from within Docker
            $callbackUrl =
                "http://host.docker.internal:8000/api/wikidata/entity-callback";
            // Direct status update approach - no callback URL needed
            \App\Jobs\CreateWikidataEntity::dispatch(
                $entryRequest->toArray(),
                $entryRequest->type,
                null, // parent entity ID
                null, // relationship type
                (int) $entryRequest->id, // request ID for direct status updates (ensure it's an integer)
            );

            // Log the job dispatch with request ID for debugging
            Log::info("Dispatched CreateWikidataEntity job for retry request", [
                "request_id" => $entryRequest->id,
                "type" => $entryRequest->type,
                "status" => $entryRequest->status,
            ]);

            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => true,
                        "message" =>
                            "Anfrage wurde zur erneuten Verarbeitung eingereicht und zur Queue hinzugefu00fcgt.",
                    ],
                    Response::HTTP_OK,
                );
            }

            return response()->json(
                [
                    "success" => true,
                    "message" =>
                        "Anfrage wurde zur erneuten Verarbeitung eingereicht und zur Queue hinzugefu00fcgt.",
                ],
                Response::HTTP_OK,
            );
        } catch (\Exception $e) {
            Log::error("Error retrying request: " . $e->getMessage());

            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => false,
                        "message" =>
                            "Fehler bei der erneuten Verarbeitung: " .
                            $e->getMessage(),
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                );
            }

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler bei der erneuten Verarbeitung: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Gibt Details zu einer Anfrage zuru00fcck.
     *
     * @param int $id ID der Anfrage
     * @return JsonResponse
     */
    public function getRequest(int $id): JsonResponse
    {
        try {
            $entryRequest = WikibaseEntryRequest::findOrFail($id);

            return response()->json(
                [
                    "success" => true,
                    "request" => $entryRequest,
                ],
                Response::HTTP_OK,
            );
        } catch (\Exception $e) {
            Log::error("Error getting request: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim Abrufen der Anfrage: " . $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Zieht die Genehmigung eines Eintrags zuru00fcck und markiert ihn als abgelehnt.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function retractRequest(Request $request, int $id): JsonResponse
    {
        // Validieren Sie die Eingaben
        $validator = Validator::make($request->all(), [
            "reason" => "required|string|max:1000",
        ]);

        if ($validator->fails()) {
            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => false,
                        "message" => "Ungu00fcltige Eingabe",
                        "errors" => $validator->errors(),
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            return response()->json(
                [
                    "success" => false,
                    "message" => "Ungu00fcltige Eingabe",
                    "errors" => $validator->errors(),
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $entryRequest = WikibaseEntryRequest::findOrFail($id);

            // Pru00fcfen, ob die Anfrage genehmigt wurde
            if ($entryRequest->status !== "approved") {
                if ($request->has("redirect")) {
                    return response()->json(
                        [
                            "redirect" => $request->input("redirect"),
                            "success" => false,
                            "message" =>
                                "Nur genehmigte Anfragen können zurückgezogen werden.",
                        ],
                        Response::HTTP_BAD_REQUEST,
                    );
                }

                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Nur genehmigte Anfragen können zurückgezogen werden.",
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Anfrage zuru00fcckziehen und als abgelehnt markieren
            $comment =
                "Genehmigung zurückgezogen: " . $request->input("reason");

            // Use the EntryStatusController to update status history
            app(
                \App\Http\Controllers\EntryStatusController::class,
            )->updateStatusHistory($entryRequest, "rejected", $comment);

            // Aktualisiere den Status und setze den Ablehnungsgrund
            $entryRequest->status = "rejected";
            $entryRequest->rejected_by = auth()->check()
                ? auth()->user()->name
                : "admin";
            $entryRequest->rejection_reason = $request->input("reason");
            $entryRequest->save();

            // Log the retraction
            Log::info("Approval retracted for entry", [
                "id" => $entryRequest->id,
                "title" => $entryRequest->title,
                "wikidata_id" => $entryRequest->wikidata_id,
                "reason" => $request->input("reason"),
                "user" => auth()->check() ? auth()->user()->name : "admin",
            ]);

            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => true,
                        "message" =>
                            "Genehmigung erfolgreich zurückgezogen. Der Eintrag wurde als abgelehnt markiert.",
                    ],
                    Response::HTTP_OK,
                );
            }

            return response()->json(
                [
                    "success" => true,
                    "message" =>
                        "Genehmigung erfolgreich zurückgezogen. Der Eintrag wurde als abgelehnt markiert.",
                ],
                Response::HTTP_OK,
            );
        } catch (\Exception $e) {
            Log::error("Error retracting approval: " . $e->getMessage());

            if ($request->has("redirect")) {
                return response()->json(
                    [
                        "redirect" => $request->input("redirect"),
                        "success" => false,
                        "message" =>
                            "Fehler beim Zuru00fcckziehen der Genehmigung: " .
                            $e->getMessage(),
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                );
            }

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim Zuru00fcckziehen der Genehmigung: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Aktualisiert das Bild eines Eintrags.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateImage(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "image_url" => "required|string|url",
            "image_filename" => "required|string",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Ungültige Bilddaten",
                    "errors" => $validator->errors(),
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $entryRequest = WikibaseEntryRequest::findOrFail($id);

            // Bild aktualisieren
            $entryRequest->image_url = $request->input("image_url");
            $entryRequest->image_filename = $request->input("image_filename");
            $entryRequest->save();

            return response()->json(
                [
                    "success" => true,
                    "message" => "Bild erfolgreich aktualisiert",
                    "image_url" => $entryRequest->image_url,
                    "image_filename" => $entryRequest->image_filename,
                ],
                Response::HTTP_OK,
            );
        } catch (\Exception $e) {
            Log::error("Error updating image: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim Aktualisieren des Bildes: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    private function getBotToken()
    {
        // In einer realen Anwendung wu00fcrden Sie Ihre Anmeldedaten sicher speichern
        // und Oauth oder einen anderen sicheren Authentifizierungsmechanismus verwenden
        // Dies ist nur zu Demonstrationszwecken

        $loginApiUrl = "https://test.wikidata.org/w/api.php";

        $loginToken = null;
        $csrfToken = null;
        $client = new \GuzzleHttp\Client([
            "cookies" => true,
            "verify" => true, // Nur für Tests, in der Produktion nicht verwenden!
        ]);

        try {
            // 1. Logintoken erhalten
            $response = $client->request("GET", $loginApiUrl, [
                "query" => [
                    "action" => "query",
                    "meta" => "tokens",
                    "type" => "login",
                    "format" => "json",
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $loginToken = $data["query"]["tokens"]["logintoken"] ?? null;

            if (!$loginToken) {
                throw new \Exception("Konnte kein Login-Token erhalten");
            }

            // 2. Einloggen
            $response = $client->request("POST", $loginApiUrl, [
                "form_params" => [
                    "action" => "login",
                    "lgname" => env("WIKIDATA_USERNAME"),
                    "lgpassword" => env("WIKIDATA_PASSWORD"),
                    "lgtoken" => $loginToken,
                    "format" => "json",
                ],
            ]);

            $loginResult = json_decode($response->getBody(), true);
            if ($loginResult["login"]["result"] !== "Success") {
                throw new \Exception(
                    "Login fehlgeschlagen: " .
                        ($loginResult["login"]["reason"] ??
                            "Unbekannter Fehler"),
                );
            }

            // 3. CSRF-Token erhalten
            $response = $client->request("GET", $loginApiUrl, [
                "query" => [
                    "action" => "query",
                    "meta" => "tokens",
                    "format" => "json",
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $csrfToken = $data["query"]["tokens"]["csrftoken"] ?? null;

            if (!$csrfToken) {
                throw new \Exception("Konnte kein CSRF-Token erhalten");
            }

            return [
                "client" => $client,
                "csrf_token" => $csrfToken,
                "api_url" => $loginApiUrl,
            ];
        } catch (\Exception $e) {
            Log::error(
                "Wikidata API Authentifizierungsfehler: " . $e->getMessage(),
            );
            return null;
        }
    }

    /**
     * Bereitet die Daten für die Erstellung einer Wikidata-Entitu00e4t vor.
     *
     * @param WikibaseEntryRequest $entryRequest
     * @return array
     */
    private function prepareEntityData(
        WikibaseEntryRequest $entryRequest,
    ): array {
        // Wir erstellen ein Label und eine Beschreibung in Deutsch
        $label = [
            "de" => $entryRequest->title,
        ];

        $description = [
            "de" => $entryRequest->description,
        ];

        // Statements (Aussagen) für die Entitu00e4t vorbereiten
        $statements = [
            // Instanz von (P31) - je nach Typ des Eintrags
            "P31" => [
                [
                    "value" => [
                        "entity-type" => "item",
                        "id" => $entryRequest->type, // Q6983405 für Ereignis, etc.
                    ],
                    "type" => "wikibase-entityid",
                ],
            ],
        ];

        // Ort/Koordinaten hinzufu00fcgen, falls vorhanden
        if ($entryRequest->coordinates) {
            [$lat, $lon] = explode(",", $entryRequest->coordinates);
            $statements["P625"] = [
                [
                    "value" => [
                        "latitude" => (float) trim($lat),
                        "longitude" => (float) trim($lon),
                        "precision" => 0.000001,
                        "globe" => "http://www.wikidata.org/entity/Q2",
                    ],
                    "type" => "globecoordinate",
                ],
            ];
        }

        // Zeitpunkt/Zeitraum hinzufu00fcgen
        if ($entryRequest->time_point) {
            // Zeitpunkt (P585)
            $statements["P585"] = [
                [
                    "value" => [
                        "time" =>
                            "+" . $entryRequest->time_point . "T00:00:00Z",
                        "precision" => $this->getDatePrecision(
                            $entryRequest->time_point,
                        ),
                        "before" => 0,
                        "after" => 0,
                        "timezone" => 0,
                        "calendarmodel" =>
                            "http://www.wikidata.org/entity/Q1985727",
                    ],
                    "type" => "time",
                ],
            ];
        } elseif ($entryRequest->time_period_start) {
            // Anfangszeitpunkt (P580)
            $statements["P580"] = [
                [
                    "value" => [
                        "time" =>
                            "+" .
                            $entryRequest->time_period_start .
                            "T00:00:00Z",
                        "precision" => $this->getDatePrecision(
                            $entryRequest->time_period_start,
                        ),
                        "before" => 0,
                        "after" => 0,
                        "timezone" => 0,
                        "calendarmodel" =>
                            "http://www.wikidata.org/entity/Q1985727",
                    ],
                    "type" => "time",
                ],
            ];

            // Endzeitpunkt (P582), falls vorhanden
            if ($entryRequest->time_period_end) {
                $statements["P582"] = [
                    [
                        "value" => [
                            "time" =>
                                "+" .
                                $entryRequest->time_period_end .
                                "T00:00:00Z",
                            "precision" => $this->getDatePrecision(
                                $entryRequest->time_period_end,
                            ),
                            "before" => 0,
                            "after" => 0,
                            "timezone" => 0,
                            "calendarmodel" =>
                                "http://www.wikidata.org/entity/Q1985727",
                        ],
                        "type" => "time",
                    ],
                ];
            }
        }
    }

    /**
     * Check if an entity might already exist in Wikidata
     * This performs a preliminary check before dispatching the job
     *
     * @param WikibaseEntryRequest $entryRequest The entry request to check
     * @return array Result with isDuplicate flag, message, and entityId if found
     */
    private function checkForExistingEntity($entryRequest): array
    {
        try {
            // Default result structure
            $result = [
                "isDuplicate" => false,
                "message" => "",
                "entityId" => null,
            ];

            // Get Wikibase service
            $wikibaseService = app(WikibaseService::class);

            // Log the search attempt
            Log::info("Checking for existing entity before creation", [
                "title" => $entryRequest->title,
                "type" => $entryRequest->type,
                "id" => $entryRequest->id,
            ]);

            // Check if there's an entity with the same label and description
            $searchResults = $wikibaseService->searchEntities(
                $entryRequest->title,
                "de", // German language code
                $entryRequest->type,
            );

            // Handle case when searchEntities returns null
            if ($searchResults === null) {
                Log::warning("Entity search returned null result", [
                    "title" => $entryRequest->title,
                    "type" => $entryRequest->type,
                ]);
                return $result; // Return default (not duplicate) result
            }

            if (!empty($searchResults) && isset($searchResults["search"])) {
                foreach ($searchResults["search"] as $entity) {
                    // If we find an exact match for title (label)
                    if (
                        Str::lower($entity["label"]) ===
                        Str::lower($entryRequest->title)
                    ) {
                        // Entity might be a duplicate
                        $result["isDuplicate"] = true;
                        $result["entityId"] = $entity["id"];
                        $result["message"] =
                            "Entity already exists in Wikidata with ID: " .
                            $entity["id"] .
                            ". You can view it at https://www.wikidata.org/wiki/" .
                            $entity["id"];

                        // Log the duplicate detection
                        Log::info(
                            "Potential duplicate entity detected during pre-check",
                            [
                                "entity_title" => $entryRequest->title,
                                "existing_id" => $entity["id"],
                                "request_id" => $entryRequest->id,
                            ],
                        );

                        break;
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            // If anything goes wrong, log it but don't block the job
            Log::warning(
                "Error in pre-checking for duplicate entity: " .
                    $e->getMessage(),
            );

            // Log detailed error information
            Log::error("Exception in checkForExistingEntity", [
                "exception" => get_class($e),
                "message" => $e->getMessage(),
                "title" => $entryRequest->title ?? "unknown",
                "type" => $entryRequest->type ?? "unknown",
                "request_id" => $entryRequest->id ?? "unknown",
                "trace" => $e->getTraceAsString(),
            ]);

            return [
                "isDuplicate" => false,
                "message" => "",
                "entityId" => null,
            ];
        }
    }

    /**
     * Create a new Wikidata entity using the orchestrated job approach
     *
     * @param Request $request The HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createEntity(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            "entity_data" => "required|array",
            "entity_type" => "required|string",
            "callback_url" => "nullable|url",
        ]);

        try {
            // Extract the data from the request
            $entityData = $validated["entity_data"];
            $entityType = $validated["entity_type"];

            // Create a new request record in the database to track this entity creation
            $requestModel = config(
                "wikidata.request_model",
                "\\App\\Models\\WikibaseEntryRequest",
            );

            $request = new $requestModel();
            $request->status = "pending";
            $request->entity_data = json_encode($entityData);
            $request->entity_type = $entityType;
            $request->save();

            // Dispatch the orchestration job
            $job = new OrchestrateWikidataEntityCreation(
                $entityData,
                $entityType,
                $request->id, // Pass the request ID for tracking
            );

            dispatch($job);

            Log::info("Dispatched orchestrated Wikidata entity creation job", [
                "request_id" => $request->id,
                "entity_type" => $entityType,
            ]);

            // Return a success response with the request ID
            return response()->json([
                "success" => true,
                "message" => "Entity creation job dispatched successfully",
                "request_id" => $request->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Error dispatching entity creation job", [
                "error" => $e->getMessage(),
            ]);

            // Return an error response
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error dispatching entity creation job: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    // The callback endpoint has been removed since we now update the status directly in the job
    /**
     * Automatically detect date precision from the date string
     *
     * @param string|null $date The date string
     * @return int The Wikidata precision value (9 for year, 10 for month, 11 for day)
     */
    protected function getDatePrecision(?string $date): int
    {
        if (!$date) {
            return 11; // Default to day precision
        }

        // Check if it's a year (4 digits only)
        if (preg_match('/^\d{4}$/', $date)) {
            return 9; // Year precision
        }

        // Check if it's a month (YYYY-MM format)
        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            return 10; // Month precision
        }

        // Otherwise it's a full date (YYYY-MM-DD format)
        return 11; // Day precision
    }
}

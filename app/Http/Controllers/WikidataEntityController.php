<?php

namespace App\Http\Controllers;

use App\Models\WikidataEntryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        // Daten validieren
        $validated = $request->validate([
            "title" => "required|string|max:250",
            "description" => "required|string|max:750",
            "type" => 'required|string|regex:/^Q\d+$/',
            "coordinates" => "nullable|string",
            "victims" => "nullable|array",
            "perpetrators" => "nullable|array",
            "eventTimeType" => "nullable|string|in:point,range",
            "eventDate" => "nullable|string",
            "eventStartDate" => "nullable|string",
            "eventEndDate" => "nullable|string",
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
            // Neue Felder für Personen und Organisationen
            "gender" => "nullable|array",
            "nationality" => "nullable|array",
            "place_of_birth" => "nullable|array",
            "place_of_death" => "nullable|array",
            "place_of_detention" => "nullable|array",
        ]);

        try {
            $entryRequest = new WikidataEntryRequest();
            $entryRequest->title = $validated["title"];
            $entryRequest->description = $validated["description"];
            $entryRequest->type = $validated["type"];
            $entryRequest->coordinates = $validated["coordinates"] ?? null;
            $entryRequest->victims = $validated["victims"] ?? null;
            $entryRequest->perpetrators = $validated["perpetrators"] ?? null;

            // Neue Wikidata-Felder
            try {
                $entryRequest->gender = $validated["gender"] ?? null;
                $entryRequest->nationality = $validated["nationality"] ?? null;
                $entryRequest->place_of_birth = $validated["place_of_birth"] ?? null;
                $entryRequest->place_of_death = $validated["place_of_death"] ?? null;
                $entryRequest->place_of_detention = $validated["place_of_detention"] ?? null;
            } catch (\Exception $e) {
                // Temporärer Workaround, falls die Spalten noch nicht existieren
                Log::warning("Wikidata fields could not be saved: " . $e->getMessage());
            }

            // Zeitpunkt oder Zeitraum je nach Typ
            if (isset($validated["eventTimeType"])) {
                if ($validated["eventTimeType"] === "point") {
                    $entryRequest->time_point = $validated["eventDate"] ?? null;
                } elseif ($validated["eventTimeType"] === "range") {
                    $entryRequest->time_period_start =
                        $validated["eventStartDate"] ?? null;
                    $entryRequest->time_period_end =
                        $validated["eventEndDate"] ?? null;
                }
            }

            // Statistiken
            $entryRequest->victim_count = $validated["victimCount"] ?? null;
            $entryRequest->survivor_count = $validated["survivorCount"] ?? null;
            $entryRequest->death_count = $validated["deathCount"] ?? null;
            $entryRequest->arrest_count = $validated["arrestCount"] ?? null;
            
            // Qualifier für Statistiken
            $entryRequest->victim_count_qualifier = $validated["victimCountQualifier"] ?? null;
            $entryRequest->survivor_count_qualifier = $validated["survivorCountQualifier"] ?? null;
            $entryRequest->death_count_qualifier = $validated["deathCountQualifier"] ?? null;
            $entryRequest->arrest_count_qualifier = $validated["arrestCountQualifier"] ?? null;
            
            // Debug-Ausgabe für Qualifier
            Log::debug("Qualifier-Werte", [
                'victim_qualifier' => $validated["victimCountQualifier"] ?? 'nicht gesetzt',
                'survivor_qualifier' => $validated["survivorCountQualifier"] ?? 'nicht gesetzt',
                'death_qualifier' => $validated["deathCountQualifier"] ?? 'nicht gesetzt',
                'arrest_qualifier' => $validated["arrestCountQualifier"] ?? 'nicht gesetzt',
            ]);

            // Ursache
            $entryRequest->cause = $validated["cause"] ?? null;
            // Speichern der ausgewählten Ursachen
            if (!empty($validated["causes"])) {
                $entryRequest->selected_causes = json_encode($validated["causes"]);
            }

            // Quellen
            $entryRequest->sources = $validated["sources"] ?? null;

            // Bildinformationen
            $entryRequest->image_url = $validated["imageUrl"] ?? null;
            $entryRequest->image_filename = $validated["imageFilename"] ?? null;

            $entryRequest->status = "pending";
            $entryRequest->submitted_by = "anonymous";
            $entryRequest->save();

            // Erfolgreiche Antwort
            return response()->json(
                [
                    "success" => true,
                    "requestId" => $entryRequest->id,
                    "message" =>
                        "Eintrag wurde zur Prüsfung eingereicht und wird nach Freigabe erstellt.",
                ],
                Response::HTTP_CREATED
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
                Response::HTTP_INTERNAL_SERVER_ERROR
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
            $query = WikidataEntryRequest::where("status", $status);

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
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Error getting requests: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim Abrufen der Anfragen: " . $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
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
            $entryRequest = WikidataEntryRequest::findOrFail($id);

            // Pru00fcfen, ob die Anfrage bereits genehmigt wurde
            if ($entryRequest->status === "approved") {
                if ($request->has('redirect')) {
                    return response()->json(
                        [
                            "redirect" => $request->input('redirect'),
                            "success" => false,
                            "message" => "Diese Anfrage wurde bereits genehmigt.",
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
                
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Diese Anfrage wurde bereits genehmigt.",
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Anfrage genehmigen
            $entryRequest->status = "approved";
            $entryRequest->approved_by = "admin"; // Spu00e4ter durch authentifizierten Benutzer ersetzen
            $entryRequest->save();

            // Job in die Queue stellen, um Wikidata-Eintrag asynchron zu erstellen
            \App\Jobs\CreateWikidataEntity::dispatch($entryRequest);

            if ($request->has('redirect')) {
                return response()->json(
                    [
                        "redirect" => $request->input('redirect'),
                        "success" => true,
                        "message" =>
                            "Anfrage genehmigt und Wikidata-Eintrag zur Queue hinzugefügt.",
                    ],
                    Response::HTTP_OK
                );
            }
            
            return response()->json(
                [
                    "success" => true,
                    "message" =>
                        "Anfrage genehmigt und Wikidata-Eintrag zur Queue hinzugefügt.",
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Error approving request: " . $e->getMessage());

            if ($request->has('redirect')) {
                return response()->json(
                    [
                        "redirect" => $request->input('redirect'),
                        "success" => false,
                        "message" =>
                            "Fehler bei der Genehmigung der Anfrage: " .
                            $e->getMessage(),
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
            
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler bei der Genehmigung der Anfrage: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
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
            if ($request->has('redirect')) {
                return response()->json(
                    [
                        "redirect" => $request->input('redirect'),
                        "success" => false,
                        "message" => "Ungültige Eingabe",
                        "errors" => $validator->errors(),
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            return response()->json(
                [
                    "success" => false,
                    "message" => "Ungültige Eingabe",
                    "errors" => $validator->errors(),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $entryRequest = WikidataEntryRequest::findOrFail($id);

            // Pru00fcfen, ob die Anfrage bereits abgelehnt wurde
            if ($entryRequest->status === "rejected") {
                if ($request->has('redirect')) {
                    return response()->json(
                        [
                            "redirect" => $request->input('redirect'),
                            "success" => false,
                            "message" => "Diese Anfrage wurde bereits abgelehnt.",
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
                
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Diese Anfrage wurde bereits abgelehnt.",
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Anfrage ablehnen
            $entryRequest->status = "rejected";
            $entryRequest->rejected_by = "admin"; // Spu00e4ter durch authentifizierten Benutzer ersetzen
            $entryRequest->rejection_reason = $request->input("reason");
            $entryRequest->save();

            if ($request->has('redirect')) {
                return response()->json(
                    [
                        "redirect" => $request->input('redirect'),
                        "success" => true,
                        "message" => "Anfrage erfolgreich abgelehnt.",
                    ],
                    Response::HTTP_OK
                );
            }
            
            return response()->json(
                [
                    "success" => true,
                    "message" => "Anfrage erfolgreich abgelehnt.",
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Error rejecting request: " . $e->getMessage());

            if ($request->has('redirect')) {
                return response()->json(
                    [
                        "redirect" => $request->input('redirect'),
                        "success" => false,
                        "message" =>
                            "Fehler bei der Ablehnung der Anfrage: " .
                            $e->getMessage(),
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
            
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler bei der Ablehnung der Anfrage: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Fu00fchrt eine erneute Verarbeitung einer fehlgeschlagenen Anfrage durch.
     *
     * @param  int  $id
     *
     * @return JsonResponse
     */
    public function retryRequest(int $id): JsonResponse
    {
        try {
            $entryRequest = WikidataEntryRequest::findOrFail($id);

            // Pru00fcfen, ob die Anfrage im Status "error" ist
            if ($entryRequest->status !== "error") {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Nur Anfragen mit dem Status 'error' ku00f6nnen erneut verarbeitet werden.",
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Status aktualisieren
            $entryRequest->status = "pending";
            $entryRequest->error_message = null; // Fehler zuru00fccksetzen
            $entryRequest->save();

            return response()->json(
                [
                    "success" => true,
                    "message" =>
                        "Anfrage wurde zur erneuten Verarbeitung markiert.",
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Error retrying request: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler bei der erneuten Verarbeitung: " .
                        $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
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
            $entryRequest = WikidataEntryRequest::findOrFail($id);

            return response()->json(
                [
                    "success" => true,
                    "request" => $entryRequest,
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Error getting request: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim Abrufen der Anfrage: " . $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
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
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $entryRequest = WikidataEntryRequest::findOrFail($id);

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
                Response::HTTP_OK
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
                Response::HTTP_INTERNAL_SERVER_ERROR
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
                            "Unbekannter Fehler")
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
                "Wikidata API Authentifizierungsfehler: " . $e->getMessage()
            );
            return null;
        }
    }

    /**
     * Bereitet die Daten für die Erstellung einer Wikidata-Entitu00e4t vor.
     *
     * @param WikidataEntryRequest $entryRequest
     * @return array
     */
    private function prepareEntityData(
        WikidataEntryRequest $entryRequest
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
            list($lat, $lon) = explode(",", $entryRequest->coordinates);
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
                        "precision" => 11, // Tag
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
                        "precision" => 11,
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
                            "precision" => 11,
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

        return [
            "labels" => $label,
            "descriptions" => $description,
            "claims" => $statements,
        ];
    }

    /**
     * Erstellt eine neue Wikidata-Entitu00e4t basierend auf der Anfrage.
     *
     * @param WikidataEntryRequest $entryRequest
     * @return string Die ID der erstellten Wikidata-Entitu00e4t
     * @throws \Exception wenn die Anfrage fehlschlu00e4gt
     */
    private function createEntity(WikidataEntryRequest $entryRequest): string
    {
        // Warnung: Dies ist nur eine Simulation der API-Interaktion
        // In einer realen Anwendung wu00fcrden Sie den Bot-Token verwenden
        // und die tatsu00e4chliche Wikidata-API aufrufen

        // Simuliert den Ru00fcckgabewert der Wikidata-API
        // In einer realen Anwendung wu00fcrde dies die tatsu00e4chliche ID von Wikidata sein
        return "Q" . rand(10000000, 99999999);
    }
}

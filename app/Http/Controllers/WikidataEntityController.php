<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\CreateWikidataEntity;
use App\Models\WikidataEntryRequest;
use Illuminate\Support\Facades\Auth;

class WikidataEntityController extends Controller
{
    // Während der Entwicklung die Test-Instanz von Wikidata verwenden
    private $apiUrl = "https://test.wikidata.org/w/api.php";
    // Für Produktion: private $apiUrl = "https://www.wikidata.org/w/api.php";

    /**
     * Create a new Wikidata entity request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // Daten validieren
        $validated = $request->validate([
            "title" => "required|string|max:250",
            "description" => "required|string|max:750",
            "type" => 'required|string|regex:/^Q\d+$/',
        ]);

        try {
            $entryRequest = new WikidataEntryRequest();
            $entryRequest->title = $validated["title"];
            $entryRequest->description = $validated["description"];
            $entryRequest->type = $validated["type"];
            $entryRequest->status = "pending";
            $entryRequest->submitted_by = "anonymous";
            $entryRequest->save();

            // Erfolgreiche Antwort
            return response()->json(
                [
                    "success" => true,
                    "requestId" => $entryRequest->id,
                    "message" =>
                        "Eintrag wurde zur Prüfung eingereicht und wird nach Freigabe erstellt.",
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
     * Get all requests with filtering and pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPendingRequests(Request $request): JsonResponse
    {
        // Authentifizierungsprüfung entfernt
        $status = $request->input("status", "pending");
        $perPage = $request->input("perPage", 10);
        $page = $request->input("page", 1);

        try {
            $requests = WikidataEntryRequest::where("status", $status)
                ->orderBy("created_at", $status === "pending" ? "asc" : "desc")
                ->paginate($perPage, ["*"], "page", $page);

            return response()->json(
                [
                    "success" => true,
                    "requests" => $requests,
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Error fetching Wikidata requests: " . $e->getMessage());

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
     * Approve a Wikidata entry request
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approveRequest(Request $request, int $id): JsonResponse
    {
        // Authentifizierungsprüfung entfernt
        try {
            $entryRequest = WikidataEntryRequest::findOrFail($id);

            if ($entryRequest->status !== "pending") {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Diese Anfrage wurde bereits bearbeitet.",
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Anfrage auf 'approved' setzen
            $entryRequest->status = "approved";
            $entryRequest->approved_by = "test_admin";
            $entryRequest->save();

            // Job zur Erstellung in Wikidata einreihen
            CreateWikidataEntity::dispatch($entryRequest);

            return response()->json(
                [
                    "success" => true,
                    "message" =>
                        "Anfrage genehmigt und wird in Wikidata erstellt.",
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Wikidata Approval Error: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler bei der Genehmigung: " . $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Reject a Wikidata entry request
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function rejectRequest(Request $request, int $id): JsonResponse
    {
        // Authentifizierungsprüfung entfernt
        try {
            $entryRequest = WikidataEntryRequest::findOrFail($id);

            if ($entryRequest->status !== "pending") {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Diese Anfrage wurde bereits bearbeitet.",
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validierung der Ablehnungsgründe
            $validated = $request->validate([
                "rejection_reason" => "required|string|max:1000",
            ]);

            // Anfrage ablehnen
            $entryRequest->status = "rejected";
            $entryRequest->rejection_reason = $validated["rejection_reason"];
            $entryRequest->rejected_by = "test_admin";
            $entryRequest->save();

            return response()->json(
                [
                    "success" => true,
                    "message" => "Anfrage wurde abgelehnt.",
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Wikidata Rejection Error: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler bei der Ablehnung: " . $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retry a failed Wikidata entry request
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function retryRequest(Request $request, int $id): JsonResponse
    {
        // Authentifizierungsprüfung entfernt
        try {
            $entryRequest = WikidataEntryRequest::findOrFail($id);

            if ($entryRequest->status !== "error") {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Nur fehlgeschlagene Anfragen können wiederholt werden.",
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Status zurücksetzen und als 'approved' markieren
            $entryRequest->status = "approved";
            $entryRequest->error_message = null;
            $entryRequest->save();

            // Job erneut in die Warteschlange stellen
            CreateWikidataEntity::dispatch($entryRequest);

            return response()->json(
                [
                    "success" => true,
                    "message" =>
                        "Anfrage wurde zur erneuten Verarbeitung eingereicht.",
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error("Wikidata Retry Error: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Fehler beim erneuten Versuch: " . $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Bot-Token von Wikidata abrufen
     */
    private function getBotToken()
    {
        // Prüfen, ob Token im Cache ist
        if (Cache::has("wikidata_bot_token")) {
            Log::info("Verwende gecachtes Bot-Token");
            return Cache::get("wikidata_bot_token");
        }

        $botUsername = config("wikidata.bot_username");
        $botPassword = config("wikidata.bot_password");

        // Debug-Ausgabe für Anmeldedaten (ABER NIEMALS IN PRODUKTION!)
        Log::debug("Versuche Login mit Benutzer: " . $botUsername);
        Log::debug("API-URL: " . $this->apiUrl);

        // Login-Token anfordern
        try {
            $loginTokenResponse = Http::get($this->apiUrl, [
                "action" => "query",
                "meta" => "tokens",
                "type" => "login",
                "format" => "json",
            ]);

            Log::debug(
                "Login Token Response: " .
                    json_encode($loginTokenResponse->json())
            );

            if (!$loginTokenResponse->ok()) {
                throw new \Exception(
                    "Login Token Request fehlgeschlagen: " .
                        $loginTokenResponse->status()
                );
            }

            $loginTokenJson = $loginTokenResponse->json();

            if (
                !isset($loginTokenJson["query"]) ||
                !isset($loginTokenJson["query"]["tokens"]) ||
                !isset($loginTokenJson["query"]["tokens"]["logintoken"])
            ) {
                throw new \Exception(
                    "Login Token nicht gefunden in Antwort: " .
                        json_encode($loginTokenJson)
                );
            }

            $loginToken = $loginTokenJson["query"]["tokens"]["logintoken"];
            Log::debug(
                "Login Token erhalten: " . substr($loginToken, 0, 5) . "..."
            );

            // Mit dem Login-Token anmelden
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();

            $loginResponse = Http::withOptions([
                "cookies" => $cookieJar,
            ])->post($this->apiUrl, [
                "action" => "login",
                "lgname" => $botUsername,
                "lgpassword" => $botPassword,
                "lgtoken" => $loginToken,
                "format" => "json",
            ]);

            Log::debug(
                "Login Response: " . json_encode($loginResponse->json())
            );

            if (!$loginResponse->ok()) {
                throw new \Exception(
                    "Login Request fehlgeschlagen: " . $loginResponse->status()
                );
            }

            $loginJson = $loginResponse->json();

            if (!isset($loginJson["login"])) {
                throw new \Exception(
                    "Login-Antwort enthält keinen 'login'-Schlüssel: " .
                        json_encode($loginJson)
                );
            }

            if ($loginJson["login"]["result"] !== "Success") {
                throw new \Exception(
                    "Bot login failed: " .
                        (isset($loginJson["login"]["reason"])
                            ? $loginJson["login"]["reason"]
                            : json_encode($loginJson["login"]))
                );
            }

            // CSRF-Token für Bearbeitungen holen
            $csrfResponse = Http::withOptions([
                "cookies" => $cookieJar,
            ])->get($this->apiUrl, [
                "action" => "query",
                "meta" => "tokens",
                "format" => "json",
            ]);

            Log::debug(
                "CSRF Token Response: " . json_encode($csrfResponse->json())
            );

            if (!$csrfResponse->ok()) {
                throw new \Exception(
                    "CSRF Token Request fehlgeschlagen: " .
                        $csrfResponse->status()
                );
            }

            $csrfJson = $csrfResponse->json();

            if (
                !isset($csrfJson["query"]) ||
                !isset($csrfJson["query"]["tokens"]) ||
                !isset($csrfJson["query"]["tokens"]["csrftoken"])
            ) {
                throw new \Exception(
                    "CSRF Token nicht gefunden in Antwort: " .
                        json_encode($csrfJson)
                );
            }

            $csrfToken = $csrfJson["query"]["tokens"]["csrftoken"];
            Log::debug(
                "CSRF Token erhalten: " . substr($csrfToken, 0, 5) . "..."
            );

            // Token im Cache speichern (1 Stunde gültig)
            $tokenData = [
                "token" => $csrfToken,
                "cookieJar" => $cookieJar,
            ];

            Cache::put("wikidata_bot_token", $tokenData, 3600);

            return $tokenData;
        } catch (\Exception $e) {
            Log::error("Fehler beim Bot-Login: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());
            throw $e; // Weitergabe des Fehlers
        }
    }

    /**
     * Datenstruktur für Wikidata vorbereiten
     */
    private function prepareEntityData($label, $description, $instanceOf)
    {
        return [
            "labels" => [
                "de" => [
                    "language" => "de",
                    "value" => $label,
                ],
            ],
            "descriptions" => [
                "de" => [
                    "language" => "de",
                    "value" => $description,
                ],
            ],
            "claims" => [
                // Instanz von (P31)
                "P31" => [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P31",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $instanceOf,
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ],
                // Staat (P17) - Deutschland im Nazi-Regime (Q7318)
                "P17" => [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P17",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => "Q7318",
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ],
            ],
        ];
    }

    /**
     * Wikidata-Entität über API erstellen
     */
    private function createEntity($entityData, $tokenData)
    {
        $response = Http::withOptions([
            "cookies" => $tokenData["cookieJar"],
        ])
            ->asForm()
            ->post($this->apiUrl, [
                "action" => "wbeditentity",
                "new" => "item",
                "data" => json_encode($entityData),
                "token" => $tokenData["token"],
                "format" => "json",
            ]);

        if (isset($response["error"])) {
            throw new \Exception(
                $response["error"]["info"] ?? "Unknown API error"
            );
        }

        return $response->json();
    }
}

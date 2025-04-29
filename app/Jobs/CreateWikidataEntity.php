<?php

namespace App\Jobs;

use App\Models\WikidataEntryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CreateWikidataEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entryRequest;
    private $apiUrl = "https://test.wikidata.org/w/api.php"; // Für Entwicklung

    /**
     * Create a new job instance.
     */
    public function __construct(WikidataEntryRequest $entryRequest)
    {
        $this->entryRequest = $entryRequest;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Debug-Ausgabe
            Log::info("Starting Wikidata entity creation", [
                "request_id" => $this->entryRequest->id,
                "title" => $this->entryRequest->title,
                "type" => $this->entryRequest->type,
            ]);

            // Bot-Token abrufen
            $token = $this->getBotToken();
            Log::info("Bot token acquired successfully");

            // Wikidata-Entität erstellen
            $entityData = $this->prepareEntityData(
                $this->entryRequest->title,
                $this->entryRequest->description,
                $this->entryRequest->type
            );
            Log::info("Entity data prepared", ["data" => $entityData]);

            $response = $this->createEntity($entityData, $token);
            Log::info("API response received", ["response" => $response]);

            // Erfolg protokollieren
            $this->entryRequest->wikidata_id = $response["entity"]["id"];
            $this->entryRequest->save();

            Log::info("Wikidata entity created successfully", [
                "entity_id" => $response["entity"]["id"],
                "request_id" => $this->entryRequest->id,
            ]);
        } catch (\Exception $e) {
            // Fehler protokollieren
            Log::error("Failed to create Wikidata entity", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
                "request_id" => $this->entryRequest->id,
            ]);

            $this->entryRequest->status = "error";
            $this->entryRequest->error_message = $e->getMessage();
            $this->entryRequest->save();

            // Job erneut in die Warteschlange stellen (mit Verzögerung) - optional
            if ($this->attempts() < 3) {
                $this->release(60 * 5); // 5 Minuten Verzögerung
            }
        }
    }

    /**
     * Bot-Token von Wikidata abrufen
     */
    private function getBotToken()
    {
        // Prüfen, ob Token im Cache ist
        if (Cache::has("wikidata_bot_token")) {
            return Cache::get("wikidata_bot_token");
        }

        $botUsername = config("wikidata.bot_username");
        $botPassword = config("wikidata.bot_password");

        // Login-Token anfordern
        $loginTokenResponse = Http::get($this->apiUrl, [
            "action" => "query",
            "meta" => "tokens",
            "type" => "login",
            "format" => "json",
        ]);

        $loginTokenJson = $loginTokenResponse->json();

        // Überprüfung auf gültige Antwort
        if (
            !isset($loginTokenJson["query"]) ||
            !isset($loginTokenJson["query"]["tokens"]) ||
            !isset($loginTokenJson["query"]["tokens"]["logintoken"])
        ) {
            throw new \Exception(
                "Failed to get login token: " . json_encode($loginTokenJson)
            );
        }

        $loginToken = $loginTokenJson["query"]["tokens"]["logintoken"];

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

        $loginJson = $loginResponse->json();

        // Überprüfung auf erfolgreiche Anmeldung
        if (
            !isset($loginJson["login"]) ||
            $loginJson["login"]["result"] !== "Success"
        ) {
            throw new \Exception(
                "Bot login failed: " .
                    (isset($loginJson["login"]["reason"])
                        ? $loginJson["login"]["reason"]
                        : json_encode($loginJson))
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

        $csrfJson = $csrfResponse->json();

        // Überprüfung auf gültigen CSRF-Token
        if (
            !isset($csrfJson["query"]) ||
            !isset($csrfJson["query"]["tokens"]) ||
            !isset($csrfJson["query"]["tokens"]["csrftoken"])
        ) {
            throw new \Exception(
                "Failed to get CSRF token: " . json_encode($csrfJson)
            );
        }

        $csrfToken = $csrfJson["query"]["tokens"]["csrftoken"];

        // Token im Cache speichern (1 Stunde gültig)
        $tokenData = [
            "token" => $csrfToken,
            "cookieJar" => $cookieJar,
        ];

        Cache::put("wikidata_bot_token", $tokenData, 3600);

        return $tokenData;
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

        $jsonResponse = $response->json();

        // Überprüfen auf API-Fehler
        if (isset($jsonResponse["error"])) {
            throw new \Exception(
                $jsonResponse["error"]["info"] ?? "Unknown API error"
            );
        }

        // Überprüfen, ob die erwartete Struktur vorhanden ist
        if (
            !isset($jsonResponse["entity"]) ||
            !isset($jsonResponse["entity"]["id"])
        ) {
            throw new \Exception(
                "Unexpected response format: Entity ID not found in response."
            );
        }

        return $jsonResponse;
    }
}

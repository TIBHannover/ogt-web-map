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
            // Bot-Token abrufen
            $token = $this->getBotToken();

            // Wikidata-Entität erstellen
            $entityData = $this->prepareEntityData(
                $this->entryRequest->title,
                $this->entryRequest->description,
                $this->entryRequest->type
            );

            $response = $this->createEntity($entityData, $token);

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

        $loginToken = $loginTokenResponse->json()["query"]["tokens"][
            "logintoken"
        ];

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

        if ($loginResponse->json()["login"]["result"] !== "Success") {
            throw new \Exception(
                "Bot login failed: " .
                    ($loginResponse->json()["login"]["reason"] ??
                        "unknown error")
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

        $csrfToken = $csrfResponse->json()["query"]["tokens"]["csrftoken"];

        // Token im Cache speichern (1 Stunde gültig)
        Cache::put(
            "wikidata_bot_token",
            [
                "token" => $csrfToken,
                "cookieJar" => $cookieJar,
            ],
            3600
        );

        return [
            "token" => $csrfToken,
            "cookieJar" => $cookieJar,
        ];
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

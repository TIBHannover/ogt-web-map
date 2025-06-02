<?php

namespace App\Services\Wikidata;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Wikidata\WikidataOptimizer;

class WikidataService
{
    /**
     * Bot Benutzername
     */
    protected $username;

    /**
     * Bot Passwort
     */
    protected $password;

    /**
     * API URL
     */
    protected $apiUrl;

    /**
     * Debug-Modus aktiviert?
     */
    protected $debug;

    /**
     * Simulationsmodus aktiviert?
     */
    protected $simulationMode;

    /**
     * @var WikidataFormatter
     */
    protected $formatter;

    /**
     * @var WikidataOptimizer
     */
    protected $optimizer;

    /**
     * WikidataService constructor.
     */
    public function __construct()
    {
        $this->username = config("wikidata.bot_username");
        $this->password = config("wikidata.bot_password");
        $this->apiUrl = config("wikidata.api_url");
        $this->formatter = new WikidataFormatter();
        $this->optimizer = new WikidataOptimizer();
        $this->debug = config("wikidata.debug", false);
        $this->simulationMode = config("wikidata.simulation_mode", false);

        if ($this->debug) {
            Log::info("WikidataService initialized", [
                "username" => $this->username,
                "api_url" => $this->apiUrl,
                "simulation_mode" => $this->simulationMode
                    ? "enabled"
                    : "disabled",
            ]);
        }
    }

    /**
     * Pru00fcft, ob Bot-Anmeldedaten vorhanden sind.
     *
     * @return bool
     */
    public function hasValidCredentials()
    {
        // Im Simulationsmodus immer true zuru00fcckgeben
        if ($this->simulationMode) {
            Log::info(
                "Running in simulation mode - credentials check bypassed"
            );
            return true;
        }

        $credentialsValid = !empty($this->username) && !empty($this->password);

        if ($this->debug) {
            Log::info("Credentials check result", [
                "valid" => $credentialsValid ? "yes" : "no",
            ]);
        }

        return $credentialsValid;
    }

    /**
     * Meldet den Bot bei der MediaWiki-API an und gibt das Login-Token zuru00fcck.
     *
     * @return string|null
     */
    protected function login()
    {
        try {
            Log::info("Starting login process for WikidataBot", [
                "username" => $this->username,
            ]);

            // HTTP-Client mit spezifischer Cookie-Jar initialisieren, damit Cookies nicht vermischt werden
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            $client = new \GuzzleHttp\Client([
                "cookies" => $cookieJar,
                "headers" => [
                    "User-Agent" => "Ontology Generator Toolkit Bot/1.0",
                ],
            ]);

            // 1. CSRF-Token abrufen
            $tokenResponse = $client->get($this->apiUrl, [
                "query" => [
                    "action" => "query",
                    "meta" => "tokens",
                    "type" => "login",
                    "format" => "json",
                ],
            ]);

            $tokenData = json_decode(
                $tokenResponse->getBody()->getContents(),
                true
            );

            Log::info("Token response received", [
                "response" => json_encode($tokenData),
            ]);

            if (!isset($tokenData["query"]["tokens"]["logintoken"])) {
                Log::error("Failed to get login token", [
                    "response" => $tokenData,
                ]);
                return null;
            }

            $loginToken = $tokenData["query"]["tokens"]["logintoken"];
            Log::info("Obtained login token from Wikidata API");

            // 2. Einloggen mit dem Token
            $loginResponse = $client->post($this->apiUrl, [
                "form_params" => [
                    "action" => "login",
                    "lgname" => $this->username,
                    "lgpassword" => $this->password,
                    "lgtoken" => $loginToken,
                    "format" => "json",
                ],
            ]);

            $loginData = json_decode(
                $loginResponse->getBody()->getContents(),
                true
            );
            Log::info("Login response received", [
                "response" => json_encode($loginData),
            ]);

            if (
                !isset($loginData["login"]["result"]) ||
                $loginData["login"]["result"] !== "Success"
            ) {
                Log::error("Failed to login", ["response" => $loginData]);
                return null;
            }

            Log::info("Successfully logged in to Wikidata API", [
                "username" => $this->username,
                "result" => $loginData["login"]["result"],
            ]);

            // 3. CSRF-Token für Bearbeitungen abrufen
            $csrfResponse = $client->get($this->apiUrl, [
                "query" => [
                    "action" => "query",
                    "meta" => "tokens",
                    "format" => "json",
                ],
            ]);

            $csrfData = json_decode(
                $csrfResponse->getBody()->getContents(),
                true
            );
            Log::info("CSRF response received", [
                "response" => json_encode($csrfData),
            ]);

            if (!isset($csrfData["query"]["tokens"]["csrftoken"])) {
                Log::error("Failed to get CSRF token", [
                    "response" => $csrfData,
                ]);
                return null;
            }

            $csrfToken = $csrfData["query"]["tokens"]["csrftoken"];
            Log::info("Obtained CSRF token for edit operations");

            // 4. Extrahiere Cookies aus dem CookieJar
            $cookies = [];
            foreach ($cookieJar->toArray() as $cookie) {
                $cookies[] = [
                    "Name" => $cookie["Name"],
                    "Value" => $cookie["Value"],
                    "Domain" => $cookie["Domain"],
                    "Path" => $cookie["Path"],
                    "Max-Age" => $cookie["Max-Age"] ?? null,
                    "Expires" => $cookie["Expires"] ?? null,
                    "Secure" => $cookie["Secure"] ?? false,
                    "HttpOnly" => $cookie["HttpOnly"] ?? false,
                ];
            }

            // 5. Cookies und CSRF-Token im Cache speichern
            $authData = [
                "cookies" => $cookies,
                "csrftoken" => $csrfToken,
                "expires" => now()->addHours(1)->timestamp,
            ];

            Cache::put("wikidata_auth_data", $authData, 3600); // 1 Stunde
            Log::info("Auth data saved to cache", [
                "cookies_count" => count($cookies),
            ]);

            return $csrfToken;
        } catch (\Exception $e) {
            Log::error("Login error", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Erstellt eine neue Wikidata-Entitu00e4t mit Bot-Authentifizierung.
     *
     * @param array $entityData Die vorbereiteten Daten (aus prepareEntityData)
     * @param string|null $type Der Typ der Entity (optional, wenn bereits in $entityData enthalten)
     * @return array|null
     */
    public function createEntity(array $entityData, string $type = null)
    {
        try {
            // Typ aus den Daten extrahieren, wenn nicht explizit angegeben
            if ($type === null) {
                $type =
                    $entityData["type"] ??
                    throw new \InvalidArgumentException("Entity type missing");
            }

            $formattedData = $this->formatter->formatEntityData(
                $entityData,
                $type,
                true // createEntities = true, weil wir hier tatsächlich eine Entity erstellen wollen
            );

            // Debug-Ausgabe der ursprüunglichen formatierten Daten
            Log::info("Original formatted entity data for Wikidata", [
                "entity_data" => json_encode(
                    $formattedData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
            ]);

            // Extrahiere die formattedData aus dem Ergebnis
            $dataToUse = is_array($formattedData) && isset($formattedData['formattedData']) 
                ? $formattedData['formattedData'] 
                : $formattedData;

            // Daten für die Erstellung optimieren (entferne ID, etc.)
            $optimizedData = $this->optimizer->optimize(
                $dataToUse,
                "create"
            );

            // Debug-Ausgabe der urspru00fcnglichen formatierten Daten
            Log::info("Optimized formatted entity data for Wikidata", [
                "entity_data" => json_encode(
                    $optimizedData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
            ]);

            // Wenn Simulationsmodus aktiv ist, beenden wir hier und geben eine simulierte Antwort zuru00fcck
            if ($this->simulationMode) {
                Log::info(
                    "Running in simulation mode - no actual Wikidata entity will be created"
                );

                // Simulierte Antwort erstellen
                $simulatedResponse = [
                    "entity" => [
                        "id" => "Q" . rand(1000000, 9999999), // Zufu00e4llige Q-ID generieren
                        "type" => "item",
                        "labels" => $optimizedData["labels"] ?? [],
                        "descriptions" => $optimizedData["descriptions"] ?? [],
                        "claims" => $optimizedData["claims"] ?? [],
                    ],
                    "success" => 1,
                ];

                Log::info("Simulated entity creation successful", [
                    "entity_id" => $simulatedResponse["entity"]["id"],
                ]);

                return $simulatedResponse;
            }

            // Authentifizierungsdaten abrufen oder neu anmelden
            $authData = Cache::get("wikidata_auth_data");

            if (!$authData || $authData["expires"] < now()->timestamp) {
                Log::info(
                    "No valid auth data found in cache, initiating bot login"
                );
                $csrfToken = $this->login();
                if (!$csrfToken) {
                    Log::error("Failed to login for creating entity");
                    return null;
                }
                $authData = Cache::get("wikidata_auth_data");
            }

            // Anfrage vorbereiten
            $formData = [
                "action" => "wbeditentity",
                "new" => "item",
                "data" => json_encode($optimizedData),
                "token" => $authData["csrftoken"],
                "format" => "json",
                "bot" => "1", // Markiere als Bot-Bearbeitung
            ];

            Log::info("Sending request to create Wikidata entity", [
                "form_data" => json_encode(
                    $formData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
                "data_length" => strlen(json_encode($optimizedData)),
                "data_sample" =>
                    mb_substr(json_encode($optimizedData), 0, 200) . "...", // Zeige die ersten 200 Zeichen
            ]);

            // Anfrage an die API senden
            $client = new \GuzzleHttp\Client([
                "headers" => [
                    "User-Agent" => "Ontology Generator Toolkit Bot/1.0",
                ],
                "cookies" => true,
            ]);

            // Cookie-Jar mit gespeicherten Cookies initialisieren
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar(false, []);
            foreach ($authData["cookies"] as $cookie) {
                $cookieJar->setCookie(
                    new \GuzzleHttp\Cookie\SetCookie([
                        "Name" => $cookie["Name"],
                        "Value" => $cookie["Value"],
                        "Domain" => $cookie["Domain"],
                        "Path" => $cookie["Path"] ?? "/",
                    ])
                );
            }

            try {
                $response = $client->post($this->apiUrl, [
                    "cookies" => $cookieJar,
                    "form_params" => $formData,
                ]);

                $responseBody = $response->getBody()->getContents();
                $jsonResponse = json_decode($responseBody, true);

                // Zur Debug-Zwecken die Antwort loggen
                Log::info("Wikidata API response", [
                    "response" => $responseBody,
                ]);
            } catch (\Exception $e) {
                Log::error("API request error", [
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]);
                return null;
            }

            if (isset($jsonResponse["error"])) {
                Log::error("Wikidata API error", [
                    "error" =>
                        $jsonResponse["error"]["info"] ?? "Unknown API error",
                    "response" => $jsonResponse,
                ]);
                return null;
            }

            if (
                !isset($jsonResponse["entity"]) ||
                !isset($jsonResponse["entity"]["id"])
            ) {
                Log::error("Unexpected API response format", [
                    "response" => $jsonResponse,
                ]);
                return null;
            }

            Log::info("Successfully created Wikidata entity", [
                "entity_id" => $jsonResponse["entity"]["id"],
            ]);

            return $jsonResponse;
        } catch (\Exception $e) {
            Log::error("Create entity error", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Bereitet Daten für eine Wikidata-Entity vor
     *
     * @param string $title Der Titel der Entität
     * @param string|null $description Die Beschreibung der Entität
     * @param string $type Der Typ der Entität (Q5, Q6983405, usw.)
     * @param array $additionalData Zusätzliche Daten für die Entität
     * @return array Die vorbereiteten Daten
     */
    public function prepareEntityData(
        string $title,
        ?string $description,
        string $type,
        array $additionalData = []
    ): array {
        // Basisdaten zusammenstellen
        $entityData = [
            "title" => $title,
            "description" => $description ?? "",
            "type" => $type,
        ];

        // Zusätzliche Daten hinzufügen
        return array_merge($entityData, $additionalData);
    }

    /**
     * Aktualisiert eine bestehende Wikidata-Entitu00e4t mit Bot-Authentifizierung.
     *
     * @param string $entityId Die ID der zu aktualisierenden Entitu00e4t (z.B. Q12345)
     * @param array $rawData Die Rohdaten aus dem Request
     * @param string $type Der Typ der Entity (Q5, Q6983405, usw.)
     * @return array|null
     */
    public function updateEntity(string $entityId, array $rawData, string $type)
    {
        try {
            // Daten für Wikidata formatieren
            $entityData = $this->formatter->formatEntityData(
                $rawData, 
                $type,
                true // createEntities = true, weil wir hier tatsächlich eine Entity aktualisieren
            );

            // Debug-Ausgabe der urspru00fcnglichen formatierten Daten
            Log::info("Original formatted entity data for update", [
                "entity_id" => $entityId,
                "entity_data" => json_encode(
                    $entityData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
            ]);

            // Extrahiere die formattedData aus dem Ergebnis
            $dataToUse = is_array($entityData) && isset($entityData['formattedData']) 
                ? $entityData['formattedData'] 
                : $entityData;

            // Daten für die Aktualisierung optimieren
            $optimizedData = $this->optimizer->optimize($dataToUse, "update");

            // Wenn Simulationsmodus aktiv ist, beenden wir hier und geben eine simulierte Antwort zuru00fcck
            if ($this->simulationMode) {
                Log::info(
                    "Running in simulation mode - no actual Wikidata entity will be updated"
                );

                // Simulierte Antwort erstellen
                $simulatedResponse = [
                    "entity" => [
                        "id" => $entityId,
                        "type" => "item",
                        "labels" => $optimizedData["labels"] ?? [],
                        "descriptions" => $optimizedData["descriptions"] ?? [],
                        "claims" => $optimizedData["claims"] ?? [],
                    ],
                    "success" => 1,
                ];

                Log::info("Simulated entity update successful", [
                    "entity_id" => $simulatedResponse["entity"]["id"],
                ]);

                return $simulatedResponse;
            }

            // Authentifizierungsdaten abrufen oder neu anmelden
            $authData = Cache::get("wikidata_auth_data");

            if (!$authData || $authData["expires"] < now()->timestamp) {
                Log::info(
                    "No valid auth data found in cache, initiating bot login"
                );
                $csrfToken = $this->login();
                if (!$csrfToken) {
                    Log::error("Failed to login for updating entity");
                    return null;
                }
                $authData = Cache::get("wikidata_auth_data");
            }

            // Anfrage vorbereiten
            $formData = [
                "action" => "wbeditentity",
                "id" => $entityId,
                "data" => json_encode($optimizedData),
                "token" => $authData["csrftoken"],
                "format" => "json",
                "bot" => "1", // Markiere als Bot-Bearbeitung
            ];

            Log::info("Sending request to update Wikidata entity", [
                "entity_id" => $entityId,
                "form_data" => $formData,
            ]);

            // Anfrage an die API senden
            $client = new \GuzzleHttp\Client([
                "headers" => [
                    "User-Agent" => "Ontology Generator Toolkit Bot/1.0",
                ],
                "cookies" => true,
            ]);

            // Cookie-Jar mit gespeicherten Cookies initialisieren
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar(false, []);
            foreach ($authData["cookies"] as $cookie) {
                $cookieJar->setCookie(
                    new \GuzzleHttp\Cookie\SetCookie([
                        "Name" => $cookie["Name"],
                        "Value" => $cookie["Value"],
                        "Domain" => $cookie["Domain"],
                        "Path" => $cookie["Path"] ?? "/",
                    ])
                );
            }

            try {
                $response = $client->post($this->apiUrl, [
                    "cookies" => $cookieJar,
                    "form_params" => $formData,
                ]);

                $responseBody = $response->getBody()->getContents();
                $jsonResponse = json_decode($responseBody, true);

                // Zur Debug-Zwecken die Antwort loggen
                Log::info("Wikidata API response", [
                    "response" => $responseBody,
                ]);
            } catch (\Exception $e) {
                Log::error("API request error", [
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]);
                return null;
            }

            if (isset($jsonResponse["error"])) {
                Log::error("Wikidata API error", [
                    "error" =>
                        $jsonResponse["error"]["info"] ?? "Unknown API error",
                    "response" => $jsonResponse,
                ]);
                return null;
            }

            if (
                !isset($jsonResponse["entity"]) ||
                !isset($jsonResponse["entity"]["id"])
            ) {
                Log::error("Unexpected API response format", [
                    "response" => $jsonResponse,
                ]);
                return null;
            }

            Log::info("Successfully updated Wikidata entity", [
                "entity_id" => $jsonResponse["entity"]["id"],
            ]);

            return $jsonResponse;
        } catch (\Exception $e) {
            Log::error("Update entity error", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}

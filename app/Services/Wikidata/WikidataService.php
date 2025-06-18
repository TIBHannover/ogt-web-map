<?php

namespace App\Services\Wikidata;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Wikidata\WikidataOptimizer;

class WikidataService
{
    /**
     * Bot Username
     */
    protected $username;

    /**
     * Bot Password
     */
    protected $password;

    /**
     * API URL
     */
    protected $apiUrl;

    /**
     * Debug mode enabled?
     */
    protected $debug;

    /**
     * Simulation mode enabled?
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
     * Checks if bot credentials are available.
     *
     * @return bool
     */
    public function hasValidCredentials()
    {
        // In simulation mode, always return true
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
     * Logs the bot into the MediaWiki API and returns the login token.
     *
     * @return string|null
     */
    protected function login()
    {
        try {
            Log::info("Starting login process for WikidataBot", [
                "username" => $this->username,
            ]);

            // Initialize HTTP client with specific cookie jar to prevent cookie mixing
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            $client = new \GuzzleHttp\Client([
                "cookies" => $cookieJar,
                "headers" => [
                    "User-Agent" => "Ontology Generator Toolkit Bot/1.0",
                ],
            ]);

            // 1. Retrieve CSRF token
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

            // 2. Login with the token
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

            // 3. Retrieve CSRF token for edits
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

            // 4. Extract cookies from CookieJar
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

            // 5. Store cookies and CSRF token in cache
            $authData = [
                "cookies" => $cookies,
                "csrftoken" => $csrfToken,
                "expires" => now()->addHours(1)->timestamp,
            ];

            Cache::put("wikidata_auth_data", $authData, 3600); // 1 hour
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
     * Creates a new Wikidata entity with bot authentication.
     *
     * @param array $entityData The prepared data (from prepareEntityData)
     * @param string|null $type The type of the entity (optional, if already included in $entityData)
     * @return array|null
     */
    public function createEntity(array $entityData, string $type = null)
    {
        try {
            if ($type === null) {
                $type =
                    $entityData["type"] ??
                    throw new \InvalidArgumentException("Entity type missing");
            }

            $formattedData = $this->formatter->formatEntityData(
                $entityData,
                $type,
                true // createEntities = true
            );

            Log::info("Original formatted entity data for Wikidata", [
                "entity_data" => json_encode(
                    $formattedData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
            ]);

            $dataToUse =
                is_array($formattedData) &&
                isset($formattedData["formattedData"])
                    ? $formattedData["formattedData"]
                    : $formattedData;

            $optimizedData = $this->optimizer->optimize($dataToUse, "create");

            Log::info("Optimized formatted entity data for Wikidata", [
                "entity_data" => json_encode(
                    $optimizedData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
            ]);

            // If simulation mode is active, we stop here and return a simulated response
            if ($this->simulationMode) {
                Log::info(
                    "Running in simulation mode - no actual Wikidata entity will be created"
                );

                // Create simulated response
                $simulatedResponse = [
                    "entity" => [
                        "id" => "Q" . rand(1000000, 9999999), // Generate random Q-ID
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

            $formData = [
                "action" => "wbeditentity",
                "new" => "item",
                "data" => json_encode($optimizedData),
                "token" => $authData["csrftoken"],
                "format" => "json",
                "bot" => "1", // Mark as bot edit
            ];

            Log::info("Sending request to create Wikidata entity", [
                "form_data" => json_encode(
                    $formData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
                "data_length" => strlen(json_encode($optimizedData)),
                "data_sample" =>
                    mb_substr(json_encode($optimizedData), 0, 200) . "...", // Show the first 200 characters
            ]);

            // Send request to the API
            $client = new \GuzzleHttp\Client([
                "headers" => [
                    "User-Agent" => "Ontology Generator Toolkit Bot/1.0",
                ],
                "cookies" => true,
            ]);

            // Initialize cookie jar with saved cookies
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

                // Log the response for debugging purposes
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
                $errorInfo =
                    $jsonResponse["error"]["info"] ?? "Unknown API error";
                $errorCode = $jsonResponse["error"]["code"] ?? "unknown-error";

                // Check for duplicate entity error (label with description conflict)
                if (
                    $errorCode === "modification-failed" &&
                    strpos($errorInfo, "already has label") !== false &&
                    strpos($errorInfo, "using the same description text") !==
                        false
                ) {
                    // Extract entity ID from error message if possible
                    preg_match("/\[\[(Q\d+)\|/", $errorInfo, $matches);
                    $duplicateId = $matches[1] ?? "unknown";

                    // Build a more helpful error message
                    $label =
                        $optimizedData["labels"]["de"]["value"] ??
                        "(unknown label)";
                    $betterErrorMessage =
                        "Ein Eintrag mit dem Titel \"$label\" und derselben Beschreibung existiert bereits in Wikidata " .
                        "(ID: $duplicateId). Bitte ändern Sie den Titel oder die Beschreibung, um einen eindeutigen Eintrag zu erstellen. " .
                        "Für Testeinträge wird empfohlen, ein Datum/Uhrzeit oder eine eindeutige Kennung im Titel zu verwenden.";

                    Log::error("Wikidata duplicate entity error", [
                        "error" => $betterErrorMessage,
                        "duplicate_id" => $duplicateId,
                        "original_error" => $errorInfo,
                        "label" => $label,
                    ]);

                    // Store better error message in the response
                    $jsonResponse["error"][
                        "better_message"
                    ] = $betterErrorMessage;
                    return $jsonResponse; // Return the response with the error instead of null
                } else {
                    // Handle other errors
                    Log::error("Wikidata API error", [
                        "error" => $errorInfo,
                        "code" => $errorCode,
                        "response" => $jsonResponse,
                    ]);
                    return null;
                }
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
     * Prepares data for a Wikidata entity
     *
     * @param string $title The title of the entity
     * @param string|null $description The description of the entity
     * @param string $type The type of the entity (Q5, Q6983405, etc.)
     * @param array $additionalData Additional data for the entity
     * @return array The prepared data
     */
    public function prepareEntityData(
        string $title,
        ?string $description,
        string $type,
        array $additionalData = []
    ): array {
        $entityData = [
            "title" => $title,
            "description" => $description ?? "",
            "type" => $type,
        ];

        return array_merge($entityData, $additionalData);
    }

    /**
     *
     * @param string $entityId The ID of the entity to update (e.g. Q12345)
     * @param array $rawData The raw data from the request
     * @param string $type The type of the entity (Q5, Q6983405, etc.)
     * @return array|null
     */
    public function updateEntity(string $entityId, array $rawData, string $type)
    {
        try {
            $entityData = $this->formatter->formatEntityData(
                $rawData,
                $type,
                true // createEntities = true
            );

            Log::info("Original formatted entity data for update", [
                "entity_id" => $entityId,
                "entity_data" => json_encode(
                    $entityData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
            ]);

            $dataToUse =
                is_array($entityData) && isset($entityData["formattedData"])
                    ? $entityData["formattedData"]
                    : $entityData;

            $optimizedData = $this->optimizer->optimize($dataToUse, "update");

            // If simulation mode is active, we stop here and return a simulated response
            if ($this->simulationMode) {
                Log::info(
                    "Running in simulation mode - no actual Wikidata entity will be updated"
                );

                // Create simulated response
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

            // Retrieve authentication data or login again
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

            // Prepare request
            $formData = [
                "action" => "wbeditentity",
                "id" => $entityId,
                "data" => json_encode($optimizedData),
                "token" => $authData["csrftoken"],
                "format" => "json",
                "bot" => "1", // Mark as bot edit
            ];

            Log::info("Sending request to update Wikidata entity", [
                "entity_id" => $entityId,
                "form_data" => $formData,
            ]);

            // Send request to the API
            $client = new \GuzzleHttp\Client([
                "headers" => [
                    "User-Agent" => "Ontology Generator Toolkit Bot/1.0",
                ],
                "cookies" => true,
            ]);

            // Initialize cookie jar with saved cookies
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

                // Log the response for debugging purposes
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

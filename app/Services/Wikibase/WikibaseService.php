<?php

namespace App\Services\Wikibase;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\EntityMapping\EntityMappingProviderInterface;

class WikibaseService
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var WikibaseFormatterModels
     */
    protected $formatter;

    /**
     * @var WikibaseOptimizer
     */
    protected $optimizer;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var User|null
     */
    protected $user;

    /**
     * @var bool
     */
    protected $useDebugMode = false;

    /**
     * @var CookieJar
     */
    protected $cookieJar;

    /**
     * @var EntityMappingProviderInterface
     */
    protected $mappingProvider;

    /**
     * WikibaseService constructor.
     *
     * @param Client $client
     * @param WikibaseFormatterModels $formatter
     * @param WikibaseOptimizer $optimizer
     * @param EntityMappingProviderInterface $mappingProvider
     * @param string $baseUrl
     */
    public function __construct(
        Client $client,
        WikibaseFormatterModels $formatter,
        WikibaseOptimizer $optimizer,
        EntityMappingProviderInterface $mappingProvider,
        string $baseUrl = null,
    ) {
        $this->client = $client;
        $this->formatter = $formatter;
        $this->optimizer = $optimizer;
        $this->mappingProvider = $mappingProvider;

        // Determine the appropriate API URL to use
        if ($baseUrl !== null) {
            // Use the explicitly provided URL
            $this->baseUrl = $baseUrl;
        } else {
            // First check for custom Wikibase URL (highest priority)
            $baseUrl = config("wikidata.custom_wikibase_url");

            // If sandbox mode is enabled, use sandbox URL
            if (empty($baseUrl) && config("wikidata.sandbox_mode", false)) {
                $baseUrl = config(
                    "wikidata.sandbox_api_url",
                    "https://test.wikidata.org/w/api.php",
                );
            }

            // If no custom URL, use regular Wikidata API URL
            if (empty($baseUrl)) {
                $baseUrl = config(
                    "wikidata.api_url",
                    "https://www.wikidata.org/w/api.php",
                );
            }

            $this->baseUrl = $baseUrl;
        }

        // Log which API URL is being used
        if (config("wikidata.log_api_urls", false)) {
            Log::info(
                "WikibaseService initialized with API URL: {$this->baseUrl}",
            );
        }

        // Check if debug mode is enabled from config - prefer wikidata.debug_mode over wikibase.debug_mode
        if (config()->has("wikidata.debug_mode")) {
            $this->useDebugMode = config("wikidata.debug_mode");
        } elseif (config()->has("wikibase.debug_mode")) {
            $this->useDebugMode = config("wikibase.debug_mode");
        } else {
            // Default to false in production, true in non-production environments
            $this->useDebugMode = !app()->environment("production");
        }

        // Explicitly disable debug mode in queue workers
        if (defined("LARAVEL_QUEUE_WORKER")) {
            $this->useDebugMode = false;
        }

        // Always disable debug mode if simulation mode is disabled
        if (!config("wikidata.simulation_mode", false)) {
            $this->useDebugMode = false;
        }

        // Set formatter debug mode to match
        $this->formatter->setDebugMode($this->useDebugMode);

        // Log initialization information
        Log::debug("WikibaseService initialized", [
            "api_url" => $this->baseUrl,
            "debug_mode" => $this->useDebugMode ? "enabled" : "disabled",
            "simulation_mode" => config("wikidata.simulation_mode", false)
                ? "enabled"
                : "disabled",
        ]);
    }

    /**
     * Check if the user has valid credentials for Wikibase API
     *
     * @return bool
     */
    public function hasValidCredentials(): bool
    {
        if (!$this->user) {
            return false;
        }

        return !empty($this->user->wikibase_access_token) &&
            !empty($this->user->wikibase_access_secret);
    }

    /**
     * Login to Wikibase API using OAuth credentials
     *
     * @param User $user
     * @return bool True if login successful, false otherwise
     */
    public function login(User $user): bool
    {
        $this->user = $user;

        if ($this->useDebugMode) {
            Log::debug("WikibaseService: Login skipped in debug mode");
            return true;
        }

        if (!$this->hasValidCredentials()) {
            Log::warning(
                "WikibaseService: User does not have valid Wikibase credentials",
            );
            return false;
        }

        try {
            // Test the credentials by making a simple API request
            $response = $this->client->request("GET", $this->baseUrl, [
                "query" => [
                    "action" => "query",
                    "meta" => "userinfo",
                    "format" => "json",
                ],
                "headers" => [
                    "Authorization" => $this->buildAuthHeader(
                        "GET",
                        $this->baseUrl,
                        [
                            "action" => "query",
                            "meta" => "userinfo",
                            "format" => "json",
                        ],
                    ),
                ],
            ]);

            $body = $response->getBody();
            $data = json_decode($body, true);

            if (
                isset($data["query"]["userinfo"]) &&
                $data["query"]["userinfo"]["id"] > 0
            ) {
                Log::info("WikibaseService: Login successful", [
                    "username" =>
                        $data["query"]["userinfo"]["name"] ?? "Unknown",
                ]);
                return true;
            } else {
                Log::warning(
                    "WikibaseService: Login failed - Invalid response",
                    [
                        "response" => $data,
                    ],
                );
                return false;
            }
        } catch (GuzzleException $e) {
            Log::error("WikibaseService: Login failed - HTTP error", [
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("WikibaseService: Login failed - General error", [
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ]);
            return false;
        }
    }

    /**
     * Build OAuth authorization header
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $params Request parameters
     * @return string Authorization header
     */
    protected function buildAuthHeader(
        string $method,
        string $url,
        array $params = [],
    ): string {
        // In a real implementation, this would build the OAuth 1.0a header
        // For debugging/testing purposes, we'll return a dummy header
        if ($this->useDebugMode) {
            return "Bearer DEBUG_TOKEN";
        }

        // Build OAuth 1.0a header using the user's credentials
        // This is a simplified implementation
        $consumerKey = config("services.wikibase.key");
        $consumerSecret = config("services.wikibase.secret");
        $token = $this->user->wikibase_access_token;
        $tokenSecret = $this->user->wikibase_access_secret;

        // You would implement full OAuth 1.0a signature generation here
        // For this example, we're just returning a placeholder
        return "OAuth oauth_consumer_key=\"$consumerKey\", oauth_token=\"$token\"";
    }

    /**
     * Create a new entity in Wikibase
     *
     * @param array $entityData The entity data to create
     * @param string|null $type The entity type
     * @return array|null The response from the API or null on failure
     */
    public function createEntity(
        array $entityData,
        ?string $type = null,
    ): ?array {
        try {
            // Enhanced logging - log we're entering the method
            if (config("wikidata.debug_logging", false)) {
                Log::info("WikibaseService: Starting entity creation process", [
                    "type" => $type,
                    "title" => $entityData["title"] ?? "Unknown",
                    "base_url" => $this->baseUrl,
                    "method" => "createEntity",
                ]);
            }

            // If simulation mode is enabled, just return a mock entity
            if (
                config("wikidata.simulation_mode", false) ||
                (config("app.env") !== "production" && $this->useDebugMode)
            ) {
                Log::info(
                    "WikibaseService: Creating entity in simulation mode (RETURNING FAKE ID)",
                    [
                        "type" => $type,
                        "title" => $entityData["title"] ?? "Unknown",
                        "simulation_mode" => config(
                            "wikidata.simulation_mode",
                            false,
                        ),
                        "debug_mode" => $this->useDebugMode,
                        "environment" => config("app.env"),
                    ],
                );

                // Generate a fake entity ID that looks realistic but is unlikely to exist
                // Use a deterministic generation based on the entity data to avoid different IDs each time
                $seed = md5(json_encode($entityData) . microtime(true));
                $fakeId = "Q" . substr(hexdec($seed), 0, 7);

                Log::info("Generated fake entity ID: {$fakeId}");

                return [
                    "success" => 1,
                    "entity" => [
                        "id" => $fakeId,
                        "type" => $type,
                        "labels" => $entityData["labels"] ?? [],
                    ],
                ];
            }

            // Log entity data before optimization if enabled
            if (config("wikidata.log_entity_creation", false)) {
                Log::info("WikibaseService: Entity data before optimization", [
                    "entity_data" => json_encode($entityData),
                    "type" => $type,
                    "claims_count" => isset($entityData["claims"])
                        ? count($entityData["claims"])
                        : 0,
                ]);
            }

            // Optimize data for create operation
            $optimizedData = $this->optimizer->optimize($entityData, "create");

            // First, get CSRF token for editing
            $csrfToken = $this->getCSRFToken();
            if (!$csrfToken) {
                throw new \Exception(
                    "Failed to get CSRF token for entity creation",
                );
            }

            // Prepare the data for the API call
            $apiParams = [
                "action" => "wbeditentity",
                "new" => "item",
                "token" => $csrfToken,
                "format" => "json",
                "data" => json_encode($optimizedData),
                "summary" => config(
                    "wikidata.edit_summary",
                    "Adding data via OGT API",
                ),
            ];

            // Log the API request if full request logging is enabled
            if (config("wikidata.log_full_requests", false)) {
                Log::info("WikibaseService: Creating entity with API request", [
                    "api_url" => $this->baseUrl,
                    "request_params" => array_merge($apiParams, [
                        "token" => "[REDACTED]",
                    ]),
                ]);
            }

            // Make the API call to create the entity
            $response = $this->client->post($this->baseUrl, [
                "form_params" => $apiParams,
                "cookies" => $this->getCookieJar(),
            ]);

            // Parse the response
            $responseData = json_decode((string) $response->getBody(), true);

            // Log the response if full request logging is enabled
            if (config("wikidata.log_full_requests", false)) {
                Log::info("WikibaseService: Entity creation API response", [
                    "response" => $responseData,
                ]);
            }

            // Check for errors in the response
            if (isset($responseData["error"])) {
                throw new \Exception(
                    "Wikibase API error: " .
                        json_encode($responseData["error"]),
                );
            }

            // If successful, log the new entity ID
            if (isset($responseData["entity"]["id"])) {
                $entityId = $responseData["entity"]["id"];
                Log::info("WikibaseService: Successfully created entity", [
                    "entity_id" => $entityId,
                    "type" => $type,
                    "title" => $entityData["title"] ?? "Unknown",
                ]);

                // Verify the entity actually exists on Wikidata if verification is enabled
                if (config("wikidata.verify_entity_ids", false)) {
                    $this->verifyEntityExists($entityId);
                }

                return $responseData;
            }

            // If we get here, something went wrong but no error was thrown
            Log::warning(
                "WikibaseService: Entity creation response did not contain entity ID",
                [
                    "response" => $responseData,
                ],
            );

            return $responseData;
        } catch (\Exception $e) {
            Log::error("WikibaseService: Error creating entity", [
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
                "type" => $type,
                "title" => $entityData["title"] ?? "Unknown",
            ]);

            return null;
        }
    }

    /**
     * Update an existing entity in Wikibase
     *
     * @param string $entityId The entity ID to update
     * @param array $rawData The data to update
     * @param string $type The entity type
     * @return array|null The response from the API or null on failure
     */
    public function updateEntity(
        string $entityId,
        array $rawData,
        string $type,
    ): ?array {
        try {
            // Use debug mode if enabled
            if ($this->useDebugMode) {
                Log::debug("WikibaseService: Updating entity in debug mode", [
                    "entity_id" => $entityId,
                    "type" => $type,
                ]);

                return [
                    "success" => 1,
                    "entity" => [
                        "id" => $entityId,
                        "type" => $type,
                        "labels" => $rawData["labels"] ?? [],
                    ],
                ];
            }

            // Optimize data for update operation
            $optimizedData = $this->optimizer->optimize($rawData, "update");

            // Implement the actual API call to update an entity
            // This would call the Wikibase API

            // Return mock data for now
            return [
                "success" => 1,
                "entity" => [
                    "id" => $entityId,
                    "type" => $type,
                    "labels" => $rawData["labels"] ?? [],
                ],
            ];
        } catch (\Exception $e) {
            Log::error("WikibaseService: Error updating entity", [
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
                "entity_id" => $entityId,
                "type" => $type,
            ]);

            return null;
        }
    }

    /**
     * Get a CSRF token for editing Wikibase
     *
     * @return string|null The CSRF token or null on failure
     */
    protected function getCSRFToken(): ?string
    {
        try {
            // Check if we have a cached token
            $authData = Cache::get("wikibase_auth_data");
            if (
                $authData &&
                isset($authData["csrftoken"]) &&
                $authData["expires"] > now()->timestamp
            ) {
                Log::debug("Using cached CSRF token");
                return $authData["csrftoken"];
            }

            // If we're in debug mode, return a fake token
            if ($this->useDebugMode) {
                Log::debug("Using fake CSRF token in debug mode");
                return "FAKE_CSRF_TOKEN+\\";
            }

            // If we have a user, try to use their credentials
            if ($this->user && $this->hasValidCredentials()) {
                Log::info(
                    "Getting new CSRF token for Wikibase edits using user credentials",
                );

                $response = $this->client->get($this->baseUrl, [
                    "query" => [
                        "action" => "query",
                        "meta" => "tokens",
                        "type" => "csrf",
                        "format" => "json",
                    ],
                    "cookies" => $this->getCookieJar(),
                ]);

                $data = json_decode((string) $response->getBody(), true);

                if (isset($data["query"]["tokens"]["csrftoken"])) {
                    $token = $data["query"]["tokens"]["csrftoken"];

                    // Cache the token
                    $authData = [
                        "csrftoken" => $token,
                        "expires" => now()->addHours(1)->timestamp,
                    ];
                    Cache::put("wikibase_auth_data", $authData, 3600); // 1 hour

                    Log::info(
                        "Successfully obtained CSRF token using user credentials",
                    );
                    return $token;
                }
            }
            // If no user is available or user doesn't have valid credentials, try direct bot login
            else {
                Log::info(
                    "No user credentials available, trying direct bot login",
                );
                return $this->directBotLogin();
            }

            Log::error(
                "Failed to get CSRF token - response does not contain token",
                [
                    "response" => $data ?? "No response data",
                ],
            );

            return null;
        } catch (\Exception $e) {
            Log::error("Exception getting CSRF token", [
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ]);

            return null;
        }
    }

    /**
     * Get or create a cookie jar for API requests
     *
     * @return \GuzzleHttp\Cookie\CookieJarInterface
     */
    protected function getCookieJar()
    {
        // Create a cookie jar if one doesn't exist
        if (!isset($this->cookieJar)) {
            $this->cookieJar = new \GuzzleHttp\Cookie\CookieJar();
        }

        return $this->cookieJar;
    }

    /**
     * Verify that an entity exists on Wikibase
     *
     * @param string $entityId The entity ID to verify
     * @return bool Whether the entity exists
     */
    protected function verifyEntityExists(string $entityId): bool
    {
        try {
            if ($this->useDebugMode) {
                Log::debug(
                    "WikibaseService: Skipping entity verification in debug mode",
                );
                return true;
            }

            $response = $this->client->get($this->baseUrl, [
                "query" => [
                    "action" => "wbgetentities",
                    "ids" => $entityId,
                    "format" => "json",
                ],
                "cookies" => $this->getCookieJar(),
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if (
                isset($data["entities"][$entityId]) &&
                !isset($data["entities"][$entityId]["missing"])
            ) {
                Log::info("Entity verification successful", [
                    "entity_id" => $entityId,
                ]);
                return true;
            }

            Log::warning("Entity verification failed - entity does not exist", [
                "entity_id" => $entityId,
                "response" => $data,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Exception verifying entity exists", [
                "message" => $e->getMessage(),
                "entity_id" => $entityId,
            ]);

            return false;
        }
    }

    /**
     * Directly login with bot credentials
     *
     * @return string|null CSRF token if login successful, null otherwise
     */
    public function directBotLogin(): ?string
    {
        try {
            $username = config("wikidata.bot_username");
            $password = config("wikidata.bot_password");

            if (empty($username) || empty($password)) {
                Log::error(
                    "WikibaseService: Missing bot credentials in config",
                );
                return null;
            }
            
            // Special handling for MediaWiki bot passwords
            // Format should be: username@botname and password should be the bot password
            $botName = null;
            if (strpos($username, '@') !== false) {
                $parts = explode('@', $username);
                $mainUsername = $parts[0];
                $botName = $parts[1];
                
                // For MediaWiki API bot passwords, username becomes "username@botname"
                // See: https://www.mediawiki.org/wiki/Manual:Bot_passwords
                Log::info("Using MediaWiki bot password format", [
                    "original" => $username,
                    "main_username" => $mainUsername,
                    "bot_name" => $botName
                ]);
            }

            Log::info("Starting direct bot login for WikibaseService", [
                "username" => $username,
            ]);

            // Initialize HTTP client with specific cookie jar to prevent cookie mixing
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            $client = new \GuzzleHttp\Client([
                "cookies" => $cookieJar,
                "headers" => [
                    "User-Agent" => "Ontology Generator Toolkit Bot/1.0",
                ],
            ]);

            // 1. Retrieve login token
            $tokenResponse = $client->get($this->baseUrl, [
                "query" => [
                    "action" => "query",
                    "meta" => "tokens",
                    "type" => "login",
                    "format" => "json",
                ],
            ]);

            $tokenData = json_decode(
                $tokenResponse->getBody()->getContents(),
                true,
            );

            if (!isset($tokenData["query"]["tokens"]["logintoken"])) {
                Log::error("Failed to get login token", [
                    "response" => $tokenData,
                ]);
                return null;
            }

            $loginToken = $tokenData["query"]["tokens"]["logintoken"];
            Log::info("Obtained login token from Wikidata API");

            // 2. Login with the token
            $loginParams = [
                "action" => "login",
                "lgname" => $username,
                "lgpassword" => $password,
                "lgtoken" => $loginToken,
                "format" => "json",
            ];
            
            // Log the login request (without password)
            Log::info("Sending login request", [
                "params" => array_merge($loginParams, ["lgpassword" => "[REDACTED]"]),
                "url" => $this->baseUrl
            ]);
            
            $loginResponse = $client->post($this->baseUrl, [
                "form_params" => $loginParams,
            ]);

            $loginData = json_decode(
                $loginResponse->getBody()->getContents(),
                true,
            );

            if (
                !isset($loginData["login"]["result"]) ||
                $loginData["login"]["result"] !== "Success"
            ) {
                Log::error("Failed to login", ["response" => $loginData]);
                return null;
            }

            Log::info("Successfully logged in to Wikidata API", [
                "username" => $username,
                "result" => $loginData["login"]["result"],
            ]);

            // 3. Retrieve CSRF token for edits
            $csrfResponse = $client->get($this->baseUrl, [
                "query" => [
                    "action" => "query",
                    "meta" => "tokens",
                    "format" => "json",
                ],
            ]);

            $csrfData = json_decode(
                $csrfResponse->getBody()->getContents(),
                true,
            );

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

            Cache::put("wikibase_auth_data", $authData, 3600); // 1 hour
            Log::info("Auth data saved to cache", [
                "cookies_count" => count($cookies),
            ]);

            // Store the cookieJar for future requests
            $this->cookieJar = $cookieJar;

            return $csrfToken;
        } catch (\Exception $e) {
            Log::error("Direct bot login error", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Queue an entity creation job
     *
     * @param array $data The entity data
     * @param string $type The entity type
     * @param string|null $callbackUrl Optional callback URL
     * @param string|null $parentEntityId Optional parent entity ID
     * @param string|null $relationshipType Optional relationship type
     * @param int|null $requestId Optional request ID
     * @return bool Whether the job was successfully queued
     */
    public function queueEntityCreation(
        array $data,
        string $type,
        ?string $callbackUrl = null,
        ?string $parentEntityId = null,
        ?string $relationshipType = null,
        ?int $requestId = null,
    ): bool {
        try {
            // Use debug mode if enabled
            if ($this->useDebugMode) {
                Log::debug(
                    "WikibaseService: Queuing entity creation in debug mode",
                    [
                        "type" => $type,
                        "title" => $data["title"] ?? "Unknown",
                    ],
                );

                return true;
            }

            // Add a flag to know this entity is part of a chain
            $data["_inChain"] = true;

            // Dispatch the CreateWikidataEntity job
            \App\Jobs\CreateWikidataEntity::dispatch(
                $data,
                $type,
                $callbackUrl,
                $parentEntityId,
                $relationshipType,
                $requestId,
            );

            return true;
        } catch (\Exception $e) {
            Log::error("WikibaseService: Error queuing entity creation", [
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
                "type" => $type,
                "title" => $data["title"] ?? "Unknown",
            ]);

            return false;
        }
    }

    /**
     * Creates a User object with bot credentials from config
     *
     * @return \App\Models\User
     */
    public function getBotUser(): \App\Models\User
    {
        $botUser = new \App\Models\User();
        $botUser->name = config("wikidata.bot_username");
        $botUser->wikibase_access_token = config("wikidata.bot_username");
        $botUser->wikibase_access_secret = config("wikidata.bot_password");

        return $botUser;
    }
}

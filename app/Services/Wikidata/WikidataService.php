<?php

namespace App\Services\Wikidata;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Wikidata\WikidataOptimizer;
use App\Services\Wikidata\WikidataFormatterModels;

class WikidataService
{
    /**
     * Entity type constants
     */
    public const ENTITY_TYPE_BOOK = "Q7725634"; // Literary work

    /**
     * Maximum number of retries for rate-limited operations
     */
    protected $maxRetries = 3;

    /**
     * Initial delay in seconds before retry
     */
    protected $initialRetryDelay = 5;

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
     * @var WikidataFormatterModels
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
        $this->formatter = new WikidataFormatterModels();
        $this->optimizer = new WikidataOptimizer();
        $this->debug = config("wikidata.debug", false);

        // Get retry configuration from config
        $this->maxRetries = config("wikidata.max_retries", 3);
        $this->initialRetryDelay = config("wikidata.initial_retry_delay", 5);

        if ($this->debug) {
            Log::info("WikidataService initialized", [
                "username" => $this->username,
                "api_url" => $this->apiUrl,
                "max_retries" => $this->maxRetries,
                "initial_retry_delay" => $this->initialRetryDelay,
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

            Log::info("Wikidata entity creation mode", [
                "entity_type" => $type,
            ]);

            // Force debug mode off to ensure we get full data
            $this->formatter->setDebugMode(false);

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

            // Check if we have both formattedData and updatedData and combine them
            if (
                is_array($formattedData) &&
                isset($formattedData["formattedData"]) &&
                isset($formattedData["updatedData"])
            ) {
                Log::info(
                    "Combining formattedData and updatedData for complete entity"
                );

                // Start with formattedData structure
                $combinedData = $formattedData["formattedData"];

                // Add claims from updatedData
                if (isset($formattedData["updatedData"]["claims"])) {
                    foreach (
                        $formattedData["updatedData"]["claims"]
                        as $property => $claims
                    ) {
                        // Don't overwrite P31 from formattedData if it exists
                        if (
                            $property !== "P31" ||
                            !isset($combinedData["claims"]["P31"])
                        ) {
                            $combinedData["claims"][$property] = $claims;
                        }
                    }
                }

                Log::info("Created combined entity data", [
                    "claim_count" => count($combinedData["claims"]),
                    "property_keys" => array_keys($combinedData["claims"]),
                ]);

                $formattedData = $combinedData;
            } elseif (
                is_array($formattedData) &&
                isset($formattedData["formattedData"])
            ) {
                $formattedData = $formattedData["formattedData"];
                Log::info("Using formattedData only");
            }

            // Apply minimal optimization - just remove ID if present for creation
            $optimizedData = $formattedData;
            if (isset($optimizedData["id"])) {
                Log::info("Removing ID for create operation");
                unset($optimizedData["id"]);
            }

            Log::info("Formatted entity data for Wikidata", [
                "entity_data" => json_encode(
                    $optimizedData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
            ]);

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

                // Check for rate limiting error - 'no-automatic-entity-id'
                if ($errorCode === "no-automatic-entity-id") {
                    // Generate a unique cache key for this specific entity creation attempt
                    $cacheKey =
                        "wikidata_rate_limit_retry_" .
                        md5(json_encode($optimizedData));
                    $currentRetry = Cache::get($cacheKey, 0);

                    // Only retry up to maxRetries times
                    if ($currentRetry >= $this->maxRetries) {
                        Log::error(
                            "Rate limiting error after maximum retries",
                            [
                                "error" => $errorInfo,
                                "retry_count" => $currentRetry,
                            ]
                        );
                        return null;
                    }

                    // Increment the retry counter
                    Cache::put($cacheKey, $currentRetry + 1, 600); // Cache for 10 minutes

                    Log::warning("Rate limiting detected from Wikidata API", [
                        "error" => $errorInfo,
                        "code" => $errorCode,
                        "retry" => $currentRetry + 1,
                    ]);

                    // Calculate exponential backoff wait time (5, 10, 20 seconds)
                    $waitTime =
                        $this->initialRetryDelay * pow(2, $currentRetry);
                    Log::info("Waiting {$waitTime} seconds before retrying...");
                    sleep($waitTime);

                    // Retry creating the entity
                    return $this->createEntity($entityData, $type);
                }

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

                    // Return the duplicate ID as if we created it
                    // This helps prevent further duplicate creation attempts
                    return [
                        "entity" => [
                            "id" => $duplicateId,
                            "type" => "item",
                        ],
                        "success" => 1,
                        "warning" => "Used existing entity",
                    ];
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
            // Force debug mode off to ensure we get full data
            $this->formatter->setDebugMode(false);

            // Special handling for relationship updates that only modify claims
            $isRelationshipUpdate = false;
            if (isset($rawData['claims']) && count($rawData) === 1) {
                $isRelationshipUpdate = true;
                Log::info("Detected relationship-only update - will preserve original labels and existing claims", [
                    "entity_id" => $entityId,
                    "properties" => array_keys($rawData['claims']),
                ]);
                
                // Check if P31 is already in the entity before we format data
                $shouldRemoveP31 = true;
            }

            $entityData = $this->formatter->formatEntityData(
                $rawData,
                $type,
                true // createEntities = true
            );
            
            // For relationship updates, remove the P31 claim to prevent duplication
            if ($isRelationshipUpdate && isset($shouldRemoveP31) && $shouldRemoveP31) {
                if (isset($entityData['formattedData']['claims']['P31'])) {
                    unset($entityData['formattedData']['claims']['P31']);
                    Log::info("Removed P31 claim from relationship update to prevent duplication");
                }
            }

            Log::info("Original formatted entity data for update", [
                "entity_id" => $entityId,
                "entity_data" => json_encode(
                    $entityData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
            ]);

            // Check if we have both formattedData and updatedData and combine them
            if (
                is_array($entityData) &&
                isset($entityData["formattedData"]) &&
                isset($entityData["updatedData"])
            ) {
                Log::info(
                    "Combining formattedData and updatedData for complete entity update"
                );

                // Start with formattedData structure
                $combinedData = $entityData["formattedData"];

                // Add claims from updatedData
                if (isset($entityData["updatedData"]["claims"])) {
                    foreach (
                        $entityData["updatedData"]["claims"]
                        as $property => $claims
                    ) {
                        // Don't overwrite P31 from formattedData if it exists
                        if (
                            $property !== "P31" ||
                            !isset($combinedData["claims"]["P31"])
                        ) {
                            $combinedData["claims"][$property] = $claims;
                        }
                    }
                }

                Log::info("Created combined entity data for update", [
                    "claim_count" => count($combinedData["claims"]),
                    "property_keys" => array_keys($combinedData["claims"]),
                ]);

                $dataToUse = $combinedData;
            } elseif (
                is_array($entityData) &&
                isset($entityData["formattedData"])
            ) {
                $dataToUse = $entityData["formattedData"];
                Log::info("Using formattedData only for update");
            } else {
                $dataToUse = $entityData;
                Log::info("Using direct formatter output for update");
            }

            // For relationship updates, only include the claims to avoid losing labels and descriptions
            if ($isRelationshipUpdate && isset($dataToUse['claims'])) {
                $optimizedData = [
                    'claims' => $dataToUse['claims']
                ];
                Log::info("Optimized update to only include claims to preserve existing labels and descriptions");
            } else {
                // Use data directly with minimal processing
                $optimizedData = $dataToUse;
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
    /**
     * Process relationship information from entity data
     * 
     * @param array $data The entity data
     * @param string|null $parentEntityId The parent entity ID parameter
     * @param string|null $relationshipType The relationship type parameter
     * @return array [processedData, parentEntityId, relationshipType]
     */
    protected function processRelationshipInfo(
        array $data,
        ?string $parentEntityId = null,
        ?string $relationshipType = null
    ): array {
        $dataCopy = $data;
        
        // Check if relationship info is available in data but not in params
        if ($parentEntityId === null && isset($dataCopy["parent_entity_id"])) {
            $parentEntityId = $dataCopy["parent_entity_id"];
            Log::debug("Using parent_entity_id from data", [
                "parent_id" => $parentEntityId
            ]);
        }
        
        if ($relationshipType === null && isset($dataCopy["relationship_type"])) {
            $relationshipType = $dataCopy["relationship_type"];
            Log::debug("Using relationship_type from data", [
                "relationship" => $relationshipType
            ]);
        }
        
        // Check if role is specified but relationship_type isn't
        if ($relationshipType === null && isset($dataCopy["role"])) {
            $relationshipType = $dataCopy["role"];
            Log::debug("Using role as relationship_type", [
                "role" => $dataCopy["role"]
            ]);
        }

        // Store relation data in the entity data itself for reference
        if ($parentEntityId !== null) {
            $dataCopy["_relation"] = [
                "parent_id" => $parentEntityId,
                "type" => $relationshipType
            ];
        }
        
        return [$dataCopy, $parentEntityId, $relationshipType];
    }

    /**
     * Queues a new entity for creation in Wikidata
     *
     * @param array $data The entity data
     * @param string $type The entity type (Q code)
     * @param string|null $callbackUrl Optional callback URL
     * @param string|null $parentEntityId Optional parent entity ID to link to
     * @param string|null $relationshipType Optional relationship type to parent (source, perpetrator, victim)
     * @return bool Whether the job was successfully queued
     */
    public function queueEntityCreation(
        array $data,
        string $type,
        ?string $callbackUrl = null,
        ?string $parentEntityId = null,
        ?string $relationshipType = null
    ): bool {
        try {
            // Ensure entity name is properly included in the data
            if (empty($data["title"]) && isset($data["name"])) {
                $data["title"] = $data["name"];
                Log::debug("Setting title from name field", [
                    "name" => $data["name"],
                    "type" => $type,
                ]);
            }
            
            // Process relationship information
            list($data, $parentEntityId, $relationshipType) = $this->processRelationshipInfo(
                $data,
                $parentEntityId,
                $relationshipType
            );
            
            // If we still don't have a parent ID, but we should create a relationship,
            // check the global variable as a last resort
            if ($parentEntityId === null && $relationshipType !== null && isset($GLOBALS['last_created_entity_id'])) {
                $parentEntityId = $GLOBALS['last_created_entity_id'];
                $data['parent_entity_id'] = $parentEntityId;
                $data['_relation'] = [
                    'parent_id' => $parentEntityId,
                    'type' => $relationshipType
                ];
                
                Log::info("Using global variable as parent entity ID", [
                    "parent_id" => $parentEntityId,
                    "relationship" => $relationshipType
                ]);
            }

            \App\Jobs\CreateWikidataEntity::dispatch(
                $data,
                $type,
                $callbackUrl,
                $parentEntityId,
                $relationshipType
            )->onQueue("wikidata");

            Log::info("Queued Wikidata entity creation job", [
                "type" => $type,
                "title" => $data["title"] ?? "Unknown",
                "name" => $data["name"] ?? null,
                "parent_id" => $parentEntityId,
                "relationship" => $relationshipType,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue Wikidata entity creation job", [
                "error" => $e->getMessage(),
                "type" => $type,
                "title" => $data["title"] ?? null,
                "name" => $data["name"] ?? null,
                "parent_id" => $parentEntityId,
            ]);

            return false;
        }
    }
}

<?php

namespace App\Services\Wikibase;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
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
        string $baseUrl = "https://test.wikidata.org/w/api.php",
    ) {
        $this->client = $client;
        $this->formatter = $formatter;
        $this->optimizer = $optimizer;
        $this->mappingProvider = $mappingProvider;
        $this->baseUrl = $baseUrl;

        // Check if debug mode is enabled from config
        if (config()->has("wikibase.debug_mode")) {
            $this->useDebugMode = config("wikibase.debug_mode");
            $this->formatter->setDebugMode($this->useDebugMode);
        }
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
    public function createEntity(array $entityData, ?string $type = null): ?array
    {
        try {
            // Use debug mode if enabled
            if ($this->useDebugMode) {
                Log::debug("WikibaseService: Creating entity in debug mode", [
                    "type" => $type,
                    "title" => $entityData["title"] ?? "Unknown",
                ]);
                
                // Generate a fake entity ID
                $fakeId = "Q" . rand(1000000, 9999999);
                
                return [
                    "success" => 1,
                    "entity" => [
                        "id" => $fakeId,
                        "type" => $type,
                        "labels" => $entityData["labels"] ?? [],
                    ],
                ];
            }
            
            // Optimize data for create operation
            $optimizedData = $this->optimizer->optimize($entityData, "create");
            
            // Implement the actual API call to create an entity
            // This would call the Wikibase API
            
            // Return mock data for now
            return [
                "success" => 1,
                "entity" => [
                    "id" => "Q" . rand(1000000, 9999999),
                    "type" => $type,
                    "labels" => $entityData["labels"] ?? [],
                ],
            ];
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
    public function updateEntity(string $entityId, array $rawData, string $type): ?array
    {
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
        ?int $requestId = null
    ): bool {
        try {
            // Use debug mode if enabled
            if ($this->useDebugMode) {
                Log::debug("WikibaseService: Queuing entity creation in debug mode", [
                    "type" => $type,
                    "title" => $data["title"] ?? "Unknown",
                ]);
                
                return true;
            }
            
            // Add a flag to know this entity is part of a chain
            $data['_inChain'] = true;
            
            // Dispatch the CreateWikidataEntity job
            \App\Jobs\CreateWikidataEntity::dispatch(
                $data,
                $type,
                $callbackUrl,
                $parentEntityId,
                $relationshipType,
                $requestId
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
}
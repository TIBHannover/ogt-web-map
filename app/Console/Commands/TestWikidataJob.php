<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WikidataEntryRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class TestWikidataJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wikidata:test-job-direct';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Wikidata job directly with URL string format';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $entryRequest = null;
        
        // Try to find an existing entry request, or create a new one
        try {
            $entryRequest = WikidataEntryRequest::where('status', 'pending')->first();
            
            if (!$entryRequest) {
                $this->info("No pending entry request found, creating one...");
                $entryRequest = WikidataEntryRequest::create([
                    'title' => 'Direct Job Test ' . date('Y-m-d H:i:s'),
                    'description' => 'Testing direct job execution',
                    'type' => 'Q6983405',
                    'status' => 'pending'
                ]);
            }
            
            $this->info("Using entry request #" . $entryRequest->id . ": " . $entryRequest->title);
        } catch (\Exception $e) {
            $this->error("Error preparing entry request: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        // Now do the direct creation using URL string format that worked in test
        $username = config('wikidata.bot_username');
        $password = config('wikidata.bot_password');
        $apiUrl = config('wikidata.api_url');
        
        $this->info("Testing direct Wikidata entity creation with username: {$username}");
        $this->info("API URL: {$apiUrl}");
        
        // HTTP-Client mit spezifischer Cookie-Jar initialisieren
        $cookieJar = new CookieJar();
        $client = new Client([
            'cookies' => $cookieJar,
            'headers' => [
                'User-Agent' => 'Ontology Generator Toolkit Bot/1.0',
            ],
            'verify' => false, // Deaktiviere SSL-Verifizierung nur fu00fcr Tests!
        ]);
        
        try {
            // Step 1: Login
            $this->info("Step 1: Login...");
            
            // 1.1: Get login token
            $tokenResponse = $client->get($apiUrl, [
                'query' => [
                    "action" => "query",
                    "meta" => "tokens",
                    "type" => "login",
                    "format" => "json",
                ],
            ]);

            $tokenData = json_decode($tokenResponse->getBody()->getContents(), true);
            if (!isset($tokenData["query"]["tokens"]["logintoken"])) {
                $this->error("Failed to get login token");
                return Command::FAILURE;
            }

            $loginToken = $tokenData["query"]["tokens"]["logintoken"];
            $this->info("Obtained login token");

            // 1.2: Login with token
            $loginResponse = $client->post($apiUrl, [
                'form_params' => [
                    "action" => "login",
                    "lgname" => $username,
                    "lgpassword" => $password,
                    "lgtoken" => $loginToken,
                    "format" => "json",
                ],
            ]);

            $loginData = json_decode($loginResponse->getBody()->getContents(), true);
            if (
                !isset($loginData["login"]["result"]) ||
                $loginData["login"]["result"] !== "Success"
            ) {
                $this->error("Failed to login: " . ($loginData["login"]["reason"] ?? "Unknown error"));
                return Command::FAILURE;
            }

            $this->info("Successfully logged in as {$username}");

            // 1.3: Get CSRF token
            $csrfResponse = $client->get($apiUrl, [
                'query' => [
                    "action" => "query",
                    "meta" => "tokens",
                    "format" => "json",
                ],
            ]);

            $csrfData = json_decode($csrfResponse->getBody()->getContents(), true);
            if (!isset($csrfData["query"]["tokens"]["csrftoken"])) {
                $this->error("Failed to get CSRF token");
                return Command::FAILURE;
            }

            $csrfToken = $csrfData["query"]["tokens"]["csrftoken"];
            $this->info("Obtained CSRF token");
            
            // Step 2: Create entity
            $this->info("Step 2: Creating entity for request " . $entryRequest->title);
            
            // Extract domain from API URL to create entity URL base
            $parsedUrl = parse_url($apiUrl);
            $domain = $parsedUrl['host'];
            $entityUrlBase = "https://{$domain}/entity/";
            
            // Prepare entity data with URL strings
            $entityData = [
                'labels' => [
                    'de' => [
                        'language' => 'de',
                        'value' => $entryRequest->title,
                    ],
                ],
                'descriptions' => [
                    'de' => [
                        'language' => 'de',
                        'value' => $entryRequest->description,
                    ],
                ],
                'claims' => [
                    'P31' => [
                        [
                            'mainsnak' => [
                                'snaktype' => 'value',
                                'property' => 'P31',
                                'datavalue' => [
                                    'value' => $entityUrlBase . $entryRequest->type,
                                    'type' => 'string',
                                ],
                            ],
                            'type' => 'statement',
                            'rank' => 'normal',
                        ],
                    ],
                    'P17' => [
                        [
                            'mainsnak' => [
                                'snaktype' => 'value',
                                'property' => 'P17',
                                'datavalue' => [
                                    'value' => $entityUrlBase . 'Q7318',
                                    'type' => 'string',
                                ],
                            ],
                            'type' => 'statement',
                            'rank' => 'normal',
                        ],
                    ],
                ],
            ];
            
            // Send create request
            $formData = [
                'action' => 'wbeditentity',
                'new' => 'item',
                'data' => json_encode($entityData),
                'token' => $csrfToken,
                'format' => 'json',
                'bot' => '1',
            ];
            
            $this->info("Sending create request with data: " . json_encode($formData, JSON_PRETTY_PRINT));
            
            $response = $client->post($apiUrl, [
                'form_params' => $formData,
            ]);
            
            $responseBody = $response->getBody()->getContents();
            $jsonResponse = json_decode($responseBody, true);
            
            $this->info("API Response: " . $responseBody);
            
            if (isset($jsonResponse['error'])) {
                $this->error("Error creating entity: " . ($jsonResponse['error']['info'] ?? 'Unknown error'));
                $entryRequest->status = 'error';
                $entryRequest->error_message = $jsonResponse['error']['info'];
                $entryRequest->save();
                return Command::FAILURE;
            }
            
            if (!isset($jsonResponse['entity']['id'])) {
                $this->error("Entity ID not found in response");
                $entryRequest->status = 'error';
                $entryRequest->error_message = 'Entity ID not found in response';
                $entryRequest->save();
                return Command::FAILURE;
            }
            
            $entityId = $jsonResponse['entity']['id'];
            $this->info("Successfully created entity with ID: {$entityId}");
            
            // Update the entry request
            $entryRequest->wikidata_id = $entityId;
            $entryRequest->status = 'approved';
            $entryRequest->save();
            
            $this->info("Updated entry request with Wikidata ID");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            $this->error("Stack trace: {$e->getTraceAsString()}");
            
            $entryRequest->status = 'error';
            $entryRequest->error_message = $e->getMessage();
            $entryRequest->save();
            
            return Command::FAILURE;
        }
    }
}
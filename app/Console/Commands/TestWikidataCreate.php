<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class TestWikidataCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "wikidata:test-create";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Test creating a Wikidata entity";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $username = config("wikidata.bot_username");
        $password = config("wikidata.bot_password");
        $apiUrl = config("wikidata.api_url");

        $this->info(
            "Testing Wikidata entity creation with username: {$username}"
        );
        $this->info("API URL: {$apiUrl}");

        // HTTP-Client mit spezifischer Cookie-Jar initialisieren
        $cookieJar = new CookieJar();
        $client = new Client([
            "cookies" => $cookieJar,
            "headers" => [
                "User-Agent" => "Ontology Generator Toolkit Bot/1.0",
            ],
            "verify" => false, // Deaktiviere SSL-Verifizierung nur für Tests!
        ]);

        try {
            // Step 1: Login
            $this->info("Step 1: Login...");

            // 1.1: Get login token
            $tokenResponse = $client->get($apiUrl, [
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
            if (!isset($tokenData["query"]["tokens"]["logintoken"])) {
                $this->error("Failed to get login token");
                return Command::FAILURE;
            }

            $loginToken = $tokenData["query"]["tokens"]["logintoken"];
            $this->info("Obtained login token");

            // 1.2: Login with token
            $loginResponse = $client->post($apiUrl, [
                "form_params" => [
                    "action" => "login",
                    "lgname" => $username,
                    "lgpassword" => $password,
                    "lgtoken" => $loginToken,
                    "format" => "json",
                ],
            ]);

            $loginData = json_decode(
                $loginResponse->getBody()->getContents(),
                true
            );
            if (
                !isset($loginData["login"]["result"]) ||
                $loginData["login"]["result"] !== "Success"
            ) {
                $this->error(
                    "Failed to login: " .
                        ($loginData["login"]["reason"] ?? "Unknown error")
                );
                return Command::FAILURE;
            }

            $this->info("Successfully logged in as {$username}");

            // 1.3: Get CSRF token
            $csrfResponse = $client->get($apiUrl, [
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
            if (!isset($csrfData["query"]["tokens"]["csrftoken"])) {
                $this->error("Failed to get CSRF token");
                return Command::FAILURE;
            }

            $csrfToken = $csrfData["query"]["tokens"]["csrftoken"];
            $this->info("Obtained CSRF token");

            // Step 2: Create test entity
            $this->info("Step 2: Creating test entity...");

            // Prepare test data
            $testData = [
                "labels" => [
                    "de" => [
                        "language" => "de",
                        "value" => "Test Entitu00e4t " . date("Y-m-d H:i:s"),
                    ],
                ],
                "descriptions" => [
                    "de" => [
                        "language" => "de",
                        "value" =>
                            "Test Beschreibung - erstellt von OGT API Tests",
                    ],
                ],
                "claims" => [
                    "P31" => [
                        [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => "P31",
                                "datavalue" => [
                                    "value" => [
                                        "entity-type" => "item",
                                        "id" => "Q6983405", // event
                                        "numeric-id" => 6983405,
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

            // Send create request
            $formData = [
                "action" => "wbeditentity",
                "new" => "item",
                "data" => json_encode($testData),
                "token" => $csrfToken,
                "format" => "json",
                "bot" => "1",
            ];

            $this->info(
                "Sending create request with data: " .
                    json_encode($formData, JSON_PRETTY_PRINT)
            );

            $response = $client->post($apiUrl, [
                "form_params" => $formData,
            ]);

            $responseBody = $response->getBody()->getContents();
            $jsonResponse = json_decode($responseBody, true);

            $this->info("API Response: " . $responseBody);

            if (isset($jsonResponse["error"])) {
                $this->error(
                    "Error creating entity: " .
                        ($jsonResponse["error"]["info"] ?? "Unknown error")
                );
                return Command::FAILURE;
            }

            if (!isset($jsonResponse["entity"]["id"])) {
                $this->error("Entity ID not found in response");
                return Command::FAILURE;
            }

            $entityId = $jsonResponse["entity"]["id"];
            $this->info("Successfully created entity with ID: {$entityId}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            $this->error("Stack trace: {$e->getTraceAsString()}");
            return Command::FAILURE;
        }
    }
}

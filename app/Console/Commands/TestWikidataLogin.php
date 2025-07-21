<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class TestWikidataLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wikidata:test-login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Wikidata login with bot credentials';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $username = config('wikidata.bot_username');
        $password = config('wikidata.bot_password');
        $apiUrl = config('wikidata.api_url');
        
        $this->info("Testing Wikidata login with username: {$username}");
        $this->info("API URL: {$apiUrl}");
        
        // HTTP-Client mit spezifischer Cookie-Jar initialisieren
        $cookieJar = new CookieJar();
        $client = new Client([
            'cookies' => $cookieJar,
            'headers' => [
                'User-Agent' => 'Ontology Generator Toolkit Bot/1.0',
            ],
            'verify' => false, // Deaktiviere SSL-Verifizierung nur für Tests!
        ]);
        
        try {
            // 1. CSRF-Token abrufen
            $this->info("Step 1: Getting login token...");
            $tokenResponse = $client->get($apiUrl, [
                'query' => [
                    "action" => "query",
                    "meta" => "tokens",
                    "type" => "login",
                    "format" => "json",
                ],
            ]);

            $tokenData = json_decode($tokenResponse->getBody()->getContents(), true);
            $this->info("Token response: " . json_encode($tokenData, JSON_PRETTY_PRINT));
            
            if (!isset($tokenData["query"]["tokens"]["logintoken"])) {
                $this->error("Failed to get login token");
                return Command::FAILURE;
            }

            $loginToken = $tokenData["query"]["tokens"]["logintoken"];
            $this->info("Obtained login token: {$loginToken}");

            // 2. Einloggen mit dem Token
            $this->info("Step 2: Trying to login...");
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
            $this->info("Login response: " . json_encode($loginData, JSON_PRETTY_PRINT));
            
            if (
                !isset($loginData["login"]["result"]) ||
                $loginData["login"]["result"] !== "Success"
            ) {
                $this->error("Failed to login: " . ($loginData["login"]["reason"] ?? "Unknown error"));
                return Command::FAILURE;
            }

            $this->info("Successfully logged in as {$username}");

            // 3. CSRF-Token für Bearbeitungen abrufen
            $this->info("Step 3: Getting CSRF token...");
            $csrfResponse = $client->get($apiUrl, [
                'query' => [
                    "action" => "query",
                    "meta" => "tokens",
                    "format" => "json",
                ],
            ]);

            $csrfData = json_decode($csrfResponse->getBody()->getContents(), true);
            $this->info("CSRF response: " . json_encode($csrfData, JSON_PRETTY_PRINT));
            
            if (!isset($csrfData["query"]["tokens"]["csrftoken"])) {
                $this->error("Failed to get CSRF token");
                return Command::FAILURE;
            }

            $csrfToken = $csrfData["query"]["tokens"]["csrftoken"];
            $this->info("Obtained CSRF token: {$csrfToken}");
            
            // 4. Zeige alle Cookies an
            $this->info("Step 4: Displaying cookies...");
            foreach ($cookieJar->toArray() as $i => $cookie) {
                $this->info("Cookie #{$i}: {$cookie['Name']} = {$cookie['Value']}");
            }
            
            $this->info("Login test completed successfully!");
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            $this->error("Stack trace: {$e->getTraceAsString()}");
            return Command::FAILURE;
        }
    }
}
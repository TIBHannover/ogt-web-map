<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TestWikidataBot extends Command
{
    protected $signature = "wikidata:test-bot {--debug : Zeige ausführliche Debug-Informationen}";
    protected $description = "Testet die Anmeldung mit dem Wikidata-Bot";

    private $apiUrl = "https://test.wikidata.org/w/api.php"; // Test-API

    public function handle()
    {
        $this->info("Starte Test der Wikidata-Bot Anmeldung...");

        $botUsername = config("wikidata.bot_username");
        $botPassword = config("wikidata.bot_password");

        $this->info("Bot-Benutzername: " . $botUsername);
        $this->info("API-URL: " . $this->apiUrl);

        if ($this->option("debug")) {
            $this->info(
                "Bot-Passwort (erste 3 Zeichen): " .
                    substr($botPassword, 0, 3) .
                    "..."
            );
        }

        try {
            // Login-Token anfordern
            $this->info("Fordere Login-Token an...");
            $loginTokenResponse = Http::get($this->apiUrl, [
                "action" => "query",
                "meta" => "tokens",
                "type" => "login",
                "format" => "json",
            ]);

            if ($this->option("debug")) {
                $this->line(
                    "Login-Token Antwort: " .
                        json_encode(
                            $loginTokenResponse->json(),
                            JSON_PRETTY_PRINT
                        )
                );
            }

            if (!$loginTokenResponse->ok()) {
                throw new \Exception(
                    "Login-Token Anfrage fehlgeschlagen: " .
                        $loginTokenResponse->status()
                );
            }

            $loginTokenJson = $loginTokenResponse->json();

            if (
                !isset($loginTokenJson["query"]) ||
                !isset($loginTokenJson["query"]["tokens"]) ||
                !isset($loginTokenJson["query"]["tokens"]["logintoken"])
            ) {
                throw new \Exception("Login-Token nicht gefunden in Antwort");
            }

            $loginToken = $loginTokenJson["query"]["tokens"]["logintoken"];
            $this->info(
                "Login-Token erhalten: " . substr($loginToken, 0, 5) . "..."
            );

            // Mit dem Login-Token anmelden
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();

            $this->info("Versuche Anmeldung mit Bot-Zugangsdaten...");
            $loginResponse = Http::withOptions([
                "cookies" => $cookieJar,
            ])->post($this->apiUrl, [
                "action" => "login",
                "lgname" => $botUsername,
                "lgpassword" => $botPassword,
                "lgtoken" => $loginToken,
                "format" => "json",
            ]);

            if ($this->option("debug")) {
                $this->line(
                    "Login Antwort: " .
                        json_encode($loginResponse->json(), JSON_PRETTY_PRINT)
                );
            }

            if (!$loginResponse->ok()) {
                throw new \Exception(
                    "Login-Anfrage fehlgeschlagen: " . $loginResponse->status()
                );
            }

            $loginJson = $loginResponse->json();

            if (!isset($loginJson["login"])) {
                throw new \Exception(
                    "Login-Antwort enthält keinen 'login'-Schlüssel"
                );
            }

            if ($loginJson["login"]["result"] !== "Success") {
                throw new \Exception(
                    "Bot-Login fehlgeschlagen: " .
                        (isset($loginJson["login"]["reason"])
                            ? $loginJson["login"]["reason"]
                            : json_encode($loginJson["login"]))
                );
            }

            $this->info("Login erfolgreich!");

            // CSRF-Token für Bearbeitungen holen
            $this->info("Fordere CSRF-Token an...");
            $csrfResponse = Http::withOptions([
                "cookies" => $cookieJar,
            ])->get($this->apiUrl, [
                "action" => "query",
                "meta" => "tokens",
                "format" => "json",
            ]);

            if ($this->option("debug")) {
                $this->line(
                    "CSRF-Token Antwort: " .
                        json_encode($csrfResponse->json(), JSON_PRETTY_PRINT)
                );
            }

            if (!$csrfResponse->ok()) {
                throw new \Exception(
                    "CSRF-Token Anfrage fehlgeschlagen: " .
                        $csrfResponse->status()
                );
            }

            $csrfJson = $csrfResponse->json();

            if (
                !isset($csrfJson["query"]) ||
                !isset($csrfJson["query"]["tokens"]) ||
                !isset($csrfJson["query"]["tokens"]["csrftoken"])
            ) {
                throw new \Exception("CSRF-Token nicht gefunden in Antwort");
            }

            $csrfToken = $csrfJson["query"]["tokens"]["csrftoken"];
            $this->info(
                "CSRF-Token erhalten: " . substr($csrfToken, 0, 5) . "..."
            );

            $this->info(
                "Test erfolgreich abgeschlossen. Bot-Anmeldung funktioniert!"
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Fehler beim Test: " . $e->getMessage());

            return 1;
        }
    }
}

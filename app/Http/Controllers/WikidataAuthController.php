<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Wikibase\WikibaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WikidataAuthController extends Controller
{
    /**
     * @var WikibaseService
     */
    protected $wikidataService;

    /**
     * WikidataAuthController constructor.
     *
     * @param WikibaseService $wikidataService
     */
    public function __construct(WikibaseService $wikidataService)
    {
        $this->wikidataService = $wikidataService;
    }

    /**
     * Startet den OAuth-Prozess für die Benutzerauthentifizierung.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToWikidata(Request $request)
    {
        try {
            // Eindeutige State-ID generieren, um die Sitzung zu identifizieren
            $state = Str::random(40);

            // Speichern in der Session für die Ru00fcckkehr
            $request->session()->put("wikidata_auth_state", $state);

            // Speichern des Redirekts nach erfolgreicher Authentifizierung
            if ($request->has("redirect")) {
                $request
                    ->session()
                    ->put(
                        "wikidata_auth_redirect",
                        $request->input("redirect")
                    );
            }

            // Initialisieren Sie den Auth-Prozess
            $authUrl = $this->wikidataService->getAuthorizationUrl([
                "state" => $state,
            ]);
            return redirect()->away($authUrl);
        } catch (\Exception $e) {
            Log::error("Wikidata OAuth login error", [
                "error" => $e->getMessage(),
            ]);
            return redirect()
                ->route("login")
                ->with(
                    "error",
                    "Bei der Verbindung mit Wikidata ist ein Fehler aufgetreten."
                );
        }
    }

    /**
     * Verarbeitet den OAuth-Callback von Wikidata für die Benutzerauthentifizierung.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleCallback(Request $request)
    {
        try {
            // u00dcberpru00fcfen, ob die Anfrage abgebrochen wurde
            if ($request->has("denied")) {
                return redirect()
                    ->route("login")
                    ->with(
                        "error",
                        "Die Wikidata-Authentifizierung wurde abgebrochen."
                    );
            }

            // u00dcberpru00fcfen des State-Parameters zur Sicherheit
            $sessionState = $request->session()->get("wikidata_auth_state");
            $requestState = $request->input("state");

            if (!$sessionState || $sessionState !== $requestState) {
                return redirect()
                    ->route("login")
                    ->with(
                        "error",
                        "Ungültiger State-Parameter. Bitte versuchen Sie es erneut."
                    );
            }

            // OAuth-Token und Verifier abrufen
            $oauthToken = $request->input("oauth_token");
            $oauthVerifier = $request->input("oauth_verifier");

            if (!$oauthToken || !$oauthVerifier) {
                return redirect()
                    ->route("login")
                    ->with("error", "Fehlende OAuth-Parameter.");
            }

            // Benutzerinformationen von Wikidata abrufen
            $userInfo = $this->wikidataService->getUserInfo(
                $oauthToken,
                $oauthVerifier
            );

            if (!$userInfo || !isset($userInfo["username"])) {
                return redirect()
                    ->route("login")
                    ->with(
                        "error",
                        "Fehler beim Abrufen der Benutzerinformationen von Wikidata."
                    );
            }

            // Legen Sie den Benutzer in der Datenbank an oder aktualisieren Sie ihn
            $user = $this->findOrCreateUser($userInfo);

            // Benutzer anmelden
            Auth::login($user);

            // Löschen Sie den State aus der Session
            $request->session()->forget("wikidata_auth_state");

            // Weiterleitung zum urspru00fcnglich angeforderten Pfad oder zur Standard-Seite
            $redirect = $request
                ->session()
                ->pull("wikidata_auth_redirect", "admin.dashboard");
            return redirect()
                ->route($redirect)
                ->with("success", "Erfolgreich mit Wikidata angemeldet!");
        } catch (\Exception $e) {
            Log::error("Wikidata OAuth callback error", [
                "error" => $e->getMessage(),
            ]);
            return redirect()
                ->route("login")
                ->with(
                    "error",
                    "Bei der Authentifizierung mit Wikidata ist ein Fehler aufgetreten: " .
                        $e->getMessage()
                );
        }
    }

    /**
     * Findet oder erstellt einen Benutzer basierend auf Wikidata-Informationen.
     *
     * @param array $userInfo
     * @return User
     */
    protected function findOrCreateUser(array $userInfo)
    {
        // Nach einem Benutzer mit der Wikidata-ID suchen
        $user = User::where("wikidata_id", $userInfo["id"])->first();

        if ($user) {
            // Aktualisieren des Benutzers mit den neuesten Informationen
            $user->update([
                "name" => $userInfo["username"],
                "wikidata_username" => $userInfo["username"],
                "wikidata_token" => $userInfo["access_token"] ?? null,
                "wikidata_token_secret" =>
                    $userInfo["access_token_secret"] ?? null,
            ]);

            return $user;
        }

        // Wenn der Benutzer eingeloggt ist, verknu00fcpfe Wikidata mit dem aktuellen Konto
        if (Auth::check()) {
            $user = Auth::user();
            $user->update([
                "wikidata_id" => $userInfo["id"],
                "wikidata_username" => $userInfo["username"],
                "wikidata_token" => $userInfo["access_token"] ?? null,
                "wikidata_token_secret" =>
                    $userInfo["access_token_secret"] ?? null,
            ]);

            return $user;
        }

        // Erstellen eines neuen Benutzers
        return User::create([
            "name" => $userInfo["username"],
            "email" => $userInfo["id"] . "@wikidata.example.com", // Platzhalter-E-Mail
            "password" => bcrypt(Str::random(16)), // Zufu00e4lliges Passwort, nicht verwendbar für direkten Login
            "wikidata_id" => $userInfo["id"],
            "wikidata_username" => $userInfo["username"],
            "wikidata_token" => $userInfo["access_token"] ?? null,
            "wikidata_token_secret" => $userInfo["access_token_secret"] ?? null,
            "is_admin" => false, // Standardmu00e4u00dfig kein Admin
        ]);
    }

    /**
     * Verbindet einen bestehenden Benutzer mit einem Wikidata-Konto.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connectAccount(Request $request)
    {
        // Sicherstellen, dass der Benutzer angemeldet ist
        if (!Auth::check()) {
            return redirect()->route("login");
        }

        // Speichern des angemeldeten Benutzers in der Session
        $request->session()->put("wikidata_connecting_user_id", Auth::id());

        // Zu Wikidata OAuth weiterleiten
        return $this->redirectToWikidata($request);
    }

    /**
     * Trennt ein Wikidata-Konto von einem bestehenden Benutzer.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnectAccount()
    {
        // Sicherstellen, dass der Benutzer angemeldet ist
        if (!Auth::check()) {
            return redirect()->route("login");
        }

        $user = Auth::user();

        // Entfernen der Wikidata-Informationen
        $user->update([
            "wikidata_id" => null,
            "wikidata_username" => null,
            "wikidata_token" => null,
            "wikidata_token_secret" => null,
        ]);

        return redirect()
            ->route("profile")
            ->with(
                "success",
                "Ihr Konto wurde erfolgreich von Wikidata getrennt."
            );
    }
}

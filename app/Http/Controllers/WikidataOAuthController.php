<?php

namespace App\Http\Controllers;

use App\Services\Wikidata\WikidataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WikidataOAuthController extends Controller
{
    /**
     * @var WikidataService
     */
    protected $wikidataService;

    /**
     * WikidataOAuthController constructor.
     *
     * @param WikidataService $wikidataService
     */
    public function __construct(WikidataService $wikidataService)
    {
        $this->wikidataService = $wikidataService;
    }

    /**
     * Startet den OAuth-Prozess und leitet zur Wikidata-Autorisierungsseite weiter.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToWikidata()
    {
        try {
            $authUrl = $this->wikidataService->getAuthorizationUrl();
            return redirect()->away($authUrl);
        } catch (\Exception $e) {
            Log::error('OAuth redirect error', ['error' => $e->getMessage()]);
            return redirect('/admin/settings')->with('error', 'OAuth-Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Verarbeitet den OAuth-Callback von Wikidata.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleCallback(Request $request)
    {
        try {
            $oauthToken = $request->input('oauth_token');
            $oauthVerifier = $request->input('oauth_verifier');

            if (!$oauthToken || !$oauthVerifier) {
                Log::error('OAuth callback missing parameters');
                return redirect('/admin/settings')->with('error', 'OAuth-Parameter fehlen.');
            }

            $success = $this->wikidataService->handleCallback($oauthToken, $oauthVerifier);

            if ($success) {
                return redirect('/admin/settings')->with('success', 'Wikidata-Verbindung erfolgreich hergestellt.');
            } else {
                return redirect('/admin/settings')->with('error', 'OAuth-Authentifizierung fehlgeschlagen.');
            }
        } catch (\Exception $e) {
            Log::error('OAuth callback error', ['error' => $e->getMessage()]);
            return redirect('/admin/settings')->with('error', 'OAuth-Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Zeigt den Status der Wikidata-Verbindung an.
     *
     * @return \Illuminate\View\View
     */
    public function status()
    {
        $hasCredentials = $this->wikidataService->hasValidCredentials();
        
        return view('admin.wikidata-status', [
            'hasCredentials' => $hasCredentials,
        ]);
    }
}
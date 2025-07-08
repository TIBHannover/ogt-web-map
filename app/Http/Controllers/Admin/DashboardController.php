<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WikidataEntryRequest;
use App\Services\Wikidata\WikidataService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Admin-Dashboard anzeigen.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Statistiken für das Dashboard abrufen
        $stats = [
            "pending_requests" => WikidataEntryRequest::where(
                "status",
                "pending"
            )->count(),
            "approved_requests" => WikidataEntryRequest::where(
                "status",
                "approved"
            )->count(),
            "rejected_requests" => WikidataEntryRequest::where(
                "status",
                "rejected"
            )->count(),
            "error_requests" => WikidataEntryRequest::where(
                "status",
                "error"
            )->count(),
        ];

        // Letzte Anfragen abrufen
        $latestRequests = WikidataEntryRequest::orderBy("created_at", "desc")
            ->limit(5)
            ->get();

        return view("admin.dashboard", [
            "stats" => $stats,
            "latestRequests" => $latestRequests,
        ]);
    }

    /**
     * Zeigt die Wikidata-Verbindungs-Einstellungen an.
     *
     * @param WikidataService $wikidataService
     * @return \Illuminate\View\View
     */
    public function wikidataSettings(WikidataService $wikidataService)
    {
        $hasCredentials = $wikidataService->hasValidCredentials();

        return view("admin.wikidata-settings", [
            "hasCredentials" => $hasCredentials,
        ]);
    }

    /**
     * Anfragen-Verwaltung anzeigen.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function requests(Request $request)
    {
        $status = $request->input("status", "all");
        $query = WikidataEntryRequest::query();

        if ($status !== "all") {
            $query->where("status", $status);
        }

        $requests = $query->orderBy("created_at", "desc")->paginate(10);

        return view("admin.requests", [
            "requests" => $requests,
            "currentStatus" => $status,
        ]);
    }

    /**
     * Benutzer-Verwaltung anzeigen.
     *
     * @return \Illuminate\View\View
     */
    public function users()
    {
        // Nur für Super-Admin zugu00e4nglich
        if (!auth()->user()->isAdmin()) {
            abort(403, "Unbefugter Zugriff");
        }

        $users = \App\Models\User::orderBy("created_at", "desc")->paginate(10);

        return view("admin.users", ["users" => $users]);
    }

    /**
     * Zeigt die Details einer einzelnen Anfrage.
     *
     * @param int $id Die ID der Anfrage
     * @return \Illuminate\View\View
     */
    public function showRequest($id)
    {
        $request = WikidataEntryRequest::findOrFail($id);

        return view("admin.request-detail", [
            "request" => $request,
        ]);
    }

    /**
     * Zeigt das Formular zum Bearbeiten einer Anfrage.
     *
     * @param int $id Die ID der Anfrage
     * @return \Illuminate\View\View
     */
    public function editRequest($id)
    {
        $request = WikidataEntryRequest::findOrFail($id);

        return view("admin.request-edit", [
            "request" => $request,
        ]);
    }

    /**
     * Aktualisiert eine Anfrage mit den neuen Daten.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id Die ID der Anfrage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRequest(Request $request, $id)
    {
        try {
            $entryRequest = WikidataEntryRequest::findOrFail($id);
            
            // Debug information
            \Log::info('Update request data', [
                'request_data' => $request->except(['_token']),
                'id' => $id
            ]);
            
            // Daten aus dem Formular übernehmen
            $entryRequest->fill($request->except(['_token']));
            $entryRequest->save();

            return redirect()
                ->route('admin.requests.show', $id)
                ->with('success', 'Die Anfrage wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            \Log::error('Error updating request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token']),
                'id' => $id
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

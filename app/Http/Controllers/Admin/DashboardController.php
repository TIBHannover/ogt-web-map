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
}

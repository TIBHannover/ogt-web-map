<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WikidataEntryRequest;
use App\Services\Wikibase\WikibaseService;
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
     * @param WikibaseService $wikidataService
     * @return \Illuminate\View\View
     */
    public function wikidataSettings(WikibaseService $wikidataService)
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
                'request_data' => $request->all(),
                'id' => $id,
                'content_type' => $request->header('Content-Type')
            ]);
            
            // Data processing depends on content type
            $data = $request->all();
            
            // If we're receiving JSON data
            if ($request->header('Content-Type') == 'application/json') {
                // Parse array fields from strings if needed
                $jsonFields = ['victims', 'perpetrators', 'selected_causes', 'sources', 'coordinates'];
                
                foreach ($jsonFields as $field) {
                    if (isset($data[$field]) && is_string($data[$field])) {
                        try {
                            $data[$field] = json_decode($data[$field], true);
                            \Log::info("Decoded JSON string {$field}", ['value' => $data[$field]]);
                        } catch (\Exception $e) {
                            \Log::warning("Failed to decode JSON string {$field}", [
                                'error' => $e->getMessage(),
                                'value' => $data[$field]
                            ]);
                        }
                    }
                }
            } else {
                // Handle form data - similar to before
                $jsonFields = ['victims', 'perpetrators', 'selected_causes', 'sources'];
                
                foreach ($jsonFields as $field) {
                    if (isset($data[$field]) && is_string($data[$field])) {
                        try {
                            // Only decode if it's a string and appears to be JSON
                            if (str_starts_with(trim($data[$field]), '[') || str_starts_with(trim($data[$field]), '{')) {
                                $data[$field] = json_decode($data[$field], true);
                                \Log::info("Decoded {$field}", ['value' => $data[$field]]);
                            }
                        } catch (\Exception $jsonEx) {
                            \Log::warning("Failed to decode {$field}", [
                                'error' => $jsonEx->getMessage(),
                                'value' => $data[$field]
                            ]);
                        }
                    }
                }
                
                // Handle coordinates special case
                if (isset($data['coordinates']) && is_string($data['coordinates'])) {
                    try {
                        if (str_starts_with(trim($data['coordinates']), '{') || 
                            str_starts_with(trim($data['coordinates']), '[')) {
                            $data['coordinates'] = json_decode($data['coordinates'], true);
                            \Log::info("Decoded coordinates", ['value' => $data['coordinates']]);
                        }
                    } catch (\Exception $coordEx) {
                        \Log::warning("Failed to decode coordinates", [
                            'error' => $coordEx->getMessage(),
                            'value' => $data['coordinates']
                        ]);
                    }
                }
            }
            
            // Handle numeric fields
            $numericFields = ['victim_count', 'survivor_count', 'death_count', 'arrest_count'];
            foreach ($numericFields as $field) {
                if (isset($data[$field])) {
                    // Convert to integer for numeric fields
                    $data[$field] = intval($data[$field]);
                    \Log::info("Converted {$field} to integer", ['value' => $data[$field]]);
                }
            }
            
            // Handle text fields - ensure they're properly handled as strings
            $textFields = ['title', 'description'];
            foreach ($textFields as $field) {
                if (isset($data[$field])) {
                    // Ensure it's a string
                    $data[$field] = (string)$data[$field];
                    \Log::info("Ensured {$field} is string", ['value' => $data[$field]]);
                }
            }
            
            // Re-encode JSON fields for storage
            foreach ($jsonFields as $field) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = json_encode($data[$field]);
                    \Log::info("Re-encoded {$field} for storage", ['field' => $field]);
                }
            }
            
            // Re-encode coordinates for storage
            if (isset($data['coordinates']) && is_array($data['coordinates'])) {
                $data['coordinates'] = json_encode($data['coordinates']);
                \Log::info("Re-encoded coordinates for storage");
            }
            
            // Use direct DB update instead of Eloquent to bypass any potential issues
            $updateResult = \DB::table('wikidata_entry_requests')
                ->where('id', $id)
                ->update($data);
                
            \Log::info('Update result via direct DB query', ['result' => $updateResult]);
            
            // Refresh the model from the database to ensure we have the latest data
            $entryRequest = WikidataEntryRequest::findOrFail($id);
            
            // Log some fields to verify they were updated
            \Log::info('Field values after update', [
                'title' => $entryRequest->title,
                'description' => $entryRequest->description,
                'victim_count' => $entryRequest->victim_count,
                'survivor_count' => $entryRequest->survivor_count,
                'death_count' => $entryRequest->death_count,
                'arrest_count' => $entryRequest->arrest_count
            ]);

            \Log::info('Request updated successfully', ['id' => $id]);
            
            // Return with a cache-busting parameter
            return redirect()
                ->route('admin.requests.show', ['id' => $id, 'refresh' => time()])
                ->with('success', 'Die Anfrage wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            \Log::error('Error updating request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'id' => $id
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

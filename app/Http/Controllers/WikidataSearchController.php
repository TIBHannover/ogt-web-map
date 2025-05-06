<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Http\Clients\WikidataClient;

class WikidataSearchController extends Controller
{
    /**
     * Search for people in Wikidata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchPeople(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $request->input('query');
        
        try {
            // Pru00fcfe, ob es sich um eine direkte Q-Nummer handelt (z.B. "Q1234567")
            if (preg_match('/^Q\d+$/', $query)) {
                // Direkte Anfrage des Entities mit der Q-ID
                $response = Http::accept('application/json')
                    ->get('https://www.wikidata.org/w/api.php', [
                        'action' => 'wbgetentities',
                        'ids' => $query,
                        'languages' => 'de|en', // Deutsch und Englisch anfordern
                        'format' => 'json'
                    ]);

                if ($response->successful() && !empty($response->json()['entities'][$query])) {
                    $entity = $response->json()['entities'][$query];
                    
                    // Pru00fcfe, ob es sich um einen Menschen handelt (P31 = instance of, Q5 = human)
                    if (isset($entity['claims']['P31'])) {
                        foreach ($entity['claims']['P31'] as $claim) {
                            if ($claim['mainsnak']['datavalue']['value']['id'] === 'Q5') {
                                // Geburtsdatum und Todesdatum ermitteln falls vorhanden
                                $birth = null;
                                $death = null;
                                
                                if (isset($entity['claims']['P569'])) { // P569 = Geburtsdatum
                                    $birth = $entity['claims']['P569'][0]['mainsnak']['datavalue']['value']['time'] ?? null;
                                    $birth = $birth ? substr($birth, 1, 10) : null; // '+' und Pru00e4zision entfernen
                                }
                                
                                if (isset($entity['claims']['P570'])) { // P570 = Todesdatum
                                    $death = $entity['claims']['P570'][0]['mainsnak']['datavalue']['value']['time'] ?? null;
                                    $death = $death ? substr($death, 1, 10) : null; // '+' und Pru00e4zision entfernen
                                }

                                // Beschreibungstext priorisieren: Deutsch > Englisch > leer
                                $description = null;
                                if (!empty($entity['descriptions']['de']['value'])) {
                                    $description = $entity['descriptions']['de']['value'];
                                } elseif (!empty($entity['descriptions']['en']['value'])) {
                                    $description = $entity['descriptions']['en']['value'];
                                }
                                
                                // Label priorisieren: Deutsch > Englisch > Q-ID
                                $label = $query;
                                if (!empty($entity['labels']['de']['value'])) {
                                    $label = $entity['labels']['de']['value'];
                                } elseif (!empty($entity['labels']['en']['value'])) {
                                    $label = $entity['labels']['en']['value'];
                                }

                                return response()->json([
                                    [
                                        'id' => $query,
                                        'label' => $label,
                                        'description' => $description,
                                        'birth' => $birth,
                                        'death' => $death
                                    ]
                                ]);
                            }
                        }
                    }
                }
            }

            // Standard-Suche, wenn es keine Q-ID ist oder die Q-ID nicht gefunden wurde
            $response = Http::accept('application/json')
                ->get('https://www.wikidata.org/w/api.php', [
                    'action' => 'wbsearchentities',
                    'search' => $query,
                    'language' => 'de',
                    'format' => 'json',
                    'type' => 'item',
                    'limit' => 10
                ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Wikidata API request failed'], 500);
            }

            $results = $response->json()['search'] ?? [];
            
            // Filter results to only include humans (Q5)
            $filteredResults = [];
            foreach ($results as $result) {
                // Get detailed information about the entity
                $entityResponse = Http::accept('application/json')
                    ->get('https://www.wikidata.org/w/api.php', [
                        'action' => 'wbgetentities',
                        'ids' => $result['id'],
                        'languages' => 'de|en', // Deutsch und Englisch anfordern
                        'format' => 'json'
                    ]);

                if ($entityResponse->successful()) {
                    $entity = $entityResponse->json()['entities'][$result['id']] ?? null;
                    
                    // Check if entity is human (P31 = instance of, Q5 = human)
                    if ($entity && isset($entity['claims']['P31'])) {
                        foreach ($entity['claims']['P31'] as $claim) {
                            if ($claim['mainsnak']['datavalue']['value']['id'] === 'Q5') {
                                // Get birth and death dates if available
                                $birth = null;
                                $death = null;
                                
                                if (isset($entity['claims']['P569'])) { // P569 = date of birth
                                    $birth = $entity['claims']['P569'][0]['mainsnak']['datavalue']['value']['time'] ?? null;
                                    $birth = $birth ? substr($birth, 1, 10) : null; // Remove '+' and precision
                                }
                                
                                if (isset($entity['claims']['P570'])) { // P570 = date of death
                                    $death = $entity['claims']['P570'][0]['mainsnak']['datavalue']['value']['time'] ?? null;
                                    $death = $death ? substr($death, 1, 10) : null; // Remove '+' and precision
                                }
                                
                                // Beschreibung priorisieren: Aus Suchergebnis > Deutsch > Englisch > leer
                                $description = $result['description'] ?? null;
                                
                                // Wenn keine Beschreibung aus dem Suchergebnis vorhanden ist
                                if (empty($description)) {
                                    if (!empty($entity['descriptions']['de']['value'])) {
                                        $description = $entity['descriptions']['de']['value'];
                                    } elseif (!empty($entity['descriptions']['en']['value'])) {
                                        $description = $entity['descriptions']['en']['value'];
                                    }
                                }

                                $filteredResults[] = [
                                    'id' => $result['id'],
                                    'label' => $result['label'] ?? $result['id'],
                                    'description' => $description,
                                    'birth' => $birth,
                                    'death' => $death
                                ];
                                break;
                            }
                        }
                    }
                }
            }

            return response()->json($filteredResults);
        } catch (\Exception $e) {
            \Log::error('Wikidata search error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search Wikidata: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Search for entities by title
     * 
     * @param Request $request
     * @param WikidataClient $wikidataClient
     * @return JsonResponse
     */
    public function searchByTitle(Request $request, WikidataClient $wikidataClient): JsonResponse
    {
        // Validiere den Request
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:250',
            'limit' => 'sometimes|integer|min:1|max:20'
        ]);
        
        $title = $validated['title'];
        $limit = $validated['limit'] ?? 10;
        
        // Suche nach dem Titel in Wikidata
        $results = $wikidataClient->searchTitles($title, $limit);
        
        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }
}
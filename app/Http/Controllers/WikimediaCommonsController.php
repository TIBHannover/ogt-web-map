<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WikimediaCommonsController extends Controller
{
    /**
     * Suche nach Bildern in Wikimedia Commons
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchImages(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $limit = $request->input('limit', 10);
        
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Ein Suchbegriff ist erforderlich.'
            ], 400);
        }
        
        try {
            // MediaWiki Action API Anfrage an Wikimedia Commons
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'format' => 'json',
                'prop' => 'imageinfo',
                'generator' => 'search',
                'gsrnamespace' => '6', // Namespace 6 ist für Dateien
                'gsrsearch' => "filetype:bitmap|drawing $query", // Nur Bilder suchen
                'gsrlimit' => $limit,
                'iiprop' => 'url|canonicaltitle|extmetadata|size|mime',
                'iiurlwidth' => 200, // Thumbnail-Breite
                'iiextmetadatafilter' => 'License|ImageDescription',
            ]);
            
            $data = $response->json();
            
            // Keine Ergebnisse
            if (!isset($data['query']['pages']) || empty($data['query']['pages'])) {
                return response()->json([
                    'success' => true,
                    'images' => [],
                    'count' => 0
                ]);
            }
            
            $images = [];
            
            foreach ($data['query']['pages'] as $page) {
                if (isset($page['imageinfo'][0])) {
                    $info = $page['imageinfo'][0];
                    $fileName = str_replace('File:', '', $info['canonicaltitle'] ?? '');
                    
                    // Basisinformationen extrahieren
                    $image = [
                        'title' => $fileName,
                        'url' => $info['url'],
                        'thumbUrl' => $info['thumburl'] ?? null,
                        'width' => $info['width'] ?? null,
                        'height' => $info['height'] ?? null,
                        'mime' => $info['mime'] ?? null,
                    ];
                    
                    // Metadaten (falls vorhanden)
                    if (isset($info['extmetadata'])) {
                        $meta = $info['extmetadata'];
                        $image['description'] = $meta['ImageDescription']['value'] ?? null;
                        $image['license'] = $meta['License']['value'] ?? null;
                    }
                    
                    $images[] = $image;
                }
            }
            
            return response()->json([
                'success' => true,
                'images' => $images,
                'count' => count($images)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Wikimedia Commons Suche fehlgeschlagen: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Die Bildsuche konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.'
            ], 500);
        }
    }
}
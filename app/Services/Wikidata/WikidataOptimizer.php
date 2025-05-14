<?php

namespace App\Services\Wikidata;

use Illuminate\Support\Facades\Log;

class WikidataOptimizer
{
    /**
     * Optimiert die formatierten Daten vor dem Senden an die Wikidata-API
     *
     * @param array $data Die zu optimierenden Daten
     * @param string $operation Die durchzuführende Operation (z.B. 'create', 'update')
     * @return array Die optimierten Daten
     */
    public function optimize(array $data, string $operation): array
    {
        // Daten für Debug-Zwecke loggen (vor der Optimierung)
        Log::info('WikidataOptimizer: Original data before optimization', [
            'operation' => $operation,
            'data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ]);
        
        // Je nach Operation unterschiedlich optimieren
        $optimizedData = match ($operation) {
            'create' => $this->optimizeForCreate($data),
            'update' => $this->optimizeForUpdate($data),
            default => $data,
        };
        
        // Optimierte Daten für Debug-Zwecke loggen
        Log::info('WikidataOptimizer: Optimized data', [
            'operation' => $operation,
            'data' => json_encode($optimizedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ]);
        
        // Überprüfen, ob das Ergebnis gültig ist
        if (empty($optimizedData) || !is_array($optimizedData)) {
            Log::error('WikidataOptimizer: Optimization resulted in invalid data', [
                'operation' => $operation,
                'result' => $optimizedData
            ]);
        }
        
        if ($operation === 'create') {
            // Überprüfen, ob alle erforderlichen Felder vorhanden sind
            if (!isset($optimizedData['labels']) || empty($optimizedData['labels'])) {
                Log::error('WikidataOptimizer: Missing required "labels" field in optimized data');
            }
            if (!isset($optimizedData['claims']) || empty($optimizedData['claims']) || !isset($optimizedData['claims']['P31'])) {
                Log::error('WikidataOptimizer: Missing required P31 claim in optimized data');
            }
        }
        
        return $optimizedData;
    }
    
    /**
     * Optimiert Daten für die Erstellung einer neuen Entity
     * 
     * @param array $data Die zu optimierenden Daten
     * @return array Die optimierten Daten
     */
    private function optimizeForCreate(array $data): array
    {
        // Bei der Erstellung einer neuen Entity darf keine ID angegeben werden
        if (isset($data['id'])) {
            Log::info('WikidataOptimizer: Removing ID for create operation');
            unset($data['id']);
        }
        
        // Weitere Optimierungen für den Create-Vorgang
        // Sicherstellen, dass alle erforderlichen Felder vorhanden sind
        $this->ensureRequiredFields($data);
        
        // Bereinigen unerwünschter oder leerer Daten
        $data = $this->cleanupData($data);
        
        return $data;
    }
    
    /**
     * Optimiert Daten für die Aktualisierung einer bestehenden Entity
     * 
     * @param array $data Die zu optimierenden Daten
     * @return array Die optimierten Daten
     */
    private function optimizeForUpdate(array $data): array
    {
        // Bei der Aktualisierung muss eine ID vorhanden sein
        if (!isset($data['id'])) {
            Log::warning('WikidataOptimizer: Missing ID for update operation');
        }
        
        // Weitere Optimierungen für den Update-Vorgang
        $data = $this->cleanupData($data);
        
        return $data;
    }
    
    /**
     * Stellt sicher, dass alle erforderlichen Felder vorhanden sind
     * 
     * @param array $data Die zu prüfenden Daten
     * @return void
     */
    private function ensureRequiredFields(array &$data): void
    {
        // Für Wikidata sind Labels und zumindest ein Statement (P31 - Instanz von) erforderlich
        if (!isset($data['labels']) || empty($data['labels'])) {
            Log::warning('WikidataOptimizer: Missing required field "labels"');
            // Standardwerte setzen, falls möglich
        }
        
        if (!isset($data['claims']) || empty($data['claims']) || !isset($data['claims']['P31'])) {
            Log::warning('WikidataOptimizer: Missing required P31 statement (instance of)');
            // Standardwerte setzen, falls möglich
        }
    }
    
    /**
     * Bereinigt die Daten von leeren oder ungültigen Werten
     * 
     * @param array $data Die zu bereinigenden Daten
     * @return array Die bereinigten Daten
     */
    private function cleanupData(array $data): array
    {
        // Rekursiv durch die Daten gehen und leere Arrays oder null-Werte entfernen
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->cleanupData($value);
                // Leere Arrays entfernen
                if (empty($data[$key])) {
                    unset($data[$key]);
                }
            } elseif ($value === null || (is_string($value) && trim($value) === '')) {
                unset($data[$key]);
            }
        }
        
        return $data;
    }
}
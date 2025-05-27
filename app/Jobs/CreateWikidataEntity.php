<?php

namespace App\Jobs;

use App\Models\WikidataEntryRequest;
use App\Services\Wikidata\WikidataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateWikidataEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entryRequest;

    /**
     * Create a new job instance.
     *
     * @param WikidataEntryRequest $entryRequest
     */
    public function __construct(WikidataEntryRequest $entryRequest)
    {
        $this->entryRequest = $entryRequest;
    }

    /**
     * Execute the job.
     *
     * @param WikidataService $wikidataService
     * @return void
     */
    public function handle(WikidataService $wikidataService): void
    {
        try {
            Log::info("Starting Wikidata entity creation", [
                "request_id" => $this->entryRequest->id,
                "title" => $this->entryRequest->title,
            ]);

            // Prüfe, ob OAuth-Zugangsdaten vorhanden sind
            if (!$wikidataService->hasValidCredentials()) {
                // Fallback zu simuliertem Modus, wenn keine OAuth-Credentials vorhanden sind
                Log::warning(
                    "No OAuth credentials available, using simulation mode"
                );
                return;
            }

            // Daten für Wikidata-Entität vorbereiten
            $additionalData = $this->entryRequest->toArray();
            
            // Entferne die Basis-Felder, die bereits explizit übergeben werden
            unset($additionalData['title']);
            unset($additionalData['description']);
            unset($additionalData['type']);
            unset($additionalData['id']);
            unset($additionalData['created_at']);
            unset($additionalData['updated_at']);
            unset($additionalData['status']);
            unset($additionalData['wikidata_id']);
            
            $entityData = $wikidataService->prepareEntityData(
                $this->entryRequest->title,
                $this->entryRequest->description,
                $this->entryRequest->type,
                $additionalData
            );
            
            // Entität mit OAuth-Authentifizierung erstellen
            $response = $wikidataService->createEntity($entityData);

            if ($response === null) {
                throw new \Exception("Failed to create Wikidata entity");
            }

            // Wikidata-ID in der Datenbank speichern
            $this->entryRequest->wikidata_id = $response["entity"]["id"];
            $this->entryRequest->save();

            Log::info("Wikidata entity created successfully", [
                "request_id" => $this->entryRequest->id,
                "wikidata_id" => $response["entity"]["id"],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create Wikidata entity", [
                "error" => $e->getMessage(),
                "request_id" => $this->entryRequest->id,
            ]);

            $this->entryRequest->status = "error";
            $this->entryRequest->error_message = $e->getMessage();
            $this->entryRequest->save();

            // Job erneut in die Warteschlange stellen (mit Verzögerung)
            if ($this->attempts() < 3) {
                $this->release(60 * 5); // 5 Minuten Verzögerung
            }
        }
    }
}

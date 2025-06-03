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
            unset($additionalData["title"]);
            unset($additionalData["description"]);
            unset($additionalData["type"]);
            unset($additionalData["id"]);
            unset($additionalData["created_at"]);
            unset($additionalData["updated_at"]);
            unset($additionalData["status"]);
            unset($additionalData["wikidata_id"]);
            
            $existingEntityIds = [];
            $newlyCreatedEntityIds = [];
            
            // Collect all existing entity IDs from perpetrators
            if (!empty($additionalData["perpetrators"]) && is_array($additionalData["perpetrators"])) {
                foreach ($additionalData["perpetrators"] as $perpetrator) {
                    if (!empty($perpetrator["id"]) && substr($perpetrator["id"], 0, 1) === "Q") {
                        $existingEntityIds[] = $perpetrator["id"];
                    }
                }
            }
            
            // Collect all existing entity IDs from victims - nur IDs, die keine tempId oder temp_id haben
            if (!empty($additionalData["victims"]) && is_array($additionalData["victims"])) {
                foreach ($additionalData["victims"] as $victim) {
                    if (!empty($victim["id"]) && substr($victim["id"], 0, 1) === "Q" && 
                        empty($victim["tempId"]) && empty($victim["temp_id"])) {
                        $existingEntityIds[] = $victim["id"];
                    }
                }
            }

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

            $createdEntities = [];

            if (
                !empty($entityData["perpetrators"]) &&
                is_array($entityData["perpetrators"])
            ) {
                foreach ($entityData["perpetrators"] as $perpetrator) {
                    if (
                        !empty($perpetrator["id"]) &&
                        substr($perpetrator["id"], 0, 1) === "Q" &&
                        (!in_array($perpetrator["id"], $existingEntityIds) || 
                         (isset($perpetrator["_wasCreated"]) && $perpetrator["_wasCreated"] === true))
                    ) {
                        // Add to the list of newly created IDs for tracking
                        if (!in_array($perpetrator["id"], $newlyCreatedEntityIds)) {
                            $newlyCreatedEntityIds[] = $perpetrator["id"];
                            
                            $createdEntities[] = [
                                "id" => $perpetrator["id"],
                                "type" => "perpetrator",
                                "name" =>
                                    $perpetrator["name"] ??
                                    $perpetrator["label"] ??
                                    (isset($perpetrator["firstName"]) && isset($perpetrator["lastName"]) ?
                                        $perpetrator["firstName"] . " " . $perpetrator["lastName"] :
                                        "Unbekannt"),
                                "label" => $perpetrator["label"] ?? null,
                            ];
                            
                            Log::info("Added newly created perpetrator to additional entities list", [
                                "id" => $perpetrator["id"],
                                "name" => $perpetrator["name"] ?? $perpetrator["label"] ?? "Unbekannt",
                            ]);
                        }
                    }
                }
            }

            if (
                !empty($entityData["victims"]) &&
                is_array($entityData["victims"])
            ) {
                foreach ($entityData["victims"] as $victim) {
                    if (
                        !empty($victim["id"]) &&
                        substr($victim["id"], 0, 1) === "Q" &&
                        (!in_array($victim["id"], $existingEntityIds) || 
                         (isset($victim["_wasCreated"]) && $victim["_wasCreated"] === true))
                    ) {
                        // Add to the list of newly created IDs for tracking
                        if (!in_array($victim["id"], $newlyCreatedEntityIds)) {
                            $newlyCreatedEntityIds[] = $victim["id"];
                            
                            $createdEntities[] = [
                                "id" => $victim["id"],
                                "type" => "victim",
                                "name" =>
                                    $victim["name"] ??
                                    $victim["label"] ??
                                    (isset($victim["firstName"]) && isset($victim["lastName"]) ?
                                        $victim["firstName"] . " " . $victim["lastName"] :
                                        "Unbekannt"),
                                "label" => $victim["label"] ?? null,
                            ];
                            
                            Log::info("Added newly created victim to additional entities list", [
                                "id" => $victim["id"],
                                "name" => $victim["name"] ?? $victim["label"] ?? "Unbekannt",
                            ]);
                        }
                    }
                }
            }

            $this->entryRequest->created_entities = $createdEntities;
            $this->entryRequest->save();

            Log::info("Wikidata entity created successfully", [
                "request_id" => $this->entryRequest->id,
                "wikidata_id" => $response["entity"]["id"],
                "additional_entities" => count($createdEntities),
                "referenced_entities" => count($existingEntityIds),
                "newly_created_ids" => $newlyCreatedEntityIds,
                "existing_ids" => $existingEntityIds
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

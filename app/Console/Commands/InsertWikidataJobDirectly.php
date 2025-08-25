<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsertWikidataJobDirectly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "wikidata:insert-job";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Insert a Wikidata job directly into the jobs table";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Inserting Wikidata job directly into the jobs table...");

        // Recreate the data for the job
        $data = [
            "title" => "Testereigniss mit allem",
            "description" => "Test description",
            "selected_causes" => '[{"id":"Q1899261","label":"Antisemitismus"}]',
        ];

        // Create the payload for the job
        $payload = json_encode([
            "displayName" => "App\\Jobs\\CreateWikidataEntity",
            "job" => "Illuminate\\Queue\\CallQueuedHandler@call",
            "maxTries" => 5,
            "maxExceptions" => 3,
            "backoff" => null,
            "timeout" => null,
            "retryUntil" => null,
            "data" => [
                "commandName" => "App\\Jobs\\CreateWikidataEntity",
                "command" => serialize(
                    new \App\Jobs\CreateWikidataEntity(
                        $data,
                        "Q6983405",
                        null, // parent entity ID
                        null, // relationship type
                        null, // request ID
                    ),
                ),
            ],
            "id" => Str::uuid()->toString(),
        ]);

        // Insert the job into the jobs table
        try {
            DB::table("jobs")->insert([
                "queue" => "wikidata",
                "payload" => $payload,
                "attempts" => 0,
                "reserved_at" => null,
                "available_at" => time(),
                "created_at" => time(),
            ]);

            $this->info("Job inserted successfully!");
            $this->info(
                'Run "php artisan queue:work --queue=wikidata" to process it.',
            );
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to insert job: " . $e->getMessage());
            return 1;
        }
    }
}

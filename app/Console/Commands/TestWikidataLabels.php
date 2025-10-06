<?php

namespace App\Console\Commands;

use App\Http\Controllers\WikidataPropertyController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestWikidataLabels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wikidata:test-labels {ids* : The Wikidata Q IDs to test} {--clear-cache : Clear the cache before testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test retrieving labels from Wikidata for specific Q IDs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ids = $this->argument('ids');
        $clearCache = $this->option('clear-cache');

        $this->info("Testing Wikidata label retrieval for " . count($ids) . " IDs");

        if ($clearCache) {
            $this->info("Clearing cache before testing");
            foreach ($ids as $qId) {
                $cacheKey = "wikidata_label_{$qId}";
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }
        }

        $this->output->createProgressBar(count($ids));
        $this->output->progressStart();

        $results = [];

        foreach ($ids as $qId) {
            try {
                $startTime = microtime(true);

                // First test the static method
                $data = WikidataPropertyController::getEntityData($qId);
                $entityData = json_encode($data);

                // Then test the label method
                $label = WikidataPropertyController::getQLabel($qId);

                $endTime = microtime(true);
                $timeElapsed = round(($endTime - $startTime) * 1000, 2); // Convert to ms

                $results[$qId] = [
                    'success' => ($label !== $qId),
                    'label' => $label,
                    'data' => $data,
                    'time_ms' => $timeElapsed
                ];

            } catch (\Exception $e) {
                $results[$qId] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                Log::error("Error testing label for {$qId}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->line('');

        // Display results in a table
        $headers = ['Q ID', 'Success', 'Label', 'Time (ms)', 'Entity Data'];
        $rows = [];

        foreach ($results as $qId => $result) {
            $rows[] = [
                $qId,
                $result['success'] ? 'Yes' : 'No',
                $result['success'] ? $result['label'] : 'Failed',
                $result['time_ms'] ?? 'N/A',
                isset($result['data']) ? json_encode($result['data'], JSON_PRETTY_PRINT) : 'N/A'
            ];
        }

        $this->table($headers, $rows);

        // Summary
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $this->info("Successfully retrieved {$successCount} out of " . count($ids) . " labels");

        return Command::SUCCESS;
    }
}

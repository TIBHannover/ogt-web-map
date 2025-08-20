<?php

namespace App\Console\Commands;

use App\Mail\UserEntryPublishedNotification;
use App\Models\WikibaseEntryRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ResendPublishedNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:resend-published
                            {id? : The ID of the specific entry to resend notification for}
                            {--all : Resend for all published entries with email}
                            {--last= : Resend for entries published in the last X hours}
                            {--force : Send even if notify_on_publish is false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resend publication notifications for Wikidata entries';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->argument('id') && !$this->option('all') && !$this->option('last')) {
            $this->error('You must specify either an ID, --all, or --last option.');
            return 1;
        }

        $force = $this->option('force');

        // If a specific ID is provided
        if ($this->argument('id')) {
            $entryId = $this->argument('id');
            $this->info("Attempting to resend notification for entry ID: {$entryId}");

            $entry = WikibaseEntryRequest::find($entryId);

            if (!$entry) {
                $this->error("Entry with ID {$entryId} not found.");
                return 1;
            }

            return $this->resendNotification($entry, $force);
        }

        // Process all published entries
        if ($this->option('all')) {
            $this->info("Fetching all published entries with emails...");
            $entries = WikibaseEntryRequest::where('status', 'wikidata_published')
                ->whereNotNull('email')
                ->get();

            $this->info("Found " . count($entries) . " published entries with emails.");
        }

        // Process entries published in the last X hours
        if ($this->option('last')) {
            $hours = intval($this->option('last'));
            $this->info("Fetching entries published in the last {$hours} hours...");

            $cutoffTime = now()->subHours($hours);
            $entries = WikibaseEntryRequest::where('status', 'wikidata_published')
                ->whereNotNull('email')
                ->where('status_updated_at', '>=', $cutoffTime)
                ->get();

            $this->info("Found " . count($entries) . " entries published in the last {$hours} hours with emails.");
        }

        if (empty($entries) || count($entries) === 0) {
            $this->warn("No matching entries found.");
            return 0;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($entries as $entry) {
            $result = $this->resendNotification($entry, $force);
            if ($result === 0) {
                $sentCount++;
            } else {
                $failedCount++;
            }

            // Add a small delay to avoid overwhelming the mail server
            usleep(500000); // 0.5 seconds
        }

        $this->info("Processed " . count($entries) . " entries.");
        $this->info("Successfully sent: {$sentCount}");
        $this->info("Failed: {$failedCount}");

        return ($failedCount > 0) ? 1 : 0;
    }

    /**
     * Resend notification for a specific entry
     *
     * @param WikibaseEntryRequest $entry
     * @param bool $force Whether to force sending even if notify_on_publish is false
     * @return int
     */
    protected function resendNotification(WikibaseEntryRequest $entry, bool $force = false)
    {
        if (empty($entry->email)) {
            $this->warn("Entry ID {$entry->id} has no email address.");
            return 1;
        }

        if ($entry->status !== 'wikidata_published') {
            $this->warn("Entry ID {$entry->id} is not in 'wikidata_published' status (current: {$entry->status}).");
            return 1;
        }

        if (!$entry->notify_on_publish && !$force) {
            $this->warn("Entry ID {$entry->id} has notify_on_publish set to false. Use --force to send anyway.");
            return 1;
        }

        $this->info("Sending notification for entry ID {$entry->id} to {$entry->email}...");

        try {
            Log::info("Manually resending published notification", [
                'entry_id' => $entry->id,
                'email' => $entry->email,
                'command' => 'ResendPublishedNotification',
                'forced' => $force ? 'yes' : 'no'
            ]);

            Mail::to($entry->email)->send(new UserEntryPublishedNotification($entry));

            $this->info("Notification sent successfully for entry ID {$entry->id}.");

            // Log success with details
            Log::info("Successfully resent published notification", [
                'entry_id' => $entry->id,
                'email' => $entry->email,
                'wikidata_id' => $entry->wikidata_id ?? 'not set',
                'title' => $entry->title
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to send notification for entry ID {$entry->id}: " . $e->getMessage());

            // Log error with details
            Log::error("Failed to resend published notification", [
                'entry_id' => $entry->id,
                'email' => $entry->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }
}

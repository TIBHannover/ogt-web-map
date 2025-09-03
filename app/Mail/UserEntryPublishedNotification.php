<?php

namespace App\Mail;

use App\Models\WikibaseEntryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserEntryPublishedNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The entry request instance.
     *
     * @var WikibaseEntryRequest
     */
    public $entryRequest;

    /**
     * The Wikidata URL.
     *
     * @var string|null
     */
    public $wikidataUrl;

    /**
     * The map URL for the entry.
     *
     * @var string|null
     */
    public $mapUrl;

    /**
     * Create a new message instance.
     *
     * @param WikibaseEntryRequest $entryRequest
     * @return void
     */
    public function __construct(WikibaseEntryRequest $entryRequest)
    {
        $this->entryRequest = $entryRequest;

        // Log the notification creation
        Log::info("Creating UserEntryPublishedNotification", [
            "entry_id" => $entryRequest->id,
            "title" => $entryRequest->title,
            "wikidata_id" => $entryRequest->wikidata_id ?? "not set",
            "has_email" => !empty($entryRequest->email) ? "yes" : "no",
            "email" => $entryRequest->email ?? "not set",
        ]);

        // Generate the Wikidata URL if ID is available
        $this->wikidataUrl = null;
        if ($entryRequest->wikidata_id) {
            $this->wikidataUrl = "https://www.wikidata.org/wiki/{$entryRequest->wikidata_id}";
        }

        // Generate the map URL if applicable
        // This assumes there's a way to link directly to the entity on the map
        $this->mapUrl = null;
        if ($entryRequest->wikidata_id) {
            $baseUrl = config("app.url");
            $this->mapUrl = "{$baseUrl}/map?id={$entryRequest->wikidata_id}";
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        Log::info("Building UserEntryPublishedNotification email", [
            "entry_id" => $this->entryRequest->id,
            "title" => $this->entryRequest->title,
            "has_wikidata_url" => !empty($this->wikidataUrl) ? "yes" : "no",
        ]);

        try {
            return $this->subject(
                "Ihr Eintrag wurde veröffentlicht: " .
                    $this->entryRequest->title,
            )
                ->markdown("emails.user.entry-published")
                ->withSwiftMessage(function ($message) {
                    $message
                        ->getHeaders()
                        ->addTextHeader("X-Entity-ID", $this->entryRequest->id)
                        ->addTextHeader("X-Priority", "1");

                    Log::info("Swift message created successfully", [
                        "entry_id" => $this->entryRequest->id,
                    ]);
                });
        } catch (\Exception $e) {
            Log::error("Exception in UserEntryPublishedNotification build", [
                "entry_id" => $this->entryRequest->id,
                "exception" => $e->getMessage(),
            ]);

            // Return a simple message if markdown fails
            return $this->subject(
                "Ihr Eintrag wurde veröffentlicht: " .
                    $this->entryRequest->title,
            )->html("<p>Ihr Eintrag wurde in Wikidata veröffentlicht.</p>
                    <p>Titel: {$this->entryRequest->title}</p>
                    <p>Wikidata: {$this->wikidataUrl}</p>");
        }
    }
}

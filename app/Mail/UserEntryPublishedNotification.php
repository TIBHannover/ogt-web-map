<?php

namespace App\Mail;

use App\Models\WikibaseEntryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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

        // Generate the Wikidata URL if ID is available
        $this->wikidataUrl = null;
        if ($entryRequest->wikidata_id) {
            $this->wikidataUrl = "https://www.wikidata.org/wiki/{$entryRequest->wikidata_id}";
        }

        // Generate the map URL if applicable
        // This assumes there's a way to link directly to the entity on the map
        $this->mapUrl = null;
        if ($entryRequest->wikidata_id) {
            $baseUrl = config('app.url');
            $this->mapUrl = "{$baseUrl}/map?entity={$entryRequest->wikidata_id}";
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Ihr Eintrag wurde veröffentlicht: ' . $this->entryRequest->title)
                    ->markdown('emails.user.entry-published');
    }
}

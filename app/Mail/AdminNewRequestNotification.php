<?php

namespace App\Mail;

use App\Models\WikibaseEntryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminNewRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The entry request instance.
     *
     * @var WikibaseEntryRequest
     */
    public $entryRequest;

    /**
     * The admin URL for the entry.
     *
     * @var string
     */
    public $adminUrl;

    /**
     * Create a new message instance.
     *
     * @param WikibaseEntryRequest $entryRequest
     * @return void
     */
    public function __construct(WikibaseEntryRequest $entryRequest)
    {
        $this->entryRequest = $entryRequest;

        // Generate the admin URL for the entry
        $baseUrl = config("app.url");
        $this->adminUrl = "{$baseUrl}/admin/requests/{$entryRequest->id}";
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(
            "Neue Wikidata-Eintragsanfrage: " . $this->entryRequest->title,
        )->markdown("emails.admin.new-request");
    }
}

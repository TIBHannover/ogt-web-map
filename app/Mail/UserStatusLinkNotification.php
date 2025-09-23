<?php

namespace App\Mail;

use App\Models\WikibaseEntryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserStatusLinkNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The entry request instance.
     *
     * @var WikibaseEntryRequest
     */
    public $entryRequest;

    /**
     * The status tracking URL.
     *
     * @var string
     */
    public $statusUrl;

    /**
     * Create a new message instance.
     *
     * @param WikibaseEntryRequest $entryRequest
     * @return void
     */
    public function __construct(WikibaseEntryRequest $entryRequest)
    {
        $this->entryRequest = $entryRequest;

        // Generate the status tracking URL
        $baseUrl = config("app.url");
        $this->statusUrl = "{$baseUrl}/eintrag-status?id={$entryRequest->status_token}";
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(
            "Ihr Eintragsanfragestatus: " . $this->entryRequest->title,
        )->markdown("emails.user.status-link");
    }
}

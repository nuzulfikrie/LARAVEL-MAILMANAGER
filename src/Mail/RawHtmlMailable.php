<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * MVP retry path: re-send stored subject + HTML without template lookup.
 */
class RawHtmlMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $htmlBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlBody);
    }
}

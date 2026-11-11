<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Asks a new sign-up to confirm their address (double opt-in), with a signed link that expires, in their language,
 * as plain text. Queued after the commit, so a rolled-back sign-up is never asked.
 */
final class ConfirmMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $site,
        public readonly string $campaign,
        public readonly string $link,
        public readonly int $days,
        public readonly ?Address $sender = null,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(from: $this->sender, subject: __('laranail/waitlist-kit::mail.confirm_subject', ['site' => $this->site]));
    }

    public function content(): Content
    {
        return new Content(text: 'laranail/waitlist-kit::mail.confirm');
    }
}

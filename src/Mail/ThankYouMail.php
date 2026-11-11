<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Thanks a new sign-up, in their language (the sender sets the locale), as plain text. Queued after the commit, so a
 * rolled-back sign-up is never thanked.
 */
final class ThankYouMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $site, public readonly string $campaign)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('laranail/waitlist-kit::mail.subject', ['site' => $this->site]));
    }

    public function content(): Content
    {
        return new Content(text: 'laranail/waitlist-kit::mail.thank-you');
    }
}

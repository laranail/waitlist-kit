<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Thanks a new sign-up, in their language (the sender sets the locale), as plain text. Queued after the commit, so a
 * rolled-back sign-up is never thanked.
 */
final class ThankYouMail extends Mailable implements ShouldQueue
{
    use Queueable;

    /** @param ?string $statusUrl the subscriber's status page, when they hold a place on the list */
    public function __construct(public readonly string $site, public readonly string $campaign, public readonly ?Address $sender = null, public readonly ?string $statusUrl = null, public readonly ?string $unsubscribeUrl = null)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(from: $this->sender, subject: __('laranail/waitlist-kit::mail.subject', ['site' => $this->site]));
    }

    /** One-click unsubscribe (RFC 8058) for mail clients that offer it; the same signed link as in the text. */
    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribeUrl === null ? [] : [
            'List-Unsubscribe'      => '<' . $this->unsubscribeUrl . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(text: 'laranail/waitlist-kit::mail.thank-you');
    }
}

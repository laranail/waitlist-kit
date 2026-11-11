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

/** Tells a subscriber their access is ready, with the link to the product, in their language, as plain text. */
final class InviteMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $site,
        public readonly string $campaign,
        public readonly string $link,
        public readonly ?Address $sender = null,
        public readonly ?string $unsubscribeUrl = null,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(from: $this->sender, subject: __('laranail/waitlist-kit::mail.invite_subject', ['site' => $this->site]));
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
        return new Content(text: 'laranail/waitlist-kit::mail.invite');
    }
}

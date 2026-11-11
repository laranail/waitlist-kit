<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;

/**
 * An enquiry, to the site's inbox, with the visitor as Reply-To so answering is replying. The topic leads the
 * subject so the inbox can be sorted by it. Carries the enquiry's id, not its content, so the queue holds no message.
 */
final class EnquiryNotice extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $enquiryId, public readonly string $site)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $enquiry = $this->enquiry();

        return new Envelope(
            replyTo: [new Address($enquiry->email, $enquiry->name)],
            subject: sprintf('[%s] %s via %s', ucfirst($enquiry->topic), $enquiry->name, $this->site),
        );
    }

    public function content(): Content
    {
        return new Content(text: 'laranail/waitlist-kit::mail.enquiry', with: ['enquiry' => $this->enquiry()]);
    }

    private function enquiry(): Enquiry
    {
        return Enquiry::query()->findOrFail($this->enquiryId);
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use Illuminate\Contracts\Routing\UrlGenerator;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;

/**
 * The links a subscriber's mail carries, built in one place: their status page (once they hold a place) and the
 * signed unsubscribe link that every mail includes. The unsubscribe link does not expire, so an old mail still works.
 */
final readonly class MailLinks
{
    public function __construct(private UrlGenerator $urls) {}

    public function status(Campaign $campaign, Subscriber $subscriber): ?string
    {
        return $subscriber->position !== null && $subscriber->referral_code !== null
            ? $this->urls->route('laranail-waitlist-kit.campaign.status', ['campaign' => $campaign->slug, 'code' => $subscriber->referral_code])
            : null;
    }

    public function unsubscribe(Campaign $campaign, Subscriber $subscriber): string
    {
        return $this->urls->signedRoute('laranail-waitlist-kit.campaign.unsubscribe', ['campaign' => $campaign->slug, 'subscriber' => $subscriber->id]);
    }
}

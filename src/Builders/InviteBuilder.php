<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Builders;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Services\Authorizer;
use Simtabi\Laranail\WaitlistKit\Builders\Concerns\ActsAs;
use Simtabi\Laranail\WaitlistKit\Actions\InviteSubscribers;

/**
 * Invites the top of a campaign's list:
 *
 *     $kit->invite('launch')->top(25)->as($user)->send();
 *
 * InviteSubscribers takes the confirmed subscribers by place, mails each their invitation, and records it; acting as a
 * user it is checked against SubscriberPolicy::invite.
 */
final class InviteBuilder
{
    use ActsAs;

    private int $count = 0;

    public function __construct(
        private readonly InviteSubscribers $invite,
        private readonly Authorizer $authorizer,
        private readonly Campaign $campaign,
    ) {}

    public function top(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    /** How many were invited. */
    public function send(): int
    {
        $this->authorizer->authorize($this->actor(), 'invite', Subscriber::class);

        return $this->invite->handle($this->campaign, $this->count, $this->actor());
    }
}

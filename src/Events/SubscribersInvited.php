<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Subscribers were invited, in place order.
 *
 * Dispatched after the change commits, so a listener never sees a row that was rolled back. `$actor` is who did it.
 */
final class SubscribersInvited implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Campaign $campaign,
        /** @var Collection<int, Subscriber> */
        public Collection $subscribers,
        public Actor $actor,
    ) {}
}

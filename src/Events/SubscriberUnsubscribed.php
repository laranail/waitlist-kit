<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * A subscriber left the list.
 *
 * Dispatched after the change commits, so a listener never sees a row that was rolled back. `$actor` is who did it.
 */
final class SubscriberUnsubscribed implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Subscriber $subscriber,
        public Actor $actor,
    ) {}
}

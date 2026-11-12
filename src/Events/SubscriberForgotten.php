<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * An address was erased everywhere. The address is carried so a listener can erase it from its own systems too; never log it.
 *
 * Dispatched after the change commits, so a listener never sees a row that was rolled back. `$actor` is who did it.
 */
final class SubscriberForgotten implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $email,
        public int $subscribers,
        public int $enquiries,
        public Actor $actor,
    ) {}
}

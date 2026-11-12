<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * A campaign's own fields were saved. `$changes` holds the attributes that changed, with their new values.
 *
 * Dispatched after the change commits, so a listener never sees a row that was rolled back. `$actor` is who did it.
 */
final class CampaignUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Campaign $campaign,
        /** @var array<string, mixed> */
        public array $changes,
        public Actor $actor,
    ) {}
}

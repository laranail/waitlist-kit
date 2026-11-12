<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * A campaign was deleted with everything it held. The model is gone: its id, slug and name are carried instead.
 *
 * Dispatched after the change commits, so a listener never sees a row that was rolled back. `$actor` is who did it.
 */
final class CampaignDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public Actor $actor,
    ) {}
}

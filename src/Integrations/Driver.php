<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Integrations;

use Simtabi\Laranail\WaitlistKit\Models\Subscriber;

/** Sends one subscriber somewhere else. Throws IntegrationFailed when the service does not take it. */
interface Driver
{
    /** @param  array<string, mixed>  $settings  the integration's decrypted settings */
    public function push(Subscriber $subscriber, array $settings): void;
}

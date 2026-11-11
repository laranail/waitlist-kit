<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Data;

use Simtabi\Laranail\WaitlistKit\Models\Subscriber;

/** What a sign-up did: the subscriber, and whether this post created them or they were already on the list. */
final readonly class SignupResult
{
    public function __construct(public Subscriber $subscriber, public bool $created) {}
}

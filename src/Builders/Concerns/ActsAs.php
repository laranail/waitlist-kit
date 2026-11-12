<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Builders\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Simtabi\Laranail\WaitlistKit\Support\Actor;

/**
 * Who a builder acts for. Application code acts as the system unless it says otherwise; an HTTP endpoint passes the
 * signed-in user (or null for a guest), and the action then checks that user against the Gate and the policies.
 */
trait ActsAs
{
    private ?Actor $actor = null;

    /** Act as this user (a guest when null), or as an Actor built elsewhere. */
    public function as(Authenticatable|Actor|null $user): static
    {
        $this->actor = $user instanceof Actor ? $user : Actor::from($user);

        return $this;
    }

    /** Act as the application itself: no authorization check. The default. */
    public function asSystem(): static
    {
        $this->actor = Actor::system();

        return $this;
    }

    public function actor(): Actor
    {
        return $this->actor ?? Actor::system();
    }
}

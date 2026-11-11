<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Exceptions;

use DomainException;

/** Invitations need somewhere to send people: the campaign's invite URL is not set. */
final class NoInviteUrl extends DomainException
{
    public static function for(string $slug): self
    {
        return new self(sprintf('The campaign "%s" has no invite URL to send invitations to.', $slug));
    }
}

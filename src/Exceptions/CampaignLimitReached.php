<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Exceptions;

use RuntimeException;

final class CampaignLimitReached extends RuntimeException
{
    public static function at(int $limit): self
    {
        return new self(sprintf(
            'This install may run %d campaign%s (laranail.waitlist-kit.campaigns), and already does.',
            $limit,
            $limit === 1 ? '' : 's',
        ));
    }
}

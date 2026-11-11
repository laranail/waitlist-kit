<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Exceptions;

use RuntimeException;

final class CampaignNotOpen extends RuntimeException
{
    public static function for(string $slug): self
    {
        return new self(sprintf('The campaign "%s" is not live, so it takes no sign-ups.', $slug));
    }
}

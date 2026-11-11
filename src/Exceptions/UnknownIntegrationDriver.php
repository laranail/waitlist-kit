<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Exceptions;

use InvalidArgumentException;

final class UnknownIntegrationDriver extends InvalidArgumentException
{
    /** @param  list<string>  $known */
    public static function named(string $driver, array $known): self
    {
        return new self(sprintf('No integration driver is named "%s"; the kit has: %s.', $driver, implode(', ', $known)));
    }
}

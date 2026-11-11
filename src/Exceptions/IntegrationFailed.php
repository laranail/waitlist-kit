<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Exceptions;

use RuntimeException;

/** A driver's service refused or could not be reached. The message names the driver and the status, never an address. */
final class IntegrationFailed extends RuntimeException
{
    public static function because(string $driver, string $reason): self
    {
        return new self(sprintf('The %s integration failed: %s', $driver, $reason));
    }
}

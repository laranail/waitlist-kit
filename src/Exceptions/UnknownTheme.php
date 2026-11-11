<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Exceptions;

use InvalidArgumentException;

final class UnknownTheme extends InvalidArgumentException
{
    /** @param  list<string>  $installed */
    public static function named(string $name, array $installed): self
    {
        return new self(sprintf('No theme named "%s" is installed; installed: %s.', $name, $installed === [] ? 'none' : implode(', ', $installed)));
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Exceptions;

use BackedEnum;
use InvalidArgumentException;

final class InvalidConfiguration extends InvalidArgumentException
{
    /** @param  list<BackedEnum>  $allowed */
    public function __construct(string $key, string $value, array $allowed)
    {
        parent::__construct(sprintf(
            'laranail.waitlist-kit.%s is "%s"; expected one of: %s.',
            $key,
            $value,
            implode(', ', array_map(static fn (BackedEnum $case): string => (string) $case->value, $allowed)),
        ));
    }
}

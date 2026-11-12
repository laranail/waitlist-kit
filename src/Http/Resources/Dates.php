<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Resources;

use DateTimeInterface;

/** Dates in API answers: ISO 8601 with the offset, or null. */
final class Dates
{
    public static function iso(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }
}

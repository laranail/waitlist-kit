<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

/** The locales field, typed as "en, es, pt-BR": split on commas and spaces, empties dropped. */
final class LocaleList
{
    /** @return list<string>|mixed */
    public static function from(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return array_values(array_filter(array_map(trim(...), preg_split('/[\s,]+/', $value) ?: []), static fn (string $l): bool => $l !== ''));
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A language tag the kit stores: `en`, or a region form like `pt-BR`. */
final readonly class Locale implements ValidationRule
{
    public const string PATTERN = '/^[a-z]{2}(-[A-Z]{2})?$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail('laranail/waitlist-kit::validation.locale')->translate();
        }
    }
}

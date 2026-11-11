<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

/**
 * Where a form without JavaScript sends a visitor back to: a path on this site. A full URL, `//host` (a path-relative
 * URL to another host) or a backslash variant browsers treat the same way falls back to the home page, so a form
 * cannot be used as an open redirect.
 */
final class ReturnPath
{
    public static function from(string $value): string
    {
        return str_starts_with($value, '/') && ! str_starts_with($value, '//') && ! str_contains($value, '\\') ? $value : '/';
    }
}

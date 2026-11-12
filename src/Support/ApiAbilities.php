<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

/**
 * The Sanctum token abilities the API's `manage/*` endpoints ask for, beside the Gate. A token made with
 * `$user->createToken('reports', [ApiAbilities::READ])` can then only read, whatever its user may do. `*` covers all.
 */
final class ApiAbilities
{
    public const string READ = 'waitlist:read';

    public const string WRITE = 'waitlist:write';

    public const string EXPORT = 'waitlist:export';

    public const string ERASE = 'waitlist:erase';

    public const array ALL = [self::READ, self::WRITE, self::EXPORT, self::ERASE];
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Tests\Fixtures\Auth;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\GenericUser;

/** An application user with Sanctum tokens, for the API tests; no table needed. */
final class ApiUser extends GenericUser
{
    use HasApiTokens;
}

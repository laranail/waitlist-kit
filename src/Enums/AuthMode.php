<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** How the backend signs people in (config `auth.mode`). */
enum AuthMode: string
{
    case Host = 'host';
    case Bundled = 'bundled';
}

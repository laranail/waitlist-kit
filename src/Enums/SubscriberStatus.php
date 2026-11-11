<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** Where a lead is in the funnel, from sign-up to conversion. */
enum SubscriberStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Invited = 'invited';
    case Converted = 'converted';
    case Unsubscribed = 'unsubscribed';
}

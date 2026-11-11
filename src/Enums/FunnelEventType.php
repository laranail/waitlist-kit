<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** One step of the funnel, recorded append-only for the dashboard. */
enum FunnelEventType: string
{
    case View = 'view';
    case Signup = 'signup';
    case Confirm = 'confirm';
    case Share = 'share';
    case Referral = 'referral';
    case Invite = 'invite';
    case Convert = 'convert';
    case Unsubscribe = 'unsubscribe';
    case Enquiry = 'enquiry';
}

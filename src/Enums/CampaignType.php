<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** What a campaign's page is for. Each type is rendered by the active theme. */
enum CampaignType: string
{
    case Waitlist = 'waitlist';
    case ComingSoon = 'coming_soon';
    case EarlyAccess = 'early_access';
}

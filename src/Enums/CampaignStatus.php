<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** Where a campaign is in its life: only a live campaign takes sign-ups. */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case Live = 'live';
    case Closed = 'closed';
    case Launched = 'launched';
}

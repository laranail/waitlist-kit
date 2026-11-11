<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** How many campaigns one install may run (config `campaigns.mode`). */
enum CampaignsMode: string
{
    case Single = 'single';
    case Limited = 'limited';
    case Multi = 'multi';
}

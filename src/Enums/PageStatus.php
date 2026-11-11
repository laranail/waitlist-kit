<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** Only a published page is served. */
enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

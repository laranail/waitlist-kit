<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** What a kit-managed page is: a legal page the forms link to, or any other page. */
enum PageKind: string
{
    case Legal = 'legal';
    case Page = 'page';
}

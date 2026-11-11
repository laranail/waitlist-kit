<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** How a theme is styled: Tailwind 4 built into the theme, or plain CSS driven by custom properties. */
enum ThemeStyling: string
{
    case Tailwind = 'tailwind';
    case Css = 'css';
}

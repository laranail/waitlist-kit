<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** The kinds of value a theme setting holds; each is checked before it reaches CSS. */
enum ThemeSettingType: string
{
    case Color = 'color';
    case Font = 'font';
    case Image = 'image';
    case Text = 'text';
    case Toggle = 'toggle';
}

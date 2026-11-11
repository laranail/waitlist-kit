<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * Which view renders a part of a page: the theme's own when it has one, else the kit's base view. That fallback is
 * what lets a theme override one block and inherit the rest.
 */
final readonly class ThemeViews
{
    public const string BASE = 'laranail/waitlist-kit::base.';

    public function __construct(private ViewFactory $views) {}

    public function resolve(Theme $theme, string $name): string
    {
        if ($theme->views !== null && $this->views->exists($theme->views . $name)) {
            return $theme->views . $name;
        }

        return self::BASE . $name;
    }
}

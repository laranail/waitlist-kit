<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;

/** Builds the ThemeContext for a page: the campaign's theme and settings, or the default theme for other pages. */
final readonly class ThemeContextFactory
{
    public function __construct(private ActiveTheme $active, private ThemeViews $views, private ThemeAssets $assets) {}

    public function for(?Campaign $campaign = null): ThemeContext
    {
        $theme = $this->active->for($campaign);

        return new ThemeContext($theme, $this->views, $this->assets->tags($theme), $this->assets->nonce(), $campaign->theme_settings ?? []);
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;

/**
 * The theme a page renders in: the campaign's own when it is installed, else `themes.default`, else the kit's default.
 * A theme that was uninstalled never breaks a page; the doctor reports the campaign instead.
 */
final readonly class ActiveTheme
{
    public function __construct(private ThemeRegistry $themes, private KitConfig $config) {}

    public function for(?Campaign $campaign = null): Theme
    {
        foreach ([$campaign?->theme, $this->config->defaultTheme(), KitThemes::DEFAULT] as $name) {
            if (is_string($name) && $this->themes->has($name)) {
                return $this->themes->get($name);
            }
        }

        return KitThemes::default();
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use InvalidArgumentException;
use Simtabi\Laranail\WaitlistKit\Enums\ThemeStyling;

/**
 * A theme: what a theme package registers in its service provider (ThemeRegistry::register()).
 *
 * - `views`: the view namespace whose files override the kit's base views path by path (`layouts.page`,
 *   `blocks.hero`, ...); null, or any view it lacks, falls back to the base.
 * - `buildDirectory` and `entries`: the theme's prebuilt assets, a Vite build published under `public/`, so switching
 *   themes needs no Node on the server.
 * - `settings`: what the backend may change, each checked by type before it reaches CSS.
 */
final readonly class Theme
{
    /**
     * @param list<string> $entries source paths as they appear in the build's manifest.json
     * @param list<ThemeSetting> $settings
     */
    public function __construct(
        public string $name,
        public string $label,
        public ThemeStyling $styling,
        public string $buildDirectory,
        public array $entries,
        public ?string $views = null,
        public array $settings = [],
        public string $description = '',
    ) {
        if (preg_match('#^[a-z0-9-]+/[a-z0-9-]+(:[a-z0-9-]+)?$#', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('A theme is named after its package, "vendor/package" or "vendor/package:theme"; "%s" is not.', $name));
        }
    }

    public function setting(string $key): ?ThemeSetting
    {
        foreach ($this->settings as $setting) {
            if ($setting->key === $key) {
                return $setting;
            }
        }

        return null;
    }

    /**
     * The settings as CSS custom properties: each value checked by its type, the default where the stored value is
     * missing or unsafe. Texts are left out: they are not CSS.
     *
     * @param array<string, mixed> $values the campaign's theme_settings
     */
    public function cssVariables(array $values): string
    {
        $declarations = [];
        foreach ($this->settings as $setting) {
            $css = $setting->cssValue($values[$setting->key] ?? $setting->default);
            if ($css !== null) {
                $declarations[] = sprintf('--wk-%s: %s;', $setting->key, $css);
            }
        }

        return $declarations === [] ? '' : ':root{' . implode('', $declarations) . '}';
    }

    /**
     * A text setting's value, or its default.
     *
     * @param array<string, mixed> $values
     */
    public function text(string $key, array $values): string
    {
        $value = $values[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : ($this->setting($key)->default ?? '');
    }
}

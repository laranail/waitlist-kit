<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use Simtabi\Laranail\WaitlistKit\Enums\ThemeStyling;

/**
 * The kit's two themes, one per styling mode, built into resources/dist and published to public/vendor/waitlist-kit.
 * Both read the same settings, so a campaign can switch between them without losing its colours.
 */
final class KitThemes
{
    public const string BUILD_DIRECTORY = 'vendor/waitlist-kit';

    public const string DEFAULT = 'laranail/waitlist-kit:default';

    public const string PLAIN = 'laranail/waitlist-kit:plain';

    public const string SCRIPT = 'resources/assets/js/kit.js';

    /** @return list<Theme> */
    public static function all(): array
    {
        return [self::default(), self::plain()];
    }

    /** Tailwind 4, prebuilt; overrides every base view with utility classes. */
    public static function default(): Theme
    {
        return new Theme(
            name: self::DEFAULT,
            label: 'Default',
            styling: ThemeStyling::Tailwind,
            buildDirectory: self::BUILD_DIRECTORY,
            entries: ['resources/assets/css/themes/default.css', self::SCRIPT],
            views: 'laranail/waitlist-kit::themes.default.',
            settings: self::settings(),
            description: 'A clean, centred page built with Tailwind 4.',
        );
    }

    /** Plain CSS on custom properties; no views of its own, so every page uses the kit's base views. */
    public static function plain(): Theme
    {
        return new Theme(
            name: self::PLAIN,
            label: 'Plain',
            styling: ThemeStyling::Css,
            buildDirectory: self::BUILD_DIRECTORY,
            entries: ['resources/assets/css/themes/plain.css', self::SCRIPT],
            settings: self::settings(),
            description: 'Semantic HTML and one small stylesheet; no build step to customise.',
        );
    }

    /** @return list<ThemeSetting> */
    private static function settings(): array
    {
        return [
            ThemeSetting::color('primary', '#4f46e5', 'Primary colour'),
            ThemeSetting::color('background', '#ffffff', 'Background'),
            ThemeSetting::color('text', '#111827', 'Text'),
            ThemeSetting::font('font', 'system-ui, sans-serif', 'Font'),
            ThemeSetting::image('logo', '', 'Logo'),
            ThemeSetting::text('footer', '', 'Footer note'),
            ThemeSetting::toggle('show-powered-by', false, 'Show "Powered by"'),
        ];
    }
}

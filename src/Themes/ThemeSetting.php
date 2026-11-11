<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use InvalidArgumentException;
use Simtabi\Laranail\WaitlistKit\Enums\ThemeSettingType;

/**
 * One value a theme lets the backend change: a colour, a font, an image, a text or a toggle. Colours, fonts, images and
 * toggles become `--wk-{key}` custom properties; texts are read by the theme's views.
 */
final readonly class ThemeSetting
{
    public function __construct(
        public string $key,
        public ThemeSettingType $type,
        public string $default,
        public string $label = '',
    ) {
        if (preg_match('/^[a-z][a-z0-9-]{0,40}$/', $key) !== 1) {
            throw new InvalidArgumentException(sprintf('A theme setting key is lower-case letters, digits and hyphens; "%s" is not.', $key));
        }
    }

    public static function color(string $key, string $default, string $label = ''): self
    {
        return new self($key, ThemeSettingType::Color, $default, $label);
    }

    public static function font(string $key, string $default, string $label = ''): self
    {
        return new self($key, ThemeSettingType::Font, $default, $label);
    }

    public static function image(string $key, string $default = '', string $label = ''): self
    {
        return new self($key, ThemeSettingType::Image, $default, $label);
    }

    public static function text(string $key, string $default = '', string $label = ''): self
    {
        return new self($key, ThemeSettingType::Text, $default, $label);
    }

    public static function toggle(string $key, bool $default, string $label = ''): self
    {
        return new self($key, ThemeSettingType::Toggle, $default ? '1' : '0', $label);
    }

    /** The value made safe for this type, or the default when it is not one. Null for a text: texts are not CSS. */
    public function cssValue(mixed $value): ?string
    {
        $candidate = is_scalar($value) ? trim((string) $value) : $this->default;

        return match ($this->type) {
            ThemeSettingType::Color  => preg_match('/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $candidate) === 1 ? $candidate : $this->default,
            ThemeSettingType::Font   => preg_match('/^[A-Za-z0-9 ,\'"-]{1,120}$/', $candidate) === 1 ? $candidate : $this->default,
            ThemeSettingType::Image  => $this->url($candidate) ?? $this->url($this->default) ?? 'none',
            ThemeSettingType::Toggle => in_array($candidate, ['1', 'true', 'on'], true) ? '1' : '0',
            ThemeSettingType::Text   => null,
        };
    }

    /** `url("...")` for an https or site-relative URL with nothing that could close it; null otherwise. */
    private function url(string $value): ?string
    {
        $safe = (str_starts_with($value, 'https://') || (str_starts_with($value, '/') && ! str_starts_with($value, '//')))
            && preg_match('/^[A-Za-z0-9\/:._~%?&=+-]+$/', $value) === 1;

        return $safe ? 'url("' . $value . '")' : null;
    }
}

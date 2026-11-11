<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Validation;

use Illuminate\Validation\Rule;
use Simtabi\Laranail\WaitlistKit\Themes\Theme;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\WaitlistKit\Enums\ThemeSettingType;

/**
 * A campaign's theme choice and its settings, from the chosen theme's own schema. The same checks the theme applies
 * before CSS refuse a bad value here first, so an administrator sees why instead of a silent default.
 */
final class ThemeSettingsRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(ThemeRegistry $themes, ?Theme $theme): array
    {
        $rules = ['theme' => ['required', 'string', Rule::in(array_keys($themes->all()))]];

        foreach ($theme->settings ?? [] as $setting) {
            $rules['settings.' . $setting->key] = match ($setting->type) {
                ThemeSettingType::Color  => ['nullable', 'string', 'regex:/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i'],
                ThemeSettingType::Font   => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9 ,\'"-]+$/'],
                ThemeSettingType::Image  => ['nullable', 'string', 'max:2000', 'regex:#^(https://|/(?!/))[A-Za-z0-9/:._~%?&=+-]*$#'],
                ThemeSettingType::Text   => ['nullable', 'string', 'max:200'],
                ThemeSettingType::Toggle => ['nullable', 'boolean'],
            };
        }

        return $rules;
    }
}

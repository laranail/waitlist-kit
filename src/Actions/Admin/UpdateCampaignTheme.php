<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\WaitlistKit\Enums\ThemeSettingType;

/**
 * Sets a campaign's theme and its settings. Only the chosen theme's own keys are kept, an empty value falls back to
 * the theme's default, and toggles are stored as '1' or '0'.
 */
final readonly class UpdateCampaignTheme
{
    public function __construct(private ThemeRegistry $themes) {}

    /** @param  array<string, mixed>  $settings */
    public function handle(Campaign $campaign, string $themeName, array $settings): Campaign
    {
        $theme = $this->themes->get($themeName);

        $kept = [];
        foreach ($theme->settings as $setting) {
            $value = $settings[$setting->key] ?? null;
            if ($setting->type === ThemeSettingType::Toggle) {
                $kept[$setting->key] = in_array($value, [true, 1, '1', 'true', 'on'], true) ? '1' : '0';
            } elseif (is_string($value) && trim($value) !== '') {
                $kept[$setting->key] = trim($value);
            }
        }

        $campaign->update(['theme' => $theme->name, 'theme_settings' => $kept]);

        return $campaign;
    }
}

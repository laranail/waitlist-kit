<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\WaitlistKit\Validation\ThemeSettingsRules;

final class CampaignThemeRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(ThemeRegistry $themes): array
    {
        $name = $this->string('theme')->toString();

        return ThemeSettingsRules::rules($themes, $themes->has($name) ? $themes->get($name) : null);
    }
}

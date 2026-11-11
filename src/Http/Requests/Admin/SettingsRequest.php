<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Illuminate\Contracts\Auth\Access\Gate;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\WaitlistKit\Settings\SettingsStore;
use Simtabi\Laranail\WaitlistKit\Settings\EditableSettings;

final class SettingsRequest extends AdminRequest
{
    /** Saving needs manage, and a store that can keep values: on the env store the settings are read-only. */
    public function authorize(Gate $gate, ?SettingsStore $store = null): bool
    {
        return parent::authorize($gate) && $store?->writable() === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(ThemeRegistry $themes): array
    {
        $rules = [];
        foreach (EditableSettings::rules($themes) as $key => $rule) {
            // The form posts `signups[per_minute]`, which validates as `signups.per_minute`.
            $rules[$key] = $rule;
        }

        return $rules;
    }
}

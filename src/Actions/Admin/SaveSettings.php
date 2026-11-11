<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Illuminate\Support\Arr;
use Simtabi\Laranail\WaitlistKit\Settings\SettingsStore;
use Simtabi\Laranail\WaitlistKit\Settings\EditableSettings;

/** Saves every editable setting to the settings store; an emptied text falls back to the configuration's value. */
final readonly class SaveSettings
{
    public function __construct(private SettingsStore $store) {}

    /** @param  array<string, mixed>  $validated  nested as posted (`['signups' => ['per_minute' => 10]]`) */
    public function handle(array $validated): void
    {
        foreach (array_keys(EditableSettings::all()) as $key) {
            $this->store->save($key, EditableSettings::cast($key, Arr::get($validated, $key)));
        }
    }
}

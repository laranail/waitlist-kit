<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\WaitlistKit\Themes\Theme;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\WaitlistKit\Settings\SettingsStore;
use Simtabi\Laranail\WaitlistKit\Settings\EditableSettings;
use Simtabi\Laranail\WaitlistKit\Actions\Admin\SaveSettings;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\SettingsRequest;

/** The kit's editable settings: read-only from the configuration, or saved through laranail/settings. */
final readonly class SettingsController
{
    public function __construct(private ViewFactory $views, private KitConfig $config, private SettingsStore $store) {}

    public function show(ThemeRegistry $themes): View
    {
        $values = [];
        foreach (array_keys(EditableSettings::all()) as $key) {
            $value = $this->config->editable($key);
            $values[$key] = match (true) {
                is_array($value)  => implode(', ', array_filter($value, is_string(...))),
                is_bool($value)   => $value,
                is_scalar($value) => (string) $value,
                default           => '',
            };
        }

        return $this->views->make('laranail/waitlist-kit::admin.settings', [
            'settings' => EditableSettings::all(),
            'values'   => $values,
            'writable' => $this->store->writable(),
            'themes'   => array_map(static fn (Theme $t): string => $t->label, $themes->all()),
        ]);
    }

    public function update(SettingsRequest $request, SaveSettings $save): RedirectResponse
    {
        $save->handle($request->validated());

        return redirect()->route('laranail-waitlist-kit.admin.settings.show')->with('status', __('laranail/waitlist-kit::admin.saved'));
    }
}

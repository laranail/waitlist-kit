<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Settings;

use Simtabi\Laranail\Settings\Contracts\Settings;

/**
 * `settings.store = database`: values saved in the backend live in laranail/settings under the kit's own prefix
 * (`laranail_waitlist_kit.signups.per_minute`), and override the configuration; a key never saved falls back to it.
 */
final readonly class LaranailSettingsStore implements SettingsStore
{
    /** Underscores, not hyphens: laranail/settings keys are dot-separated [a-z0-9_] segments. */
    public const string PREFIX = 'laranail_waitlist_kit.';

    public function __construct(private Settings $settings) {}

    public function writable(): bool
    {
        return true;
    }

    public function override(string $key): mixed
    {
        return $this->settings->has(self::PREFIX . $key) ? $this->settings->get(self::PREFIX . $key) : null;
    }

    public function save(string $key, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->settings->forget(self::PREFIX . $key);

            return;
        }

        $this->settings->set(self::PREFIX . $key, $value);
    }
}

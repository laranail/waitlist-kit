<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Settings;

/**
 * Where the backend's editable settings are kept (config `settings.store`): the configuration itself, read-only, or
 * laranail/settings, where a saved value overrides the configuration's.
 */
interface SettingsStore
{
    /** Whether the backend may change settings through this store. */
    public function writable(): bool;

    /** A saved override for a kit key (`signups.per_minute`), or null to use the configuration's value. */
    public function override(string $key): mixed;

    public function save(string $key, mixed $value): void;
}

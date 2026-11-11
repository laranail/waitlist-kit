<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Settings;

use LogicException;

/** `settings.store = env`: the configuration (and so .env) is the only source; the backend shows it read-only. */
final readonly class ConfigStore implements SettingsStore
{
    public function writable(): bool
    {
        return false;
    }

    public function override(string $key): mixed
    {
        return null;
    }

    public function save(string $key, mixed $value): void
    {
        throw new LogicException('Settings come from the configuration (settings.store = env); set them in .env.');
    }
}

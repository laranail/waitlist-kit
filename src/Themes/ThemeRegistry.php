<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use Simtabi\Laranail\WaitlistKit\Exceptions\UnknownTheme;

/**
 * The installed themes. The kit registers its two; a theme package registers its own from its service provider:
 *
 *     $this->app->afterResolving(ThemeRegistry::class, fn (ThemeRegistry $themes) => $themes->register(new Theme(...)));
 *
 * Process-wide, like the service providers that fill it: it holds what is installed, never anything from a request.
 */
final class ThemeRegistry
{
    /** @var array<string, Theme> */
    private array $themes = [];

    public function register(Theme $theme): void
    {
        $this->themes[$theme->name] = $theme;
    }

    public function has(string $name): bool
    {
        return isset($this->themes[$name]);
    }

    public function get(string $name): Theme
    {
        return $this->themes[$name] ?? throw UnknownTheme::named($name, array_keys($this->themes));
    }

    /** @return array<string, Theme> */
    public function all(): array
    {
        return $this->themes;
    }
}

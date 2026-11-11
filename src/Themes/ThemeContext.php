<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use Illuminate\Contracts\Support\Htmlable;
use Simtabi\Laranail\WaitlistKit\Enums\BlockType;

/**
 * What a themed view needs about its theme, in one object: which view renders a part, the settings as CSS, the asset
 * tags and the CSP nonce. Views ask `$theme->view('blocks.hero')` and never resolve a view name themselves.
 */
final readonly class ThemeContext
{
    /** @param  array<string, mixed>  $settings  the campaign's theme_settings */
    public function __construct(
        public Theme $theme,
        private ThemeViews $views,
        public Htmlable $assets,
        public ?string $nonce,
        public array $settings = [],
    ) {}

    public function view(string $name): string
    {
        return $this->views->resolve($this->theme, $name);
    }

    /** The view for a block, or null for a type no theme renders. */
    public function block(string $type): ?string
    {
        return BlockType::tryFrom($type) instanceof BlockType ? $this->view('blocks.' . $type) : null;
    }

    public function css(): string
    {
        return $this->theme->cssVariables($this->settings);
    }

    public function text(string $key): string
    {
        return $this->theme->text($key, $this->settings);
    }

    public function enabled(string $key): bool
    {
        return $this->theme->setting($key)?->cssValue($this->settings[$key] ?? null) === '1';
    }
}

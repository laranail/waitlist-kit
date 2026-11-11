<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Themes;

use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Foundation\Application;

/**
 * A theme's `<link>` and `<script>` tags, from its own prebuilt manifest.
 *
 * Built from a clone of the application's Vite, so the application's own @vite calls keep their build directory, and
 * with a hot file that never exists, so the application's dev server never serves a theme's entries. The CSP nonce
 * the application sets on Vite carries over to these tags.
 */
final readonly class ThemeAssets
{
    public function __construct(private Vite $vite, private Application $app) {}

    public function published(Theme $theme): bool
    {
        return is_file($this->app->publicPath($theme->buildDirectory . '/manifest.json'));
    }

    /**
     * The tags, or nothing when the theme's assets are not published: an unstyled page beats a 500 on a public page,
     * and the doctor names the missing publish.
     */
    public function tags(Theme $theme): Htmlable
    {
        if (! $this->published($theme)) {
            return new HtmlString('');
        }

        $vite = clone $this->vite;
        $vite->useHotFile(sys_get_temp_dir() . '/laranail-waitlist-kit-never-hot');

        return $vite($theme->entries, $theme->buildDirectory);
    }

    public function nonce(): ?string
    {
        return $this->vite->cspNonce();
    }
}

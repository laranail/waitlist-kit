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
        return $this->published($theme) ? $this->tagsFor($theme->buildDirectory, $theme->entries) : new HtmlString('');
    }

    /**
     * Tags for any entries of a published build (the backend's own stylesheet uses the kit's build).
     *
     * @param list<string> $entries
     */
    public function tagsFor(string $buildDirectory, array $entries): Htmlable
    {
        if (! is_file($this->app->publicPath($buildDirectory . '/manifest.json'))) {
            return new HtmlString('');
        }

        $vite = clone $this->vite;
        $vite->useHotFile(sys_get_temp_dir() . '/laranail-waitlist-kit-never-hot');

        return $vite($entries, $buildDirectory);
    }

    /**
     * A classic, blocking `<script>` for one entry of a published build: for code that must run before the first paint
     * (Vite's own tags are modules, which are deferred). Empty when the build is not published.
     */
    public function classicScript(string $buildDirectory, string $entry): Htmlable
    {
        if (! is_file($this->app->publicPath($buildDirectory . '/manifest.json'))) {
            return new HtmlString('');
        }

        $vite = clone $this->vite;
        $vite->useHotFile(sys_get_temp_dir() . '/laranail-waitlist-kit-never-hot');
        $nonce = $this->nonce();

        return new HtmlString(sprintf(
            '<script src="%s"%s></script>',
            e($vite->asset($entry, $buildDirectory)),
            $nonce !== null ? ' nonce="' . e($nonce) . '"' : '',
        ));
    }

    public function nonce(): ?string
    {
        return $this->vite->cspNonce();
    }
}

# Build a theme

Ship a look as a Composer package that any install can select.

```php
// In your package's service provider:
public function register(): void
{
    $this->app->afterResolving(ThemeRegistry::class, fn (ThemeRegistry $themes) => $themes->register(new Theme(
        name: 'acme/waitlist-theme',
        label: 'Acme',
        styling: ThemeStyling::Tailwind,
        buildDirectory: 'vendor/acme-waitlist-theme',
        entries: ['resources/css/acme.css', 'resources/js/acme.js'],
        views: 'acme/waitlist-theme::',
        settings: [ThemeSetting::color('primary', '#0f766e', 'Primary colour')],
    )));

    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'acme/waitlist-theme');
}
```

Put only the views you change in `resources/views` (`blocks/hero.blade.php`, `layouts/page.blade.php`); the rest fall back
to the kit's base views. Build your assets with Vite into a directory you publish under `public/`, and commit the build so
sites need no Node. Read settings in CSS as `var(--wk-primary)`. See [Themes](../tools/themes.md).

---

[← Docs index](../../README.md#documentation)

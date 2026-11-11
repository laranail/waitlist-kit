# Switch a campaign's theme

Give one campaign a different look, or change the default for all.

```php
$campaign->update(['theme' => 'laranail/waitlist-kit:plain', 'theme_settings' => ['primary' => '#0f766e']]);
```

```dotenv
WAITLIST_KIT_THEME=laranail/waitlist-kit:plain
```

Both kit themes read the same settings, so a campaign keeps its colours when it switches. See [Themes](../tools/themes.md).

---

[← Docs index](../../README.md#documentation)

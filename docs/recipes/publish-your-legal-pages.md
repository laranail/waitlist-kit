# Publish your legal pages

Turn the seeded privacy and terms drafts into published pages.

```php
$page = Page::query()->where('slug', Page::PRIVACY)->sole();
// Replace every [bracketed] item in its blocks, have it reviewed, then:
$page->update(['status' => PageStatus::Published]);
```

The forms link to it as soon as it is published. See [Legal pages](../tools/legal-pages.md).

---

[← Docs index](../../README.md#documentation)

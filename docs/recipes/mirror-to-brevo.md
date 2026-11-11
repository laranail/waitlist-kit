# Mirror sign-ups to Brevo

Add every new subscriber to a Brevo contact list as well.

```php
$campaign->integrations()->create(['driver' => 'brevo', 'settings' => ['api_key' => $key, 'list_id' => 7]]);
```

Run a queue worker. Turn `WAITLIST_KIT_THANK_YOU` off if Brevo sends its own welcome. See [Integrations](../tools/integrations.md).

---

[← Docs index](../../README.md#documentation)

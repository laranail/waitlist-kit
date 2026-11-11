# Integrations

Two drivers send new subscribers elsewhere, each from a queued job after the sign-up commits.

| Driver | Settings | Sends |
| --- | --- | --- |
| `app` | `endpoint` (https only) | `{"email", "locale", "campaign"}` as JSON; any 2xx is a success |
| `brevo` | `api_key`, `list_id` | A Brevo contact with `updateEnabled`, so a repeat is a success |

An integration serves one campaign (`integrable`) or, with none, every campaign. Its settings are encrypted at rest.
`MirrorSubscriber` retries a failure twice (after 10 and 60 seconds), then logs it with the subscriber's id, never
the address. The driver set is closed: an unknown name throws, and the doctor reports any stored integration that
names one.

```php
$campaign->integrations()->create(['driver' => 'brevo', 'settings' => ['api_key' => $key, 'list_id' => 7]]);
```

---

[← Docs index](../../README.md#documentation)

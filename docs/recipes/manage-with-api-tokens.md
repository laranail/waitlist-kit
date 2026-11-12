# Manage with API tokens

Give a script or another service its own narrow access to the `manage/*` endpoints.

```bash
composer require laravel/sanctum
```

```php
use Simtabi\Laranail\WaitlistKit\Support\ApiAbilities;

$token = $user->createToken('weekly-report', [ApiAbilities::READ])->plainTextToken;
```

```bash
curl -H "Authorization: Bearer $TOKEN" https://example.com/waitlist/api/v1/manage/campaigns
```

The user must pass the kit's [policies](../tools/authorization.md), and the token must hold the endpoint's ability.
See [REST API](../tools/api.md).

---

[← Docs index](../../README.md#documentation)

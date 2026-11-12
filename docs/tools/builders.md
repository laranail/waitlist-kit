# Builders

`WaitlistKit` (`Simtabi\Laranail\WaitlistKit\WaitlistKit`) is the kit from application code: five fluent builders,
each ending in one call that runs an action.

```php
use Simtabi\Laranail\WaitlistKit\WaitlistKit;

public function __construct(private WaitlistKit $kit) {}

$this->kit->campaign()->named('Launch')->slug('launch')->type(CampaignType::ComingSoon)->locales('en', 'es')->live()->create();
$this->kit->signup('launch')->email('dana@example.com')->locale('es')->referredBy($code)->subscribe();
$this->kit->subscribers('launch')->confirmed()->search('example.com')->byPosition()->paginate(50);
$this->kit->invite('launch')->top(25)->as($user)->send();
$this->kit->enquiry()->from('Dana Lee', 'dana@example.com')->topic('sales')->message('...')->consented()->submit();
```

The `WaitlistKit` facade (`Simtabi\Laranail\WaitlistKit\Facades\WaitlistKit`) reaches the same methods.

| Builder | Chain | Ends with |
| --- | --- | --- |
| `campaign(?$campaign)` | `named`, `slug`, `type`, `locales`, `status`, `draft`, `live`, `closed`, `launched`, `host`, `launchesAt` | `create()`, `save()`, `delete()` |
| `signup(?$campaign)` | `to`, `email`, `locale`, `referredBy`, `attribution` | `subscribe(): SignupResult` |
| `subscribers($campaign)` | `status`, `pending`, `confirmed`, `invited`, `converted`, `search`, `referredBy`, `latest`, `oldest`, `byPosition` | `get()`, `paginate()`, `count()`, `first()` |
| `invite($campaign)` | `top` | `send(): int` |
| `enquiry()` | `from`, `company`, `topic`, `message`, `locale`, `about`, `consented` | `submit(): Enquiry` |

A campaign is a model, its id or its slug. Values pass the same rules as the forms and the backend
(`CampaignRules`, `SignupRules`, `ContactRules`) and fail with a `ValidationException`.

## Acting as someone

Every builder takes `->as($user)`. Without it a builder acts as the system (the application itself) and nothing is
checked; with it, the action checks that user against the [policies](authorization.md) and throws
`AuthorizationException` when they refuse. `->as(null)` acts as a guest. The actor is carried by every
[event](events.md).

## Layers

Builders hold no rules: they collect values and call an action. Actions (one intent, `handle()`) do the work inside a
transaction and dispatch events; services hold what several actions share (`Authorizer`, `CaptchaGuard`,
`AlertDispatcher`). The HTTP controllers, the API and the commands call the same actions, so a rule exists once.

---

[← Docs index](../../README.md#documentation)

# Getting started

Install the kit, create its tables, and add a first campaign and subscriber.

## Install and check

```bash
php artisan laranail::waitlist-kit.install --migrate
```

This publishes `config/laranail/waitlist-kit.php`, runs the migrations and ends with the doctor. Without
`--migrate`, run `php artisan migrate` yourself: the kit's migrations load from the package, so nothing needs
publishing.

## Choose the campaign mode

```dotenv
WAITLIST_KIT_CAMPAIGNS=single
```

`single` runs one campaign, `limited` up to `WAITLIST_KIT_CAMPAIGNS_MAX`, `multi` any number (the default). See
[Campaign limits](tools/campaign-limits.md).

## A first campaign

```php
$campaign = app(CreateCampaign::class)->handle('Launch list', 'launch', CampaignType::Waitlist, ['en']);
$campaign->update(['status' => CampaignStatus::Live]);

$campaign->subscribers()->create(['email' => 'dana@example.com', 'locale' => 'en']);
```

A campaign starts as a draft and takes sign-ups only while live. The same address twice on one campaign is refused by
the database; on another campaign it is another lead. See [Models](tools/models.md).

---

[← Docs index](../README.md#documentation)

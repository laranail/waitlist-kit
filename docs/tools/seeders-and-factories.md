# Seeders and factories

Three seeders and a factory for every model.

## Seeders

| Seeder | Runs | Seeds |
| --- | --- | --- |
| `WaitlistKitSeeder` | With the application's `php artisan db:seed` (registered by the provider) | One draft campaign, `waitlist`, with a hero, a form and an FAQ, when the install has none; draft `privacy` and `terms` pages when missing ([Legal pages](legal-pages.md)) |
| `WaitlistKitAdminSeeder` | From `WaitlistKitSeeder` | The first administrator, in `bundled` sign-in with `seeding.admin_email` set and no administrator yet; its password is generated and printed once |
| `WaitlistKitDemoSeeder` | Only when named | A live campaign with 25 subscribers (5 referred) and 85 funnel events |

The starter seeder is idempotent, holds no fake data and needs no Faker; `seeding.default = false` turns off its
campaign and pages, not the administrator. The administrator seeder writes no password down: it exists only in the
output of the `db:seed` run that created it. The
demo seeder needs Faker and refuses to run in production unless `seeding.demo_in_production` is true.

```bash
php artisan db:seed --class="Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitDemoSeeder"
```

## Factories

| Factory | States |
| --- | --- |
| `Campaign::factory()` | `live()`, `comingSoon()`, `locales([...])` |
| `Subscriber::factory()` | `confirmed()`, `referredBy($subscriber)` |
| `Block::factory()` | `on($campaign)` |
| `FunnelEvent::factory()` | `about($subscriber, $type)` |
| `Integration::factory()` | `forCampaign($campaign)` |
| `Enquiry::factory()` | — |
| `Page::factory()` | `published()`, `legal($slug)` |
| `Admin::factory()` | — (password generated per run) |

Subscriber factories use Faker's safe addresses (`example.com/.org/.net`), which no mailer delivers.

---

[← Docs index](../../README.md#documentation)

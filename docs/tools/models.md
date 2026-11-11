# Models

Five Eloquent models in `Simtabi\Laranail\WaitlistKit\Models`, each keyed by ULID, on the configured table prefix
and connection.

| Model | Table | Holds |
| --- | --- | --- |
| `Campaign` | `campaigns` | One page: `name`, `slug` (unique), `type`, `status`, `host`, `locales`, `theme`, `theme_settings`, `settings`, `launches_at` |
| `Subscriber` | `subscribers` | A lead: `email` (unique per campaign), `status`, `locale`, `fields`, `position`, `referral_code`, `referred_by_id`, `consent`, `attribution`; soft-deleted |
| `Block` | `blocks` | A page section, owned by a `blockable` (a campaign): `type`, `position`, `content` per locale, `visible` |
| `FunnelEvent` | `events` | One funnel step about an `eventable` (the campaign or a subscriber), with `campaign_id`: `type`, `data`, `occurred_at`; never updated |
| `Integration` | `integrations` | A driver for an `integrable` (a campaign) or, with none, every campaign: `driver`, `settings` (encrypted, hidden), `enabled` |

## Enums

| Enum | Cases |
| --- | --- |
| `CampaignType` | `waitlist`, `coming_soon`, `early_access` |
| `CampaignStatus` | `draft`, `live`, `closed`, `launched`; only `live` takes sign-ups (`Campaign::acceptsSignups()`) |
| `SubscriberStatus` | `pending`, `confirmed`, `invited`, `converted`, `unsubscribed` |
| `FunnelEventType` | `view`, `signup`, `confirm`, `share`, `referral`, `invite`, `convert`, `unsubscribe` |

## Relations and helpers

- `Campaign::subscribers()`, `blocks()` (by position), `events()`, `integrations()`.
- `Subscriber::campaign()`, `referredBy()`, `referrals()`, `events()`.
- `Block::blockable()`, `FunnelEvent::eventable()`, `Integration::integrable()`.

Every model has a factory (`Campaign::factory()->live()`, `Subscriber::factory()->confirmed()`, ...); see
[Seeders and factories](seeders-and-factories.md). Polymorphic columns store aliases; see [Morph map](morph-map.md).
- `Block::contentFor($locale, $fallback = 'en')`: the block's fields in one locale, falling back key by key.

---

[← Docs index](../../README.md#documentation)

# Models

Five Eloquent models in `Simtabi\Laranail\WaitlistKit\Models`, each keyed by ULID, on the configured table prefix
and connection.

| Model | Table | Holds |
| --- | --- | --- |
| `Campaign` | `campaigns` | One page: `name`, `slug` (unique), `type`, `status`, `host`, `locales`, `theme`, `theme_settings`, `settings`, `launches_at` |
| `Subscriber` | `subscribers` | A lead: `email` (unique per campaign), `status`, `locale`, `fields`, `position`, `referral_code`, `referred_by_id`, `consent`, `attribution`; soft-deleted |
| `Block` | `blocks` | A page section: `type`, `position`, `content` per locale, `visible` |
| `FunnelEvent` | `events` | One funnel step: `type`, `data`, `occurred_at`; never updated |
| `Integration` | `integrations` | A driver: `driver`, `settings` (encrypted, hidden), `enabled` |

## Enums

| Enum | Cases |
| --- | --- |
| `CampaignType` | `waitlist`, `coming_soon`, `early_access` |
| `CampaignStatus` | `draft`, `live`, `closed`, `launched`; only `live` takes sign-ups (`Campaign::acceptsSignups()`) |
| `SubscriberStatus` | `pending`, `confirmed`, `invited`, `converted`, `unsubscribed` |
| `FunnelEventType` | `view`, `signup`, `confirm`, `share`, `referral`, `invite`, `convert`, `unsubscribe` |

## Relations and helpers

- `Campaign::subscribers()`, `blocks()` (by position), `events()`.
- `Subscriber::campaign()`, `referredBy()`, `referrals()`.
- `Block::contentFor($locale, $fallback = 'en')`: the block's fields in one locale, falling back key by key.

---

[← Docs index](../../README.md#documentation)

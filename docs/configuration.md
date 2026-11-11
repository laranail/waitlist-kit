# Configuration

Every key in `config/laranail/waitlist-kit.php`, read as `laranail.waitlist-kit.*`, with its environment variable.

## Database

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `database.connection` | `WAITLIST_KIT_DB_CONNECTION` | the default connection | The connection the kit's tables live on |
| `database.table_prefix` | `WAITLIST_KIT_TABLE_PREFIX` | `laranail_waitlist_kit_` | Prepended to every table the kit creates |

Set both before the first migration: changing them later points the kit at tables that do not exist yet.

## Campaigns

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `campaigns.mode` | `WAITLIST_KIT_CAMPAIGNS` | `multi` | `single`, `limited` or `multi` |
| `campaigns.max` | `WAITLIST_KIT_CAMPAIGNS_MAX` | `3` | The ceiling in `limited` mode |

## Routes

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `routes.enabled` | `WAITLIST_KIT_ROUTES` | `true` | Register the public page and sign-up routes |
| `routes.prefix` | `WAITLIST_KIT_ROUTE_PREFIX` | `waitlist` | Pages live at `/{prefix}/{campaign slug}`; empty puts them at the root |
| `routes.middleware` | — | `['web']` | The group that gives the form its session and CSRF protection |

## Sign-ups

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `signups.per_minute` | `WAITLIST_KIT_SIGNUPS_PER_MINUTE` | `10` | Sign-ups per campaign per visitor per minute |
| `signups.thank_you` | `WAITLIST_KIT_THANK_YOU` | `true` | Thank a new sign-up by email, once |
| `signups.honeypot` | — | `website` | The hidden field only a script fills in |

## Security

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `security.captcha` | `WAITLIST_KIT_CAPTCHA` | `false` | Require `laranail/captcha`'s challenge on the form; the doctor fails if the package is missing |

## Seeding

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `seeding.default` | `WAITLIST_KIT_SEED_DEFAULT` | `true` | Seed a starter draft campaign with `php artisan db:seed` when there is none |
| `seeding.demo_in_production` | `WAITLIST_KIT_DEMO_IN_PRODUCTION` | `false` | Let the demo seeder run in production |

## Sign-in

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `auth.mode` | `WAITLIST_KIT_AUTH` | `host` | `host` (the application's guard) or `bundled` (the kit's own admin login) |
| `auth.guard` | `WAITLIST_KIT_AUTH_GUARD` | `web` | The guard `host` mode uses; must exist in `config/auth.php` |

The backend that uses these arrives in a later release; the doctor already checks them. Mail is signed with
`app.name`.

## Invalid values

A value outside its list is never replaced by a default: the doctor reports it, and the code that needs it throws
`InvalidConfiguration` naming the key, the value and the allowed values.

---

[← Docs index](../README.md#documentation)

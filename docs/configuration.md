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

## Themes

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `themes.default` | `WAITLIST_KIT_THEME` | `laranail/waitlist-kit:default` | The theme a campaign without its own uses; must be installed |

## Contact

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `contact.enabled` | `WAITLIST_KIT_CONTACT` | `true` | The contact endpoint and component |
| `contact.to` | `WAITLIST_KIT_CONTACT_TO` | empty | The inbox enquiries are mailed to; empty keeps them in the database only |
| `contact.topics` | — | `sales, support, partnerships, other` | The topics on offer, the first preselected |
| `contact.company` | `WAITLIST_KIT_CONTACT_COMPANY` | `optional` | `optional`, `required` or `off` |
| `contact.consent` | `WAITLIST_KIT_CONTACT_CONSENT` | `true` | Require agreeing to the privacy notice |
| `contact.per_minute` | `WAITLIST_KIT_CONTACT_PER_MINUTE` | `5` | Enquiries per visitor per minute |

## Security

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `security.captcha` | `WAITLIST_KIT_CAPTCHA` | `false` | Require `laranail/captcha`'s challenge on the form; the doctor fails if the package is missing |

## Settings and mail

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `settings.store` | `WAITLIST_KIT_SETTINGS_STORE` | `env` | `env` (this file and `.env` only; the backend's Settings screen is read-only) or `database` (`laranail/settings`; a value saved in the backend overrides the configuration) |
| `mail.from_address` | `WAITLIST_KIT_MAIL_FROM_ADDRESS` | empty | The sender of the kit's mail; empty uses the application's `mail.from` |
| `mail.from_name` | `WAITLIST_KIT_MAIL_FROM_NAME` | empty | The sender's name, with `mail.from_address` |

Twelve keys are editable in the backend when the store is `database`: `mail.from_address`, `mail.from_name`,
`signups.thank_you`, `signups.per_minute`, `contact.enabled`, `contact.to`, `contact.topics`, `contact.company`,
`contact.consent`, `contact.per_minute`, `security.captcha` and `themes.default`. A saved value wins over the
configuration everywhere the kit reads it; emptying a field removes the saved value, so the configuration applies
again. `database` without `laranail/settings` installed is reported by the doctor and falls back to `env`. See
[Edit settings in the backend](recipes/edit-settings-in-the-backend.md).

## Seeding

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `seeding.default` | `WAITLIST_KIT_SEED_DEFAULT` | `true` | Seed a starter draft campaign with `php artisan db:seed` when there is none |
| `seeding.demo_in_production` | `WAITLIST_KIT_DEMO_IN_PRODUCTION` | `false` | Let the demo seeder run in production |
| `seeding.admin_email` | `WAITLIST_KIT_SEED_ADMIN_EMAIL` | empty | In `bundled` sign-in, the first administrator `db:seed` creates when there is none (password printed once) |

## Backend

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `admin.enabled` | `WAITLIST_KIT_ADMIN` | `true` | Register the backend's routes |
| `admin.prefix` | `WAITLIST_KIT_ADMIN_PREFIX` | `waitlist-admin` | The backend lives at `/{prefix}` |
| `admin.middleware` | — | `['web']` | The group in front of the backend |
| `admin.emails` | `WAITLIST_KIT_ADMIN_EMAILS` | empty | In `host` sign-in, who the kit's default abilities let in |

## Sign-in

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `auth.mode` | `WAITLIST_KIT_AUTH` | `host` | `host` (the application's guard) or `bundled` (the kit's own admin login) |
| `auth.guard` | `WAITLIST_KIT_AUTH_GUARD` | `web` | The guard `host` mode uses; must exist in `config/auth.php` |

See [Backend](tools/backend.md#signing-in). Mail is signed with `app.name`, and sent from `mail.from_address` when set.

## Invalid values

A value outside its list is never replaced by a default: the doctor reports it, and the code that needs it throws
`InvalidConfiguration` naming the key, the value and the allowed values.

---

[← Docs index](../README.md#documentation)

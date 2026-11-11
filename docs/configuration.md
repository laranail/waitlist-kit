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

## Sign-in

| Key | Variable | Default | Meaning |
| --- | --- | --- | --- |
| `auth.mode` | `WAITLIST_KIT_AUTH` | `host` | `host` (the application's guard) or `bundled` (the kit's own admin login) |
| `auth.guard` | `WAITLIST_KIT_AUTH_GUARD` | `web` | The guard `host` mode uses; must exist in `config/auth.php` |

The backend that uses these arrives in a later release; the doctor already checks them.

## Invalid values

A value outside its list is never replaced by a default: the doctor reports it, and the code that needs it throws
`InvalidConfiguration` naming the key, the value and the allowed values.

---

[← Docs index](../README.md#documentation)

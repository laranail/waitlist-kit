# Commands

Two Artisan commands, both named `laranail::waitlist-kit.<command>`.

| Command | Does |
| --- | --- |
| `laranail::waitlist-kit.install` | Publishes the config (`--force` to overwrite), runs the migrations with `--migrate`, then runs the doctor |
| `laranail::waitlist-kit.doctor` | Checks the configuration, the database connection and every table; exits non-zero on any problem |

## What the doctor checks

- `campaigns.mode` and `auth.mode` are values the kit knows.
- `campaigns.max` is at least 1 in `limited` mode.
- In `host` sign-in, `auth.guard` exists in `config/auth.php`.
- The database answers, and all five tables exist under the configured prefix.

---

[← Docs index](../../README.md#documentation)

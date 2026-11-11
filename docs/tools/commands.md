# Commands

Four Artisan commands, all named `laranail::waitlist-kit.<command>`.

| Command | Does |
| --- | --- |
| `laranail::waitlist-kit.install` | Publishes the config (`--force` to overwrite) and the theme assets, runs the migrations with `--migrate`, then runs the doctor |
| `laranail::waitlist-kit.doctor` | Checks the configuration, the database connection and every table; exits non-zero on any problem |
| `laranail::waitlist-kit.export {campaign?} [--enquiries] [--output=]` | Writes a campaign's subscribers, or the enquiries (all, or one campaign's), as CSV; a cell starting with `= + - @` is quoted so a spreadsheet does not run it |
| `laranail::waitlist-kit.forget {email}` | Erases an address everywhere: subscriber records (soft-deleted ones included), enquiries and their funnel events; referrals it brought keep their row |

## What the doctor checks

- `campaigns.mode` and `auth.mode` are values the kit knows.
- `campaigns.max` is at least 1 in `limited` mode.
- In `host` sign-in, `auth.guard` exists in `config/auth.php`.
- The database answers, and all seven tables exist under the configured prefix.
- Every stored integration names a driver the kit has.
- With `security.captcha` on, `laranail/captcha` is installed.
- `themes.default` is installed, every installed theme's assets are published, and no campaign names a theme that is not.

---

[← Docs index](../../README.md#documentation)

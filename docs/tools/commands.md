# Commands

Four Artisan commands, all named `laranail::waitlist-kit.<command>`.

| Command | Does |
| --- | --- |
| `laranail::waitlist-kit.install` | Publishes the config (`--force` to overwrite), runs the migrations with `--migrate`, then runs the doctor |
| `laranail::waitlist-kit.doctor` | Checks the configuration, the database connection and every table; exits non-zero on any problem |
| `laranail::waitlist-kit.export {campaign} [--output=]` | Writes a campaign's subscribers as CSV; a cell starting with `= + - @` is quoted so a spreadsheet does not run it |
| `laranail::waitlist-kit.forget {email}` | Erases an address from every campaign, soft-deleted rows and funnel events included; referrals it brought keep their row |

## What the doctor checks

- `campaigns.mode` and `auth.mode` are values the kit knows.
- `campaigns.max` is at least 1 in `limited` mode.
- In `host` sign-in, `auth.guard` exists in `config/auth.php`.
- The database answers, and all five tables exist under the configured prefix.
- Every stored integration names a driver the kit has.
- With `security.captcha` on, `laranail/captcha` is installed.

---

[← Docs index](../../README.md#documentation)

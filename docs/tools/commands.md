# Commands

Six Artisan commands, all named `laranail::waitlist-kit.<command>`.

| Command | Does |
| --- | --- |
| `laranail::waitlist-kit.install` | Publishes the config (`--force` to overwrite) and the theme assets, runs the migrations with `--migrate`, then runs the doctor |
| `laranail::waitlist-kit.admin {email} [--name=]` | Creates a bundled-sign-in administrator, or resets one's password; the password is asked for, hidden and twice, and must be at least 12 characters |
| `laranail::waitlist-kit.doctor` | Checks the configuration, the database connection and every table; exits non-zero on any problem |
| `laranail::waitlist-kit.eject [--force]` | Copies the public views, controllers and routes into the application, turns the kit's public routes off in the published config, and requires `routes/waitlist-kit.php` from `routes/web.php`; refuses a second run without `--force` |
| `laranail::waitlist-kit.export {campaign?} [--enquiries] [--output=]` | Writes a campaign's subscribers, or the enquiries (all, or one campaign's), as CSV; a cell starting with `= + - @` is quoted so a spreadsheet does not run it |
| `laranail::waitlist-kit.forget {email}` | Erases an address everywhere: subscriber records (soft-deleted ones included), enquiries and their funnel events; referrals it brought keep their row |

## What the doctor checks

- `campaigns.mode` and `auth.mode` are values the kit knows.
- `campaigns.max` is at least 1 in `limited` mode.
- In `host` sign-in, `auth.guard` exists in `config/auth.php`.
- The database answers, and all eight tables exist under the configured prefix.
- Every stored integration names a driver the kit has.
- With `security.captcha` on, `laranail/captcha` is installed.
- `themes.default` is installed, every installed theme's assets are published, and no campaign names a theme that is not.

## What eject copies

| What | Where it lands | Then |
| --- | --- | --- |
| The views | `resources/views/vendor/laranail/waitlist-kit` | Laravel reads them before the package's |
| The 10 public controllers | `app/Http/Controllers/WaitlistKit`, namespace `App\Http\Controllers\WaitlistKit` | The application's code; the actions and models they call stay in the package |
| The public routes | `routes/waitlist-kit.php`, using those controllers | Required from `routes/web.php` |
| The route switch | `config/laranail/waitlist-kit.php`: `env('WAITLIST_KIT_ROUTES', false)` | The kit no longer registers its own public routes |

The backend, the mail, the models and the actions stay in the package and keep updating with it. If the published
config no longer holds `env('WAITLIST_KIT_ROUTES', true)`, eject stops before writing anything; set
`routes.enabled` to false there yourself and run it with `--force`.

---

[← Docs index](../../README.md#documentation)

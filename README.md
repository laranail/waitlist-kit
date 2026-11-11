# laranail/waitlist-kit

[![Tests](https://github.com/laranail/waitlist-kit/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/waitlist-kit/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/waitlist-kit/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/waitlist-kit/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Three badges, not four: the laranail family resolves through its git repositories, not Packagist, so there is no
registry version to show (see Install).

> Waitlists, coming-soon and early-access pages for Laravel: lead capture and conversion, a Blade + Alpine.js backend, and themes as packages.

Requires PHP `^8.5` on Laravel `^13`. Pre-release: campaigns take sign-ups on a plain page, thank each new address
once and forward it to integrations; themes and the backend arrive in the next releases.

## Install

The laranail family resolves through git VCS repositories. Add them, then require the package:

```bash
composer config repositories.waitlist-kit vcs https://github.com/laranail/waitlist-kit
composer config repositories.console vcs https://github.com/laranail/console
composer config repositories.package-tools vcs https://github.com/laranail/package-tools
composer require laranail/waitlist-kit
```

## Quick start guide and usage

### Getting started

Publish the config, run the migrations and check the install in one command:

```bash
php artisan laranail::waitlist-kit.install --migrate
```

It creates five tables under the `laranail_waitlist_kit_` prefix and ends by running the doctor, which exits
non-zero on any problem. Choose how many campaigns the install may run with `WAITLIST_KIT_CAMPAIGNS`
(`single`, `limited` or `multi`) before you create one.

### Usage

Create a campaign through the action, which holds the install to its campaign limit:

```php
use Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;

$campaign = app(CreateCampaign::class)->handle('Launch list', 'launch', CampaignType::Waitlist, ['en', 'es']);

$campaign->subscribers()->create(['email' => 'dana@example.com']);
```

Make it live and its page takes sign-ups at `/waitlist/launch`:

```php
$campaign->update(['status' => CampaignStatus::Live]);
```

Check an install at any time, for example as a deploy step:

```bash
php artisan laranail::waitlist-kit.doctor
```

[Getting started](docs/getting-started.md) walks through it; everything else is in the
[Documentation](#documentation) index.

## <a name="documentation"></a>Documentation

Hosted at [opensource.simtabi.com/documentation/laranail/waitlist-kit](https://opensource.simtabi.com/documentation/laranail/waitlist-kit/).

### Guides

- [Installation](docs/installation.md) — requirements, repositories, supported databases
- [Getting started](docs/getting-started.md) — install, migrate, a first campaign and subscriber
- [Configuration](docs/configuration.md) — every key and environment variable
- [Architecture](docs/architecture.md) — the layers, and why the kit is built this way
- [Release](docs/release.md) — versions, tags and upgrading

### Reference

- [Models](docs/tools/models.md) — campaigns, subscribers, blocks, funnel events, integrations
- [Sign-ups](docs/tools/signups.md) — routes, the request, the guards and the answer
- [Mail](docs/tools/mail.md) — the thank-you, once, in the sign-up's language
- [Integrations](docs/tools/integrations.md) — the `app` and `brevo` drivers, queued and retried
- [Morph map](docs/tools/morph-map.md) — `*able` columns and the namespaced aliases
- [Seeders and factories](docs/tools/seeders-and-factories.md) — the starter and demo seeders, a factory per model
- [Campaign limits](docs/tools/campaign-limits.md) — `single`, `limited`, `multi`, and `CreateCampaign`
- [Commands](docs/tools/commands.md) — `install`, `doctor`, `export`, `forget`

### Recipes

- [Use a separate database](docs/recipes/use-a-separate-database.md) — the kit's tables on their own connection
- [Check an install in a deploy](docs/recipes/check-an-install-in-a-deploy.md) — the doctor as a gate
- [Require a captcha](docs/recipes/require-a-captcha.md) — Turnstile on every sign-up
- [Mirror sign-ups to Brevo](docs/recipes/mirror-to-brevo.md) — a contact list kept in step
- [Export subscribers and erase an address](docs/recipes/export-and-erase.md) — CSV and erasure requests
- [Try it with demo data](docs/recipes/try-it-with-demo-data.md) — a filled local install

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities privately as [SECURITY.md](SECURITY.md) describes
(security@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).

# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- The foundation: a service provider built on `laranail/package-tools`, config at `laranail.waitlist-kit`, and
  five tables under a configurable prefix (`laranail_waitlist_kit_` by default) on a configurable connection.
- Models for campaigns, subscribers, page blocks, funnel events and integrations, keyed by ULID. An address is
  unique per campaign; subscribers are soft-deleted; an integration's settings are encrypted at rest.
- `campaigns.mode`: `single`, `limited` (up to `campaigns.max`) or `multi`, enforced by `CreateCampaign`.
- `auth.mode`: `host` or `bundled`, checked by the doctor; the backend that uses it arrives in a later release.
- `laranail::waitlist-kit.install` (publish the config, optionally migrate, then check) and
  `laranail::waitlist-kit.doctor` (configuration, database and tables; non-zero on any problem).
- Sign-ups: a page per live campaign at `/{routes.prefix}/{slug}` and `POST` to the same path, in the `web` group
  (CSRF), throttled per campaign and visitor, behind a honeypot, validated by `SignupRequest` (`SignupRules`), and
  kept once by `SubscribeToCampaign` (`createOrFirst` in a savepoint). Every outcome gets the same answer, never cached;
  a post without JavaScript returns to the campaign's own page, never to a Referer.
- A thank-you mail, queued for a new sign-up only, in its language (seven shipped); `signups.thank_you` turns it off.
- Integrations: `app` (an https endpoint) and `brevo` drivers, run by a queued, retried `MirrorSubscriber` job for the
  campaign's and the global integrations. Failures are logged by subscriber id, never by address.
- Optional server-side captcha through `laranail/captcha` (`security.captcha`); the doctor fails if it is missing.
- `laranail::waitlist-kit.export` (CSV, formula cells quoted) and `laranail::waitlist-kit.forget` (erasure across
  campaigns, with funnel events).
- `CampaignRules`, applied by `CreateCampaign`: slug pattern and uniqueness, type, and distinct, well-formed locales.
- Factories for every model, a starter seeder registered with the application's `db:seed`, and a demo seeder that
  refuses production unless `seeding.demo_in_production`.
- Polymorphic owners: blocks (`blockable`), funnel events (`eventable`) and integrations (`integrable`), stored as
  namespaced morph-map aliases (`laranail-waitlist-kit.*`).

### Changed

- The five migrations are one migration. Nothing was released, so no install has run the old ones.

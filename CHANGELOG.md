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
- `auth.mode`: `host` (the application's guard and users) or `bundled` (the kit's own administrators and login),
  checked by the doctor.
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

- Themes: `laranail/waitlist-kit:default` (Tailwind) and `laranail/waitlist-kit:plain` (plain CSS) over one set of
  base views, a `ThemeRegistry` for theme packages, per-campaign theme and settings rendered as `--wk-*` custom
  properties, and prebuilt assets published to `public/vendor/waitlist-kit` so a site needs no Node. Alpine.js on
  its CSP build answers the forms in place; every page works without it.
- A headless contact form: `<x-laranail-waitlist-kit::contact-form />` with a `data-part` contract, `POST
  /{prefix}/contact`, enquiries kept and mailed to `contact.to`, configurable topics, company and consent.
- Privacy and terms pages, seeded as drafts and served at `/{prefix}/pages/{slug}` once published.
- The backend at `/{admin.prefix}`: dashboard, campaigns and their blocks per language, subscribers (search,
  export, erase), enquiries, pages and integrations, behind four abilities (`view`, `manage`, `export`, `erase`).
- Settings: `settings.store` keeps twelve settings in `.env` (`env`, the default) or makes them editable on the
  backend's Settings screen through `laranail/settings` (`database`), where a saved value overrides the
  configuration. `mail.from_address` and `mail.from_name` set the sender of the kit's mail.
- [Accessibility](docs/accessibility.md): what the pages guarantee, how it is tested, and a screen-reader script.

- The backend is redesigned on Tabler (`@tabler/core` 1.7.1): a vertical sidebar, page headers with actions, KPI
  cards on the dashboard, tables in cards with empty states and status badges, Bootstrap-5 pagination, danger-zone
  cards and Tabler's form controls, with Tabler Icons inline. Tabler's CSS is compiled into the kit's stylesheet; its
  Bootstrap JavaScript is not shipped (the sidebar toggle is an Alpine component).
- Backend appearance: Match the system, Light or Dark, chosen in the sidebar and kept in a cookie. Light and Dark are
  rendered by the server; Match the system is applied by a tiny blocking head script, so there is no flash.
- `WaitlistKitAdminSeeder`: in `bundled` sign-in, `db:seed` creates the first administrator for
  `seeding.admin_email` when there is none, with a generated password printed once.

- Conversion ([docs](docs/tools/conversion.md)): per-campaign settings (double opt-in, referral bump and cap,
  invite URL); double opt-in with a signed 7-day link; a place and a referral code on joining; a status page with
  the place and share links; referrals that move the referrer up, capped; invites in list order; a token-protected
  conversion endpoint; a signed unsubscribe link with one-click headers in every mail; page views as events; and a
  Funnel screen with steps, rates, sign-ups by day and top sources.

### Fixed

- The block editor repeated control ids in every block's form, so labels and the error summary reached only the
  first block, and one block's rejected input and errors appeared in all of them.
- A link styled as a backend button (New campaign, New integration) had its label in the button's own colour (1:1).
- Form controls' edges in both public themes were about 1.5:1 against the page; they are now 3:1 or more.
- The countdown announced itself to screen readers every minute; it now reads the launch date instead.
- An error returned in place (JavaScript) was shown but not announced: the field is now marked invalid, described by
  its error and focused. An answer's status region is revealed before it is filled, and takes focus on success.
- After a reload with errors, the first rejected field takes focus; the consent and captcha errors are tied to
  their controls.
- The backend never showed who was signed in: it read the address with `property_exists()`, which is false for an
  Eloquent model's attributes.
- Tabler's tinted badges, alert text and danger buttons were below 4.5:1; the kit's stylesheet corrects them.
- From a virtual screen-reader pass: the contact form is a region named by its heading, footer blocks render after
  `<main>` as the page's contentinfo, the backend sidebar is no longer an unnamed complementary landmark, the
  add-block form is named, and the backend's error summary takes focus after a reload (with a script fallback where
  a browser skips `autofocus`).
- The contact form's consent checkbox sat on its own line above its label; it now sits beside it.
- The sign-up and contact forms shared one error bag and old input, so a contact error marked the sign-up form's
  email field too. Their errors are now in `$errors->signup` and `$errors->contact`.

### Changed
### Changed

- The five migrations are one migration. Nothing was released, so no install has run the old ones.

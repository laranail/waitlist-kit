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

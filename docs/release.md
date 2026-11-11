# Release

How the kit is versioned and released.

## Versions

Semantic versioning. Before `1.0.0`, a breaking change (a config key, a table or column, a command, a public
method) bumps the minor version and is listed under **Changed** in `CHANGELOG.md`.

## Cutting a release

Move `[Unreleased]` under a new version heading through a pull request, then tag the merge commit. The release
workflow publishes the GitHub release from that section, with an SBOM. A tag is final: it is never moved or reused.

## Upgrading

Run `php artisan migrate` after every update, then `php artisan laranail::waitlist-kit.doctor`.

---

[← Docs index](../README.md#documentation)

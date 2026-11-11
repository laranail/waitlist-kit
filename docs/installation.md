# Installation

What the kit needs, how it resolves, and which databases it supports.

## Requirements

| Need | Version |
| --- | --- |
| PHP | `^8.5` |
| Laravel | `^13.0` |
| Database | SQLite, MySQL 8.4 or PostgreSQL 17 (each migrated up and down in CI) |

## Repositories

The laranail family resolves through its git repositories, not Packagist. Composer ignores a dependency's own
`repositories`, so the application declares every laranail package the kit needs:

```bash
composer config repositories.waitlist-kit vcs https://github.com/laranail/waitlist-kit
composer config repositories.console vcs https://github.com/laranail/console
composer config repositories.package-tools vcs https://github.com/laranail/package-tools
composer require laranail/waitlist-kit
```

The service provider registers itself through Laravel's package discovery.

## Database

The kit's five tables start with `laranail_waitlist_kit_`, so they cannot collide with an application's own, and
can live on their own connection ([Use a separate database](recipes/use-a-separate-database.md)). Every index name is
explicit and short, so a longer prefix stays inside PostgreSQL's 63-character limit.

---

[← Docs index](../README.md#documentation)

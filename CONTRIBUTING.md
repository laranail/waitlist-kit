# Contributing

Where this file is silent, the [laranail contributing guide](https://github.com/laranail/.github/blob/HEAD/CONTRIBUTING.md) applies.

Thanks for helping. The kit is small on purpose: waitlists, coming-soon and early-access pages, and what
converts their leads. A change that turns it into a general CMS belongs elsewhere.

## Getting set up

```bash
composer install
vendor/bin/pest
```

The laranail family resolves through git VCS repositories rather than Packagist; they are declared in
`composer.json`, so a plain `composer install` works.

## Before you push

```bash
composer ci     # pint --test, phpstan (max), rector --dry-run, pest
```

`composer pint-fix` and `composer rector-fix` apply the mechanical fixes.

The schema, model, signup, command, contact, legal and admin tests also run on MySQL and PostgreSQL:

```bash
composer test-db   # vendor/bin/laranail-test-db: dbpantry's fixture, one database per run
```

It needs a dbpantry checkout (`DBPANTRY_DIR`, or `simtabi/packages/dbpantry` above this package) with
`MDB_PUBLISH=direct`. CI runs the same files against its own MySQL 8.4 and PostgreSQL 17 services.

## Conventions

- PHP `^8.5`, Laravel `^13`. `declare(strict_types=1)` and `final` in every class.
- Commands are named `laranail::waitlist-kit.<command>`; config is read as `laranail.waitlist-kit.*`.
- Actions, models and support classes take their collaborators by injection; `tests/Arch` enforces it.
- No real site's name or address anywhere in the repository; use `example.org` or a `.test` host.
- Test credentials are generated at run time, never written as literals.
- A new guard is proven red by planting its defect before it is relied on.
- No AI or assistant attribution in commits or pull requests.

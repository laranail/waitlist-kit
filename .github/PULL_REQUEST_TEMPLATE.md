## What and why

<!-- Link any issue with "Closes #123". -->

## Checklist

- [ ] `composer ci` passes locally (Pint, PHPStan max, Rector, Pest).
- [ ] New behaviour has a test, and a new guard was seen failing before it passed.
- [ ] New config keys are read somewhere in this change and documented in `docs/configuration.md`.
- [ ] Migrations run up and down; index names stay under 63 characters with the longest prefix.
- [ ] Commands follow `laranail::waitlist-kit.<command>`; no real site's name or address; no literal credentials.
- [ ] No AI/assistant attribution in commits or this PR.

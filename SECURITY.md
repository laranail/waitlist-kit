# Security policy

Where this file is silent, the [laranail security policy](https://github.com/laranail/.github/blob/HEAD/SECURITY.md) applies.

## Reporting a vulnerability

Report vulnerabilities privately to `security@simtabi.com`. Never open a public issue for a
security problem.

Include the package version, the campaign type and configuration in use, and a minimal reproduction if you
have one. You will get an acknowledgement within a few working days.

## Scope

The kit takes posts from anyone on the internet and stores personal data, so these are always in scope:

- Signing up without passing the form's guards (CSRF, rate limit, honeypot, captcha).
- Learning whether an address is on a list, or reading another subscriber's data.
- Making the kit mail an address of the attacker's choosing, or mail one address repeatedly.
- Moving a subscriber's position, referral count or status without the action that should do it.
- Reaching the backend without the configured sign-in and abilities.
- An integration's credentials reaching a log, an exception, an export or rendered output.

## Supported versions

Pre-1.0, only the latest tag receives fixes.

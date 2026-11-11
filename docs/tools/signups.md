# Sign-ups

How a sign-up travels from the form to the list: two routes, a request, an action and one answer for every outcome.

## Routes

| Method and path | Name | Does |
| --- | --- | --- |
| `GET /{prefix}/{slug}` | `laranail-waitlist-kit.campaign.show` | The campaign's page; a campaign that is not live is a 404 |
| `POST /{prefix}/{slug}` | `laranail-waitlist-kit.campaign.signup` | A sign-up, in the `web` group (session and CSRF), throttled, behind the honeypot |
| `GET /{prefix}/{slug}/confirm/{subscriber}` | `laranail-waitlist-kit.campaign.confirm` | Double opt-in confirmation; signed, expiring |
| `GET /{prefix}/{slug}/status/{code}` | `laranail-waitlist-kit.campaign.status` | A subscriber's status page |
| `GET`, `POST /{prefix}/{slug}/unsubscribe/{subscriber}` | `laranail-waitlist-kit.campaign.unsubscribe` | Leaving the list; signed |
| `POST /{prefix}/api/conversions` | `laranail-waitlist-kit.conversions.store` | The product reports a conversion ([Conversion](conversion.md)) |

## A post, in order

1. **Throttle**: `signups.per_minute` per campaign per visitor (limiter `laranail-waitlist-kit.signups`, keyed by the
   slug and `$request->ip()`, which honours the application's TrustProxies).
2. **Honeypot** (`RejectHoneypot`): a filled `website` field gets the success answer; nothing is validated or kept.
3. **Validation** (`SignupRequest`, rules in `SignupRules`): `email` (required, RFC, 254 characters), optional
   `locale`, `ref` (a referral code) and `utm_*`; plus the captcha with `security.captcha` on.
4. **Subscribe** (`SubscribeToCampaign`): `createOrFirst` in a savepoint, so a repeat address returns the existing
   row (on PostgreSQL too); only a new one gets a `signup` funnel event.
5. **After the commit**, for a new subscriber only: the thank-you mail ([Mail](mail.md)) and one queued job per
   integration ([Integrations](integrations.md)). A failure there is logged by id and never shown.

## The answer

The same for a new address, a repeat and a honeypot hit, so the form cannot tell anyone who signed up, and always
`Cache-Control: no-store`.

| Request | Answer |
| --- | --- |
| `Accept: application/json` | `201` with `{"message": "..."}` ("check your email" under double opt-in); `422` with `errors` on a bad field; `429` when throttled |
| A form without JavaScript | A redirect to the campaign's own page with a flash, or back there with errors in the `signup` bag (`$errors->signup`); never to a `Referer` |

The language kept is the form's `locale` when the campaign is built in it, else the campaign's first. The referring
host and `utm_*` values are kept in `attribution`.

---

[← Docs index](../../README.md#documentation)

# Conversion

How a sign-up becomes a place on the list, moves up through referrals, is invited, and converts: the settings, the
pages and mails a subscriber sees, the endpoint the product calls, and the funnel that counts it all.

## Settings

Each campaign keeps its conversion settings in `settings.conversion`, edited on its screen's Conversion card and read
through `Campaign::conversion()`, a `ConversionSettings` that can only be built valid.

| Setting | Default | Meaning |
| --- | --- | --- |
| `double_opt_in` | `false` | New sign-ups confirm their address before they join the list |
| `referrals` | `true` | Someone who joins through a subscriber's link moves that subscriber up |
| `referral_bump` | `1` | Places per referral, 0 to 100 |
| `referral_cap` | `10` | Most referrals credited to one subscriber, 0 to 10,000 |
| `invite_url` | none | Where an invitation sends people; an absolute `https://` address |

A stored value of the wrong type or out of range is read as its default, so one bad row never breaks a public page.

## Joining the list

| Double opt-in | At sign-up | Later |
| --- | --- | --- |
| Off | Confirmed at once: the next place, a referral code, the thank-you mail and the integrations | |
| On | Pending, and mailed a signed link that works for 7 days; the answer says to check email | Opening the link confirms them and only then sends the thank-you mail and runs the integrations |

Places are handed out under a lock on the campaign row, so two people joining at once never share one, and a
removed subscriber's place is never reused. Signing up again while pending re-sends the link, at most once every
15 minutes. The answer is the same for a new and a repeat address, so the form never tells who is on the list.

## Status page

`GET /{prefix}/{slug}/status/{code}` (`laranail-waitlist-kit.campaign.status`) shows a subscriber their place, how
many joined through them, and their referral link (`/{prefix}/{slug}?ref={code}`) with plain share links to email,
X and LinkedIn (no third-party script). It is reached only from the thank-you mail, keyed by the subscriber's
unguessable code, shows no address, and is never indexed or cached. An unknown code, another campaign's code or
someone who has left is a 404.

## Referrals

The sign-up form carries a referral link's `ref` code. When the referred person joins the list (at sign-up, or on
confirming), their referrer moves up the bump, never past first place, and everyone passed moves down one. Each
credit is a `referral` event, and the cap counts those. A referrer who has left, or isn't on the list yet, is not
credited.

## Invites and conversion

From a campaign's subscribers screen, someone with `manage` invites the next N confirmed subscribers (up to 500 at a
time), in list order. Each becomes invited, gets an `invite` event, and is mailed a link to the invite URL carrying
their code as `?invite=`. Nobody is invited twice, and pending or departed subscribers are never invited.

The product reports a conversion:

```http
POST /waitlist/api/conversions
Authorization: Bearer <WAITLIST_KIT_CONVERSION_TOKEN>
Content-Type: application/json

{"campaign": "launch", "invite": "k3m9p2x7qa"}
```

It answers `204` whether or not anything changed (so a retry is safe), `401` for a wrong token, and `404` for an
unknown code. Without `conversion.token` set, the endpoint itself answers `404`. It runs without a session, cookies
or CSRF, throttled to 60 requests a minute.

## Leaving

Every mail carries a signed unsubscribe link and the one-click headers (`List-Unsubscribe`,
`List-Unsubscribe-Post`, RFC 8058). Opening the link shows one button, so a mail scanner that opens links cannot
unsubscribe anyone; posting it (the button, or the mail client's one-click) marks the subscriber unsubscribed with an
`unsubscribe` event. They keep their row, so signing up again is a repeat, and they drop out of invites and referral
credit.

## Funnel

The Funnel screen (`/{admin.prefix}/campaigns/{campaign}/funnel`) reads the last 30 days of events: page views,
sign-ups, confirmations, referral credits, invites, conversions and departures; the rates between them; sign-ups by
day; and the top five sources (`utm_source`, else the referring host, else `direct`). Each campaign page view is
recorded, except a browser's prefetch. Counts are of events, so someone who signed up and later left still counts as
a sign-up. `FunnelReport::for($campaign)` returns the same numbers as a `FunnelSummary`.

---

[← Docs index](../../README.md#documentation)

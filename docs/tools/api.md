# REST API

21 JSON endpoints at `/{routes.prefix}/{api.prefix}` (`/waitlist/api/v1`), named `laranail-waitlist-kit.api.*`: the public
ones a headless front end needs, and `manage/*` for everything the backend does.

## Turning it on

```dotenv
WAITLIST_KIT_API=true          # or WAITLIST_KIT_HEADLESS=true, which also turns the pages and backend off
WAITLIST_KIT_API_GUARD=sanctum # any guard in config/auth.php
```

Every answer is JSON, whatever the client's `Accept` header: a validation error is a `422` with `errors`, never a
redirect. No session or CSRF is involved.

## Public endpoints

| Method and path | Name | Does |
| --- | --- | --- |
| `GET captcha` | `api.captcha` | The configured captcha: provider, the fields a token may be posted in, the widget's script and attributes, and a fresh challenge for Math and ALTCHA |
| `GET campaigns/{slug}` | `api.campaigns.show` | A published campaign: name, type, status, languages, launch date, opt-in and referral flags, search tags (`?lang=`), and its sign-up URL; a draft is a 404 |
| `POST campaigns/{slug}/subscribers` | `api.campaigns.subscribers.store` | A sign-up: the page form's request, rules, captcha and throttle; `201` with the same message for a new or repeat address |
| `GET campaigns/{slug}/status/{code}` | `api.campaigns.status` | A subscriber's place, referrals and referral link, by their code; no address; never cached |
| `POST enquiries` | `api.enquiries.store` | A contact enquiry: the contact form's request, rules, captcha and throttle; 404 while contact is off |

The two `POST` endpoints answer Precognition too (`Precognition: true`, `Precognition-Validate-Only: email`), so a
front end can validate fields as they change.

## Manage endpoints

Behind `api.guard`. Each request is checked against the kit's [policies](authorization.md) as the signed-in user,
and, with a Sanctum token, against the token's ability.

| Method and path | Policy | Token ability |
| --- | --- | --- |
| `GET manage/campaigns` | `viewAny` | `waitlist:read` |
| `POST manage/campaigns` | `create` | `waitlist:write` |
| `GET manage/campaigns/{id}` | `view` | `waitlist:read` |
| `PUT`/`PATCH manage/campaigns/{id}` | `update` | `waitlist:write` |
| `DELETE manage/campaigns/{id}` | `delete` (erase) | `waitlist:erase` |
| `GET manage/campaigns/{id}/funnel` | `view` | `waitlist:read` |
| `GET manage/campaigns/{id}/subscribers` | `viewAny` | `waitlist:read` |
| `POST manage/campaigns/{id}/subscribers` | `create` | `waitlist:write` |
| `GET manage/campaigns/{id}/subscribers/export` | `export` | `waitlist:export` |
| `GET manage/campaigns/{id}/subscribers/{id}` | `view` | `waitlist:read` |
| `DELETE manage/campaigns/{id}/subscribers/{id}` | `delete` | `waitlist:write` |
| `POST manage/campaigns/{id}/subscribers/{id}/convert` | `convert` | `waitlist:write` |
| `POST manage/campaigns/{id}/invites` | `invite` | `waitlist:write` |
| `POST manage/forget` | `erase` | `waitlist:erase` |
| `GET manage/enquiries` | `viewAny` | `waitlist:read` |
| `GET manage/enquiries/{id}` | `view` | `waitlist:read` |

Lists are paginated (`per_page`, at most 200) and take `status`, `q` and `order` (`latest`, `oldest`, `position`) for
subscribers, and `days` for the funnel. Writes go through the [builders](builders.md), so they meet the same rules,
campaign limits and events as the backend and application code. A subscriber outside the campaign in the path is a
404.

| Answer | When |
| --- | --- |
| `401` | Not signed in on the guard |
| `403` | The policy refuses, or the token lacks the ability |
| `422` | Validation |
| `429` | Over `api.per_minute` (per user on `manage/*`, per visitor on public reads), or a form's own limit |
| `503` | `api.guard` is not a guard in `config/auth.php`; the doctor reports it too |

## Sanctum

```php
$token = $user->createToken('reports', [ApiAbilities::READ])->plainTextToken;
```

`ApiAbilities` holds `waitlist:read`, `waitlist:write`, `waitlist:export` and `waitlist:erase`; `*` covers all. A
session request through Sanctum (an SPA) carries a transient token that can do anything, so only the policies apply.
`WAITLIST_KIT_API_TOKEN_ABILITIES=false` turns the token check off.

---

[← Docs index](../../README.md#documentation)

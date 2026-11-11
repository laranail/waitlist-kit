# Campaign limits

Three modes decide how many campaigns one install may run, and `CreateCampaign` enforces them.

## Modes

| `campaigns.mode` | Limit |
| --- | --- |
| `single` | One campaign |
| `limited` | Up to `campaigns.max` (at least 1; the doctor fails otherwise) |
| `multi` | No limit |

## `CreateCampaign`

`Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign::handle($name, $slug, $type, $locales = ['en'])` creates a draft
campaign, or throws `CampaignLimitReached` when the install already runs its limit. An unknown mode throws
`InvalidConfiguration` rather than choosing one.

Creating a `Campaign` model directly skips the check; the action is the supported path. Two administrators creating
the last allowed campaign at the same instant can both succeed: the limit is a policy for people, not a lock.

---

[← Docs index](../../README.md#documentation)

# Authorization

Four Gate abilities and five policies decide who may do what, for the backend, the API and the builders alike.

## Abilities

| Ability | Lets someone |
| --- | --- |
| `laranail-waitlist-kit.view` | Open the backend and read everything |
| `laranail-waitlist-kit.manage` | Change campaigns, blocks, themes, pages, integrations and subscribers |
| `laranail-waitlist-kit.export` | Download lists |
| `laranail-waitlist-kit.erase` | Remove people for good, and delete campaigns with their lists |

The kit defines an ability only when the application has not: define it yourself (`Gate::define(...)`) to use roles.
By default, bundled administrators hold all four, and in host sign-in the addresses in `admin.emails` do.

## Policies

| Model | Policy | Methods |
| --- | --- | --- |
| `Campaign` | `CampaignPolicy` | `viewAny`, `view` (view); `create`, `update` (manage); `delete` (erase) |
| `Subscriber` | `SubscriberPolicy` | `viewAny`, `view` (view); `create`, `update`, `delete`, `invite`, `convert` (manage); `export`; `erase` |
| `Enquiry` | `EnquiryPolicy` | `viewAny`, `view` (view); `delete` (erase) |
| `Integration` | `IntegrationPolicy` | `viewAny`, `view` (view); `create`, `update`, `delete` (manage) |
| `Page` | `PagePolicy` | `viewAny`, `view` (view); `create`, `update`, `delete` (manage) |

Each method asks the Gate for its ability, so redefining an ability changes every policy that uses it. A policy is
registered only when the application has none for the model (`Gate::policy(Campaign::class, YourPolicy::class)`
wins), and `WaitlistKitServiceProvider::POLICIES` lists them.

## The actor

`Actor` (`Support\Actor`) is who is acting: `Actor::from($user)`, `Actor::guest()` or `Actor::system()`. The
`Authorizer` service checks an actor against the Gate as that user, never as whoever is signed in to the current
request; the system actor is never checked. Actions take an optional trailing `?Actor`, builders take `->as($user)`,
and the API passes the signed-in user.

---

[← Docs index](../../README.md#documentation)

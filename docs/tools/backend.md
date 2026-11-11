# Backend

A Blade + Alpine.js backend at `/{admin.prefix}` (default `/waitlist-admin`) that runs the kit: campaigns and their
page blocks and theme, subscribers, enquiries, the kit's pages and the integrations.

## Signing in

| `auth.mode` | Who signs in | How |
| --- | --- | --- |
| `host` (default) | The application's users, on `auth.guard` | A guest goes to the application's `login` route (403 when it has none) |
| `bundled` | The kit's own administrators (`Admin`, guard `laranail-waitlist-kit`) | `/waitlist-admin/login`, throttled to five tries a minute per address; created with `laranail::waitlist-kit.admin` |

The bundled login answers wrong passwords and unknown addresses the same way, regenerates the session on sign-in and
invalidates it on sign-out. Its routes exist in both modes and answer 404 in `host`, so the route list never
depends on the mode.

## Abilities

| Ability | Lets someone |
| --- | --- |
| `laranail-waitlist-kit.view` | Open the backend and read everything |
| `laranail-waitlist-kit.manage` | Change campaigns, blocks, themes, pages and integrations, and remove a subscriber |
| `laranail-waitlist-kit.export` | Download a campaign's subscribers as CSV |
| `laranail-waitlist-kit.erase` | Erase an address everywhere, and delete a campaign |

The kit defines each only when the application has not. Its default lets in bundled administrators and, in `host`
mode, the addresses in `admin.emails`. Define them yourself for roles:

```php
Gate::define('laranail-waitlist-kit.view', fn (User $user) => $user->hasRole('marketing'));
Gate::define('laranail-waitlist-kit.erase', fn (User $user) => $user->hasRole('owner'));
```

## Screens

| Screen | Does |
| --- | --- |
| Dashboard | Each campaign's subscribers and sign-ups in the last 7 days; the configuration's problems |
| Campaigns | Create (held to `campaigns.mode`), edit details and status, choose the theme and its settings, edit blocks per language, delete (`erase`, slug typed back) |
| Subscribers | Search by address, filter by status, remove one, export CSV (`export`), erase an address (`erase`) |
| Enquiries | Newest first, and one in full |
| Pages | The privacy notice and terms: title per language, text blocks, publish |
| Integrations | `app` and `brevo`, per campaign or for every campaign; a stored key is never shown back and an empty field keeps it |
| Settings | The twelve editable settings: a form (`manage`) on the `database` store, otherwise read-only with the variable that sets each |

Every change goes through a form request (its ability, then the same rule classes the rest of the kit uses) and an
action. Blocks are edited one language at a time, so saving Spanish leaves English as it was.

## Accessibility

Every screen has a skip link, one `h1`, landmarks, the current section marked with `aria-current`, a label for every
control, captions and header scopes on tables, unique ids, and an error summary (`role="alert"`) linking to each
field. The block editor names each block's form apart (`b-<block id>-…`, `add-block-…`), so an error shows only in
the block that was saved and the summary links there. A test checks every screen in both sign-in modes; see
[Accessibility](../accessibility.md) for the rest and the screen-reader pass.

---

[← Docs index](../../README.md#documentation)

# Themes

A theme decides how a campaign page and the kit's pages look. The kit ships two; any package can add more.

| Theme | Styling | Views |
| --- | --- | --- |
| `laranail/waitlist-kit:default` | Tailwind 4, prebuilt | The kit's base views, styled with `@apply` |
| `laranail/waitlist-kit:plain` | Plain CSS on custom properties | The kit's base views |

A campaign uses its own `theme` when that theme is installed, else `themes.default`, else the kit's default. A theme
that was uninstalled never breaks a page: it falls back, and the doctor names the campaign.

## What a theme is

`Simtabi\Laranail\WaitlistKit\Themes\Theme`, registered in `ThemeRegistry`:

| Field | Meaning |
| --- | --- |
| `name` | `vendor/package` or `vendor/package:theme` |
| `styling` | `ThemeStyling::Tailwind` or `ThemeStyling::Css` |
| `views` | A view prefix (`acme/theme::`) whose files override the kit's base views path by path; null to use the base |
| `buildDirectory`, `entries` | Its prebuilt Vite assets under `public/`, and the entries to load from their manifest |
| `settings` | What the backend may change: `ThemeSetting::color()`, `font()`, `image()`, `text()`, `toggle()` |

## Views and fallback

A page asks its theme for each part: `layouts.page`, `campaign`, `page`, and `blocks.<type>` for the nine block
types (`hero`, `form`, `features`, `countdown`, `faq`, `social_proof`, `contact`, `text`, `footer`). The theme's
view wins when it has one; otherwise the kit's base view (`laranail/waitlist-kit::base.*`) renders it. Override one
block and inherit the rest. A test renders every block, in every installed kit theme, for every campaign type.

## Settings become CSS

Each colour, font, image and toggle becomes a `--wk-<key>` custom property in an inline `<style>`, carrying the
application's CSP nonce when Vite has one. Every value is checked by its type first: a colour must be a hex colour, a
font a plain family list, an image an https or site-relative URL with nothing that could close `url()`. Anything else
gets the setting's default. Texts are not CSS; views read them with `$theme->text('footer')`.

## Assets, without Node

Themes ship built. The kit's are in `resources/dist` (published to `public/vendor/waitlist-kit` by `install` or
`vendor:publish --tag=laranail::waitlist-kit-assets`), every file hashed under `assets/<kind>/`. The tags come from a
copy of the application's Vite, so the application's own `@vite` and dev server are untouched. A page whose theme's
assets are not published renders unstyled rather than failing, and the doctor reports it.

## Alpine.js

The kit's script uses Alpine's CSP build (no `unsafe-eval`): `wkForm` posts the sign-up, contact and early-access
forms in place (AJAX) and shows the answer in their `status` and `error` parts, marking the form `aria-busy` while it
sends; `wkCountdown` counts down to the campaign's launch. Every form works without it.

## Live validation

The sign-up and contact forms, and every form in the backend, check a field as soon as it is left after a change, and
re-check it while an error shows, so the error goes as soon as it is fixed. The check is
[Laravel Precognition](https://laravel.com/docs/precognition): the form posts to its own route with
`Precognition: true` and `Precognition-Validate-Only: <field>`, the route's FormRequest validates that field alone, and
the answer is `204` or `422` with the server's own message. Nothing is stored or sent, and the rules exist once, on
the server.

| Where | Limit |
| --- | --- |
| Sign-up | 60 checks a minute per campaign and visitor, apart from the sign-up limit, so typing never uses it up |
| Contact | 60 checks a minute per visitor, apart from the enquiry limit |
| Backend | Signed-in administrators only; the sign-in form has none, since each check would spend a sign-in attempt |

A theme styles a live error the same way as a submitted one: the field's `data-state="invalid"`, the input's
`aria-invalid`, and its `data-part="error"` element.

---

[← Docs index](../../README.md#documentation)

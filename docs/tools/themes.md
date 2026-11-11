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

The kit's script uses Alpine's CSP build (no `unsafe-eval`): `wkForm` posts the sign-up and contact forms in place
and shows the answer in their `status` and `error` parts; `wkCountdown` counts down to the campaign's launch. Both
forms work without it.

---

[← Docs index](../../README.md#documentation)

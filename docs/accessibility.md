# Accessibility

What the kit's pages and backend guarantee against WCAG 2.2 AA, how that is tested, and the screen-reader pass a
release still needs from a person.

## What is guaranteed

| Area | Guarantee |
| --- | --- |
| Structure | A `lang` on `<html>`, one `h1`, and a `main` landmark on every page; the backend adds a skip link and marks the current section with `aria-current` |
| Names | A label for every control; text on every button; captions and header scopes on backend tables |
| References | Unique ids on every page, and every label `for`, `aria-describedby` and `aria-labelledby` points at an element that exists |
| Errors without JavaScript | The rejected control carries `aria-invalid` and `aria-describedby` to its error, and the first one takes focus on reload; the backend adds a summary (`role="alert"`) linking to each field |
| Errors with JavaScript | The same attributes are set in place, and focus moves to the first rejected field, which reads its error |
| Answers | A form's answer is in a `role="status"` region, revealed before it is filled so it is announced; on success focus moves to it |
| Two forms on one page | The sign-up and contact forms keep separate error bags (`$errors->signup`, `$errors->contact`), so an error in one never marks the other |
| The block editor | Each block's form names its own ids (`b-<block id>-…`), so labels and the error summary reach the right block |
| Countdown | No live region: the ticking label is hidden from assistive technology, which reads the launch date instead |
| Honeypot | `hidden`, `aria-hidden` and `tabindex="-1"`: never reached by keyboard or read out |
| Contrast | Text at 4.5:1 and control edges at 3:1 with the default colours, in both themes and the backend |
| Dark scheme | The same corrections in dark: tinted badges lightened, primary and danger text lightened at rest; axe clean on every backend screen in both schemes |
| Backend errors | The error summary takes focus on load, so it is read out at once; each line links to its field |
| Landmarks | Public pages: main, a named region per form, and the footer as contentinfo after main. Backend: the named navigation, the page header, main, and named forms per card |
| Backend on Tabler | Tabler's tinted badges, alert text and danger buttons fell short (1.97 to 4.47:1) and are corrected in the kit's stylesheet; table links are underlined; each scrolling table is a labelled region reachable by keyboard; the sidebar toggle reports `aria-expanded` |
| Motion | Transitions off under `prefers-reduced-motion` |

A site's own theme settings (`primary`, `background`, `text`) are its own to check: the kit cannot know a brand's
colours are readable.

## How it is tested

Feature tests check the structure and references on every backend screen in both sign-in modes, and on the public
pages in both themes; they also check each error path above. A unit test computes the contrast of the public themes'
shipped colours from their tokens. Tabler's colours are oklch and are measured in a browser instead; the test pins
the Tabler release that was measured and each correction, so an upgrade fails until it is measured again. Each check was seen failing against the defect it guards.

axe-core 4.10 (WCAG 2.2 A and AA, plus best practices) was run by hand against a demo install: the campaign page
in both themes, the privacy page, the login and 12 backend screens (again after the move to Tabler), with no violations once the defects listed in the
changelog were fixed. Contrast, focus order and error announcements were checked by hand alongside it.

## The screen-reader pass

A virtual screen reader (Guidepup's Virtual Screen Reader 0.33, which voices the browser's accessibility tree the way
VoiceOver and NVDA phrase it) was run through this script on 2026-11-11: reading order, landmarks, headings, the
error paths with and without JavaScript, the success announcement, the countdown, the sidebar toggle and the
appearance switch. It found and the kit fixed: an unnamed contact form, the footer inside main, an unnamed sidebar
landmark and add-block form, and a backend error summary that was not focused after a reload. It does not voice
focus moves or test real speech timing, so a pass with VoiceOver or NVDA is still worth doing before a release.

Tests read markup; they cannot hear a page. Run this with VoiceOver (macOS: Safari, `Cmd F5`) and NVDA (Windows:
Firefox or Chrome) on a demo install ([Try it with demo data](recipes/try-it-with-demo-data.md)).

| # | Do | Expect to hear or see |
| --- | --- | --- |
| 1 | Open a campaign page and list headings (VoiceOver rotor, NVDA `H`) | One heading level 1, then each block's heading at level 2 |
| 2 | Tab from the top | Email field, privacy link, the join button, each FAQ question, then the contact form in visual order; never the hidden "website" field |
| 3 | Submit the sign-up with `not-an-email` | Focus lands on the email field and its error is read with it |
| 4 | Submit a valid address | "You're on the list…" is read, and focus is on that message |
| 5 | Stay on the countdown a minute | Nothing is announced as it ticks; reading it gives the launch date |
| 6 | Submit the contact form empty | Focus on the first rejected field with its error; the sign-up form above shows no error |
| 7 | Turn off JavaScript and repeat 3 and 6 | After the reload, focus is on the first rejected field and its error is read |
| 8 | Zoom to 200% and to a 320px-wide window | Nothing is cut off and nothing scrolls sideways |
| 9 | Backend: press `Tab` once on any screen, then `Enter` | "Skip to content", and focus moves to the main content |
| 10 | Backend: save a block with a 161-character heading | The error summary is read; its link moves to that block's heading field |
| 11 | Backend: remove a block | The confirmation dialog is read and can be cancelled |

Record the date, the screen reader and its version, and any step that did not behave as listed.

---

[← Docs index](../README.md#documentation)

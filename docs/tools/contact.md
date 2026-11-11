# Contact

A headless contact form: an endpoint and an unstyled Blade component any page of the application can use, with a
campaign or without one.

## The component

```blade
<x-laranail-waitlist-kit::contact-form />
<x-laranail-waitlist-kit::contact-form campaign="launch" return="/thanks" class="contact" />
```

It renders accessible markup with no classes and no styles, only a contract for CSS to target:
`data-scope="contact-form"` on the form, and `data-part` (`field`, `label`, `input`, `error`, `submit`, `status`),
`data-field` and `data-state="invalid"` inside. Attributes on the tag land on the form. It renders nothing while
`contact.enabled` is false, and links the consent line to the privacy notice once that page is published.

## The endpoint

`POST /{prefix}/contact` (`laranail-waitlist-kit.contact.store`), in the `web` group (CSRF), throttled per visitor
(`laranail-waitlist-kit.enquiries`), behind the honeypot.

| Field | Rule |
| --- | --- |
| `name` | Required, 2 to 120 characters |
| `email` | Required, a valid address |
| `company` | Per `contact.company`: `optional`, `required` or `off` |
| `topic` | One of `contact.topics` |
| `message` | Required, 10 to 2000 characters |
| `agree` | Accepted, while `contact.consent` is on |
| `campaign` | Optional: the slug of a live campaign the enquiry came from |
| `return` | Optional: the path to go back to without JavaScript; anything but a path on this site becomes `/` |

`Accept: application/json` gets `201` with `{"message"}` (or `422`, `429`); a form without JavaScript returns to
`return` with a flash. Every answer is `no-store`.

## What happens

`SubmitEnquiry` keeps every enquiry (an enquiry is a message, not a list entry), with a consent record naming the
privacy notice as it stood, and an `enquiry` funnel event when it came from a campaign. After the commit,
`EnquiryNotice` goes to `contact.to` with the visitor as Reply-To and the topic leading the subject; with no inbox
set, enquiries are kept only. A failed notice is logged by id.

Export with `laranail::waitlist-kit.export --enquiries`; `laranail::waitlist-kit.forget` erases an address's
enquiries with its subscriber records.

---

[← Docs index](../../README.md#documentation)

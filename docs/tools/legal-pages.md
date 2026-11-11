# Legal pages

Kit-managed pages, served at `/{prefix}/pages/{slug}`, for the privacy notice and terms the forms link to.

A `Page` has a `slug`, a `kind` (`legal` or `page`), a `status` (`draft` or `published`) and a title per locale.
Its content is its blocks, the same blocks a campaign page uses: each `text` block holds a `heading` and
`paragraphs` per locale. A draft is a 404.

## The drafts

`WaitlistKitSeeder` adds `privacy` and `terms` as **drafts** when they do not exist. They describe what the kit itself
stores (a sign-up's address, language, attribution and referrer; an enquiry's fields and consent) and leave every fact
only the site knows in `[brackets]`: who you are, your lawful basis, who else receives the data, how long you keep it.
They are not served until someone completes, reviews and publishes them. The kit does not publish legal text nobody
has read, and it does not give legal advice.

## Where they appear

- The contact form's consent line links the privacy notice once published.
- The sign-up form shows "By joining, you agree to the privacy notice" once published.
- `<x-laranail-waitlist-kit::legal-links />` lists the published legal pages, unstyled, for any footer.

Edit and publish them in the backend's Pages screen ([Backend](backend.md)).

---

[← Docs index](../../README.md#documentation)

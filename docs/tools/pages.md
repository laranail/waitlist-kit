# Pages

What a campaign's public page shows for each type and status, and how it presents itself to search engines.

## Status

| Status | The page |
| --- | --- |
| Draft | None: 404 |
| Live | The campaign's blocks and sign-up form |
| Closed | A closed notice, no form |
| Launched | A redirect to the launch URL (https), or a launched notice when there is none |

Only a live campaign takes sign-ups. Closed and launched pages are never indexed.

## Types

| Type | Adds |
| --- | --- |
| Waitlist | Nothing: blocks and the form |
| Coming soon | A countdown first when the campaign has a launch date and no countdown block; the form's button says "Notify me" |
| Early access | With an invite URL, an "I have an invite" form: an invited (or converted) subscriber's code goes on to the invite URL with `?invite=`; any other code gets the same error, ten tries a minute per visitor |

## Search and link previews

Each campaign has, on its screen's Page and search card:

| Setting | Meaning |
| --- | --- |
| Page title, description | Per language; the title falls back to the campaign name |
| Share image | An https image for link previews (Open Graph and Twitter cards) |
| Let search engines index this page | Off by default; applies only while the campaign is live |
| Launch URL | Where a launched campaign's page sends visitors |

The page writes its title, description, canonical address and Open Graph tags. `robots` is `index, follow` only for a
live campaign with indexing on, and `noindex` everywhere else. `?lang=es` shows one of the campaign's languages (any
other value falls back to the first); an indexable campaign with several languages lists each as an `hreflang`
alternate, with the first as `x-default`.

## Sitemap

`GET /{prefix}/sitemap.xml` lists the indexable live campaigns, each language with its alternates. Point a site's
`robots.txt` or sitemap index at it.

---

[← Docs index](../../README.md#documentation)

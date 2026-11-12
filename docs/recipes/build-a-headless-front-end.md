# Build a headless front end

Run the kit without its pages or backend, and drive it from your own front end and code.

```dotenv
WAITLIST_KIT_HEADLESS=true
```

The public pages and the backend are off; the [REST API](../tools/api.md) and the [builders](../tools/builders.md)
are on. Each part keeps its own switch (`WAITLIST_KIT_ROUTES`, `WAITLIST_KIT_ADMIN`, `WAITLIST_KIT_API`), and the
views stay registered, so you can render a kit view yourself.

```js
const campaign = await (await fetch('/waitlist/api/v1/campaigns/launch')).json();
await fetch(campaign.data.signup_url, { method: 'POST', body: new FormData(form) }); // 201, or 422 with errors
```

With a captcha on, `GET /waitlist/api/v1/captcha` says which provider to render and where to post its token.

---

[← Docs index](../../README.md#documentation)

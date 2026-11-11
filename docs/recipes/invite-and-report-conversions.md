# Invite people and report conversions

Open access in batches and tell the kit who actually signed up to the product.

1. On the campaign's screen, set the **Invite URL** (Conversion card) to the product's sign-up page.
2. On its subscribers screen, **Invite** the next batch. Each invitation links to the invite URL with `?invite=<code>`.
3. When someone signs up to the product with a code, report it:

```dotenv
WAITLIST_KIT_CONVERSION_TOKEN=a-long-random-secret
```

```php
Http::withToken(config('services.waitlist.token'))
    ->post('https://example.com/waitlist/api/conversions', ['campaign' => 'launch', 'invite' => $request->query('invite')]);
```

The Funnel screen then shows invitations converted. See [Conversion](../tools/conversion.md).

---

[← Docs index](../../README.md#documentation)

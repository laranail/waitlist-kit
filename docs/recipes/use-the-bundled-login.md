# Use the bundled login

Run the backend in an application that has no users of its own.

```dotenv
WAITLIST_KIT_AUTH=bundled
```

```bash
php artisan laranail::waitlist-kit.admin owner@example.org --name="Owner"
```

The command asks for the password twice, hidden; it is never an argument. Sign in at `/waitlist-admin/login`. See
[Backend](../tools/backend.md#signing-in).

---

[← Docs index](../../README.md#documentation)

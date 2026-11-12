# Use the bundled login

Run the backend in an application that has no users of its own.

```dotenv
WAITLIST_KIT_AUTH=bundled
```

```bash
php artisan laranail::waitlist-kit.admin owner@example.org --name="Owner"
```

The command asks for the password twice, hidden; it is never an argument. Or let the seeder create the first one:

```dotenv
WAITLIST_KIT_SEED_ADMIN_EMAIL=owner@example.org
```

```bash
php artisan db:seed
```

It prints a generated password once; change it with the command above. Sign in at `/admin/waitlist/login`. See
[Backend](../tools/backend.md#signing-in).

---

[← Docs index](../../README.md#documentation)

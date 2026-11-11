# Use a separate database

Keep the kit's tables on their own connection.

```dotenv
WAITLIST_KIT_DB_CONNECTION=waitlist
```

Define `waitlist` in `config/database.php`, then `php artisan migrate`: the kit's migrations and models both follow
the setting. Set it before the first migration. See [Configuration](../configuration.md).

---

[← Docs index](../../README.md#documentation)

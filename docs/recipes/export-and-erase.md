# Export subscribers and erase an address

Read a campaign's list, and honour an erasure request.

```bash
php artisan laranail::waitlist-kit.export launch --output=launch.csv
php artisan laranail::waitlist-kit.forget dana@example.com
```

Erase the address in each integration too: they keep their own copies. See [Commands](../tools/commands.md).

---

[← Docs index](../../README.md#documentation)

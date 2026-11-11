# Check an install in a deploy

Fail a deploy whose waitlist is misconfigured or unmigrated.

```bash
php artisan migrate --force
php artisan laranail::waitlist-kit.doctor
```

The doctor exits non-zero on any problem, so a deploy script stops there. See [Commands](../tools/commands.md).

---

[← Docs index](../../README.md#documentation)

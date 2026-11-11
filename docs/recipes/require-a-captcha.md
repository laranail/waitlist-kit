# Require a captcha

Put Cloudflare Turnstile (or another provider) in front of every sign-up.

```bash
composer require laranail/captcha
```

```dotenv
WAITLIST_KIT_CAPTCHA=true
```

Configure the provider and its keys as `laranail/captcha` describes; the form renders its challenge and the request
requires it. `php artisan laranail::waitlist-kit.doctor` fails if the package is missing. See [Sign-ups](../tools/signups.md).

---

[← Docs index](../../README.md#documentation)

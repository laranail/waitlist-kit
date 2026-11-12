# Require a captcha

Put any captcha `laranail/captcha` supports in front of every sign-up and contact form, page or API.

```bash
composer require laranail/captcha
```

```dotenv
WAITLIST_KIT_CAPTCHA=true
```

```dotenv
CAPTCHA_PROVIDER=turnstile   # hcaptcha, recaptcha-v2, recaptcha-v2-invisible, recaptcha-v3, recaptcha-enterprise,
                             # friendly-captcha, arkose, altcha or math
```

Configure the provider's keys as `laranail/captcha` describes; the form renders its challenge and the request requires
it. A token is accepted under `captcha` or the provider's own field (`cf-turnstile-response`, `h-captcha-response`,
`g-recaptcha-response`, ...), and is verified for the form's own action (`laranail-waitlist-kit.signup`,
`laranail-waitlist-kit.contact`), which reCAPTCHA v3 and Enterprise score. A headless front end reads the provider from
`GET /waitlist/api/v1/captcha`. `php artisan laranail::waitlist-kit.doctor` fails if the package is missing. See [Sign-ups](../tools/signups.md).

---

[← Docs index](../../README.md#documentation)

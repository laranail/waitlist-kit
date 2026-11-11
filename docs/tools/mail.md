# Mail

One mail, `ThankYouMail`, queued after the commit for a new sign-up only, in the language the sign-up was made in.

It is plain text, signed with `app.name`, with its words in `resources/lang/<locale>/mail.php` (en, es, fr, id, it,
ja, tr) under `laranail/waitlist-kit::mail.*`. Publish the translations to change them:

```bash
php artisan vendor:publish --tag=laranail::waitlist-kit-translations
```

`signups.thank_you = false` turns it off. A mail that cannot be sent or queued is logged with the subscriber's id;
the sign-up is already kept and the visitor is not told.

---

[← Docs index](../../README.md#documentation)

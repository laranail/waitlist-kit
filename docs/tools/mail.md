# Mail

Three mails, each queued after the commit, in the language the sign-up was made in, and each carrying a signed
unsubscribe link with one-click headers:

| Mail | Sent | Carries |
| --- | --- | --- |
| `ThankYouMail` | When someone joins the list (at sign-up, or on confirming under double opt-in) | A link to their status page |
| `ConfirmMail` | At sign-up under double opt-in, and again (at most every 15 minutes) if they sign up while pending | The signed confirmation link, valid 7 days |
| `InviteMail` | When a manager invites them | The campaign's invite URL with their `?invite=` code |

It is plain text, signed with `app.name`, with its words in `resources/lang/<locale>/mail.php` (en, es, fr, id, it,
ja, tr) under `laranail/waitlist-kit::mail.*`. Publish the translations to change them:

```bash
php artisan vendor:publish --tag=laranail::waitlist-kit-translations
```

`signups.thank_you = false` turns the thank-you off; the confirmation and invitation always go. A mail that cannot be sent or queued is logged with the subscriber's id;
the sign-up is already kept and the visitor is not told.

---

[← Docs index](../../README.md#documentation)

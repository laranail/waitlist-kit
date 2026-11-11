# Add a contact form

Put the kit's contact form on any page of your application.

```blade
<x-laranail-waitlist-kit::contact-form return="{{ url()->current() }}" />
```

```dotenv
WAITLIST_KIT_CONTACT_TO=support@example.org
```

Style it through its `data-part` attributes; set the topics and the company field in `config/laranail/waitlist-kit.php`.
See [Contact](../tools/contact.md).

---

[← Docs index](../../README.md#documentation)

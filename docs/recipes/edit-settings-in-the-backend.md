# Edit settings in the backend

Let the team change the sender, the rate limits, the contact inbox and the default theme without a deploy.

```bash
composer config repositories.settings vcs https://github.com/laranail/settings
composer config repositories.db-tools vcs https://github.com/laranail/db-tools
composer config repositories.enumerator vcs https://github.com/laranail/enumerator
composer require laranail/settings
php artisan migrate
```

The kit already brings `laranail/console` and `laranail/package-tools`; see
[Installation](../installation.md#repositories) for why each repository is declared.

```dotenv
WAITLIST_KIT_SETTINGS_STORE=database
```

The Settings screen at `/{admin.prefix}/settings` becomes a form for anyone with the `manage` ability. A saved value
overrides the configuration; emptying a field returns it to the configuration. Leave the store at `env` to keep
every setting in `.env`, with the screen showing the values and the variable that sets each. See
[Configuration](../configuration.md#settings-and-mail).

---

[← Docs index](../../README.md#documentation)

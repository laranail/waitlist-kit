<?php

declare(strict_types=1);

/*
 * laranail/waitlist-kit. Published to config/laranail/waitlist-kit.php and read as `laranail.waitlist-kit.*`.
 * docs/configuration.md describes every key. Every value here is serialisable, so `config:cache` works.
 */

return [

    'database' => [
        // The connection the kit's tables live on. Null is the application's default connection.
        'connection' => env('WAITLIST_KIT_DB_CONNECTION'),

        // Every table the kit creates starts with this, so it cannot collide with an application's own.
        'table_prefix' => env('WAITLIST_KIT_TABLE_PREFIX', 'laranail_waitlist_kit_'),
    ],

    'campaigns' => [
        // How many campaigns (waitlist, coming-soon or early-access pages) this install may run:
        // `single` (one), `limited` (up to `max`) or `multi` (no limit). The schema always allows many.
        'mode' => env('WAITLIST_KIT_CAMPAIGNS', 'multi'),

        // The ceiling for `limited`. Ignored by the other modes.
        'max' => (int) env('WAITLIST_KIT_CAMPAIGNS_MAX', 3),
    ],

    'routes' => [
        // Register the public pages and the sign-up endpoint. Off for an application that routes them itself.
        'enabled' => (bool) env('WAITLIST_KIT_ROUTES', true),

        // Pages live at /{prefix}/{campaign slug}. An empty prefix puts them at the root.
        'prefix' => env('WAITLIST_KIT_ROUTE_PREFIX', 'waitlist'),

        // The `web` group gives the form its session and CSRF protection.
        'middleware' => ['web'],
    ],

    'signups' => [
        // Sign-ups per campaign per visitor per minute. The visitor's address is Laravel's `$request->ip()`, so
        // behind a proxy it is right once the application's TrustProxies trusts that proxy.
        'per_minute' => (int) env('WAITLIST_KIT_SIGNUPS_PER_MINUTE', 10),

        // Thank a new sign-up by email, once, in their language. Off when a service sends its own welcome.
        'thank_you' => (bool) env('WAITLIST_KIT_THANK_YOU', true),

        // A form field people never see; a post that fills it is told it worked and nothing is kept.
        'honeypot' => 'website',
    ],

    'themes' => [
        // The theme a campaign without its own uses: `laranail/waitlist-kit:default` (Tailwind 4),
        // `laranail/waitlist-kit:plain` (plain CSS), or any installed theme package's name.
        'default' => env('WAITLIST_KIT_THEME', 'laranail/waitlist-kit:default'),
    ],

    'contact' => [
        // The contact form: an endpoint at POST /{routes.prefix}/contact and an unstyled Blade component
        // (<x-laranail-waitlist-kit::contact-form />) any page of the application can use.
        'enabled' => (bool) env('WAITLIST_KIT_CONTACT', true),

        // The inbox enquiries are mailed to, with the visitor as Reply-To. Empty keeps them in the database only.
        'to' => env('WAITLIST_KIT_CONTACT_TO'),

        // The topics the form offers. The first is preselected.
        'topics' => ['sales', 'support', 'partnerships', 'other'],

        // The company field: `optional`, `required` or `off`.
        'company' => env('WAITLIST_KIT_CONTACT_COMPANY', 'optional'),

        // Require agreeing to the privacy notice (the `privacy` page) before sending.
        'consent' => (bool) env('WAITLIST_KIT_CONTACT_CONSENT', true),

        // Enquiries per visitor per minute.
        'per_minute' => (int) env('WAITLIST_KIT_CONTACT_PER_MINUTE', 5),
    ],

    'security' => [
        // Require laranail/captcha's challenge (Turnstile and others) on the sign-up form. Needs that package.
        'captcha' => (bool) env('WAITLIST_KIT_CAPTCHA', false),
    ],

    'seeding' => [
        // Seed a starter draft campaign with `php artisan db:seed` when the install has none.
        'default' => (bool) env('WAITLIST_KIT_SEED_DEFAULT', true),

        // Let the demo seeder run in production. It refuses there otherwise.
        'demo_in_production' => (bool) env('WAITLIST_KIT_DEMO_IN_PRODUCTION', false),
    ],

    'admin' => [
        // The backend: campaigns, page blocks, themes, subscribers, enquiries, pages and integrations.
        'enabled' => (bool) env('WAITLIST_KIT_ADMIN', true),

        // It lives at /{prefix}.
        'prefix' => env('WAITLIST_KIT_ADMIN_PREFIX', 'waitlist-admin'),

        'middleware' => ['web'],

        // In `host` sign-in, who the kit's default abilities let in, by email. Define the abilities yourself
        // (Gate::define('laranail-waitlist-kit.view', ...)) to use roles instead; the kit never overrides yours.
        'emails' => array_values(array_filter(array_map(trim(...), explode(',', (string) env('WAITLIST_KIT_ADMIN_EMAILS', ''))))),
    ],

    'auth' => [
        // How the backend signs people in: `host` (the application's own guard and Gate abilities) or
        // `bundled` (the kit's own admin login and admin table, for an application without users).
        'mode' => env('WAITLIST_KIT_AUTH', 'host'),

        // The guard `host` mode authenticates against.
        'guard' => env('WAITLIST_KIT_AUTH_GUARD', 'web'),
    ],

];

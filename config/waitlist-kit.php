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

    'auth' => [
        // How the backend signs people in: `host` (the application's own guard and Gate abilities) or
        // `bundled` (the kit's own admin login and admin table, for an application without users).
        'mode' => env('WAITLIST_KIT_AUTH', 'host'),

        // The guard `host` mode authenticates against.
        'guard' => env('WAITLIST_KIT_AUTH_GUARD', 'web'),
    ],

];

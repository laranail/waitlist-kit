<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/**
 * The backend's colour scheme, chosen per browser and kept in a cookie. `light` and `dark` are rendered by the server
 * as Tabler's `data-bs-theme`, so the first paint is already right; `system` follows the operating system through
 * a small blocking script in the page's head (admin-theme.js), before anything is painted.
 */
enum AdminAppearance: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public const string COOKIE = 'laranail_waitlist_kit_appearance';

    /** A year: a colour scheme is a standing preference, not a session's. */
    public const int COOKIE_MINUTES = 525_600;

    /** The cookie's value, or `system` for anything else (no cookie, a stale or tampered value). */
    public static function fromCookie(mixed $value): self
    {
        return is_string($value) ? self::tryFrom($value) ?? self::System : self::System;
    }

    /** What the server renders as `data-bs-theme`; `system` starts light and the head script corrects it at once. */
    public function initialTheme(): string
    {
        return $this === self::Dark ? 'dark' : 'light';
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Settings;

use Illuminate\Validation\Rule;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;

/**
 * The settings the backend may change, each with its type, its rules and the .env variable that sets it otherwise.
 * One list, read by the Settings screen, its request and KitConfig, so they cannot disagree. Everything else (routes,
 * database, sign-in mode) stays deploy-time only: changing those at runtime would break routing or sign-in.
 */
final class EditableSettings
{
    /** @return array<string, array{type: 'text'|'email'|'number'|'toggle'|'list'|'select', env: string}> */
    public static function all(): array
    {
        return [
            'mail.from_address'  => ['type' => 'email', 'env' => 'WAITLIST_KIT_MAIL_FROM_ADDRESS'],
            'mail.from_name'     => ['type' => 'text', 'env' => 'WAITLIST_KIT_MAIL_FROM_NAME'],
            'signups.thank_you'  => ['type' => 'toggle', 'env' => 'WAITLIST_KIT_THANK_YOU'],
            'signups.per_minute' => ['type' => 'number', 'env' => 'WAITLIST_KIT_SIGNUPS_PER_MINUTE'],
            'contact.enabled'    => ['type' => 'toggle', 'env' => 'WAITLIST_KIT_CONTACT'],
            'contact.to'         => ['type' => 'email', 'env' => 'WAITLIST_KIT_CONTACT_TO'],
            'contact.topics'     => ['type' => 'list', 'env' => '(config file)'],
            'contact.company'    => ['type' => 'select', 'env' => 'WAITLIST_KIT_CONTACT_COMPANY'],
            'contact.consent'    => ['type' => 'toggle', 'env' => 'WAITLIST_KIT_CONTACT_CONSENT'],
            'contact.per_minute' => ['type' => 'number', 'env' => 'WAITLIST_KIT_CONTACT_PER_MINUTE'],
            'security.captcha'   => ['type' => 'toggle', 'env' => 'WAITLIST_KIT_CAPTCHA'],
            'themes.default'     => ['type' => 'select', 'env' => 'WAITLIST_KIT_THEME'],
        ];
    }

    /** @return array<string, list<mixed>> keyed as the form posts them (`signups.per_minute`) */
    public static function rules(ThemeRegistry $themes): array
    {
        return [
            'mail.from_address'  => ['nullable', 'email', 'max:254'],
            'mail.from_name'     => ['nullable', 'string', 'max:120'],
            'signups.thank_you'  => ['boolean'],
            'signups.per_minute' => ['required', 'integer', 'min:1', 'max:1000'],
            'contact.enabled'    => ['boolean'],
            'contact.to'         => ['nullable', 'email', 'max:254'],
            'contact.topics'     => ['required', 'string', 'max:500', 'regex:/^[a-z0-9-]+(\s*,\s*[a-z0-9-]+)*$/'],
            'contact.company'    => ['required', Rule::in(['optional', 'required', 'off'])],
            'contact.consent'    => ['boolean'],
            'contact.per_minute' => ['required', 'integer', 'min:1', 'max:1000'],
            'security.captcha'   => ['boolean'],
            'themes.default'     => ['required', Rule::in(array_keys($themes->all()))],
        ];
    }

    /** A posted value turned into what is stored: toggles as booleans, numbers as integers, lists as arrays. */
    public static function cast(string $key, mixed $value): mixed
    {
        return match (self::all()[$key]['type'] ?? 'text') {
            'toggle' => in_array($value, [true, 1, '1', 'true', 'on'], true),
            'number' => is_numeric($value) ? (int) $value : null,
            'list'   => array_values(array_filter(array_map(trim(...), explode(',', is_string($value) ? $value : '')), static fn (string $t): bool => $t !== '')),
            default  => is_string($value) && trim($value) !== '' ? trim($value) : null,
        };
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Contracts\Config\Repository;
use Simtabi\Laranail\WaitlistKit\Enums\AuthMode;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignsMode;
use Simtabi\Laranail\WaitlistKit\Settings\SettingsStore;
use Simtabi\Laranail\WaitlistKit\Settings\EditableSettings;
use Simtabi\Laranail\WaitlistKit\Exceptions\InvalidConfiguration;

/**
 * `laranail.waitlist-kit.*`, read by type. An invalid value is reported by problems() and refused by the reader
 * that needs it, so a typo in the environment fails loudly instead of choosing a default nobody picked.
 */
final readonly class KitConfig
{
    public const string KEY = 'laranail.waitlist-kit';

    /** The guard (and its user provider) the kit registers for `bundled` sign-in. */
    public const string BUNDLED_GUARD = 'laranail-waitlist-kit';

    public function __construct(private Repository $config, private ?SettingsStore $overrides = null) {}

    /** The raw value for the Settings screen. */
    public function editable(string $key): mixed
    {
        return $this->value($key);
    }

    public function settingsStore(): string
    {
        $value = $this->config->get(self::KEY . '.settings.store');

        return is_string($value) ? $value : 'env';
    }

    public function mailFromAddress(): ?string
    {
        $value = $this->value('mail.from_address');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** The kit's own sender, or null to use the application's mail.from. */
    public function sender(): ?Address
    {
        $address = $this->mailFromAddress();

        return $address === null ? null : new Address($address, $this->mailFromName());
    }

    public function mailFromName(): ?string
    {
        $value = $this->value('mail.from_name');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function connection(): ?string
    {
        $value = $this->config->get(self::KEY . '.database.connection');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function tablePrefix(): string
    {
        $value = $this->config->get(self::KEY . '.database.table_prefix');

        return is_string($value) ? $value : 'laranail_waitlist_kit_';
    }

    public function campaignsMode(): CampaignsMode
    {
        return CampaignsMode::tryFrom($this->string('campaigns.mode'))
            ?? throw new InvalidConfiguration('campaigns.mode', $this->string('campaigns.mode'), CampaignsMode::cases());
    }

    public function campaignsMax(): int
    {
        $value = $this->config->get(self::KEY . '.campaigns.max');

        return is_int($value) ? $value : 0;
    }

    /** The most campaigns this install may hold, or null for no limit. */
    public function campaignLimit(): ?int
    {
        return match ($this->campaignsMode()) {
            CampaignsMode::Single  => 1,
            CampaignsMode::Limited => $this->campaignsMax(),
            CampaignsMode::Multi   => null,
        };
    }

    public function signupsPerMinute(): int
    {
        $value = $this->value('signups.per_minute');

        return is_int($value) && $value > 0 ? $value : 10;
    }

    public function thanksNewSignups(): bool
    {
        return $this->value('signups.thank_you') === true;
    }

    public function honeypotField(): string
    {
        $value = $this->config->get(self::KEY . '.signups.honeypot');

        return is_string($value) && $value !== '' ? $value : 'website';
    }

    public function requiresCaptcha(): bool
    {
        return $this->value('security.captcha') === true;
    }

    /** The name sign-up mail is signed with: the application's. */
    public function siteName(): string
    {
        $value = $this->config->get('app.name');

        return is_string($value) && $value !== '' ? $value : 'Laravel';
    }

    public function defaultTheme(): string
    {
        $value = $this->value('themes.default');

        return is_string($value) && $value !== '' ? $value : 'laranail/waitlist-kit:default';
    }

    public function contactEnabled(): bool
    {
        return $this->value('contact.enabled') === true;
    }

    /** The inbox enquiries are mailed to, or null to keep them in the database only. */
    public function contactInbox(): ?string
    {
        $value = $this->value('contact.to');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return list<string> */
    public function contactTopics(): array
    {
        $value = $this->value('contact.topics');
        $topics = is_array($value) ? array_values(array_filter($value, is_string(...))) : [];

        return $topics !== [] ? $topics : ['other'];
    }

    /** `optional`, `required` or `off`. */
    public function contactCompany(): string
    {
        $value = $this->value('contact.company');

        return in_array($value, ['optional', 'required', 'off'], true) ? $value : 'optional';
    }

    public function contactRequiresConsent(): bool
    {
        return $this->value('contact.consent') === true;
    }

    public function enquiriesPerMinute(): int
    {
        $value = $this->value('contact.per_minute');

        return is_int($value) && $value > 0 ? $value : 5;
    }

    public function seedsDefaultCampaign(): bool
    {
        return $this->config->get(self::KEY . '.seeding.default') === true;
    }

    public function seedsDemoInProduction(): bool
    {
        return $this->config->get(self::KEY . '.seeding.demo_in_production') === true;
    }

    public function authMode(): AuthMode
    {
        return AuthMode::tryFrom($this->string('auth.mode'))
            ?? throw new InvalidConfiguration('auth.mode', $this->string('auth.mode'), AuthMode::cases());
    }

    public function adminEnabled(): bool
    {
        return $this->config->get(self::KEY . '.admin.enabled') === true;
    }

    /** @return list<string> lower-cased */
    public function adminEmails(): array
    {
        $value = $this->config->get(self::KEY . '.admin.emails');

        return is_array($value) ? array_values(array_map(static fn (mixed $e): string => is_string($e) ? mb_strtolower($e) : '', $value)) : [];
    }

    /** The guard the backend authenticates against: the host's in `host` mode, the kit's own in `bundled`. */
    public function adminGuard(): string
    {
        return $this->authMode() === AuthMode::Bundled ? self::BUNDLED_GUARD : $this->authGuard();
    }

    public function authGuard(): string
    {
        return $this->string('auth.guard');
    }

    /**
     * Everything wrong with the configuration, in words, for the doctor.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];

        if (CampaignsMode::tryFrom($this->string('campaigns.mode')) === null) {
            $problems[] = new InvalidConfiguration('campaigns.mode', $this->string('campaigns.mode'), CampaignsMode::cases())->getMessage();
        } elseif ($this->campaignsMode() === CampaignsMode::Limited && $this->campaignsMax() < 1) {
            $problems[] = 'campaigns.max must be at least 1 when campaigns.mode is limited.';
        }

        if (AuthMode::tryFrom($this->string('auth.mode')) === null) {
            $problems[] = new InvalidConfiguration('auth.mode', $this->string('auth.mode'), AuthMode::cases())->getMessage();
        } elseif ($this->authMode() === AuthMode::Host && ! is_array($this->config->get('auth.guards.' . $this->authGuard()))) {
            $problems[] = sprintf('auth.guard names "%s", which is not a guard in config/auth.php.', $this->authGuard());
        }

        return $problems;
    }

    /**
     * A kit setting: the backend's saved override when the settings store keeps one, else the configuration's value.
     * Every reader below goes through this, so an override applies everywhere at once.
     */
    private function value(string $key): mixed
    {
        if ($this->overrides instanceof SettingsStore && array_key_exists($key, EditableSettings::all())) {
            $override = $this->overrides->override($key);
            if ($override !== null) {
                return $override;
            }
        }

        return $this->config->get(self::KEY . '.' . $key);
    }

    private function string(string $key): string
    {
        $value = $this->config->get(self::KEY . '.' . $key);

        return is_string($value) ? $value : '';
    }
}

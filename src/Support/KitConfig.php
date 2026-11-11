<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use Illuminate\Contracts\Config\Repository;
use Simtabi\Laranail\WaitlistKit\Enums\AuthMode;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignsMode;
use Simtabi\Laranail\WaitlistKit\Exceptions\InvalidConfiguration;

/**
 * `laranail.waitlist-kit.*`, read by type. An invalid value is reported by problems() and refused by the reader
 * that needs it, so a typo in the environment fails loudly instead of choosing a default nobody picked.
 */
final readonly class KitConfig
{
    public const string KEY = 'laranail.waitlist-kit';

    public function __construct(private Repository $config) {}

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

    public function authMode(): AuthMode
    {
        return AuthMode::tryFrom($this->string('auth.mode'))
            ?? throw new InvalidConfiguration('auth.mode', $this->string('auth.mode'), AuthMode::cases());
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

    private function string(string $key): string
    {
        $value = $this->config->get(self::KEY . '.' . $key);

        return is_string($value) ? $value : '';
    }
}

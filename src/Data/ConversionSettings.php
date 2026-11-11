<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Data;

use InvalidArgumentException;

/**
 * How a campaign turns sign-ups into its list: whether an address must confirm first (double opt-in), and whether
 * referrals move a subscriber up, by how many places each and by how many at most. Kept in `campaigns.settings`
 * under `conversion`; built only valid, so a stored value outside its range can never reach the ranking.
 */
final readonly class ConversionSettings
{
    public const int MAX_BUMP = 100;

    public const int MAX_CAP = 10_000;

    public function __construct(
        public bool $doubleOptIn = false,
        public bool $referrals = true,
        public int $referralBump = 1,
        public int $referralCap = 10,
    ) {
        if ($referralBump < 0 || $referralBump > self::MAX_BUMP) {
            throw new InvalidArgumentException(sprintf('A referral moves a subscriber up 0 to %d places, not %d.', self::MAX_BUMP, $referralBump));
        }
        if ($referralCap < 0 || $referralCap > self::MAX_CAP) {
            throw new InvalidArgumentException(sprintf('The referral cap is 0 to %d, not %d.', self::MAX_CAP, $referralCap));
        }
    }

    /**
     * From a campaign's stored settings. A missing key takes its default; a stored value of the wrong type or out of
     * range takes its default too, rather than failing a public page over one bad row.
     *
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $defaults = new self;
        $int = static fn (string $key, int $default, int $max): int => is_int($stored[$key] ?? null) && $stored[$key] >= 0 && $stored[$key] <= $max ? $stored[$key] : $default;

        return new self(
            doubleOptIn: is_bool($stored['double_opt_in'] ?? null) ? $stored['double_opt_in'] : $defaults->doubleOptIn,
            referrals: is_bool($stored['referrals'] ?? null) ? $stored['referrals'] : $defaults->referrals,
            referralBump: $int('referral_bump', $defaults->referralBump, self::MAX_BUMP),
            referralCap: $int('referral_cap', $defaults->referralCap, self::MAX_CAP),
        );
    }

    /** @return array{double_opt_in: bool, referrals: bool, referral_bump: int, referral_cap: int} */
    public function toArray(): array
    {
        return [
            'double_opt_in' => $this->doubleOptIn,
            'referrals'     => $this->referrals,
            'referral_bump' => $this->referralBump,
            'referral_cap'  => $this->referralCap,
        ];
    }
}

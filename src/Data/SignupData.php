<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Data;

/**
 * A validated sign-up: what SignupRequest hands SubscribeToCampaign, so the action never reads a raw request.
 */
final readonly class SignupData
{
    /** @param  array<string, string>  $attribution  utm_* values and the referring host */
    public function __construct(
        public string $email,
        public string $locale,
        public array $attribution = [],
        public ?string $referralCode = null,
    ) {}
}

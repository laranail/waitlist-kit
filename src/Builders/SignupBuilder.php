<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Builders;

use LogicException;
use Simtabi\Laranail\WaitlistKit\Data\SignupData;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Data\SignupResult;
use Simtabi\Laranail\WaitlistKit\Support\CampaignLookup;
use Simtabi\Laranail\WaitlistKit\Validation\SignupRules;
use Simtabi\Laranail\WaitlistKit\Builders\Concerns\ActsAs;
use Simtabi\Laranail\WaitlistKit\Actions\SubscribeToCampaign;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

/**
 * A sign-up from code:
 *
 *     $kit->signup('launch')->email('dana@example.com')->locale('es')->referredBy($code)->subscribe();
 *
 * The values pass the same rules as the public form (SignupRules), and a language the campaign is not built in falls
 * back to its first, as on the page. The action does the rest: once per address, opt-in, welcome, mirrors, events.
 */
final class SignupBuilder
{
    use ActsAs;

    private ?Campaign $campaign = null;

    private ?string $email = null;

    private ?string $locale = null;

    private ?string $referralCode = null;

    /** @var array<string, string> */
    private array $attribution = [];

    public function __construct(
        private readonly SubscribeToCampaign $subscribe,
        private readonly ValidationFactory $validator,
    ) {}

    public function to(Campaign|string $campaign): self
    {
        $this->campaign = CampaignLookup::resolve($campaign);

        return $this;
    }

    public function email(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /** The referral code of the subscriber who brought this one (their `?ref=`). */
    public function referredBy(?string $code): self
    {
        $this->referralCode = $code;

        return $this;
    }

    /**
     * UTM values and the referring host, as the public form records them.
     *
     * @param array<string, string> $attribution
     */
    public function attribution(array $attribution): self
    {
        $this->attribution = array_merge($this->attribution, $attribution);

        return $this;
    }

    public function subscribe(): SignupResult
    {
        $campaign = $this->campaign ?? throw new LogicException('Name the campaign first: ->to($campaign).');

        $this->validator->make(array_filter([
            'email'  => $this->email,
            'locale' => $this->locale,
            'ref'    => $this->referralCode,
            ...$this->attribution,
        ], static fn (mixed $value): bool => $value !== null), SignupRules::rules())->validate();

        $locale = $this->locale !== null && in_array($this->locale, $campaign->locales, true) ? $this->locale : ($campaign->locales[0] ?? 'en');

        return $this->subscribe->handle($campaign, new SignupData(
            email: (string) $this->email,
            locale: $locale,
            attribution: $this->attribution,
            referralCode: $this->referralCode,
        ), $this->actor());
    }
}

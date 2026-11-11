<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests;

use Override;
use LogicException;
use Illuminate\Foundation\Http\FormRequest;
use Simtabi\Laranail\WaitlistKit\Data\SignupData;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Validation\SignupRules;

/**
 * A sign-up post, validated at the edge. The language is kept only when the campaign is built in it; anything else
 * falls back to the campaign's first language. A failed post without JavaScript goes back to the campaign's own page,
 * never to a Referer, so the redirect cannot be pointed at another site.
 */
final class SignupRequest extends FormRequest
{
    /**
     * Its own error bag: a page can hold the sign-up and the contact form at once, and both have an `email` field,
     * so a shared bag marked (and focused) the wrong form's field. Read it as `$errors->signup`.
     */
    #[Override]
    protected $errorBag = 'signup';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(KitConfig $config): array
    {
        $rules = SignupRules::rules();

        if ($config->requiresCaptcha()) {
            $rules['captcha'] = ['required', 'laranail_captcha'];
        }

        return $rules;
    }

    public function toData(): SignupData
    {
        $campaign = $this->campaign();
        $locale = $this->string('locale')->toString();

        $attribution = [];
        foreach (SignupRules::UTM as $key) {
            if ($this->filled($key)) {
                $attribution[$key] = $this->string($key)->toString();
            }
        }

        $referrer = parse_url((string) $this->headers->get('referer'), PHP_URL_HOST);
        if (is_string($referrer) && $referrer !== $this->getHost()) {
            $attribution['referrer'] = $referrer;
        }

        return new SignupData(
            email: mb_strtolower(trim($this->string('email')->toString())),
            locale: in_array($locale, $campaign->locales, true) ? $locale : ($campaign->locales[0] ?? 'en'),
            attribution: $attribution,
            referralCode: $this->filled('ref') ? $this->string('ref')->toString() : null,
        );
    }

    public function campaign(): Campaign
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof Campaign ? $campaign : throw new LogicException('The sign-up route binds a campaign.');
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->redirector->getUrlGenerator()->route('laranail-waitlist-kit.campaign.show', $this->campaign());
    }
}

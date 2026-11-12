<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Tests\Fixtures\Captcha;

use Simtabi\Laranail\Captcha\Enums\Provider;
use Simtabi\Laranail\Captcha\ValueObjects\Widget;
use Simtabi\Laranail\Captcha\Contracts\CaptchaAdapter;
use Simtabi\Laranail\Captcha\ValueObjects\VerificationResult;
use Simtabi\Laranail\Captcha\ValueObjects\VerificationContext;

/**
 * A captcha adapter that says it is a real provider and passes (or fails) every token, recording each. laranail/captcha's
 * own fake reports the null provider, so the rule would look for the null provider's field: this one lets a test post
 * a token under Turnstile's, hCaptcha's or reCAPTCHA's own field name and see it reach the provider.
 */
final class ProviderFake implements CaptchaAdapter
{
    /** @var list<array{token: string, action: ?string}> */
    public array $attempts = [];

    public function __construct(private readonly Provider $provider, private readonly bool $passes = true) {}

    public function verify(string $token, VerificationContext $context): VerificationResult
    {
        $this->attempts[] = ['token' => $token, 'action' => $context->action];

        return $this->passes
            ? VerificationResult::passed(score: 0.9, action: $context->action)
            : VerificationResult::failed([]);
    }

    public function widget(string $instanceId): Widget
    {
        return new Widget(provider: $this->provider, instanceId: $instanceId, containerClass: 'fake-' . $this->provider->value);
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function provider(): Provider
    {
        return $this->provider;
    }
}

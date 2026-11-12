<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Services;

use Simtabi\Laranail\Captcha\Rules\Captcha;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\Captcha\Services\CaptchaService;
use Simtabi\Laranail\Captcha\Contracts\ChallengePayload;

/**
 * The kit's captcha, through laranail/captcha, for whichever provider it is configured with: Turnstile, hCaptcha,
 * reCAPTCHA (v2, v2 invisible, v3, Enterprise), Friendly Captcha, Arkose, ALTCHA or Math.
 *
 * The rule is laranail/captcha's own, bound to an action per form (reCAPTCHA v3 and Enterprise score per action). It
 * is implicit and reads the token from `captcha` or the provider's own field (`cf-turnstile-response`,
 * `h-captcha-response`, `g-recaptcha-response`, ...), or Math's challenge and answer, so no separate `required` rule
 * sits in front of it: that one only looked at `captcha` and refused a token posted under the provider's own name.
 *
 * laranail/captcha is optional; nothing here touches its classes unless security.captcha is on and it is installed.
 */
final readonly class CaptchaGuard
{
    public const string SIGNUP = 'laranail-waitlist-kit.signup';

    public const string CONTACT = 'laranail-waitlist-kit.contact';

    public function __construct(private KitConfig $config, private Container $container) {}

    public function installed(): bool
    {
        return class_exists(CaptchaService::class);
    }

    /** Whether a form must pass the captcha. */
    public function required(): bool
    {
        return $this->config->requiresCaptcha();
    }

    /**
     * The rules for the `captcha` field of one form (SIGNUP or CONTACT); none while the captcha is off. With the
     * captcha on and the package missing, every post fails (`laranail_captcha` is then an unknown rule), as the doctor
     * warns: failing closed beats taking sign-ups the install meant to guard.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(string $action): array
    {
        if (! $this->required()) {
            return [];
        }

        return ['captcha' => [$this->installed() ? Captcha::for($action) : 'laranail_captcha']];
    }

    /**
     * What a headless front end needs to show the configured provider: its name, the fields it may post, the script
     * and attributes of its widget, and, for a self-hosted challenge (Math, ALTCHA), a fresh challenge.
     *
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        if (! $this->required() || ! $this->installed()) {
            return ['enabled' => $this->required(), 'installed' => $this->installed()];
        }

        $captcha = $this->container->make(CaptchaService::class);
        $provider = $captcha->provider();
        $widget = $captcha->widget();
        $challenge = $captcha->issueChallenge();

        return [
            'enabled'         => true,
            'installed'       => true,
            'provider'        => $provider->value,
            'configured'      => $captcha->isConfigured(),
            'response_fields' => ['captcha', $provider->vendorResponseField()],
            'widget'          => [
                'script_url'      => $widget->scriptUrl,
                'container_class' => $widget->containerClass,
                'attributes'      => $widget->attributes(),
                'callback'        => $widget->callbackName,
            ],
            'challenge' => $challenge instanceof ChallengePayload ? $challenge->toArray() : null,
        ];
    }
}

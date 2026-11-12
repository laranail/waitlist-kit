<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\Captcha\Enums\Provider;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\Captcha\Services\CaptchaService;
use Simtabi\Laranail\WaitlistKit\Services\CaptchaGuard;
use Simtabi\Laranail\Package\Tools\Testing\TestCredentials;
use Simtabi\Laranail\WaitlistKit\Tests\Fixtures\Captcha\ProviderFake;

beforeEach(function (): void {
    Mail::fake();
    config()->set('laranail.waitlist-kit.security.captcha', true);
});

/** laranail/captcha set to one provider, its verification replaced by a fake that still says which provider it is. */
function captchaFor(Provider $provider, bool $passes = true): ProviderFake
{
    config()->set('laranail.captcha.provider', $provider->value);
    app()->forgetInstance(CaptchaService::class);
    $fake = new ProviderFake($provider, $passes);
    new ReflectionProperty(CaptchaService::class, 'adapter')->setValue(app(CaptchaService::class), $fake);

    return $fake;
}

it('takes every provider\'s token under the provider\'s own field, on the page and the API', function (Provider $provider): void {
    $captcha = captchaFor($provider);
    liveCampaign();
    $field = $provider->vendorResponseField();
    [$page, $api] = [TestCredentials::secret('page'), TestCredentials::secret('api')];
    expect($page)->not->toBe($api);

    $this->postJson('/waitlist/launch', ['email' => 'dana@example.com', $field => $page])->assertCreated();
    $this->postJson('/waitlist/api/v1/campaigns/launch/subscribers', ['email' => 'kai@example.com', $field => $api])->assertCreated();

    expect(array_column($captcha->attempts, 'token'))->toBe([$page, $api])
        ->and(array_unique(array_column($captcha->attempts, 'action')))->toBe([CaptchaGuard::SIGNUP])
        ->and(Subscriber::query()->count())->toBe(2);
})->with(array_values(array_filter(Provider::cases(), static fn (Provider $p): bool => $p !== Provider::NullProvider)));

it('refuses a sign-up with no token, whatever the provider', function (Provider $provider): void {
    captchaFor($provider);
    liveCampaign();

    $this->postJson('/waitlist/launch', ['email' => 'dana@example.com'])->assertStatus(422)->assertJsonValidationErrors(['captcha']);
    expect(Subscriber::query()->count())->toBe(0);
})->with([Provider::Turnstile, Provider::ReCaptchaV3, Provider::Math]);

it('refuses a token the provider rejects', function (): void {
    captchaFor(Provider::HCaptcha, passes: false);
    liveCampaign();

    $this->postJson('/waitlist/launch', ['email' => 'dana@example.com', 'h-captcha-response' => TestCredentials::secret()])->assertStatus(422)->assertJsonValidationErrors(['captcha']);
});

it('binds the contact form to its own action', function (): void {
    $captcha = captchaFor(Provider::ReCaptchaV3);
    config()->set('laranail.waitlist-kit.contact.consent', false);

    $this->postJson('/waitlist/contact', ['name' => 'Dana Lee', 'email' => 'dana@example.com', 'topic' => 'sales', 'message' => 'Do you have a plan for teams?', 'g-recaptcha-response' => TestCredentials::secret('contact')])
        ->assertCreated();
    expect(array_column($captcha->attempts, 'action'))->toBe([CaptchaGuard::CONTACT]);
});

it('describes the configured provider to a headless front end', function (Provider $provider): void {
    captchaFor($provider);

    $this->getJson('/waitlist/api/v1/captcha')->assertOk()
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.provider', $provider->value)
        ->assertJsonPath('data.response_fields', ['captcha', $provider->vendorResponseField()]);
})->with([Provider::Turnstile, Provider::Altcha, Provider::Math]);

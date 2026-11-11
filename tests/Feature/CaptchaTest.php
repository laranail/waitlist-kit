<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;

it('requires the captcha challenge when security.captcha is on', function (): void {
    Mail::fake();
    config()->set('laranail.waitlist-kit.security.captcha', true);
    Campaign::factory()->live()->create(['slug' => 'launch']);

    $this->postJson('/waitlist/launch', ['email' => 'dana@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('captcha');

    expect(Subscriber::query()->count())->toBe(0);
});

it('renders the challenge on the page when security.captcha is on', function (): void {
    config()->set('laranail.waitlist-kit.security.captcha', true);
    Campaign::factory()->live()->create(['slug' => 'launch']);

    $this->get('/waitlist/launch')->assertOk()->assertSee('data-captcha-config', false);
});

it('passes the doctor with the captcha package loaded', function (): void {
    config()->set('laranail.waitlist-kit.security.captcha', true);

    $this->artisan('laranail::waitlist-kit.doctor')->assertSuccessful();
});

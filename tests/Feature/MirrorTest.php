<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Integrations\Drivers;
use Simtabi\Laranail\WaitlistKit\Jobs\MirrorSubscriber;
use Simtabi\Laranail\WaitlistKit\Integrations\BrevoDriver;
use Simtabi\Laranail\WaitlistKit\Exceptions\IntegrationFailed;
use Simtabi\Laranail\WaitlistKit\Exceptions\UnknownIntegrationDriver;

beforeEach(fn () => Mail::fake());

it('sends a new subscriber to the campaign\'s integrations and the global ones', function (): void {
    Http::fake(['*' => Http::response(null, 201)]);
    $campaign = liveCampaign();
    $brevoKey = bin2hex(random_bytes(16));
    Integration::factory()->create(['settings' => ['endpoint' => 'https://app.example.org/api/waitlist']]);
    Integration::factory()->forCampaign($campaign)->create(['driver' => 'brevo', 'settings' => ['api_key' => $brevoKey, 'list_id' => 7]]);
    Integration::factory()->forCampaign(liveCampaign('other'))->create(['settings' => ['endpoint' => 'https://other.example.org/']]);
    Integration::factory()->create(['enabled' => false, 'settings' => ['endpoint' => 'https://off.example.org/']]);

    test()->postJson('/waitlist/launch', ['email' => 'dana@example.com', 'locale' => 'en'])->assertCreated();
    test()->postJson('/waitlist/launch', ['email' => 'dana@example.com'])->assertCreated();

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $r): bool => $r->url() === 'https://app.example.org/api/waitlist'
        && $r['email'] === 'dana@example.com' && $r['campaign'] === 'launch');
    Http::assertSent(fn (Request $r): bool => $r->url() === BrevoDriver::ENDPOINT
        && $r->header('api-key') === [$brevoKey]
        && $r->data() === ['email' => 'dana@example.com', 'listIds' => [7], 'updateEnabled' => true, 'attributes' => ['LOCALE' => 'en']]);
});

it('keeps the sign-up when an integration fails, and logs no address', function (): void {
    Http::fake(['*' => Http::response(null, 500)]);
    Log::spy();
    Integration::factory()->create();
    liveCampaign();

    test()->postJson('/waitlist/launch', ['email' => 'dana@example.com'])->assertCreated();

    expect(Subscriber::query()->count())->toBe(1);
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => ! str_contains((string) json_encode($context), 'dana@example.com') && str_contains($context['reason'], 'answered 500'));
});

it('refuses an application endpoint that is not https', function (): void {
    $subscriber = Subscriber::factory()->create();

    expect(fn () => app(Drivers::class)->get('app')->push($subscriber, ['endpoint' => 'http://app.example.org/']))
        ->toThrow(IntegrationFailed::class, 'https://');
});

it('fails loudly on a driver it does not have', function (): void {
    expect(fn () => app(Drivers::class)->get('mailchimp'))
        ->toThrow(UnknownIntegrationDriver::class, 'the kit has: app, brevo');
});

it('logs the job\'s final failure by id, never the address', function (): void {
    Http::fake(['*' => Http::response(null, 503)]);
    Log::spy();
    $subscriber = Subscriber::factory()->create(['email' => 'dana@example.com']);
    $integration = Integration::factory()->create();

    $job = new MirrorSubscriber($subscriber->id, $integration->id);
    $job->tries = 1; // outside a queue attempts() is 1, so this is the final attempt
    app()->call($job->handle(...));

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context['subscriber'] === $subscriber->id
        && ! str_contains((string) json_encode($context), 'dana@example.com'));
});

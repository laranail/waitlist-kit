<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Simtabi\Laranail\WaitlistKit\Data\SignupData;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Mail\ThankYouMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Exceptions\CampaignNotOpen;
use Simtabi\Laranail\WaitlistKit\Actions\SubscribeToCampaign;

beforeEach(fn () => Mail::fake());

function signUp(array $fields, array $headers = ['Accept' => 'application/json'], string $slug = 'launch'): TestResponse
{
    return test()->post('/waitlist/' . $slug, $fields, $headers);
}

it('keeps a sign-up once and gives a repeat the same answer', function (): void {
    liveCampaign();

    $first = signUp(['email' => 'Dana@Example.com']);
    $repeat = signUp(['email' => ' dana@example.com ']);

    $first->assertCreated();
    expect($repeat->status())->toBe(201)
        ->and($repeat->json())->toBe($first->json())
        ->and(Subscriber::query()->sole()->email)->toBe('dana@example.com')
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Signup)->count())->toBe(1);
});

it('thanks only a new sign-up', function (): void {
    liveCampaign();

    signUp(['email' => 'dana@example.com']);
    signUp(['email' => 'dana@example.com']);

    Mail::assertQueued(ThankYouMail::class, 1);
});

it('refuses an invalid address with its field', function (): void {
    liveCampaign();

    signUp(['email' => 'nope'])->assertUnprocessable()->assertJsonValidationErrors('email');
    expect(Subscriber::query()->count())->toBe(0);
});

it('tells a filled honeypot it worked, and keeps nothing', function (): void {
    liveCampaign();

    signUp(['email' => 'bot@example.com', 'website' => 'spam'])->assertCreated();
    signUp(['email' => 'not-even-valid', 'website' => 'spam'])->assertCreated();

    expect(Subscriber::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('limits sign-ups per campaign per visitor', function (): void {
    config()->set('laranail.waitlist-kit.signups.per_minute', 2);
    liveCampaign();
    liveCampaign('other');

    signUp(['email' => 'a@example.com'])->assertCreated();
    signUp(['email' => 'b@example.com'])->assertCreated();
    signUp(['email' => 'c@example.com'])->assertTooManyRequests();

    // Another campaign has its own limit.
    signUp(['email' => 'c@example.com'], slug: 'other')->assertCreated();
});

it('sends a post without JavaScript back to its own page, never to a Referer', function (): void {
    $campaign = liveCampaign();
    $page = route('laranail-waitlist-kit.campaign.show', $campaign);

    test()->post('/waitlist/launch', ['email' => 'dana@example.com'], ['Referer' => 'https://evil.example/x'])
        ->assertRedirect($page)
        ->assertSessionHas('laranail/waitlist-kit.signed-up', true);

    test()->post('/waitlist/launch', ['email' => 'nope'], ['Referer' => 'https://evil.example/x'])
        ->assertRedirect($page)
        ->assertSessionHasErrors('email');
});

it('never lets an answer to a post be cached', function (): void {
    liveCampaign();

    expect(signUp(['email' => 'dana@example.com'])->headers->get('Cache-Control'))->toContain('no-store');
    expect(signUp(['email' => 'bot@example.com', 'website' => 'x'])->headers->get('Cache-Control'))->toContain('no-store');
});

it('takes no sign-ups for a campaign that is not live', function (): void {
    Campaign::factory()->create(['slug' => 'draft']);

    signUp(['email' => 'dana@example.com'], slug: 'draft')->assertNotFound();
    signUp(['email' => 'dana@example.com'], slug: 'nowhere')->assertNotFound();
    expect(Subscriber::query()->count())->toBe(0);
});

it('keeps the page language only when the campaign is built in it', function (): void {
    liveCampaign(locales: ['en', 'es']);

    signUp(['email' => 'es@example.com', 'locale' => 'es']);
    signUp(['email' => 'ja@example.com', 'locale' => 'ja']);
    signUp(['email' => 'bad@example.com', 'locale' => '../../x']);

    expect(Subscriber::query()->where('email', 'es@example.com')->value('locale'))->toBe('es')
        ->and(Subscriber::query()->where('email', 'ja@example.com')->value('locale'))->toBe('en');
    expect(Subscriber::query()->where('email', 'bad@example.com')->exists())->toBeFalse();
});

it('records where a sign-up came from and who referred it', function (): void {
    $campaign = liveCampaign();
    $referrer = Subscriber::factory()->create(['campaign_id' => $campaign->id, 'referral_code' => 'DANA1']);

    test()->post('/waitlist/launch?x=1', ['email' => 'kai@example.com', 'ref' => 'DANA1', 'utm_source' => 'newsletter'], [
        'Accept'  => 'application/json',
        'Referer' => 'https://news.example.org/issue-4',
    ])->assertCreated();

    $kai = Subscriber::query()->where('email', 'kai@example.com')->sole();
    expect($kai->referred_by_id)->toBe($referrer->id)
        ->and($kai->attribution)->toBe(['utm_source' => 'newsletter', 'referrer' => 'news.example.org']);
});

it('refuses, in the action itself, a campaign that is not live', function (): void {
    $draft = Campaign::factory()->create();

    expect(fn () => app(SubscribeToCampaign::class)
        ->handle($draft, new SignupData('dana@example.com', 'en')))
        ->toThrow(CampaignNotOpen::class);
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

beforeEach(fn () => Mail::fake());

/** The headers the forms' script sends to check one field. */
function precognition(string $field): array
{
    return ['Accept' => 'application/json', 'Precognition' => 'true', 'Precognition-Validate-Only' => $field];
}

it('checks one sign-up field and stores nothing', function (): void {
    liveCampaign();

    $this->post('/waitlist/launch', ['email' => 'not-an-address'], precognition('email'))
        ->assertStatus(422)
        ->assertHeader('Precognition', 'true')
        ->assertJsonValidationErrors(['email']);

    $this->post('/waitlist/launch', ['email' => 'dana@example.com'], precognition('email'))
        ->assertNoContent()
        ->assertHeader('Precognition', 'true');

    expect(Subscriber::query()->count())->toBe(0);
});

it('gives live checks their own limit, so typing never uses up the sign-ups', function (): void {
    config()->set('laranail.waitlist-kit.signups.per_minute', 2);
    liveCampaign();

    foreach (range(1, 20) as $attempt) {
        $this->post('/waitlist/launch', ['email' => "not-an-address-{$attempt}"], precognition('email'))->assertStatus(422);
    }

    $this->postJson('/waitlist/launch', ['email' => 'dana@example.com'])->assertCreated();
    $this->postJson('/waitlist/launch', ['email' => 'kai@example.com'])->assertCreated();
    $this->postJson('/waitlist/launch', ['email' => 'lee@example.com'])->assertTooManyRequests();
});

it('limits the live checks too', function (): void {
    liveCampaign();

    foreach (range(1, 60) as $attempt) {
        $this->post('/waitlist/launch', ['email' => 'dana@example.com'], precognition('email'))->assertNoContent();
    }
    $this->post('/waitlist/launch', ['email' => 'dana@example.com'], precognition('email'))->assertTooManyRequests();
});

it('checks one contact field and sends nothing', function (): void {
    config()->set('laranail.waitlist-kit.contact.to', 'support@example.org');

    $this->post('/waitlist/contact', ['message' => 'short'], precognition('message'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message'])
        ->assertJsonMissingValidationErrors(['email', 'name']);

    expect(Enquiry::query()->count())->toBe(0);
});

it('checks backend fields for a signed-in administrator only', function (): void {
    $this->post('/admin/waitlist/campaigns', ['slug' => 'Has Spaces'], precognition('slug'))->assertStatus(403);

    signInAdmin();
    $this->post('/admin/waitlist/campaigns', ['slug' => 'Has Spaces'], precognition('slug'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['slug'])
        ->assertJsonMissingValidationErrors(['name']);
    $this->post('/admin/waitlist/campaigns', ['slug' => 'launch-two'], precognition('slug'))->assertNoContent();

    expect(Campaign::query()->count())->toBe(0);
});

it('answers the early-access form as JSON: the same error for any wrong code, or where to go', function (): void {
    $campaign = liveCampaign();
    $campaign->update(['type' => CampaignType::EarlyAccess, 'settings' => ['conversion' => ['invite_url' => 'https://app.example.com/join']]]);
    $invited = Subscriber::factory()->for($campaign)->create(['status' => SubscriberStatus::Invited]);

    $this->postJson('/waitlist/launch/access', ['code' => 'nosuchcode'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
    $this->postJson('/waitlist/launch/access', ['code' => $invited->referral_code])
        ->assertOk()
        ->assertExactJson(['redirect' => 'https://app.example.com/join?invite=' . $invited->referral_code]);
});

it('wires every public form and the backend to the live checks', function (): void {
    $campaign = liveCampaign();
    $campaign->update(['type' => CampaignType::EarlyAccess, 'settings' => ['conversion' => ['invite_url' => 'https://app.example.com/join']]]);

    $html = (string) $this->get('/waitlist/launch')->assertOk()->getContent();
    expect($html)->toContain('x-data="wkForm" x-on:submit="submit" x-on:focusout="left" x-on:input="edited" x-on:change="edited"')
        ->and(substr_count($html, 'x-data="wkForm"'))->toBe(2); // sign-up and early access

    signInAdmin();
    $admin = (string) $this->get('/admin/waitlist/campaigns/create')->assertOk()->getContent();
    expect($admin)->toContain('x-data="wkLive" x-on:focusout="left"')
        ->toContain('data-part="error" data-for="slug"');
});

it('never runs live checks against the sign-in, which would spend its attempts', function (): void {
    config()->set('laranail.waitlist-kit.auth.mode', 'bundled');

    // Without Precognition the header is ignored: this is an ordinary (failed) sign-in attempt, not a 204.
    $this->post('/admin/waitlist/login', ['email' => 'nope'], precognition('email'))
        ->assertHeaderMissing('Precognition');
});

it('checks a field of a form that saves with PUT, as the browser sends it (_method)', function (): void {
    signInAdmin();
    $campaign = liveCampaign();

    $this->post("/admin/waitlist/campaigns/{$campaign->id}/seo", ['_method' => 'PUT', 'locale' => 'en', 'image' => 'http://insecure.example/x.png'], precognition('image'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
    $this->post("/admin/waitlist/campaigns/{$campaign->id}/seo", ['_method' => 'PUT', 'locale' => 'en', 'image' => 'https://cdn.example.com/x.png'], precognition('image'))
        ->assertNoContent();

    expect($campaign->fresh()?->seo()->image)->toBeNull();
});

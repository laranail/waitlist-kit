<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Support\ApiAbilities;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Tests\Fixtures\Auth\ApiUser;

beforeEach(function (): void {
    Mail::fake();
    config()->set('laranail.waitlist-kit.admin.emails', ['owner@example.org']);
});

const API = '/waitlist/api/v1';

/** A user the kit's default abilities admit, signed in through Sanctum with a token holding these abilities. */
function apiOwner(array $abilities = ['*']): ApiUser
{
    $user = new ApiUser(['id' => 1, 'email' => 'owner@example.org']);
    Sanctum::actingAs($user, $abilities);

    return $user;
}

it('describes a published campaign to anyone, and hides a draft', function (): void {
    $campaign = liveCampaign(locales: ['en', 'es']);

    $this->get(API . '/campaigns/launch?lang=es')->assertOk()
        ->assertJsonPath('data.slug', 'launch')
        ->assertJsonPath('data.locale', 'es')
        ->assertJsonPath('data.signup_url', url(API . '/campaigns/launch/subscribers'))
        ->assertJsonMissingPath('data.id');

    $campaign->update(['status' => CampaignStatus::Draft]);
    $this->get(API . '/campaigns/launch')->assertNotFound()->assertHeader('Content-Type', 'application/json');
});

it('signs up through the API with the same answer for new and repeat addresses', function (): void {
    liveCampaign();

    $first = $this->post(API . '/campaigns/launch/subscribers', ['email' => 'dana@example.com'])->assertCreated();
    $repeat = $this->post(API . '/campaigns/launch/subscribers', ['email' => 'dana@example.com'])->assertCreated();

    expect($repeat->json())->toBe($first->json())
        ->and(Subscriber::query()->count())->toBe(1);

    // No Accept header: still JSON, never a redirect.
    $this->post(API . '/campaigns/launch/subscribers', ['email' => 'nope'])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('shows a subscriber their place by code, and nothing else', function (): void {
    $campaign = liveCampaign();
    $dana = Subscriber::factory()->for($campaign)->create(['status' => SubscriberStatus::Confirmed, 'position' => 4]);

    $this->get(API . "/campaigns/launch/status/{$dana->referral_code}")->assertOk()
        ->assertJsonPath('data.position', 4)
        ->assertJsonMissingPath('data.email')
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->get(API . '/campaigns/launch/status/nosuchcode')->assertNotFound();
});

it('takes an enquiry through the API', function (): void {
    config()->set('laranail.waitlist-kit.contact.to', 'support@example.org');
    config()->set('laranail.waitlist-kit.contact.consent', false);

    $this->post(API . '/enquiries', ['name' => 'Dana Lee', 'email' => 'dana@example.com', 'topic' => 'sales', 'message' => 'Do you have a plan for teams?'])
        ->assertCreated();
    expect(Enquiry::query()->count())->toBe(1);
});

it('keeps manage endpoints for signed-in users, answering 401 as JSON otherwise', function (): void {
    $this->get(API . '/manage/campaigns')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
});

it('says so when the API guard is not configured, instead of failing', function (): void {
    config()->set('laranail.waitlist-kit.api.guard', 'nowhere');

    $this->get(API . '/manage/campaigns')->assertStatus(503)->assertJsonPath('message', 'The waitlist API guard "nowhere" is not configured.');
});

it('refuses a signed-in user the policies do not admit', function (): void {
    Sanctum::actingAs(new ApiUser(['id' => 2, 'email' => 'someone@example.org']), ['*']);

    $this->get(API . '/manage/campaigns')->assertForbidden();
});

it('manages campaigns: create, list, read, change, funnel, delete', function (): void {
    apiOwner();

    $id = $this->postJson(API . '/manage/campaigns', ['name' => 'Launch', 'slug' => 'launch', 'type' => 'coming_soon', 'locales' => 'en,es', 'status' => 'live'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'live')
        ->assertJsonPath('data.locales', ['en', 'es'])
        ->json('data.id');

    $this->postJson(API . '/manage/campaigns', ['name' => 'Bad', 'slug' => 'Has Spaces', 'type' => 'party'])
        ->assertStatus(422)->assertJsonValidationErrors(['slug', 'type']);

    $this->getJson(API . '/manage/campaigns')->assertOk()->assertJsonPath('meta.total', 1);
    $this->getJson(API . "/manage/campaigns/{$id}")->assertOk()->assertJsonPath('data.slug', 'launch');
    $this->patchJson(API . "/manage/campaigns/{$id}", ['name' => 'Launch list'])->assertOk()->assertJsonPath('data.name', 'Launch list')->assertJsonPath('data.status', 'live');
    $this->getJson(API . "/manage/campaigns/{$id}/funnel?days=7")->assertOk()->assertJsonPath('data.days', 7);
    $this->deleteJson(API . "/manage/campaigns/{$id}")->assertNoContent();

    expect(Campaign::query()->count())->toBe(0);
});

it('manages subscribers: add, list, read, convert, invite, export, remove, forget', function (): void {
    apiOwner();
    $campaign = liveCampaign();
    $campaign->update(['settings' => ['conversion' => ['invite_url' => 'https://app.example.com/join']]]);
    $base = API . "/manage/campaigns/{$campaign->id}";

    $dana = $this->postJson("{$base}/subscribers", ['email' => 'Dana@Example.com'])->assertCreated()->assertJsonPath('data.status', 'confirmed')->json('data.id');
    $this->postJson("{$base}/subscribers", ['email' => 'dana@example.com'])->assertOk();
    $this->postJson("{$base}/subscribers", ['email' => 'kai@example.com'])->assertCreated();

    $this->getJson("{$base}/subscribers?order=position&per_page=1")->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.email', 'dana@example.com');
    $this->getJson("{$base}/subscribers?status=nonsense")->assertStatus(422);
    $this->getJson("{$base}/subscribers/{$dana}")->assertOk()->assertJsonPath('data.email', 'dana@example.com');

    $this->postJson("{$base}/invites", ['count' => 1])->assertOk()->assertJsonPath('data.invited', 1);
    $this->postJson("{$base}/subscribers/{$dana}/convert")->assertOk()->assertJsonPath('data.status', 'converted');

    $csv = $this->get("{$base}/subscribers/export")->assertOk()->streamedContent();
    expect($csv)->toContain('kai@example.com');

    $kai = Subscriber::query()->where('email', 'kai@example.com')->sole();
    $this->deleteJson("{$base}/subscribers/{$kai->id}")->assertNoContent();
    $this->postJson(API . '/manage/forget', ['email' => 'dana@example.com'])->assertOk()->assertJsonPath('data.subscribers', 1);
});

it('keeps a subscriber to its own campaign', function (): void {
    apiOwner();
    $launch = liveCampaign();
    $other = liveCampaign('other');
    $elsewhere = Subscriber::factory()->for($other)->create();

    $this->getJson(API . "/manage/campaigns/{$launch->id}/subscribers/{$elsewhere->id}")->assertNotFound();
});

it('holds a Sanctum token to its abilities as well as its user to the policies', function (): void {
    $campaign = liveCampaign();
    apiOwner([ApiAbilities::READ]);

    $this->getJson(API . '/manage/campaigns')->assertOk();
    $this->postJson(API . '/manage/campaigns', ['name' => 'X', 'slug' => 'x'])->assertForbidden()->assertJsonPath('message', 'This token lacks the waitlist:write ability.');
    $this->get(API . "/manage/campaigns/{$campaign->id}/subscribers/export")->assertForbidden();
    $this->postJson(API . '/manage/forget', ['email' => 'dana@example.com'])->assertForbidden();

    config()->set('laranail.waitlist-kit.api.token_abilities', false);
    $this->postJson(API . '/manage/campaigns', ['name' => 'X', 'slug' => 'x'])->assertCreated();
});

it('rate-limits the manage endpoints per user', function (): void {
    config()->set('laranail.waitlist-kit.api.per_minute', 2);
    apiOwner();

    $this->getJson(API . '/manage/enquiries')->assertOk();
    $this->getJson(API . '/manage/enquiries')->assertOk();
    $this->getJson(API . '/manage/enquiries')->assertTooManyRequests();
});

it('describes the captcha to a headless front end', function (): void {
    $this->getJson(API . '/captcha')->assertOk()->assertJsonPath('data.enabled', false);
});

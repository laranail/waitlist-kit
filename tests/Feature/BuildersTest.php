<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Collection;
use Simtabi\Laranail\WaitlistKit\WaitlistKit;
use Illuminate\Validation\ValidationException;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Illuminate\Auth\Access\AuthorizationException;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Events\CampaignCreated;
use Simtabi\Laranail\WaitlistKit\Events\CampaignUpdated;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberSignedUp;
use Simtabi\Laranail\WaitlistKit\Facades\WaitlistKit as Kit;

beforeEach(fn () => Mail::fake());

function kit(): WaitlistKit
{
    return app(WaitlistKit::class);
}

/** An application user the kit's default abilities admit (admin.emails), and one they do not. */
function owner(): GenericUser
{
    config()->set('laranail.waitlist-kit.admin.emails', ['owner@example.org']);

    return new GenericUser(['id' => 1, 'email' => 'owner@example.org']);
}

function stranger(): GenericUser
{
    return new GenericUser(['id' => 2, 'email' => 'someone@example.org']);
}

it('creates a campaign fluently, with a status, as the system', function (): void {
    $campaign = kit()->campaign()->named('Launch')->slug('launch')->type(CampaignType::ComingSoon)->locales('en', 'es')->live()->create();

    expect($campaign->fresh())
        ->name->toBe('Launch')
        ->type->toBe(CampaignType::ComingSoon)
        ->status->toBe(CampaignStatus::Live)
        ->locales->toBe(['en', 'es']);
});

it('holds a new campaign to the same rules as the backend', function (): void {
    kit()->campaign()->named('Launch')->slug('Has Spaces')->create();
})->throws(ValidationException::class);

it('changes and deletes a campaign by slug, checking the user against the policy', function (): void {
    liveCampaign();

    expect(fn (): Campaign => kit()->campaign('launch')->named('Nope')->as(stranger())->save())->toThrow(AuthorizationException::class)
        ->and(Campaign::query()->sole()->name)->not->toBe('Nope');

    kit()->campaign('launch')->named('Launch list')->closed()->as(owner())->save();
    expect(Campaign::query()->sole())->name->toBe('Launch list')->status->toBe(CampaignStatus::Closed);

    // Deleting takes the subscribers with it, so it needs `erase` like the backend; admin.emails holds all four.
    kit()->campaign('launch')->as(owner())->delete();
    expect(Campaign::query()->count())->toBe(0);
});

it('signs someone up fluently, falling back to the campaign language', function (): void {
    liveCampaign(locales: ['en', 'es']);

    $result = kit()->signup('launch')->email(' Dana@Example.com ')->locale('fr')->attribution(['utm_source' => 'news'])->subscribe();

    expect($result->created)->toBeTrue()
        ->and($result->subscriber)->email->toBe('dana@example.com')->locale->toBe('en')
        ->and($result->subscriber->attribution)->toBe(['utm_source' => 'news']);
});

it('refuses a sign-up the public form would refuse', function (): void {
    liveCampaign();

    kit()->signup('launch')->email('not-an-address')->subscribe();
})->throws(ValidationException::class);

it('queries a list fluently and only for someone allowed to see it', function (): void {
    $campaign = liveCampaign();
    Subscriber::factory()->for($campaign)->create(['email' => 'dana@example.com', 'status' => SubscriberStatus::Confirmed, 'position' => 2]);
    Subscriber::factory()->for($campaign)->create(['email' => 'kai@example.com', 'status' => SubscriberStatus::Confirmed, 'position' => 1]);
    Subscriber::factory()->for($campaign)->create(['email' => 'lee@example.org', 'status' => SubscriberStatus::Pending]);

    expect(kit()->subscribers('launch')->confirmed()->byPosition()->get()->pluck('email')->all())->toBe(['kai@example.com', 'dana@example.com'])
        ->and(kit()->subscribers('launch')->search('EXAMPLE.ORG')->count())->toBe(1)
        ->and(kit()->subscribers('launch')->search('%')->count())->toBe(0)
        ->and(kit()->subscribers($campaign)->as(owner())->paginate(1)->total())->toBe(3)
        ->and(fn (): Collection => kit()->subscribers('launch')->as(stranger())->get())->toThrow(AuthorizationException::class);
});

it('invites the top of the list, checking the user', function (): void {
    $campaign = liveCampaign();
    $campaign->update(['settings' => ['conversion' => ['invite_url' => 'https://app.example.com/join']]]);
    Subscriber::factory()->for($campaign)->count(3)->sequence(fn ($s): array => ['position' => $s->index + 1, 'status' => SubscriberStatus::Confirmed])->create();

    expect(fn (): int => kit()->invite('launch')->top(2)->as(stranger())->send())->toThrow(AuthorizationException::class)
        ->and(kit()->invite('launch')->top(2)->as(owner())->send())->toBe(2)
        ->and(Subscriber::query()->where('status', SubscriberStatus::Invited)->pluck('position')->sort()->values()->all())->toBe([1, 2]);
});

it('submits an enquiry fluently, held to the contact rules', function (): void {
    config()->set('laranail.waitlist-kit.contact.consent', false);

    $enquiry = kit()->enquiry()->from(' Dana Lee ', 'DANA@example.com')->topic('sales')->message('Do you have a plan for teams?')->submit();

    expect($enquiry)->toBeInstanceOf(Enquiry::class)->name->toBe('Dana Lee')->email->toBe('dana@example.com')
        ->and(fn (): Enquiry => kit()->enquiry()->from('D', 'dana@example.com')->topic('sales')->message('short')->submit())->toThrow(ValidationException::class);
});

it('reaches the same builders through the facade', function (): void {
    liveCampaign();

    expect(Kit::signup('launch')->email('dana@example.com')->subscribe()->created)->toBeTrue()
        ->and(Kit::findCampaign('launch')->slug)->toBe('launch');
});

it('carries who acted in every event', function (): void {
    Event::fake([CampaignCreated::class, CampaignUpdated::class, SubscriberSignedUp::class]);
    $user = owner();

    kit()->campaign()->named('Launch')->slug('launch')->live()->as($user)->create();
    kit()->signup('launch')->email('dana@example.com')->as(null)->subscribe();

    Event::assertDispatched(CampaignCreated::class, fn (CampaignCreated $e): bool => $e->actor->user === $user && $e->campaign->slug === 'launch');
    Event::assertDispatched(CampaignUpdated::class, fn (CampaignUpdated $e): bool => $e->changes['status'] === 'live');
    Event::assertDispatched(SubscriberSignedUp::class, fn (SubscriberSignedUp $e): bool => $e->actor->isGuest());
});

it('names its actors', function (): void {
    expect(Actor::system())->kind()->toBe('system')->isSystem()->toBeTrue()->id()->toBeNull()
        ->and(Actor::guest())->kind()->toBe('guest')->isGuest()->toBeTrue()
        ->and(Actor::from(owner()))->kind()->toBe('user')->id()->toBe(1)
        ->and(Actor::from(null)->isGuest())->toBeTrue();
});

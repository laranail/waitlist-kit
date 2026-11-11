<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

it('keys every model with a ULID and casts its enums', function (): void {
    $campaign = campaign();
    $subscriber = $campaign->subscribers()->create(['email' => 'dana@example.com']);

    expect(Str::isUlid($campaign->id))->toBeTrue()
        ->and(Str::isUlid($subscriber->id))->toBeTrue()
        ->and($campaign->fresh()?->type)->toBe(CampaignType::Waitlist)
        ->and($campaign->fresh()?->status)->toBe(CampaignStatus::Draft)
        ->and($subscriber->fresh()?->status)->toBe(SubscriberStatus::Pending)
        ->and($campaign->fresh()?->locales)->toBe(['en']);
});

it('takes sign-ups only while live', function (): void {
    $campaign = campaign();
    expect($campaign->acceptsSignups())->toBeFalse();

    $campaign->update(['status' => CampaignStatus::Live]);
    expect($campaign->acceptsSignups())->toBeTrue();
});

it('falls back to another locale key by key', function (): void {
    $block = campaign()->blocks()->create([
        'type'    => 'hero',
        'content' => ['en' => ['title' => 'Join', 'body' => 'Be first'], 'es' => ['title' => 'Únete']],
    ]);

    expect($block->contentFor('es'))->toBe(['title' => 'Únete', 'body' => 'Be first'])
        ->and($block->contentFor('fr'))->toBe(['title' => 'Join', 'body' => 'Be first']);
});

it('orders a campaign\'s blocks by position', function (): void {
    $campaign = campaign();
    $campaign->blocks()->create(['type' => 'faq', 'position' => 2]);
    $campaign->blocks()->create(['type' => 'hero', 'position' => 0]);
    $campaign->blocks()->create(['type' => 'form', 'position' => 1]);

    expect($campaign->blocks()->pluck('type')->all())->toBe(['hero', 'form', 'faq']);
});

it('tracks referrals between subscribers', function (): void {
    $campaign = campaign();
    $referrer = $campaign->subscribers()->create(['email' => 'dana@example.com', 'referral_code' => 'DANA1']);
    $referred = $campaign->subscribers()->create(['email' => 'kai@example.com', 'referred_by_id' => $referrer->id]);

    expect($referred->referredBy?->is($referrer))->toBeTrue()
        ->and($referrer->referrals()->count())->toBe(1);
});

it('soft-deletes a subscriber before any purge', function (): void {
    $subscriber = campaign()->subscribers()->create(['email' => 'dana@example.com']);
    $subscriber->delete();

    expect(Subscriber::query()->count())->toBe(0)
        ->and(Subscriber::withTrashed()->count())->toBe(1);
});

it('records funnel events about a subject, without an update time', function (): void {
    $subscriber = campaign()->subscribers()->create(['email' => 'dana@example.com']);
    $event = FunnelEvent::factory()->about($subscriber)->create();

    expect($event->fresh()?->type)->toBe(FunnelEventType::Signup)
        ->and($event->eventable?->is($subscriber))->toBeTrue()
        ->and($subscriber->events()->count())->toBe(1)
        ->and(array_key_exists('updated_at', $event->fresh()?->getAttributes() ?? []))->toBeFalse();
});

it('encrypts an integration\'s settings at rest and hides them', function (): void {
    $secret = bin2hex(random_bytes(16));
    $integration = Integration::query()->create(['driver' => 'brevo', 'settings' => ['api_key' => $secret]]);

    $raw = DB::table('laranail_waitlist_kit_integrations')->value('settings');

    expect($raw)->not->toContain($secret)
        ->and($integration->fresh()?->settings)->toBe(['api_key' => $secret])
        ->and($integration->toArray())->not->toHaveKey('settings');
});

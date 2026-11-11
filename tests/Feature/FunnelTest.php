<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Support\FunnelReport;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

/** The seeded mix the exit test checks: per type, how many events inside the window. */
const SEEDED = ['view' => 40, 'signup' => 12, 'confirm' => 9, 'referral' => 3, 'invite' => 5, 'convert' => 2, 'unsubscribe' => 1];

/** Seeds SEEDED inside the window, noise outside it and on another campaign, and returns the campaign. */
function seededCampaign(): Campaign
{
    $campaign = Campaign::factory()->live()->create(['slug' => 'launch']);
    $other = Campaign::factory()->live()->create(['slug' => 'other']);
    $subscriber = Subscriber::factory()->create(['campaign_id' => $campaign->id]);

    foreach (SEEDED as $type => $count) {
        foreach (range(1, $count) as $i) {
            // Sign-ups land on three known days: 5 today, 4 two days ago, 3 ten days ago.
            $daysAgo = $type === 'signup' ? ($i <= 5 ? 0 : ($i <= 9 ? 2 : 10)) : $i % 20;
            FunnelEvent::factory()->about($subscriber, FunnelEventType::from($type))->create(['occurred_at' => now()->subDays($daysAgo)]);
        }
    }
    // Outside the 30-day window, and on another campaign: neither may be counted.
    FunnelEvent::factory()->about($subscriber, FunnelEventType::Signup)->count(4)->create(['occurred_at' => now()->subDays(31)]);
    FunnelEvent::factory()->about(Subscriber::factory()->create(['campaign_id' => $other->id]), FunnelEventType::Signup)->count(6)->create();

    return $campaign;
}

it('reports exactly the seeded events, the rates between them, and sign-ups by day', function (): void {
    $funnel = app(FunnelReport::class)->for(seededCampaign());

    foreach (SEEDED as $type => $count) {
        expect($funnel->count(FunnelEventType::from($type)))->toBe($count, $type);
    }
    expect($funnel->count(FunnelEventType::Enquiry))->toBe(0)
        ->and($funnel->rate(FunnelEventType::View, FunnelEventType::Signup))->toBe(30.0)
        ->and($funnel->rate(FunnelEventType::Signup, FunnelEventType::Confirm))->toBe(75.0)
        ->and($funnel->rate(FunnelEventType::Invite, FunnelEventType::Convert))->toBe(40.0)
        ->and($funnel->byDay)->toHaveCount(30)
        ->and($funnel->byDay[now()->toDateString()])->toBe(5)
        ->and($funnel->byDay[now()->subDays(2)->toDateString()])->toBe(4)
        ->and($funnel->byDay[now()->subDays(10)->toDateString()])->toBe(3)
        ->and(array_sum($funnel->byDay))->toBe(SEEDED['signup']);
});

it('has no rate where nothing reached the first step', function (): void {
    $funnel = app(FunnelReport::class)->for(Campaign::factory()->live()->create());

    expect($funnel->rate(FunnelEventType::Invite, FunnelEventType::Convert))->toBeNull()
        ->and(array_sum($funnel->counts))->toBe(0);
});

it('ranks sign-up sources by utm_source, then the referring host, then direct', function (): void {
    $campaign = Campaign::factory()->live()->create();
    foreach ([['utm_source' => 'Newsletter'], ['utm_source' => 'newsletter'], ['referrer' => 'https://news.example.com/a'], []] as $attribution) {
        Subscriber::factory()->create(['campaign_id' => $campaign->id, 'attribution' => $attribution]);
    }

    expect(app(FunnelReport::class)->for($campaign)->sources)->toBe(['newsletter' => 2, 'news.example.com' => 1, 'direct' => 1]);
});

it('counts a page view, but not a browser prefetch', function (): void {
    liveCampaign();

    $this->get('/waitlist/launch')->assertOk();
    $this->get('/waitlist/launch', ['Sec-Purpose' => 'prefetch'])->assertOk();

    expect(FunnelEvent::query()->where('type', FunnelEventType::View)->count())->toBe(1);
});

it('shows the funnel screen with the seeded numbers', function (): void {
    $campaign = seededCampaign();
    signInAdmin('host');

    $html = (string) $this->get("/waitlist-admin/campaigns/{$campaign->id}/funnel")->assertOk()->getContent();

    expect($html)->toContain('<p class="h1 mb-0">40</p>')->toContain('<p class="h1 mb-0">12</p>')
        ->toContain('30.0%')->toContain('75.0%')->toContain('40.0%')
        ->and(referenceProblems($html))->toBe([]);
});

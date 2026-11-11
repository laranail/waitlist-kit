<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitSeeder;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitDemoSeeder;

it('builds every model from its factory', function (): void {
    $subscriber = Subscriber::factory()->confirmed()->create();
    $referred = Subscriber::factory()->referredBy($subscriber)->create();
    $block = Block::factory()->on($subscriber->campaign()->firstOrFail())->create();

    expect(Campaign::factory()->live()->create()->status)->toBe(CampaignStatus::Live)
        ->and($referred->referredBy?->is($subscriber))->toBeTrue()
        ->and($block->blockable?->is($subscriber->campaign))->toBeTrue()
        ->and(FunnelEvent::factory()->create()->eventable)->toBeInstanceOf(Campaign::class)
        ->and(Integration::factory()->create()->driver)->toBe('app');
});

it('only ever generates addresses no mailer delivers', function (): void {
    foreach (Subscriber::factory()->count(30)->create() as $subscriber) {
        expect($subscriber->email)->toMatch('/@example\.(com|org|net)$/');
    }
});

it('seeds a starter campaign once, and nothing when one exists', function (): void {
    $this->seed(WaitlistKitSeeder::class);
    $this->seed(WaitlistKitSeeder::class);

    $campaign = Campaign::query()->sole();
    expect($campaign->slug)->toBe(WaitlistKitSeeder::SLUG)
        ->and($campaign->status)->toBe(CampaignStatus::Draft)
        ->and($campaign->blocks()->pluck('type')->all())->toBe(['hero', 'form', 'faq']);
});

it('seeds nothing with seeding.default off', function (): void {
    config()->set('laranail.waitlist-kit.seeding.default', false);

    $this->seed(WaitlistKitSeeder::class);

    expect(Campaign::query()->count())->toBe(0);
});

it('runs the starter seeder from the application\'s db:seed', function (): void {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(Campaign::query()->where('slug', WaitlistKitSeeder::SLUG)->exists())->toBeTrue();
});

it('seeds demo data outside production', function (): void {
    $this->seed(WaitlistKitDemoSeeder::class);

    expect(Subscriber::query()->count())->toBe(25)
        ->and(Subscriber::query()->whereNotNull('referred_by_id')->count())->toBe(5)
        ->and(FunnelEvent::query()->count())->toBe(85);
});

it('refuses demo data in production unless overridden', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    // Called directly: db:seed itself stops to ask for confirmation in production.
    $seed = fn (): mixed => app()->call([app(WaitlistKitDemoSeeder::class), 'run']);

    expect($seed)->toThrow(RuntimeException::class, 'does not run in production');
    expect(Subscriber::query()->count())->toBe(0);

    config()->set('laranail.waitlist-kit.seeding.demo_in_production', true);
    $seed();
    expect(Subscriber::query()->count())->toBe(25);
});

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Seeders;

use RuntimeException;
use Illuminate\Database\Seeder;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

/**
 * Demo data for trying the kit: a live campaign with subscribers, referrals and funnel events, built by the
 * factories (so it needs Faker, a development dependency). Run it explicitly:
 *
 *     php artisan db:seed --class="Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitDemoSeeder"
 *
 * It refuses to run in production unless `seeding.demo_in_production` is true: the guard fails closed and the
 * override is explicit. Its addresses are Faker's safe ones (example.org/.com/.net), which no mailer delivers.
 */
final class WaitlistKitDemoSeeder extends Seeder
{
    public function run(Application $app, KitConfig $config): void
    {
        if ($app->isProduction() && ! $config->seedsDemoInProduction()) {
            throw new RuntimeException('The waitlist-kit demo seeder does not run in production; set WAITLIST_KIT_DEMO_IN_PRODUCTION=true to override.');
        }

        $campaign = Campaign::factory()->live()->locales(['en', 'es'])->create(['name' => 'Demo launch']);

        $subscribers = Subscriber::factory()->count(20)->create(['campaign_id' => $campaign->id]);
        Subscriber::factory()->count(5)->referredBy($subscribers->firstOrFail())->create();

        FunnelEvent::factory()->count(60)->create(['campaign_id' => $campaign->id, 'eventable_id' => $campaign->id]);
        foreach (Subscriber::query()->where('campaign_id', $campaign->id)->get() as $subscriber) {
            FunnelEvent::factory()->about($subscriber, FunnelEventType::Signup)->create();
        }
    }
}

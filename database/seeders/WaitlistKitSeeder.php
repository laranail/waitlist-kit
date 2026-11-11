<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Seeders;

use Illuminate\Database\Seeder;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign;

/**
 * The kit's baseline: one draft waitlist campaign with a starter page (hero, sign-up form, FAQ), so a fresh install
 * has something to open and edit. Runs with the application's `php artisan db:seed` (registered by the provider).
 *
 * Idempotent and safe in production: it seeds nothing when any campaign exists, holds no fake data and no
 * credential, and needs no Faker. `seeding.default = false` turns it off.
 */
final class WaitlistKitSeeder extends Seeder
{
    public const string SLUG = 'waitlist';

    public function run(KitConfig $config, CreateCampaign $createCampaign): void
    {
        if (! $config->seedsDefaultCampaign() || Campaign::query()->exists()) {
            return;
        }

        $campaign = $createCampaign->handle('Waitlist', self::SLUG, CampaignType::Waitlist);

        $blocks = [
            ['type' => 'hero', 'content' => ['en' => ['title' => 'Something new is coming', 'body' => 'Join the waitlist and be first to know when it opens.']]],
            ['type' => 'form', 'content' => ['en' => ['label' => 'Email address', 'button' => 'Join the waitlist']]],
            ['type' => 'faq', 'content' => ['en' => ['items' => [
                ['question' => 'What happens after I join?', 'answer' => 'We email you once, when your access opens.'],
                ['question' => 'Can I leave the list?', 'answer' => 'Yes, at any time, from the link in any email we send.'],
            ]]]],
        ];

        foreach ($blocks as $position => $block) {
            $campaign->blocks()->create([...$block, 'position' => $position]);
        }
    }
}

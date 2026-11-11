<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Tests\TestCase;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;

// Feature tests get a booted application with the kit's migrations run; Unit and Arch tests do not.
uses(TestCase::class)->in('Feature');

/** A draft waitlist campaign, for tests that need one. */
function campaign(string $slug = 'launch'): Campaign
{
    return Campaign::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'type' => CampaignType::Waitlist,
    ]);
}

/** A live campaign the public routes answer for. */
function liveCampaign(string $slug = 'launch', array $locales = ['en']): Campaign
{
    return Campaign::factory()->live()->create(['slug' => $slug, 'locales' => $locales]);
}

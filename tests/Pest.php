<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Simtabi\Laranail\WaitlistKit\Tests\TestCase;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
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

/**
 * Signs in to the backend the way a site would in each sign-in mode: an application user listed in admin.emails
 * (host), or one of the kit's own administrators (bundled).
 */
function signInAdmin(string $mode = 'host'): void
{
    if ($mode === 'bundled') {
        config()->set('laranail.waitlist-kit.auth.mode', 'bundled');
        test()->actingAs(Admin::factory()->create(), KitConfig::BUNDLED_GUARD);

        return;
    }

    config()->set('laranail.waitlist-kit.auth.mode', 'host');
    config()->set('laranail.waitlist-kit.admin.emails', ['owner@example.org']);
    test()->actingAs(new GenericUser(['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.org']), 'web');
}

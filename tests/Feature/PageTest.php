<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitSeeder;

it('shows a live campaign\'s page with its blocks and a working form', function (): void {
    $this->seed(WaitlistKitSeeder::class);
    Campaign::query()->sole()->update(['status' => CampaignStatus::Live]);

    $this->get('/waitlist/waitlist')
        ->assertOk()
        ->assertSee('Something new is coming')
        ->assertSee('What happens after I join?')
        ->assertSee('name="_token"', false)
        ->assertSee('action="' . route('laranail-waitlist-kit.campaign.signup', 'waitlist') . '"', false)
        ->assertSee('name="website"', false);
});

it('shows the confirmation after a sign-up without JavaScript', function (): void {
    liveCampaign();

    $this->followingRedirects()->post('/waitlist/launch', ['email' => 'dana@example.com'])
        ->assertOk()
        ->assertSee("You're on the list.")
        ->assertDontSee('<form', false);
});

it('has no page for a campaign that is not live', function (): void {
    Campaign::factory()->create(['slug' => 'draft']);

    $this->get('/waitlist/draft')->assertNotFound();
});

it('moves under another prefix', function (): void {
    expect(route('laranail-waitlist-kit.campaign.show', 'launch'))->toEndWith('/waitlist/launch');
});

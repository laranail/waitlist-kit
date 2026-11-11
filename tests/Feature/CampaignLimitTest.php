<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign;
use Simtabi\Laranail\WaitlistKit\Exceptions\CampaignLimitReached;
use Simtabi\Laranail\WaitlistKit\Exceptions\InvalidConfiguration;

function createCampaigns(int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        app(CreateCampaign::class)->handle("Campaign {$i}", "campaign-{$i}", CampaignType::Waitlist);
    }
}

it('allows one campaign in single mode', function (): void {
    config()->set('laranail.waitlist-kit.campaigns.mode', 'single');

    createCampaigns(1);

    expect(fn () => createCampaigns(1))->toThrow(CampaignLimitReached::class, 'may run 1 campaign (laranail');
    expect(Campaign::query()->count())->toBe(1);
});

it('allows up to the maximum in limited mode', function (): void {
    config()->set('laranail.waitlist-kit.campaigns.mode', 'limited');
    config()->set('laranail.waitlist-kit.campaigns.max', 2);

    createCampaigns(2);

    expect(fn () => createCampaigns(1))->toThrow(CampaignLimitReached::class, 'may run 2 campaigns');
});

it('allows any number in multi mode', function (): void {
    config()->set('laranail.waitlist-kit.campaigns.mode', 'multi');

    createCampaigns(5);

    expect(Campaign::query()->count())->toBe(5);
});

it('refuses a mode nobody chose rather than picking a default', function (): void {
    config()->set('laranail.waitlist-kit.campaigns.mode', 'many');

    expect(fn () => createCampaigns(1))->toThrow(InvalidConfiguration::class, 'expected one of: single, limited, multi');
});

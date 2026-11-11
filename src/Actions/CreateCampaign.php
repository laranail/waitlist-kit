<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Exceptions\CampaignLimitReached;

/**
 * Creates a campaign, holding the install to `campaigns.mode`: one in `single`, up to `campaigns.max` in
 * `limited`, any number in `multi`.
 */
final readonly class CreateCampaign
{
    public function __construct(private KitConfig $config) {}

    /** @param  list<string>  $locales */
    public function handle(string $name, string $slug, CampaignType $type, array $locales = ['en']): Campaign
    {
        $limit = $this->config->campaignLimit();

        if ($limit !== null && Campaign::query()->count() >= $limit) {
            throw CampaignLimitReached::at($limit);
        }

        return Campaign::query()->create([
            'name'    => $name,
            'slug'    => $slug,
            'type'    => $type,
            'status'  => CampaignStatus::Draft,
            'locales' => $locales,
        ]);
    }
}

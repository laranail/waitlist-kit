<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Events\CampaignCreated;
use Simtabi\Laranail\WaitlistKit\Validation\CampaignRules;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Simtabi\Laranail\WaitlistKit\Exceptions\CampaignLimitReached;

/**
 * Creates a campaign after validating its fields against CampaignRules (an invalid slug, type or locale throws a
 * ValidationException), holding the install to `campaigns.mode`: one in `single`, up to `campaigns.max` in
 * `limited`, any number in `multi`.
 */
final readonly class CreateCampaign
{
    public function __construct(private KitConfig $config, private ValidationFactory $validator, private Dispatcher $events) {}

    /** @param  list<string>  $locales */
    public function handle(string $name, string $slug, CampaignType $type, array $locales = ['en'], ?Actor $actor = null): Campaign
    {
        $this->validator->make(
            ['name' => $name, 'slug' => $slug, 'type' => $type->value, 'locales' => $locales],
            CampaignRules::create(),
        )->validate();

        $limit = $this->config->campaignLimit();

        if ($limit !== null && Campaign::query()->count() >= $limit) {
            throw CampaignLimitReached::at($limit);
        }

        $campaign = Campaign::query()->create([
            'name'    => $name,
            'slug'    => $slug,
            'type'    => $type,
            'status'  => CampaignStatus::Draft,
            'locales' => $locales,
        ]);

        $this->events->dispatch(new CampaignCreated($campaign, $actor ?? Actor::system()));

        return $campaign;
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Factories;

use Override;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\WaitlistKit\Models\Integration;

/** @extends Factory<Integration> */
final class IntegrationFactory extends Factory
{
    #[Override]
    protected $model = Integration::class;

    /** An application endpoint every campaign uses; use for() a campaign to scope it. */
    public function definition(): array
    {
        return [
            'driver'   => 'app',
            'settings' => ['endpoint' => 'https://app.example.org/api/waitlist'],
            'enabled'  => true,
        ];
    }

    public function forCampaign(Campaign $campaign): self
    {
        return $this->state(['integrable_type' => $campaign->getMorphClass(), 'integrable_id' => $campaign->id]);
    }
}

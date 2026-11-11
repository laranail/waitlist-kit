<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Factories;

use Override;
use Illuminate\Support\Str;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;

/** @extends Factory<Campaign> */
final class CampaignFactory extends Factory
{
    #[Override]
    protected $model = Campaign::class;

    public function definition(): array
    {
        $name = implode(' ', $this->faker->unique()->words(2));

        return [
            'name'    => Str::title($name),
            'slug'    => Str::slug($name) . '-' . Str::lower(Str::random(4)),
            'type'    => CampaignType::Waitlist,
            'status'  => CampaignStatus::Draft,
            'locales' => ['en'],
        ];
    }

    public function live(): self
    {
        return $this->state(['status' => CampaignStatus::Live]);
    }

    public function comingSoon(): self
    {
        return $this->state(['type' => CampaignType::ComingSoon, 'launches_at' => now()->addMonth()]);
    }

    /** @param  list<string>  $locales */
    public function locales(array $locales): self
    {
        return $this->state(['locales' => $locales]);
    }
}

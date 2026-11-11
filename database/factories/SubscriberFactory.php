<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Factories;

use Override;
use Illuminate\Support\Str;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

/** @extends Factory<Subscriber> */
final class SubscriberFactory extends Factory
{
    #[Override]
    protected $model = Subscriber::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            // safeEmail: example.org/.com/.net only, so a seeded list can never mail a real inbox.
            'email'         => $this->faker->unique()->safeEmail(),
            'status'        => SubscriberStatus::Pending,
            'locale'        => 'en',
            'referral_code' => Str::upper(Str::random(8)),
        ];
    }

    public function confirmed(): self
    {
        return $this->state(['status' => SubscriberStatus::Confirmed]);
    }

    public function referredBy(Subscriber $referrer): self
    {
        return $this->state(['campaign_id' => $referrer->campaign_id, 'referred_by_id' => $referrer->id]);
    }
}

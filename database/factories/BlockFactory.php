<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Factories;

use Override;
use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Block> */
final class BlockFactory extends Factory
{
    #[Override]
    protected $model = Block::class;

    public function definition(): array
    {
        return [
            'blockable_type' => (new Campaign)->getMorphClass(),
            'blockable_id'   => Campaign::factory(),
            'type'           => 'hero',
            'position'       => 0,
            'content'        => ['en' => ['title' => $this->faker->sentence(4), 'body' => $this->faker->sentence(12)]],
        ];
    }

    public function on(Campaign $campaign): self
    {
        return $this->state(['blockable_type' => $campaign->getMorphClass(), 'blockable_id' => $campaign->id]);
    }
}

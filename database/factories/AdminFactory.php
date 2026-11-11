<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Factories;

use Override;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Admin> */
final class AdminFactory extends Factory
{
    #[Override]
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name'  => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            // Generated per run, never written down.
            'password' => bin2hex(random_bytes(16)),
        ];
    }
}

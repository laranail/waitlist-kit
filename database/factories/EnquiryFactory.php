<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Factories;

use Override;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enquiry> */
final class EnquiryFactory extends Factory
{
    #[Override]
    protected $model = Enquiry::class;

    public function definition(): array
    {
        return [
            'name'    => $this->faker->name(),
            'email'   => $this->faker->safeEmail(),
            'company' => $this->faker->optional()->company(),
            'topic'   => 'sales',
            'message' => $this->faker->paragraph(),
            'locale'  => 'en',
        ];
    }
}

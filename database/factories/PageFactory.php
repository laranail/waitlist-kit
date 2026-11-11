<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Factories;

use Override;
use Illuminate\Support\Str;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Enums\PageKind;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Page> */
final class PageFactory extends Factory
{
    #[Override]
    protected $model = Page::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->word() . ' ' . $this->faker->word();

        return [
            'slug'   => Str::slug($title),
            'kind'   => PageKind::Page,
            'status' => PageStatus::Draft,
            'title'  => ['en' => Str::title($title)],
        ];
    }

    public function published(): self
    {
        return $this->state(['status' => PageStatus::Published]);
    }

    public function legal(string $slug): self
    {
        return $this->state(['slug' => $slug, 'kind' => PageKind::Legal]);
    }
}

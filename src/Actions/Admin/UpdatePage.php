<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;

/** Saves a page's title in one locale and whether it is published. */
final readonly class UpdatePage
{
    public function handle(Page $page, string $locale, string $title, PageStatus $status): Page
    {
        $titles = $page->title;
        $titles[$locale] = $title;

        $page->update(['title' => $titles, 'status' => $status]);

        return $page;
    }
}

<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Data\SeoSettings;

/**
 * Saves a campaign's page and search settings: the title and description for one language (other languages keep
 * theirs), the share image, whether it may be indexed, and the launch address. Other settings are left as they were.
 */
final readonly class UpdateCampaignSeo
{
    public function handle(Campaign $campaign, string $locale, ?string $title, ?string $description, ?string $image, bool $index, ?string $launchUrl): void
    {
        $seo = $campaign->seo();
        $titles = $seo->titles;
        $descriptions = $seo->descriptions;
        unset($titles[$locale], $descriptions[$locale]);
        if ($title !== null && trim($title) !== '') {
            $titles[$locale] = trim($title);
        }
        if ($description !== null && trim($description) !== '') {
            $descriptions[$locale] = trim($description);
        }

        $updated = new SeoSettings($titles, $descriptions, $image !== null && $image !== '' ? $image : null, $index);
        $pages = is_array($campaign->settings['pages'] ?? null) ? $campaign->settings['pages'] : [];

        $campaign->update(['settings' => [
            ...$campaign->settings,
            'seo'   => $updated->toArray(),
            'pages' => [...$pages, 'launch_url' => $launchUrl !== null && $launchUrl !== '' ? $launchUrl : null],
        ]]);
    }
}

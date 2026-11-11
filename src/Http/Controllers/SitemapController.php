<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\Response;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;

/**
 * `GET /{prefix}/sitemap.xml`: the campaign pages search engines may index (live, with indexing on), each language as
 * an alternate. A site lists it in its own robots.txt or sitemap index.
 */
final readonly class SitemapController
{
    public function __invoke(): Response
    {
        $entries = [];
        foreach (Campaign::query()->where('status', CampaignStatus::Live)->orderBy('slug')->get() as $campaign) {
            if (! $campaign->indexable()) {
                continue;
            }
            $fallback = $campaign->locales[0] ?? 'en';
            $urls = [];
            foreach ($campaign->locales as $code) {
                $urls[$code] = route('laranail-waitlist-kit.campaign.show', $code === $fallback ? ['campaign' => $campaign->slug] : ['campaign' => $campaign->slug, 'lang' => $code]);
            }
            foreach ($urls as $url) {
                $entries[] = ['loc' => $url, 'lastmod' => $campaign->updated_at?->toAtomString(), 'alternates' => count($urls) > 1 ? $urls : []];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($entries as $entry) {
            $xml .= '  <url><loc>' . e($entry['loc']) . '</loc>' . ($entry['lastmod'] !== null ? '<lastmod>' . e($entry['lastmod']) . '</lastmod>' : '');
            foreach ($entry['alternates'] as $code => $href) {
                $xml .= '<xhtml:link rel="alternate" hreflang="' . e((string) $code) . '" href="' . e($href) . '"/>';
            }
            $xml .= "</url>\n";
        }
        $xml .= "</urlset>\n";

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}

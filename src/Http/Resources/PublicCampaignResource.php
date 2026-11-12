<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Resources;

use Override;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;

/**
 * What anyone may know about a live campaign: what a headless front end needs to draw its page and post a sign-up.
 *
 * @property Campaign $resource
 */
final class PublicCampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $campaign = $this->resource;
        $locale = $campaign->localeFor($request->string('lang')->toString());
        $fallback = $campaign->locales[0] ?? 'en';

        return [
            'slug'          => $campaign->slug,
            'name'          => $campaign->name,
            'type'          => $campaign->type->value,
            'status'        => $campaign->status->value,
            'locales'       => $campaign->locales,
            'locale'        => $locale,
            'launches_at'   => Dates::iso($campaign->launches_at),
            'double_opt_in' => $campaign->conversion()->doubleOptIn,
            'referrals'     => $campaign->conversion()->referrals,
            'seo'           => [
                'title'       => $campaign->seo()->title($locale, $fallback, $campaign->name),
                'description' => $campaign->seo()->description($locale, $fallback),
                'image'       => $campaign->seo()->image,
                'index'       => $campaign->indexable(),
            ],
            'signup_url' => route('laranail-waitlist-kit.api.campaigns.subscribers.store', $campaign->slug),
        ];
    }
}

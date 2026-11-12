<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Resources;

use Override;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;

/**
 * A campaign as its managers see it: every field the backend edits.
 *
 * @property Campaign $resource
 */
final class CampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $campaign = $this->resource;

        return [
            'id'             => $campaign->id,
            'slug'           => $campaign->slug,
            'name'           => $campaign->name,
            'type'           => $campaign->type->value,
            'status'         => $campaign->status->value,
            'host'           => $campaign->host,
            'locales'        => $campaign->locales,
            'theme'          => $campaign->theme,
            'theme_settings' => $campaign->theme_settings,
            'settings'       => $campaign->settings,
            'launches_at'    => Dates::iso($campaign->launches_at),
            'created_at'     => Dates::iso($campaign->created_at),
            'updated_at'     => Dates::iso($campaign->updated_at),
        ];
    }
}

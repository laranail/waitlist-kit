<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/** Turns what a caller has (a campaign, its id, or its slug) into the campaign. */
final class CampaignLookup
{
    /** @throws ModelNotFoundException<Campaign> */
    public static function resolve(Campaign|string $campaign): Campaign
    {
        if ($campaign instanceof Campaign) {
            return $campaign;
        }

        return Campaign::query()->whereKey($campaign)->orWhere('slug', $campaign)->firstOrFail();
    }
}
